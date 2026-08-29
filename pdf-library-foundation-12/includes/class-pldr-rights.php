<?php

defined('ABSPATH') || exit;

final class PLDR_Rights {
    private const EVIDENCE_KEYS_MAX = 30;
    private const EVIDENCE_VALUE_MAX = 2000;
    private const EVIDENCE_JSON_MAX = 32768;
    private const REVIEW_NOTE_MAX = 4000;

    private static function limit_text(string $value,int $limit):string {
        $value=sanitize_textarea_field($value);
        return function_exists('mb_substr')?mb_substr($value,0,$limit,'UTF-8'):substr($value,0,$limit);
    }

    private static function evidence_value($value,int $depth=0) {
        if(is_scalar($value)||null===$value)return self::limit_text((string)$value,self::EVIDENCE_VALUE_MAX);
        if(!is_array($value)||$depth>=2)return self::limit_text(wp_json_encode($value),self::EVIDENCE_VALUE_MAX);
        $out=array();$count=0;
        foreach($value as $key=>$child){
            if($count++>=20)break;
            $safe_key=sanitize_key((string)$key);
            if(''===$safe_key)$safe_key='item-'.$count;
            $out[$safe_key]=self::evidence_value($child,$depth+1);
        }
        return $out;
    }

    private static function sanitize_evidence(array $evidence) {
        $safe=array();$count=0;
        foreach($evidence as $key=>$value){
            if($count++>=self::EVIDENCE_KEYS_MAX)break;
            $safe_key=sanitize_key((string)$key);
            if(''===$safe_key)$safe_key='item-'.$count;
            $safe[$safe_key]=self::evidence_value($value,0);
        }
        $json=wp_json_encode($safe,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($json)||strlen($json)>self::EVIDENCE_JSON_MAX)return PLDR_Core::machine_error('pldr_case_evidence_size','Rights-case evidence exceeds the bounded safe payload size.',413,array('max_bytes'=>self::EVIDENCE_JSON_MAX));
        return array('data'=>$safe,'json'=>$json);
    }

