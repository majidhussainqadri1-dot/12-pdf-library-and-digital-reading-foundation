from pathlib import Path
import subprocess,re


def read(p): return Path(p).read_text()
def write(p,s): Path(p).write_text(s)
def replace_once(p,old,new):
    s=read(p)
    if s.count(old)!=1: raise SystemExit(f'expected one match in {p}; found {s.count(old)} for {old[:120]!r}')
    write(p,s.replace(old,new,1))
def lint_commit(n,msg,files):
    for f in files:
        if f.endswith('.php'): subprocess.run(['php','-l',f],check=True)
    subprocess.run(['git','add',*files],check=True)
    subprocess.run(['git','commit','-m',f'R18 round {n:02d}: {msg}'],check=True)

data='pdf-library-foundation-12/includes/class-pldr-future-data.php'
fingerprint='pdf-library-foundation-12/includes/class-pldr-future-fingerprint.php'
access='pdf-library-foundation-12/includes/class-pldr-access.php'
rights='pdf-library-foundation-12/includes/class-pldr-rights.php'
admin='pdf-library-foundation-12/includes/class-pldr-admin.php'
privacy='pdf-library-foundation-12/includes/class-pldr-privacy.php'

# Round 16 — outline GET called the external provider on every request without a provider ceiling.
replace_once(
    data,
    "        $external_failure=false;$external_error='';\n        try {\n            $external = apply_filters('pldr_outline_extract', null, $edition_id, $edition);\n        } catch (Throwable $e) {\n            $external=null;$external_failure=true;$external_error=self::limit_text(sanitize_text_field($e->getMessage()),500);\n            PLDR_Core::audit('edition',$edition_id,'outline_provider_failed',array('error'=>$external_error));\n        }",
    "        $external_failure=false;$external_error='';$external=null;\n        $provider_rate=self::consume_provider_rate('outline',$edition_id);\n        if(is_wp_error($provider_rate)){$external_failure=true;$external_error=$provider_rate->get_error_code();}\n        else{try {\n            $external = apply_filters('pldr_outline_extract', null, $edition_id, $edition);\n        } catch (Throwable $e) {\n            $external=null;$external_failure=true;$external_error=self::limit_text(sanitize_text_field($e->getMessage()),500);\n            PLDR_Core::audit('edition',$edition_id,'outline_provider_failed',array('error'=>$external_error));\n        }}"
)
lint_commit(16,'rate limit outline provider while preserving local fallback',[data])

# Round 17 — fingerprint review silently truncated both its source evidence pool and its candidate results.
replace_once(fingerprint,"WHERE f.edition_id<>%d ORDER BY f.updated_at DESC LIMIT 1000","WHERE f.edition_id<>%d ORDER BY f.updated_at DESC LIMIT 1001")
replace_once(
    fingerprint,
    "        $rows=is_array($rows)?$rows:array();\n        $grouped=array(); foreach($rows as $row)$grouped[(int)$row['edition_id']][]=$row;\n        $out=array();",
    "        $rows=is_array($rows)?$rows:array();\n        if(count($rows)>1000)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_scan_truncated','Scan-fingerprint candidate evidence exceeds the bounded 1000-row review window; no misleading partial candidate list was returned.',409,array('scan_limit'=>1000,'source_truncated'=>true)));\n        $grouped=array(); foreach($rows as $row)$grouped[(int)$row['edition_id']][]=$row;\n        $out=array();$candidate_seen=0;"
)
replace_once(
    fingerprint,
    "            $out[]=array('edition_id'=>$otherId,'document_id'=>$info['public_id'],'title'=>$info['title'],'edition_label'=>$info['edition_label'],'visual_distance'=>$visualDistance,'ocr_distance'=>$ocrDistance,'metadata_match'=>$meta,'classification'=>$class,'automatic_merge'=>false);\n            if(count($out)>=50)break;",
    "            $candidate_seen++;if($candidate_seen>50)return array('error'=>PLDR_Core::machine_error('pldr_fingerprint_results_truncated','More than 50 scan-family candidates matched; no misleading partial candidate list was returned.',409,array('candidate_limit'=>50,'results_truncated'=>true)));\n            $out[]=array('edition_id'=>$otherId,'document_id'=>$info['public_id'],'title'=>$info['title'],'edition_label'=>$info['edition_label'],'visual_distance'=>$visualDistance,'ocr_distance'=>$ocrDistance,'metadata_match'=>$meta,'classification'=>$class,'automatic_merge'=>false);"
)
lint_commit(17,'make fingerprint scan and result truncation fail visible',[fingerprint])

