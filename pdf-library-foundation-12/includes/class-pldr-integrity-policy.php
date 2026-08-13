<?php

defined('ABSPATH') || exit;

/**
 * Exact-integrity repair policy for manual/REST/scheduled health paths.
 * Keeps legacy PLDR_Health public API intact while ensuring checksum evidence
 * covers both encrypted stored bytes and authenticated plaintext.
 */
final class PLDR_Integrity_Policy {
    private static bool $hooked=false;

    public static function hooks():void {
        if(self::$hooked)return;self::$hooked=true;
        add_action('pldr_integrity_sample',array(__CLASS__,'scheduled_sample'),1);
        add_action('admin_post_pldr_safe_repair',array(__CLASS__,'admin_repair'),1);
        add_filter('rest_pre_dispatch',array(__CLASS__,'rest_repair'),7,3);
    }

    public static function scheduled_sample():void { self::integrity_sample(3); }

    public static function admin_repair():void {
        $operation=sanitize_key((string)($_POST['operation']??''));
        if(!in_array($operation,array('integrity-sample','rotate-keys'),true))return;
        if(!PLDR_Core::authorize('repair'))wp_die('Denied',array('response'=>403));
        check_admin_referer('pldr_safe_repair');
        $result='rotate-keys'===$operation?self::rotate_keys(10):self::integrity_sample(10);
        if(isset($result['error'])&&is_wp_error($result['error']))wp_die(esc_html($result['error']->get_error_message()),array('response'=>(int)($result['error']->get_error_data()['status']??500)));
        wp_safe_redirect(admin_url('admin.php?page=pldr-health'));exit;
    }

    public static function rest_repair($result,$server,WP_REST_Request $request) {
        if(null!==$result||'/pldr/v1/repair'!==(string)$request->get_route()||'POST'!==$request->get_method())return $result;
        $operation=sanitize_key((string)$request['operation']);
        if(!in_array($operation,array('integrity-sample','rotate-keys'),true))return null;
        if(!PLDR_Core::authorize('repair'))return PLDR_Core::machine_error('pldr_repair_forbidden','Repair authority is required.',403);
        return rest_ensure_response('rotate-keys'===$operation?self::rotate_keys(10):self::integrity_sample(10));
    }

    public static function integrity_sample(int $limit=3):array {
        global $wpdb;$wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".PLDR_Core::table('objects')." WHERE object_status='available' ORDER BY COALESCE(verified_at,'1970-01-01') ASC,id ASC LIMIT %d",max(1,min(20,$limit))),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_integrity_sample_read','Integrity-sample object state could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();$results=array();
        foreach($rows as $object){
            $object_id=(int)$object['id'];$path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);$error='';$evidence=array();
            $ok=!is_wp_error($path)&&PLDR_Object_Integrity::verify($object,(string)$path,$error,$evidence);
            if(is_wp_error($path))$error=$path->get_error_message();
            if($ok){
                $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND storage_scope=%s AND key_id=%s AND sha256=%s AND encrypted_sha256=%s',PLDR_Core::now(),$object_id,'available',(string)$object['storage_name'],(string)$object['storage_scope'],(string)$object['key_id'],(string)$object['sha256'],(string)$object['encrypted_sha256']));
                if(1!==$updated){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Object changed while exact integrity verification was being persisted.','reconcile_required'=>true);continue;}
                $results[]=array('object_id'=>$object_id,'ok'=>true,'integrity_evidence'=>$evidence);continue;
            }
            if(false===$wpdb->query('START TRANSACTION')){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Exact integrity failed, but quarantine transaction could not start.');continue;}
            $changed=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET object_status=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND storage_scope=%s AND key_id=%s AND sha256=%s AND encrypted_sha256=%s','quarantined',PLDR_Core::now(),$object_id,'available',(string)$object['storage_name'],(string)$object['storage_scope'],(string)$object['key_id'],(string)$object['sha256'],(string)$object['encrypted_sha256']));
            if(1!==$changed){$wpdb->query('ROLLBACK');$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Exact integrity failed, but sampled object changed before quarantine.','reconcile_required'=>true);continue;}
            $edition_id=(int)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('editions').' WHERE object_id=%d ORDER BY id DESC LIMIT 1',$object_id));
            $document_id=0;if($edition_id>0){$edition=PLDR_Core::edition($edition_id);$document_id=(int)($edition['document_id']??0);}
            if($document_id>0&&PLDR_Access::revoke_document($document_id,'exact-integrity-failure')<0){$wpdb->query('ROLLBACK');$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Exact integrity quarantine rolled back because delivery grants could not be revoked.');continue;}
            if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Exact integrity quarantine could not be committed.');continue;}
            PLDR_Core::audit('object',$object_id,'exact_integrity_quarantined',array('edition_id'=>$edition_id,'document_id'=>$document_id,'error'=>substr($error,0,500),'integrity_evidence'=>$evidence));
            $results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$error,'quarantined'=>true,'integrity_evidence'=>$evidence);
        }
        return array('operation'=>'integrity-sample','results'=>$results,'ciphertext_and_plaintext_verified'=>true,'cas_persistence'=>true);
    }