    public static function file_case(int $document_id, string $reason, array $evidence, int $reporter_id = 0) {
        global $wpdb;
        $reporter_id = $reporter_id ?: get_current_user_id();
        if (!$reporter_id) return PLDR_Core::machine_error('pldr_case_login', 'Log in to file a rights or safety report.', 401);
        $wpdb->last_error='';$doc = PLDR_Core::document($document_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_case_document_read','Rights-report document state could not be read reliably; the report was not filed.',503,array('degraded'=>true));
        if (!$doc) return PLDR_Core::machine_error('pldr_document_missing', 'Document not found.', 404);
        $allowed = array('copyright','unauthorized-scan','attribution','patient-privacy','medical-safety','misleading-claim','false-credentials','broken-pdf','rights-expired','other');
        $reason = sanitize_key($reason);
        if (!in_array($reason,$allowed,true)) return PLDR_Core::machine_error('pldr_case_reason','Invalid report reason.',400);
        $safe_evidence=self::sanitize_evidence($evidence);
        if(is_wp_error($safe_evidence))return $safe_evidence;
        $case_key = PLDR_Core::uuid();
        $sensitive=in_array($reason,array('patient-privacy','unauthorized-scan'),true);
        $transition=null;

        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_case_transaction','Rights report transaction could not be started.',500);
        $ok=$wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>$case_key,'document_id'=>$document_id,'reporter_id'=>$reporter_id,'parent_case_id'=>null,'state'=>'reported','reason'=>$reason,'evidence_json'=>$safe_evidence['json'],'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now(),'closed_at'=>null));
        if(false===$ok){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_store','The report could not be recorded.',500);}
        $case_id=(int)$wpdb->insert_id;
        if($case_id<1){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_store','The report persistence could not be confirmed.',500);}

        if($sensitive&&!in_array((string)$doc['status'],array('restricted','removed'),true)){
            $transition=self::transition_document_status_row($document_id,'restricted');
            if(is_wp_error($transition)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_sensitive_restriction_failed','Sensitive rights report was not accepted because immediate restriction could not be committed safely.',503,array('cause'=>$transition->get_error_code()));}
            $status_event=self::queue_document_status_event($transition['document'],'restricted','temporary-'.$reason);
            if(is_wp_error($status_event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_sensitive_event_atomic','Sensitive restriction was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false));}
        }
        $event=PLDR_Core::emit('RightsReportFiled.v1','rights_case',$case_id,array('case_key'=>$case_key,'document_id'=>$doc['public_id'],'reason'=>$reason));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_event_atomic','Rights report was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_commit','Rights report could not be committed atomically.',500);}

        if(is_array($transition)){ $after=self::after_document_status_change($transition['document'],'restricted','temporary-'.$reason); if(is_wp_error($after))return $after; }
        PLDR_Core::audit('rights_case',$case_id,'reported',array('document_id'=>$document_id,'reason'=>$reason,'sensitive_restriction_committed'=>(bool)$transition),$reporter_id);
        return array('case_id'=>$case_id,'case_key'=>$case_key,'state'=>'reported','sensitive_restriction_committed'=>$sensitive ? ('restricted'===$doc['status']||'removed'===$doc['status']||(bool)$transition) : false);
    }

    public static function decide(int $case_id,string $decision,string $note,int $reviewer_id=0,$expected_version=0) {
        global $wpdb;
        $reviewer_id=$reviewer_id?:get_current_user_id();
        $wpdb->last_error='';$case=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('rights_cases').' WHERE id=%d',$case_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_case_read','Rights-case state could not be read reliably.',503,array('degraded'=>true));
        if(!$case)return PLDR_Core::machine_error('pldr_case_missing','Rights case not found.',404);
        $document_id=(int)$case['document_id'];
        if(!PLDR_Core::authorize('rights',$document_id,$reviewer_id) && !PLDR_Core::authorize('manage',$document_id,$reviewer_id))return PLDR_Core::machine_error('pldr_rights_forbidden','Rights-review authority for this document is required.',403);
        if($expected_version<1)return PLDR_Core::machine_error('pldr_case_precondition','Rights-case decisions require the exact expected case version.',428,array('current_version'=>(int)$case['version']));
        if((int)$case['version']!==$expected_version)return PLDR_Core::machine_error('pldr_case_conflict','Rights case changed; refresh before deciding.',409,array('current_version'=>(int)$case['version']));
        if('closed'===$case['state'])return PLDR_Core::machine_error('pldr_case_state','This rights case cannot transition from its current state.',409);
        $allowed=array('restrict','remove','restore','dismiss','request-evidence');
        if(!in_array($decision,$allowed,true))return PLDR_Core::machine_error('pldr_case_decision','Invalid rights-case decision.',400);
        $note=self::limit_text($note,self::REVIEW_NOTE_MAX);
        if(''===trim($note))return PLDR_Core::machine_error('pldr_case_note','A reasoned reviewer note is required.',400);
        $new_state='reviewed'; $closed=null;
        if(in_array($decision,array('remove','restore','dismiss'),true)){ $new_state='closed'; $closed=PLDR_Core::now(); }
        if('request-evidence'===$decision)$new_state='reviewed';
        $status_map=array('restrict'=>'restricted','remove'=>'removed','restore'=>'published');
        $status=$status_map[$decision]??'';
        $reason='rights-case-'.$case_id;
        $transition=null;

        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_case_transaction','Rights decision transaction could not be started.',500);
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('rights_cases').' SET state=%s,decision_note=%s,assigned_to=%d,version=version+1,updated_at=%s,closed_at=%s WHERE id=%d AND version=%d',$new_state,$note,$reviewer_id,PLDR_Core::now(),$closed,$case_id,(int)$case['version']));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_conflict','Concurrent rights-case update detected.',409);}
        if($status){
            $transition=self::transition_document_status_row($document_id,$status);
            if(is_wp_error($transition)){$wpdb->query('ROLLBACK');return $transition;}
            $status_event=self::queue_document_status_event($transition['document'],$status,$reason);
            if(is_wp_error($status_event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_status_event_atomic','Rights decision was rolled back because its document-status event could not be persisted atomically.',503,array('committed'=>false,'case_id'=>$case_id));}
        }
        $event=PLDR_Core::emit('PDFRightsCaseDecided.v1','rights_case',$case_id,array('case_id'=>$case_id,'document_id'=>$document_id,'decision'=>$decision,'state'=>$new_state));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_decision_event_atomic','Rights decision was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false,'case_id'=>$case_id));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_commit','Rights decision could not be committed atomically.',500);}
        if(is_array($transition)){ $after=self::after_document_status_change($transition['document'],$status,$reason); if(is_wp_error($after))return $after; }
        PLDR_Core::audit('rights_case',$case_id,'decided',array('decision'=>$decision,'document_id'=>$document_id),$reviewer_id);
        return array('case_id'=>$case_id,'state'=>$new_state,'decision'=>$decision,'version'=>(int)$case['version']+1);
    }

    public static function appeal(int $case_id,string $reason,array $evidence,int $actor_id=0) {
        global $wpdb;
        $actor_id=$actor_id?:get_current_user_id();
        if(!$actor_id)return PLDR_Core::machine_error('pldr_appeal_login','Log in to appeal.',401);
        $wpdb->last_error='';$parent=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('rights_cases').' WHERE id=%d',$case_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_appeal_parent_read','Appeal parent state could not be read reliably.',503,array('degraded'=>true));
        if(!$parent)return PLDR_Core::machine_error('pldr_case_missing','Rights case not found.',404);
        if(!in_array($parent['state'],array('closed','reviewed'),true))return PLDR_Core::machine_error('pldr_appeal_state','This case is not eligible for appeal yet.',409);
        $document_id=(int)$parent['document_id'];
        if((int)$parent['reporter_id']!==$actor_id && !PLDR_Core::authorize('rights',$document_id,$actor_id) && !PLDR_Core::authorize('manage',$document_id,$actor_id))return PLDR_Core::machine_error('pldr_appeal_forbidden','You cannot appeal this rights case.',403);
        $safe_evidence=self::sanitize_evidence($evidence);
        if(is_wp_error($safe_evidence))return $safe_evidence;
        $appeal_reason=self::limit_text($reason,self::REVIEW_NOTE_MAX);
        if(''===trim($appeal_reason))return PLDR_Core::machine_error('pldr_appeal_reason','A bounded appeal reason is required.',400);
        $appeal_payload=wp_json_encode(array('reason'=>$appeal_reason,'evidence'=>$safe_evidence['data']),JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($appeal_payload)||strlen($appeal_payload)>self::EVIDENCE_JSON_MAX)return PLDR_Core::machine_error('pldr_case_evidence_size','Rights appeal evidence exceeds the bounded safe payload size.',413,array('max_bytes'=>self::EVIDENCE_JSON_MAX));
        $key=PLDR_Core::uuid();
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_appeal_transaction','Rights appeal transaction could not be started.',500);
        $inserted=$wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>$key,'document_id'=>$document_id,'reporter_id'=>$actor_id,'parent_case_id'=>$case_id,'state'=>'appealed','reason'=>'appeal','evidence_json'=>$appeal_payload,'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now(),'closed_at'=>null));
        $new_id=(int)$wpdb->insert_id;
        if(false===$inserted||$new_id<1){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_appeal_store','The rights appeal could not be persisted; no appeal event was emitted.',500);}
        $event=PLDR_Core::emit('PDFRightsCaseAppealed.v1','rights_case',$new_id,array('case_id'=>$new_id,'parent_case_id'=>$case_id,'document_id'=>$document_id));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_appeal_event_atomic','Rights appeal was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_appeal_commit','Rights appeal could not be committed atomically.',500);}
        return array('case_id'=>$new_id,'case_key'=>$key,'state'=>'appealed');
    }

    public static function approve_document(int $document_id, int $reviewer_id = 0, int $expected_version = 0) {
        global $wpdb;
        $reviewer_id = $reviewer_id ?: get_current_user_id();
        if (!PLDR_Core::authorize('rights', $document_id, $reviewer_id) && !PLDR_Core::authorize('manage', $document_id, $reviewer_id)) return PLDR_Core::machine_error('pldr_approve_forbidden','Rights-review authority is required.',403);
        $wpdb->last_error='';$doc = PLDR_Core::document($document_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_document_read','Document state could not be read reliably before publication approval.',503,array('degraded'=>true));
        if(!$doc) return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);
        if ($expected_version<1) return PLDR_Core::machine_error('pldr_approve_precondition','Document publication approval requires the exact expected document version.',428,array('current_version'=>(int)$doc['version']));
        if ((int)$doc['version'] !== $expected_version) return PLDR_Core::machine_error('pldr_document_conflict','Document changed; refresh before approval.',409,array('current_version'=>(int)$doc['version']));
        if (!in_array($doc['status'],array('rights_review','scan','restricted'),true)) return PLDR_Core::machine_error('pldr_approve_state','Document is not in an approvable state.',409);
        $wpdb->last_error='';$edition = PLDR_Core::latest_edition($document_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_edition_read','Latest edition state could not be read reliably before publication approval.',503,array('degraded'=>true));
        $object=null;if($edition){$wpdb->last_error='';$object=PLDR_Core::object((int)$edition['object_id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_object_read','Encrypted object state could not be read reliably before publication approval.',503,array('degraded'=>true));}
        if(!$edition || !$object || 'available'!==$object['object_status'] || 'clean'!==$object['scan_status']) return PLDR_Core::machine_error('pldr_approve_scan','A clean available encrypted object is required before publication.',409);
        foreach(array('author_name','source_name','rights_basis','sha256') as $field) if(empty($edition[$field])) return PLDR_Core::machine_error('pldr_approve_metadata','Rights/source metadata is incomplete.',409,array('field'=>$field));

        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_approve_transaction','Document publication transaction could not be started.',500);
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d','published',PLDR_Core::now(),$document_id,(int)$doc['version']));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_document_conflict','Concurrent document update detected.',409);}

        $superseded=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('editions').' SET status=%s,updated_at=%s WHERE document_id=%d AND id<>%d AND status=%s','superseded',PLDR_Core::now(),$document_id,(int)$edition['id'],'published'));
        if(false===$superseded){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_edition_supersede_failed','Existing published editions could not be superseded; publication was rolled back.',500);}

        $edition_updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('editions').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d','published',PLDR_Core::now(),(int)$edition['id'],(int)$edition['version']));
        if(1!==$edition_updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_edition_publish_conflict','The target edition changed concurrently; publication was rolled back.',409);}
        $event=PLDR_Core::emit('PDFDocumentPublished.v1','document',$document_id,array('document_id'=>$doc['public_id'],'edition_id'=>(int)$edition['id']));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_publish_event_atomic','Publication was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false,'document_id'=>$doc['public_id']));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_approve_commit','Document publication could not be committed atomically.',500);}

        if(PLDR_Access::revoke_document($document_id,'publication-state-change')<0)return PLDR_Core::machine_error('pldr_publish_revoke_reconcile','Publication was committed but prior delivery grants could not be revoked; reconciliation is required.',503,array('committed'=>true));
        PLDR_Core::audit('document',$document_id,'approved',array('edition_id'=>(int)$edition['id'],'superseded_editions'=>(int)$superseded),$reviewer_id);
        return array('document_id'=>$doc['public_id'],'status'=>'published','version'=>(int)$doc['version']+1);
    }