# Round 18 — operations review: bound cleanup/expiry/reindex work and reject corrupt outbox payloads.
# Access-token/idempotency cleanup was unbounded.
s=read(access)
pattern=r"    public static function cleanup_tokens\(\): void \{.*?\n    \}\n\}"
replacement="""    public static function cleanup_tokens(): void {
        global $wpdb;$batch=500;
        $tokens=$wpdb->query($wpdb->prepare("DELETE FROM ".PLDR_Core::table('access_tokens')." WHERE expires_at<%s OR (revoked_at IS NOT NULL AND revoked_at<%s) ORDER BY id ASC LIMIT {$batch}",gmdate('Y-m-d H:i:s',time()-DAY_IN_SECONDS),gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS)));
        if(false===$tokens)PLDR_Core::audit('system',0,'access_token_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
        $idem=$wpdb->query($wpdb->prepare("DELETE FROM ".PLDR_Core::table('idempotency')." WHERE expires_at<%s LIMIT {$batch}",PLDR_Core::now()));
        if(false===$idem)PLDR_Core::audit('system',0,'idempotency_cleanup_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
        if($batch===$tokens||$batch===$idem){if(!wp_next_scheduled('pldr_cleanup_tokens'))wp_schedule_single_event(time()+60,'pldr_cleanup_tokens');}
    }
}"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round18 cleanup method matches={n}')
write(access,s2)
# Rights-expiry cron was unbounded; process a bounded batch and immediately continue when full.
replace_once(rights,"            \"SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s\",","            \"SELECT e.document_id FROM {$editions} e INNER JOIN (SELECT document_id,MAX(id) current_id FROM {$editions} WHERE status=%s GROUP BY document_id) current ON current.current_id=e.id WHERE e.rights_expires_at IS NOT NULL AND e.rights_expires_at<=%s ORDER BY e.rights_expires_at ASC LIMIT 100\",")
replace_once(rights,"        foreach(is_array($rows)?$rows:array() as $r){\n            $doc=PLDR_Core::document((int)$r['document_id']);\n            if($doc && 'published'===$doc['status'])self::set_document_status((int)$doc['id'],'restricted','rights-expired');\n        }","        $rows=is_array($rows)?$rows:array();\n        foreach($rows as $r){$wpdb->last_error='';$doc=PLDR_Core::document((int)$r['document_id']);if(''!==(string)$wpdb->last_error){PLDR_Core::audit('document',(int)$r['document_id'],'rights_expiry_document_read_failed',array());continue;}if($doc && 'published'===$doc['status'])self::set_document_status((int)$doc['id'],'restricted','rights-expired');}\n        if(100===count($rows))wp_schedule_single_event(time()+60,'pldr_rights_expiry');")
# Search-index repair loaded all documents in one request; make it a resumable 100-row cursor batch.
s=read(admin)
pattern=r"        if\('search-index'===\$operation\)\{.*?\}\n        if\('tokens'===\$operation\)"
replacement="""        if('search-index'===$operation){
            $state=(array)get_option('pldr_search_repair_state',array());$after=absint($state['after_id']??0);$limit=100;
            $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare('SELECT d.id,d.title,d.language,d.subjects_json,d.collections_json,e.author_name,e.translator,e.publisher,e.isbn FROM '.PLDR_Core::table('documents').' d LEFT JOIN '.PLDR_Core::table('editions').' e ON e.id=(SELECT MAX(e2.id) FROM '.PLDR_Core::table('editions').' e2 WHERE e2.document_id=d.id) WHERE d.id>%d ORDER BY d.id ASC LIMIT %d',$after,$limit),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_repair_search_read','Search-index source state could not be read reliably.',503,array('degraded'=>true));$rows=is_array($rows)?$rows:array();$last=$after;
            foreach($rows as $r){$subjects=json_decode((string)$r['subjects_json'],true);$collections=json_decode((string)$r['collections_json'],true);if(!is_array($subjects)||!is_array($collections))return PLDR_Core::machine_error('pldr_repair_search_metadata','Search-index source metadata failed JSON integrity validation; repair stopped before writing this document.',500,array('document_id'=>(int)$r['id']));$text=PLDR_Core::normalize_search(implode(' ',array($r['title'],$r['language'],$r['author_name'],$r['translator'],$r['publisher'],$r['isbn'],implode(' ',$subjects),implode(' ',$collections))));if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text,'updated_at'=>PLDR_Core::now()),array('id'=>(int)$r['id'])))return PLDR_Core::machine_error('pldr_repair_search_write','Search-index rebuild failed before completion.',500,array('document_id'=>(int)$r['id']));$last=(int)$r['id'];}
            $done=count($rows)<$limit;if($done)delete_option('pldr_search_repair_state');else update_option('pldr_search_repair_state',array('after_id'=>$last,'updated_at'=>PLDR_Core::now()),false);PLDR_Core::audit('system',0,'search_index_rebuilt_batch',array('documents'=>count($rows),'after_id'=>$last,'done'=>$done));return array('operation'=>'search-index','documents'=>count($rows),'done'=>$done,'after_id'=>$last,'batch_limit'=>$limit,'resumable'=>true);
        }
        if('tokens'===$operation)"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round18 search-index block matches={n}')
