<?php

defined('ABSPATH') || exit;

final class PLDR_Rights {
    public static function file_case(int $document_id, string $reason, array $evidence, int $reporter_id = 0) {
        global $wpdb;
        $reporter_id = $reporter_id ?: get_current_user_id();
        if (!$reporter_id) return PLDR_Core::machine_error('pldr_case_login', 'Log in to file a rights or safety report.', 401);
        $doc = PLDR_Core::document($document_id);
        if (!$doc) return PLDR_Core::machine_error('pldr_document_missing', 'Document not found.', 404);
        $allowed = array('copyright','unauthorized-scan','attribution','patient-privacy','medical-safety','misleading-claim','false-credentials','broken-pdf','rights-expired','other');
        $reason = sanitize_key($reason);
        if (!in_array($reason,$allowed,true)) return PLDR_Core::machine_error('pldr_case_reason','Invalid report reason.',400);
        $case_key = PLDR_Core::uuid();
        $safe_evidence = array();
        foreach ($evidence as $k=>$v) $safe_evidence[sanitize_key((string)$k)] = is_scalar($v)?sanitize_textarea_field((string)$v):wp_json_encode($v);
        $ok=$wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>$case_key,'document_id'=>$document_id,'reporter_id'=>$reporter_id,'parent_case_id'=>null,'state'=>'reported','reason'=>$reason,'evidence_json'=>wp_json_encode($safe_evidence),'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now(),'closed_at'=>null));
        if(false===$ok)return PLDR_Core::machine_error('pldr_case_store','The report could not be recorded.',500);
        $case_id=(int)$wpdb->insert_id;
        if(in_array($reason,array('patient-privacy','unauthorized-scan'),true)) self::temporary_restrict($document_id,$reason);
        PLDR_Core::audit('rights_case',$case_id,'reported',array('document_id'=>$document_id,'reason'=>$reason),$reporter_id);
        PLDR_Core::emit('RightsReportFiled.v1','rights_case',$case_id,array('case_key'=>$case_key,'document_id'=>$doc['public_id'],'reason'=>$reason));
        return array('case_id'=>$case_id,'case_key'=>$case_key,'state'=>'reported');
    }