    private static function temporary_restrict(int $document_id,string $reason) { return self::set_document_status($document_id,'restricted','temporary-'.$reason); }

    private static function transition_document_status_row(int $document_id,string $status) {
        global $wpdb;
        $wpdb->last_error='';$doc=PLDR_Core::document($document_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_document_state_read','Document state could not be read reliably during rights transition.',503,array('degraded'=>true));
        if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found during rights-state transition.',404);
        $allowed=array('published','restricted','removed','superseded','rights_review','scan');
        if(!in_array($status,$allowed,true))return PLDR_Core::machine_error('pldr_document_status','Invalid document rights state.',400);
        if('published'===$status){
            $wpdb->last_error='';$edition=PLDR_Core::latest_edition($document_id);
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_restore_edition_read','Current edition could not be read reliably before restoration.',503,array('degraded'=>true));
            $wpdb->last_error='';$object=$edition?PLDR_Core::object((int)$edition['object_id']):null;
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_restore_object_read','Current object could not be read reliably before restoration.',503,array('degraded'=>true));
            if(!$edition||!$object||'available'!==$object['object_status']||'clean'!==$object['scan_status'])return PLDR_Core::machine_error('pldr_restore_unavailable','The document cannot be restored until its current edition has a clean available object.',409);
        }
        $changed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d',$status,PLDR_Core::now(),$document_id,(int)$doc['version']));
        if(1!==$changed)return PLDR_Core::machine_error('pldr_document_conflict','Document state changed concurrently; the rights decision was not committed.',409);
        return array('document'=>$doc,'version'=>(int)$doc['version']+1);
    }

    private static function queue_document_status_event(array $doc,string $status,string $reason) {
        $event='published'===$status?'PDFDocumentPublished.v1':'PDFDocumentAccessChanged.v1';
        return PLDR_Core::emit($event,'document',(int)$doc['id'],array('document_id'=>$doc['public_id'],'status'=>$status,'reason'=>$reason));
    }

    private static function after_document_status_change(array $doc,string $status,string $reason) {
        $document_id=(int)$doc['id'];
        $revoked=PLDR_Access::revoke_document($document_id,$reason);
        if($revoked<0){PLDR_Core::audit('document',$document_id,'rights_status_revoke_reconciliation_required',array('status'=>$status,'reason'=>$reason));return PLDR_Core::machine_error('pldr_rights_revoke_reconcile','Rights state changed but prior grants could not be revoked; reconciliation is required.',503,array('committed'=>true));}
        return true;
    }

    private static function set_document_status(int $document_id,string $status,string $reason) {
        global $wpdb;
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_document_status_transaction','Document rights-state transaction could not be started.',500);
        $transition=self::transition_document_status_row($document_id,$status);
        if(is_wp_error($transition)){$wpdb->query('ROLLBACK');return $transition;}
        $status_event=self::queue_document_status_event($transition['document'],$status,$reason);
        if(is_wp_error($status_event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_rights_event_atomic','Document rights state was rolled back because its reliable event could not be persisted in the same transaction.',503,array('committed'=>false));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_document_status_commit','Document rights-state transition could not be committed.',500);}
        $after=self::after_document_status_change($transition['document'],$status,$reason);
        if(is_wp_error($after))return $after;
        return true;
    }

    public static function expire_rights():array {
        global $wpdb;$summary=array('ok'=>true,'selected'=>0,'restricted'=>0,'failed'=>0,'batch_limit'=>100,'continuation_scheduled'=>false);
        $editions=PLDR_Core::table('editions');$wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare("SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s ORDER BY e.rights_expires_at ASC LIMIT 100",'published',PLDR_Core::now()),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'rights_expiry_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['failed']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $r){
            $document_id=(int)$r['document_id'];$wpdb->last_error='';$doc=PLDR_Core::document($document_id);
            if(''!==(string)$wpdb->last_error){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_document_read_failed',array());continue;}
            if(!$doc||'published'!==$doc['status'])continue;
            $changed=self::set_document_status((int)$doc['id'],'restricted','rights-expired');
            if(is_wp_error($changed)){$summary['ok']=false;$summary['failed']++;PLDR_Core::audit('document',$document_id,'rights_expiry_transition_failed',array('error_code'=>$changed->get_error_code()));continue;}
            $summary['restricted']++;
        }
        if(100===count($rows)||$summary['failed']>0){if(!wp_next_scheduled('pldr_rights_expiry')){$summary['continuation_scheduled']=(bool)wp_schedule_single_event(time()+60,'pldr_rights_expiry');}else$summary['continuation_scheduled']=true;}
        return $summary;
    }
}

final class PLDR_Book_Packs {
    public static function register(array $manifest,int $actor_id=0) {
        global $wpdb;
        $actor_id=$actor_id?:get_current_user_id();
        if($actor_id && !PLDR_Core::authorize('manage',0,$actor_id) && !PLDR_Core::founder($actor_id))return PLDR_Core::machine_error('pldr_pack_forbidden','Book Content Pack registration requires File 12 management authority.',403);
        $required=array('pack_key','version','title','author','language','rights','checksum','update_manifest');
        foreach($required as $field)if(empty($manifest[$field]))return PLDR_Core::machine_error('pldr_pack_required','Book Content Pack manifest is incomplete.',400,array('field'=>$field));
        $rights=sanitize_key((string)$manifest['rights']);
        if(!in_array($rights,array('founder-owned','public-domain','permission','licensed','open-license'),true))return PLDR_Core::machine_error('pldr_pack_rights','Book Content Pack has no approved rights basis.',400);
        if(!preg_match('/^[a-f0-9]{64}$/i',(string)$manifest['checksum']))return PLDR_Core::machine_error('pldr_pack_checksum','Book Content Pack checksum must be SHA-256.',400);
        if(!empty($manifest['binary'])||!empty($manifest['pdf_bytes'])||!empty($manifest['embedded_pdf']))return PLDR_Core::machine_error('pldr_pack_binary','Book Content Packs may register metadata and secure object references, not embed large PDF binaries in plugin code.',400);
        $canonical=array(
            'pack_key'=>sanitize_key((string)$manifest['pack_key']),'version'=>sanitize_text_field((string)$manifest['version']),'title'=>sanitize_text_field((string)$manifest['title']),'author'=>sanitize_text_field((string)$manifest['author']),'translator'=>sanitize_text_field((string)($manifest['translator']??'')),'edition'=>sanitize_text_field((string)($manifest['edition']??'')),'year'=>absint($manifest['year']??0),'language'=>sanitize_text_field((string)$manifest['language']),'volumes'=>absint($manifest['volumes']??1),'chapters'=>PLDR_Core::sanitize_json_list($manifest['chapters']??array()),'cover'=>sanitize_text_field((string)($manifest['cover']??'')),'table_of_contents'=>$manifest['table_of_contents']??array(),'rights'=>$rights,'checksum'=>strtolower((string)$manifest['checksum']),'search_metadata'=>$manifest['search_metadata']??array(),'update_manifest'=>$manifest['update_manifest'],'provenance'=>sanitize_textarea_field((string)($manifest['provenance']??'')),'document_public_id'=>sanitize_text_field((string)($manifest['document_public_id']??'')),
        );
        if('founder-owned'===$rights && empty($canonical['provenance']))return PLDR_Core::machine_error('pldr_pack_provenance','Founder-owned Book Content Packs require explicit provenance and edition history.',400);
        $manifest_json=wp_json_encode($canonical,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(!is_string($manifest_json)||strlen($manifest_json)>262144)return PLDR_Core::machine_error('pldr_pack_manifest_size','Book Content Pack manifest could not be encoded within the governed metadata limit.',413,array('max_bytes'=>262144));
        $manifest_hash=hash('sha256',$manifest_json);
        $table=PLDR_Core::table('book_packs');
        $wpdb->last_error='';
        $existing=$wpdb->get_row($wpdb->prepare('SELECT id,manifest_sha256,status FROM '.$table.' WHERE pack_key=%s AND pack_version=%s LIMIT 1',$canonical['pack_key'],$canonical['version']),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_pack_read','Book Content Pack version state could not be read reliably; registration was not attempted.',503,array('degraded'=>true));
        if($existing){
            if(hash_equals((string)$existing['manifest_sha256'],$manifest_hash))return array('id'=>(int)$existing['id'],'pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'status'=>(string)$existing['status'],'already_registered'=>true,'immutable_version'=>true);
            return PLDR_Core::machine_error('pldr_pack_version_conflict','This Book Content Pack version is already registered with different immutable metadata; publish a new pack version instead of overwriting history.',409,array('pack_key'=>$canonical['pack_key'],'version'=>$canonical['version']));
        }
        if(false===$wpdb->query('START TRANSACTION'))return PLDR_Core::machine_error('pldr_pack_transaction','Book Content Pack registration transaction could not be started.',500);
        $stored=$wpdb->insert($table,array('pack_key'=>$canonical['pack_key'],'pack_version'=>$canonical['version'],'title'=>$canonical['title'],'author'=>$canonical['author'],'translator'=>$canonical['translator'],'rights_basis'=>$rights,'manifest_sha256'=>$manifest_hash,'metadata_json'=>$manifest_json,'status'=>'registered','created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        if(false===$stored){
            $wpdb->query('ROLLBACK');
            $wpdb->last_error='';
            $race=$wpdb->get_row($wpdb->prepare('SELECT id,manifest_sha256,status FROM '.$table.' WHERE pack_key=%s AND pack_version=%s LIMIT 1',$canonical['pack_key'],$canonical['version']),ARRAY_A);
            if(''===(string)$wpdb->last_error&&$race&&hash_equals((string)$race['manifest_sha256'],$manifest_hash))return array('id'=>(int)$race['id'],'pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'status'=>(string)$race['status'],'already_registered'=>true,'immutable_version'=>true,'concurrent_registration'=>true);
            return PLDR_Core::machine_error('pldr_pack_store','Book Content Pack metadata could not be persisted or reconciled; no registration event was emitted.',500);
        }
        $id=(int)$wpdb->insert_id;
        if($id<1){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_pack_store','Book Content Pack persistence could not be confirmed; no registration event was emitted.',500);}
        $event=PLDR_Core::emit('PDFBookPackRegistered.v1','book_pack',$id,array('pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'document_public_id'=>$canonical['document_public_id']));
        if(is_wp_error($event)){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_pack_event_atomic','Book Content Pack registration was rolled back because its reliable event could not be persisted atomically.',503,array('committed'=>false));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_pack_commit','Book Content Pack registration could not be committed atomically.',500);}
        PLDR_Core::audit('book_pack',$id,'registered',array('pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash),$actor_id);
        return array('id'=>$id,'pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'status'=>'registered','immutable_version'=>true);
    }

    public static function scan_bundled_manifests():void { $dir=PLDR_DIR.'book-packs'; if(!is_dir($dir))return; foreach(glob($dir.'/*.json')?:array() as $file){$raw=file_get_contents($file);$manifest=json_decode((string)$raw,true);if(is_array($manifest))self::register($manifest,0);} }
}

final class PLDR_Integrations {
    public static function register_contracts():void {
        $contracts=array(
            array('owner'=>'12','name'=>'pdf-library','version'=>PLDR_CONTRACT_VERSION,'capabilities'=>array('catalog','reader','signed-range-delivery','reading-state','rights','ocr','citations','book-packs')),
        );
        do_action('spf_register_contract','file-12',PLDR_CONTRACT_VERSION,$contracts[0]);
        do_action('sabri_register_module',array('file'=>12,'slug'=>'pdf-library-digital-reading','version'=>PLDR_VERSION,'contract'=>PLDR_CONTRACT_VERSION));
        foreach(array(
            array('route'=>'/library/','owner'=>'12','visibility'=>'public'),array('route'=>'/library/document/{id}/{slug}/','owner'=>'12','visibility'=>'public-conditional'),array('route'=>'/library/read/{id}/','owner'=>'12','visibility'=>'conditional-noindex'),array('route'=>'/account/reading/','owner'=>'12','visibility'=>'authenticated-noindex-no-cache')
        ) as $route) do_action('spf_register_route',$route);
        do_action('suas_register_slot','file-12-reader',array('owner'=>'12','layout'=>'immersive','shell_owner'=>'20'));
        do_action('spui_register_component_provider','file-12',array('components'=>array('document-card','reader-toolbar','download-manager'),'visual_owner'=>'25'));
    }

    public static function dispatch_outbox():array {
        global $wpdb;
        $summary=array('ok'=>true,'selected'=>0,'claimed'=>0,'sent'=>0,'retried'=>0,'dead_lettered'=>0,'errors'=>0,'batch_limit'=>50);
        $now=PLDR_Core::now();$wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('outbox').' WHERE status IN (%s,%s,%s) AND available_at<=%s ORDER BY id ASC LIMIT 50','pending','retry','processing',$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'outbox_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$summary['ok']=false;$summary['errors']=1;return $summary;}
        $rows=is_array($rows)?$rows:array();$summary['selected']=count($rows);
        foreach($rows as $row){
            $lease_until=gmdate('Y-m-d H:i:s',time()+10*MINUTE_IN_SECONDS);$wpdb->last_error='';
            $claimed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('outbox').' SET status=%s,available_at=%s WHERE id=%d AND status IN (%s,%s,%s) AND available_at<=%s','processing',$lease_until,(int)$row['id'],'pending','retry','processing',$now));
            if(false===$claimed){$summary['ok']=false;$summary['errors']++;PLDR_Core::audit('outbox',(int)$row['id'],'outbox_claim_failed',array('event_id'=>(string)$row['event_id'],'db_error'=>substr((string)$wpdb->last_error,0,500)));continue;}
            if(1!==$claimed)continue;$summary['claimed']++;
            $payload=json_decode((string)$row['payload_json'],true);
            if(!is_array($payload)||JSON_ERROR_NONE!==json_last_error()){
                $dead=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'dead-letter','attempts'=>max(8,(int)$row['attempts']+1),'last_error'=>'invalid-payload-json'),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1===$dead)$summary['dead_lettered']++;else{$summary['ok']=false;$summary['errors']++;}
                PLDR_Core::audit('outbox',(int)$row['id'],'outbox_payload_corrupt',array('event_id'=>(string)$row['event_id'],'dead_letter_persisted'=>1===$dead));continue;
            }
            try{
                $accepted=apply_filters('pldr_dispatch_event',true,(string)$row['event_name'],$payload,(string)$row['event_id']);if(false===$accepted)throw new RuntimeException('A consumer requested retry.');
                do_action('sabri_domain_event',(string)$row['event_name'],$payload,(string)$row['event_id'],'file-12');do_action('pldr_event',(string)$row['event_name'],$payload,(string)$row['event_id']);
                $stored=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'sent','sent_at'=>PLDR_Core::now(),'last_error'=>''),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));if(1!==$stored)throw new RuntimeException('Dispatched event lease changed or state could not be persisted.');$summary['sent']++;
            }catch(Throwable $e){
                $attempts=(int)$row['attempts']+1;$status=$attempts>=8?'dead-letter':'retry';$delay=min(3600,30*(2**min($attempts,6)));
                $retry=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>$status,'attempts'=>$attempts,'available_at'=>gmdate('Y-m-d H:i:s',time()+$delay),'last_error'=>sanitize_text_field($e->getMessage())),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1===$retry){if('dead-letter'===$status)$summary['dead_lettered']++;else$summary['retried']++;}else{$summary['ok']=false;$summary['errors']++;PLDR_Core::audit('outbox',(int)$row['id'],'outbox_retry_persist_failed',array('event_id'=>(string)$row['event_id']));}
            }
        }
        return $summary;
    }
}