write(admin,s2)
# Corrupt outbox JSON previously became an empty fabricated payload and was dispatched.
replace_once(rights,"            $payload=json_decode((string)$row['payload_json'],true)?:array();\n            try{","            $payload=json_decode((string)$row['payload_json'],true);\n            if(!is_array($payload)||JSON_ERROR_NONE!==json_last_error()){\n                $dead=$wpdb->update(PLDR_Core::table('outbox'),array('status'=>'dead-letter','attempts'=>max(8,(int)$row['attempts']+1),'last_error'=>'invalid-payload-json'),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));\n                PLDR_Core::audit('outbox',(int)$row['id'],'outbox_payload_corrupt',array('event_id'=>(string)$row['event_id'],'dead_letter_persisted'=>1===$dead));\n                continue;\n            }\n            try{")
lint_commit(18,'bound operational jobs and reject corrupt outbox payloads',[access,rights,admin])

# Round 19 — integrity sampling claimed quarantine/verification without confirming a CAS-safe database transition.
s=read(admin)
pattern=r"    public static function integrity_sample\(int \$limit = 3\): array \{.*?\n    \}\n\}\n\nfinal class PLDR_Admin"
replacement="""    public static function integrity_sample(int $limit = 3): array {
        global $wpdb;$wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".PLDR_Core::table('objects')." WHERE object_status='available' ORDER BY COALESCE(verified_at,'1970-01-01') ASC,id ASC LIMIT %d",max(1,min(20,$limit))),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('operation'=>'integrity-sample','results'=>array(),'error'=>'Integrity-sample object state could not be read reliably.');$rows=is_array($rows)?$rows:array();$results=array();
        foreach($rows as $object){
            $object_id=(int)$object['id'];$path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);$error='';$ok=!is_wp_error($path)&&PLDR_Crypto::verify_file((string)$path,(string)$object['sha256'],$error);
            if($ok){
                $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND key_id=%s AND encrypted_sha256=%s',PLDR_Core::now(),$object_id,'available',(string)$object['storage_name'],(string)$object['key_id'],(string)$object['encrypted_sha256']));
                if(1!==$updated){PLDR_Core::audit('object',$object_id,'integrity_verify_reconcile_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Object state changed while integrity verification was being persisted; retry required.','verification_persisted'=>false);continue;}
                $results[]=array('object_id'=>$object_id,'ok'=>true,'error'=>'','verification_persisted'=>true);continue;
            }
            $changed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET object_status=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND key_id=%s AND encrypted_sha256=%s','quarantined',PLDR_Core::now(),$object_id,'available',(string)$object['storage_name'],(string)$object['key_id'],(string)$object['encrypted_sha256']));
            if(1===$changed){PLDR_Core::audit('object',$object_id,'integrity_quarantined',array('reason'=>$error));$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$error,'quarantine_persisted'=>true);continue;}
            $wpdb->last_error='';$current=PLDR_Core::object($object_id);if(''!==(string)$wpdb->last_error){PLDR_Core::audit('object',$object_id,'integrity_quarantine_reconcile_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Integrity failed and quarantine persistence could not be reconciled.','quarantine_persisted'=>false);continue;}
            if($current&&'quarantined'===(string)$current['object_status']){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$error,'quarantine_persisted'=>true,'concurrent_quarantine'=>true);continue;}
            PLDR_Core::audit('object',$object_id,'integrity_quarantine_reconcile_failed',array('state_changed'=>true));$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Integrity failed but object state changed concurrently; quarantine was not falsely claimed.','quarantine_persisted'=>false);
        }
        return array('operation'=>'integrity-sample','results'=>$results,'cas_persistence'=>true);
    }
}

final class PLDR_Admin"""
s2,n=re.subn(pattern,replacement,s,flags=re.S)
if n!=1: raise SystemExit(f'round19 integrity method matches={n}')
write(admin,s2)
lint_commit(19,'make integrity verification and quarantine CAS-safe',[admin])