    public static function decide(int $case_id,string $decision,string $note,int $reviewer_id=0,$expected_version=0) {
        global $wpdb;
        $reviewer_id=$reviewer_id?:get_current_user_id();
        $case=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('rights_cases').' WHERE id=%d',$case_id),ARRAY_A);
        if(!$case)return PLDR_Core::machine_error('pldr_case_missing','Rights case not found.',404);
        $document_id=(int)$case['document_id'];
        if(!PLDR_Core::authorize('rights',$document_id,$reviewer_id) && !PLDR_Core::authorize('manage',$document_id,$reviewer_id))return PLDR_Core::machine_error('pldr_rights_forbidden','Rights-review authority for this document is required.',403);
        if($expected_version && (int)$case['version']!==$expected_version)return PLDR_Core::machine_error('pldr_case_conflict','Rights case changed; refresh before deciding.',409,array('current_version'=>(int)$case['version']));
        if('closed'===$case['state'])return PLDR_Core::machine_error('pldr_case_state','This rights case cannot transition from its current state.',409);
        $allowed=array('restrict','remove','restore','dismiss','request-evidence');
        if(!in_array($decision,$allowed,true))return PLDR_Core::machine_error('pldr_case_decision','Invalid rights-case decision.',400);
        if(''===trim($note))return PLDR_Core::machine_error('pldr_case_note','A reasoned reviewer note is required.',400);
        $new_state='reviewed'; $closed=null;
        if(in_array($decision,array('remove','restore','dismiss'),true)){ $new_state='closed'; $closed=PLDR_Core::now(); }
        if('request-evidence'===$decision)$new_state='reviewed';
        $status_map=array('restrict'=>'restricted','remove'=>'removed','restore'=>'published');
        $status=$status_map[$decision]??'';
        $reason='rights-case-'.$case_id;
        $transition=null;

        $wpdb->query('START TRANSACTION');
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('rights_cases').' SET state=%s,decision_note=%s,assigned_to=%d,version=version+1,updated_at=%s,closed_at=%s WHERE id=%d AND version=%d',$new_state,sanitize_textarea_field($note),$reviewer_id,PLDR_Core::now(),$closed,$case_id,(int)$case['version']));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_conflict','Concurrent rights-case update detected.',409);}
        if($status){
            $transition=self::transition_document_status_row($document_id,$status);
            if(is_wp_error($transition)){$wpdb->query('ROLLBACK');return $transition;}
        }
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_case_commit','Rights decision could not be committed atomically.',500);}
        if(is_array($transition))self::after_document_status_change($transition['document'],$status,$reason);
        PLDR_Core::audit('rights_case',$case_id,'decided',array('decision'=>$decision,'document_id'=>$document_id),$reviewer_id);
        PLDR_Core::emit('PDFRightsCaseDecided.v1','rights_case',$case_id,array('case_id'=>$case_id,'document_id'=>$document_id,'decision'=>$decision,'state'=>$new_state));
        return array('case_id'=>$case_id,'state'=>$new_state,'decision'=>$decision,'version'=>(int)$case['version']+1);
    }

    public static function appeal(int $case_id,string $reason,array $evidence,int $actor_id=0) {
        global $wpdb;
        $actor_id=$actor_id?:get_current_user_id();
        if(!$actor_id)return PLDR_Core::machine_error('pldr_appeal_login','Log in to appeal.',401);
        $parent=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('rights_cases').' WHERE id=%d',$case_id),ARRAY_A);
        if(!$parent)return PLDR_Core::machine_error('pldr_case_missing','Rights case not found.',404);
        if(!in_array($parent['state'],array('closed','reviewed'),true))return PLDR_Core::machine_error('pldr_appeal_state','This case is not eligible for appeal yet.',409);
        $document_id=(int)$parent['document_id'];
        if((int)$parent['reporter_id']!==$actor_id && !PLDR_Core::authorize('rights',$document_id,$actor_id) && !PLDR_Core::authorize('manage',$document_id,$actor_id))return PLDR_Core::machine_error('pldr_appeal_forbidden','You cannot appeal this rights case.',403);
        $key=PLDR_Core::uuid();
        $wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>$key,'document_id'=>$document_id,'reporter_id'=>$actor_id,'parent_case_id'=>$case_id,'state'=>'appealed','reason'=>'appeal','evidence_json'=>wp_json_encode(array('reason'=>sanitize_textarea_field($reason),'evidence'=>$evidence)),'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now(),'closed_at'=>null));
        $new_id=(int)$wpdb->insert_id;
        PLDR_Core::emit('PDFRightsCaseAppealed.v1','rights_case',$new_id,array('case_id'=>$new_id,'parent_case_id'=>$case_id,'document_id'=>$document_id));
        return array('case_id'=>$new_id,'case_key'=>$key,'state'=>'appealed');
    }

    public static function approve_document(int $document_id, int $reviewer_id = 0, int $expected_version = 0) {
        global $wpdb;
        $reviewer_id = $reviewer_id ?: get_current_user_id();
        if (!PLDR_Core::authorize('rights', $document_id, $reviewer_id) && !PLDR_Core::authorize('manage', $document_id, $reviewer_id)) return PLDR_Core::machine_error('pldr_approve_forbidden','Rights-review authority is required.',403);
        $doc = PLDR_Core::document($document_id); if(!$doc) return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);
        if ($expected_version && (int)$doc['version'] !== $expected_version) return PLDR_Core::machine_error('pldr_document_conflict','Document changed; refresh before approval.',409,array('current_version'=>(int)$doc['version']));
        if (!in_array($doc['status'],array('rights_review','scan','restricted'),true)) return PLDR_Core::machine_error('pldr_approve_state','Document is not in an approvable state.',409);
        $edition = PLDR_Core::latest_edition($document_id); $object = $edition ? PLDR_Core::object((int)$edition['object_id']) : null;
        if(!$edition || !$object || 'available'!==$object['object_status'] || 'clean'!==$object['scan_status']) return PLDR_Core::machine_error('pldr_approve_scan','A clean available encrypted object is required before publication.',409);
        foreach(array('author_name','source_name','rights_basis','sha256') as $field) if(empty($edition[$field])) return PLDR_Core::machine_error('pldr_approve_metadata','Rights/source metadata is incomplete.',409,array('field'=>$field));
        $wpdb->query('START TRANSACTION');
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d','published',PLDR_Core::now(),$document_id,(int)$doc['version']));
        if(1!==$updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_document_conflict','Concurrent document update detected.',409);}
        $wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('editions').' SET status=%s,updated_at=%s WHERE document_id=%d AND id<>%d AND status=%s','superseded',PLDR_Core::now(),$document_id,(int)$edition['id'],'published'));
        $edition_updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('editions').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d','published',PLDR_Core::now(),(int)$edition['id']));
        if(false===$edition_updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_edition_publish_failed','Edition publication could not be committed.',500);}
        $wpdb->query('COMMIT');
        PLDR_Access::revoke_document($document_id,'publication-state-change');
        PLDR_Core::audit('document',$document_id,'approved',array('edition_id'=>(int)$edition['id']),$reviewer_id);
        PLDR_Core::emit('PDFDocumentPublished.v1','document',$document_id,array('document_id'=>$doc['public_id'],'edition_id'=>(int)$edition['id']));
        return array('document_id'=>$doc['public_id'],'status'=>'published','version'=>(int)$doc['version']+1);
    }

    private static function temporary_restrict(int $document_id,string $reason):void { self::set_document_status($document_id,'restricted','temporary-'.$reason); }

    private static function transition_document_status_row(int $document_id,string $status) {
        global $wpdb;
        $doc=PLDR_Core::document($document_id);
        if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found during rights-state transition.',404);
        $allowed=array('published','restricted','removed','superseded','rights_review','scan');
        if(!in_array($status,$allowed,true))return PLDR_Core::machine_error('pldr_document_status','Invalid document rights state.',400);
        if('published'===$status){
            $edition=PLDR_Core::latest_edition($document_id);
            $object=$edition?PLDR_Core::object((int)$edition['object_id']):null;
            if(!$edition||!$object||'available'!==$object['object_status']||'clean'!==$object['scan_status'])return PLDR_Core::machine_error('pldr_restore_unavailable','The document cannot be restored until its current edition has a clean available object.',409);
        }
        $changed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET status=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d',$status,PLDR_Core::now(),$document_id,(int)$doc['version']));
        if(1!==$changed)return PLDR_Core::machine_error('pldr_document_conflict','Document state changed concurrently; the rights decision was not committed.',409);
        return array('document'=>$doc,'version'=>(int)$doc['version']+1);
    }

    private static function after_document_status_change(array $doc,string $status,string $reason):void {
        $document_id=(int)$doc['id'];
        PLDR_Access::revoke_document($document_id,$reason);
        $event='published'===$status?'PDFDocumentPublished.v1':'PDFDocumentAccessChanged.v1';
        PLDR_Core::emit($event,'document',$document_id,array('document_id'=>$doc['public_id'],'status'=>$status,'reason'=>$reason));
    }

    private static function set_document_status(int $document_id,string $status,string $reason) {
        global $wpdb;
        $wpdb->query('START TRANSACTION');
        $transition=self::transition_document_status_row($document_id,$status);
        if(is_wp_error($transition)){$wpdb->query('ROLLBACK');return $transition;}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_document_status_commit','Document rights-state transition could not be committed.',500);}
        self::after_document_status_change($transition['document'],$status,$reason);
        return true;
    }

    public static function expire_rights():void {
        global $wpdb;
        $editions=PLDR_Core::table('editions');
        $rows=$wpdb->get_results($wpdb->prepare(
            "SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s",
            'published',PLDR_Core::now()
        ),ARRAY_A);
        foreach($rows as $r){
            $doc=PLDR_Core::document((int)$r['document_id']);
            if($doc && 'published'===$doc['status'])self::set_document_status((int)$doc['id'],'restricted','rights-expired');
        }
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
        $manifest_hash=hash('sha256',wp_json_encode($canonical,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
        $wpdb->replace(PLDR_Core::table('book_packs'),array('pack_key'=>$canonical['pack_key'],'pack_version'=>$canonical['version'],'title'=>$canonical['title'],'author'=>$canonical['author'],'translator'=>$canonical['translator'],'rights_basis'=>$rights,'manifest_sha256'=>$manifest_hash,'metadata_json'=>wp_json_encode($canonical),'status'=>'registered','created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        $id=(int)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('book_packs').' WHERE pack_key=%s AND pack_version=%s',$canonical['pack_key'],$canonical['version']));
        PLDR_Core::audit('book_pack',$id,'registered',array('pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash),$actor_id);
        PLDR_Core::emit('PDFBookPackRegistered.v1','book_pack',$id,array('pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'document_public_id'=>$canonical['document_public_id']));
        return array('id'=>$id,'pack_key'=>$canonical['pack_key'],'version'=>$canonical['version'],'manifest_sha256'=>$manifest_hash,'status'=>'registered');
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

    public static function dispatch_outbox():void {
        global $wpdb;
        $now=PLDR_Core::now();
        $rows=$wpdb->get_results($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('outbox').' WHERE status IN (%s,%s,%s) AND available_at<=%s ORDER BY id ASC LIMIT 50','pending','retry','processing',$now),ARRAY_A);
        foreach($rows as $row){
            $lease_until=gmdate('Y-m-d H:i:s',time()+10*MINUTE_IN_SECONDS);
            $claimed=$wpdb->query($wpdb->prepare(
                'UPDATE '.PLDR_Core::table('outbox').' SET status=%s,available_at=%s WHERE id=%d AND status IN (%s,%s,%s) AND available_at<=%s',
                'processing',$lease_until,(int)$row['id'],'pending','retry','processing',$now
            ));
            if(1!==$claimed)continue;
            $payload=json_decode((string)$row['payload_json'],true)?:array();
            try{
                $accepted=apply_filters('pldr_dispatch_event',true,(string)$row['event_name'],$payload,(string)$row['event_id']);
                if(false===$accepted)throw new RuntimeException('A consumer requested retry.');
                do_action('sabri_domain_event',(string)$row['event_name'],$payload,(string)$row['event_id'],'file-12');
                do_action('pldr_event',(string)$row['event_name'],$payload,(string)$row['event_id']);
                $stored=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'sent','sent_at'=>PLDR_Core::now(),'last_error'=>''),array('id'=>(int)$row['id'],'status'=>'processing'));
                if(false===$stored)throw new RuntimeException('Dispatched event state could not be persisted.');
            }catch(Throwable $e){
                $attempts=(int)$row['attempts']+1;$status=$attempts>=8?'dead-letter':'retry';$delay=min(3600,30*(2**min($attempts,6)));
                $wpdb->update(PLDR_Core::table('outbox'),array('status'=>$status,'attempts'=>$attempts,'available_at'=>gmdate('Y-m-d H:i:s',time()+$delay),'last_error'=>sanitize_text_field($e->getMessage())),array('id'=>(int)$row['id'],'status'=>'processing'));
            }
        }
    }
}