    public static function rotate_keys(int $limit=10):array {
        global $wpdb;$active=PLDR_Crypto::active_key_id();
        if(!$active)return array('error'=>PLDR_Core::machine_error('pldr_key_rotation_active','No active File 12 encryption key is configured.',503,array('degraded'=>true)));
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".PLDR_Core::table('objects')." WHERE object_status='available' AND key_id<>%s ORDER BY id ASC LIMIT %d",$active,max(1,min(25,$limit))),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_key_rotation_read','Object key-rotation state could not be read reliably.',503,array('degraded'=>true)));
        $rows=is_array($rows)?$rows:array();$results=array();
        foreach($rows as $object){
            $id=(int)$object['id'];$old=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);$error='';$before=array();
            if(is_wp_error($old)||!PLDR_Object_Integrity::verify($object,(string)$old,$error,$before)){$results[]=array('object_id'=>$id,'ok'=>false,'error'=>is_wp_error($old)?$old->get_error_message():$error,'rotation_refused'=>true);continue;}
            $plain=PLDR_Storage::temp('key-rotate-plain');$enc=PLDR_Storage::temp('key-rotate-encrypted');
            if(is_wp_error($plain)||is_wp_error($enc)){
                if(is_string($plain))PLDR_Storage::delete($plain);
                if(is_string($enc))PLDR_Storage::delete($enc);
                $results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Temporary storage unavailable.');continue;
            }
            try{
                if(!PLDR_Crypto::decrypt_to_file((string)$old,(string)$plain,$error)){$results[]=array('object_id'=>$id,'ok'=>false,'error'=>$error);continue;}
                $allocation=PLDR_Storage::allocate('pldr');$meta=array();
                if(!empty($allocation['error'])||!PLDR_Crypto::encrypt_file((string)$plain,(string)$enc,$meta,$error)||!PLDR_Storage::atomic_commit((string)$enc,(string)$allocation['path'])){$results[]=array('object_id'=>$id,'ok'=>false,'error'=>$error?:'Key rotation encryption/commit failed.');continue;}
                if((string)($meta['key_id']??'')!==$active||''===(string)($meta['encrypted_sha256']??'')){PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Rotated object did not bind to the active key/checksum.');continue;}
                $rotated=$object;$rotated['encrypted_sha256']=(string)$meta['encrypted_sha256'];$after=array();$verify_error='';
                if(!PLDR_Object_Integrity::verify($rotated,(string)$allocation['path'],$verify_error,$after)){PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Rotated object failed exact post-write verification: '.$verify_error);continue;}
                if(false===$wpdb->query('START TRANSACTION')){PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Key rotation transaction could not start.');continue;}
                $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('objects').' SET storage_name=%s,storage_scope=%s,encrypted_sha256=%s,key_id=%s,format_version=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND storage_scope=%s AND key_id=%s AND sha256=%s AND encrypted_sha256=%s',(string)$allocation['name'],'pldr',(string)$meta['encrypted_sha256'],(string)$meta['key_id'],(string)$meta['format'],PLDR_Core::now(),$id,'available',(string)$object['storage_name'],(string)$object['storage_scope'],(string)$object['key_id'],(string)$object['sha256'],(string)$object['encrypted_sha256']));
                if(1!==$updated){$wpdb->query('ROLLBACK');PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Object changed during key rotation; stale metadata was not committed.','cas_conflict'=>true);continue;}
                if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$id,'ok'=>false,'error'=>'Key rotation commit failed.');continue;}
                PLDR_Storage::delete((string)$old);PLDR_Core::audit('object',$id,'key_rotated_exact_integrity',array('old_key'=>$object['key_id'],'new_key'=>$meta['key_id'],'before'=>$before,'after'=>$after));$results[]=array('object_id'=>$id,'ok'=>true,'key_id'=>$meta['key_id'],'before'=>$before,'after'=>$after,'cas_committed'=>true);
            }finally{
                PLDR_Storage::delete((string)$plain);
                PLDR_Storage::delete((string)$enc);
            }
        }
        return array('operation'=>'rotate-keys','active_key_id'=>$active,'results'=>$results,'ciphertext_and_plaintext_verified'=>true);
    }
}