# Round 20 — privacy export omitted Smart Shelf membership even though shelf items are private user organization state.
block="""        $shelf_items_exists=self::table_exists('shelf_items');
        if(self::table_check_failed())return self::export_failure($data,'shelf_items_table',$user_id);
        $shelves_exists=self::table_exists('shelves');
        if(self::table_check_failed())return self::export_failure($data,'shelves_table',$user_id);
        if($shelf_items_exists&&$shelves_exists){
            $items=PLDR_Core::table('shelf_items');$shelves=PLDR_Core::table('shelves');$editions=PLDR_Core::table('editions');$documents=PLDR_Core::table('documents');$wpdb->last_error='';
            $shelf_rows=$wpdb->get_results($wpdb->prepare("SELECT i.id,i.edition_id,i.added_at,s.shelf_key,s.name shelf_name,d.public_id,d.title FROM {$items} i JOIN {$shelves} s ON s.id=i.shelf_id JOIN {$editions} e ON e.id=i.edition_id JOIN {$documents} d ON d.id=e.document_id WHERE s.user_id=%d ORDER BY i.id ASC LIMIT %d OFFSET %d",$user_id,$limit,$offset),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return self::export_failure($data,'shelf_items',$user_id);$shelf_rows=is_array($shelf_rows)?$shelf_rows:array();$counts[]=count($shelf_rows);
            self::add_export_rows($data,$shelf_rows,'pldr-future-shelf-items',__('PDF Library private shelf membership','pdf-library-digital-reading'),'id',static function(array $row):array{return array(array('name'=>'Shelf key','value'=>(string)$row['shelf_key']),array('name'=>'Shelf name','value'=>(string)$row['shelf_name']),array('name'=>'Document','value'=>(string)$row['title'].' ('.(string)$row['public_id'].')'),array('name'=>'Edition','value'=>(int)$row['edition_id']),array('name'=>'Added','value'=>(string)$row['added_at']));});
        }

"""
replace_once(privacy,"        return array('data'=>$data, 'done'=>max($counts ?: array(0)) < $limit);",block+"        return array('data'=>$data, 'done'=>max($counts ?: array(0)) < $limit);")
replace_once(privacy,"        PLDR_Core::audit('privacy', 0, 'user_reading_erasure_batch', array('user_id'=>$user_id,'removed'=>$removed,'remaining'=>$remaining,'page'=>max(1,$page)));","        $subject_ref=substr(hash_hmac('sha256',(string)$user_id,wp_salt('auth')),0,24);\n        PLDR_Core::audit('privacy', 0, 'user_reading_erasure_batch', array('subject_ref'=>$subject_ref,'removed'=>$removed,'remaining'=>$remaining,'page'=>max(1,$page)));")
lint_commit(20,'export private shelf membership and avoid raw erasure subject IDs',[privacy])
