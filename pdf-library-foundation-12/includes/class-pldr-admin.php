<?php

defined('ABSPATH') || exit;

final class PLDR_Health {
    public static function report(): array {
        global $wpdb;
        $checks=array();$blockers=array();$warnings=array();
        $crypto='';$checks['crypto']=PLDR_Crypto::is_ready($crypto)?array('status'=>'ok'):array('status'=>'blocked','message'=>$crypto);if('blocked'===$checks['crypto']['status'])$blockers[]=$crypto;
        $storage=PLDR_Storage::root();$checks['storage']=is_wp_error($storage)?array('status'=>'blocked','message'=>$storage->get_error_message()):array('status'=>'ok','path_class'=>'private-outside-webroot');if(is_wp_error($storage))$blockers[]=$storage->get_error_message();
        $tables=array('documents','editions','objects','access_policies','reading_state','reading_items','rights_cases','derivatives','ocr_text','access_tokens','outbox','audit','book_packs','idempotency');$missing=array();$schema_read_failed=false;foreach($tables as $table){$name=PLDR_Core::table($table);$wpdb->last_error='';$found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$name));if(''!==(string)$wpdb->last_error){$schema_read_failed=true;break;}if($found!==$name)$missing[]=$table;}if($schema_read_failed){$checks['schema']=array('status'=>'blocked','reason'=>'database-read-failed');$blockers[]='File 12 database schema state could not be read reliably.';}else{$checks['schema']=$missing?array('status'=>'blocked','missing'=>$missing):array('status'=>'ok','version'=>(string)get_option('pldr_db_version'));if($missing)$blockers[]='File 12 database schema is incomplete.';}
        $checks['cron']=array(
            'outbox'=>wp_next_scheduled('pldr_dispatch_outbox')?:0,
            'token_cleanup'=>wp_next_scheduled('pldr_cleanup_tokens')?:0,
            'integrity'=>wp_next_scheduled('pldr_integrity_sample')?:0,
            'rights_expiry'=>wp_next_scheduled('pldr_rights_expiry')?:0,
            'future_preservation'=>wp_next_scheduled('pldr_future_preservation_scan')?:0,
            'future_cleanup'=>wp_next_scheduled('pldr_future_cleanup')?:0,
        );
        foreach($checks['cron'] as $job=>$timestamp)if(!$timestamp)$blockers[]='Required File 12 scheduled job '.$job.' is not registered.';
        $future_loader=(array)get_option('pldr_future_loader_error',array());
        $future_schema=(array)get_option('pldr_future_schema_error',array());
        $checks['future_runtime']=array(
            'db_version'=>(string)get_option('pldr_future_db_version',''),
            'schema_revision'=>(string)get_option('pldr_future_schema_revision',''),
            'loader_error'=>$future_loader?:null,
            'schema_error'=>$future_schema?:null,
        );
        if($future_loader)$blockers[]='File 12 Future-24 loader reported an incomplete deployed class set.';
        if($future_schema)$blockers[]='File 12 Future-24 schema upgrade reported an unresolved error.';
        if(''===$checks['future_runtime']['db_version']||''===$checks['future_runtime']['schema_revision'])$blockers[]='File 12 Future-24 database/schema revision evidence is missing.';
        $deps=array('File 00 identity/capabilities'=>class_exists('SMC_Plugin')||function_exists('smc_has_entitlement')||has_filter('pldr_authorize'),'File 01 route/contract registry'=>has_action('spf_register_route')||has_action('spf_register_contract'),'File 19 notifications'=>has_action('sabri_domain_event'),'File 20 shell'=>has_action('suas_register_slot'),'File 25 visual components'=>has_action('spui_register_component_provider'));
        $checks['integrations']=array();foreach($deps as $label=>$present){$checks['integrations'][$label]=$present?'available':'adapter-awaiting-runtime';if(!$present)$warnings[]=$label.' adapter is not detectable in this runtime.';}
        $scanner=has_filter('pldr_malware_scan');$ocr=has_filter('pldr_ocr_extract_text');$checks['providers']=array('malware_scanner'=>$scanner?'adapter-present':'adapter-missing','ocr'=>$ocr?'adapter-present':'adapter-missing','preview'=>class_exists('Imagick')?'imagick-present':'adapter-missing');if(defined('PLDR_REQUIRE_MALWARE_SCANNER')&&PLDR_REQUIRE_MALWARE_SCANNER&&!$scanner)$blockers[]='Required malware scanner adapter is missing.';if(!$ocr)$warnings[]='OCR provider is not configured; lawful full-text search will remain degraded.';if(!class_exists('Imagick'))$warnings[]='Imagick PDF preview provider is unavailable; page-thumbnail generation will remain degraded.';
        $counts=array();$count_failed=false;foreach(array('documents','editions','objects','rights_cases','outbox','book_packs') as $table){$wpdb->last_error='';$counts[$table]=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.PLDR_Core::table($table));if(''!==(string)$wpdb->last_error){$count_failed=true;break;}}$checks['counts']=$count_failed?array('status'=>'blocked','reason'=>'database-read-failed'):$counts;if($count_failed)$blockers[]='File 12 health counters could not be read reliably.';
        $wpdb->last_error='';$dead=(int)$wpdb->get_var("SELECT COUNT(*) FROM ".PLDR_Core::table('outbox')." WHERE status='dead-letter'");if(''!==(string)$wpdb->last_error)$warnings[]='Outbox dead-letter state could not be read reliably.';elseif($dead)$warnings[]=$dead.' File 12 events are in dead-letter state.';
        try{$backup=apply_filters('pldr_backup_evidence',null);}catch(Throwable $e){$backup=null;$warnings[]='Backup/restore evidence provider failed and staging acceptance remains blocked.';} $checks['backup_restore_evidence']=is_array($backup)?$backup:array('status'=>'external-evidence-not-provided');if(!is_array($backup))$warnings[]='Backup/restore/key-recovery proof is a staging acceptance gate and has not been supplied by the runtime adapter.';
        $legacy=(array)get_option('pldr_legacy_migration_state',array());$checks['legacy_migration']=$legacy?:array('status'=>'not-required-or-not-detected');
        return array('module'=>'File 12 — PDF Library and Digital Reading','version'=>PLDR_VERSION,'db_version'=>(string)get_option('pldr_db_version'),'contract_version'=>PLDR_CONTRACT_VERSION,'status'=>$blockers?'blocked':'code-ready-runtime-checks-required','blockers'=>array_values(array_unique($blockers)),'warnings'=>array_values(array_unique($warnings)),'checks'=>$checks,'staging_accepted'=>false,'live_verified'=>false,'operational_verified'=>false);
    }

    public static function repair(string $operation) {
        if(!PLDR_Core::authorize('repair'))return PLDR_Core::machine_error('pldr_repair_forbidden','Repair authority is required.',403);
        global $wpdb;
        if('schema'===$operation){delete_transient('pldr_r21_core_schema_ready');PLDR_Schema_Corrections::invalidate_health_cache();$ok=PLDR_R21_Readiness::core_ready(true);PLDR_Core::audit('system',0,'schema_repair_r21',array('ok'=>$ok,'correction_revision'=>PLDR_Schema_Corrections::revision()));return array('operation'=>'schema','ok'=>$ok,'canonical_guard'=>'R21');}
        if('search-index'===$operation){
            $state=(array)get_option('pldr_search_repair_state',array());$after=absint($state['after_id']??0);$limit=100;
            $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare('SELECT d.id,d.title,d.language,d.subjects_json,d.collections_json,e.author_name,e.translator,e.publisher,e.isbn FROM '.PLDR_Core::table('documents').' d LEFT JOIN '.PLDR_Core::table('editions').' e ON e.id=(SELECT MAX(e2.id) FROM '.PLDR_Core::table('editions').' e2 WHERE e2.document_id=d.id) WHERE d.id>%d ORDER BY d.id ASC LIMIT %d',$after,$limit),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_repair_search_read','Search-index source state could not be read reliably.',503,array('degraded'=>true));$rows=is_array($rows)?$rows:array();$last=$after;
            foreach($rows as $r){$subjects=json_decode((string)$r['subjects_json'],true);$collections=json_decode((string)$r['collections_json'],true);if(!is_array($subjects)||!is_array($collections))return PLDR_Core::machine_error('pldr_repair_search_metadata','Search-index source metadata failed JSON integrity validation; repair stopped before writing this document.',500,array('document_id'=>(int)$r['id']));$text=PLDR_Core::normalize_search(implode(' ',array($r['title'],$r['language'],$r['author_name'],$r['translator'],$r['publisher'],$r['isbn'],implode(' ',$subjects),implode(' ',$collections))));if(false===$wpdb->update(PLDR_Core::table('documents'),array('search_text'=>$text,'updated_at'=>PLDR_Core::now()),array('id'=>(int)$r['id'])))return PLDR_Core::machine_error('pldr_repair_search_write','Search-index rebuild failed before completion.',500,array('document_id'=>(int)$r['id']));$last=(int)$r['id'];}
            $done=count($rows)<$limit;if($done)delete_option('pldr_search_repair_state');else update_option('pldr_search_repair_state',array('after_id'=>$last,'updated_at'=>PLDR_Core::now()),false);PLDR_Core::audit('system',0,'search_index_rebuilt_batch',array('documents'=>count($rows),'after_id'=>$last,'done'=>$done));return array('operation'=>'search-index','documents'=>count($rows),'done'=>$done,'after_id'=>$last,'batch_limit'=>$limit,'resumable'=>true);
        }
        if('tokens'===$operation){PLDR_Access::cleanup_tokens();return array('operation'=>'tokens','ok'=>true);}
        if('outbox'===$operation){$result=PLDR_R21_Outbox::dispatch();return array_merge(array('operation'=>'outbox','canonical_guard'=>'R21'),$result);}
        if('legacy-migration'===$operation){PLDR_R21_Runtime_Guards::legacy_migration_guarded();return array('operation'=>'legacy-migration','state'=>get_option('pldr_legacy_migration_state'),'canonical_guard'=>'R21');}
        if('rescan-pending'===$operation){$wpdb->last_error='';$ids=$wpdb->get_col("SELECT id FROM ".PLDR_Core::table('documents')." WHERE status='scan' ORDER BY id ASC LIMIT 25");if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_rescan_queue_read','Pending rescan queue could not be read reliably.',503,array('degraded'=>true));$results=array();foreach(is_array($ids)?$ids:array() as $id)$results[]=PLDR_Ingest::rescan_document((int)$id);return array('operation'=>'rescan-pending','results'=>$results);}
        if('rotate-keys'===$operation){return self::rotate_keys(10);}
        if('integrity-sample'===$operation){return self::integrity_sample(10);}
        return PLDR_Core::machine_error('pldr_repair_operation','Unknown safe repair operation.',400);
    }

    public static function rotate_keys(int $limit=10):array {
        global $wpdb;
        $active=PLDR_Crypto::active_key_id();
        if(!$active)return array('operation'=>'rotate-keys','results'=>array(),'error'=>'No active key.');
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM ".PLDR_Core::table('objects')." WHERE object_status='available' AND key_id<>%s ORDER BY id ASC LIMIT %d",$active,max(1,min(25,$limit))),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('operation'=>'rotate-keys','results'=>array(),'error'=>'Object key-rotation state could not be read reliably.');
        $rows=is_array($rows)?$rows:array();$results=array();
        foreach($rows as $object){
            $object_id=(int)$object['id'];
            $old=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);
            if(is_wp_error($old)){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$old->get_error_message());continue;}
            $error='';
            if(!PLDR_Crypto::verify_file((string)$old,(string)$object['sha256'],$error)){
                $results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation refused an object that failed plaintext integrity verification: '.$error,'integrity_verified'=>false);continue;
            }
            $plain=PLDR_Storage::temp('key-rotate-plain');$enc=PLDR_Storage::temp('key-rotate-encrypted');
            if(is_wp_error($plain)||is_wp_error($enc)){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Temporary storage unavailable.');continue;}
            if(!PLDR_Crypto::decrypt_to_file((string)$old,(string)$plain,$error)){$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$error);PLDR_Storage::delete((string)$plain);continue;}
            $plain_hash=hash_file('sha256',(string)$plain)?:'';
            if(''===$plain_hash||!hash_equals(strtolower((string)$object['sha256']),strtolower($plain_hash))){PLDR_Storage::delete((string)$plain);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation plaintext checksum reconciliation failed.');continue;}
            $allocation=PLDR_Storage::allocate('pldr');$meta=array();
            if(!empty($allocation['error'])||!PLDR_Crypto::encrypt_file((string)$plain,(string)$enc,$meta,$error)||!PLDR_Storage::atomic_commit((string)$enc,(string)$allocation['path'])){
                $results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>$error?:'Key rotation encryption/commit failed.');PLDR_Storage::delete((string)$plain);PLDR_Storage::delete((string)$enc);continue;
            }
            PLDR_Storage::delete((string)$plain);
            if((string)($meta['key_id']??'')!==$active||''===(string)($meta['encrypted_sha256']??'')){
                PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation output did not bind to the configured active key.');continue;
            }
            $verify_error='';
            if(!PLDR_Crypto::verify_file((string)$allocation['path'],(string)$object['sha256'],$verify_error)){
                PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation output failed post-encryption decrypt/checksum verification: '.$verify_error);continue;
            }
            if(false===$wpdb->query('START TRANSACTION')){PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation transaction could not be started.');continue;}
            $updated=$wpdb->query($wpdb->prepare(
                'UPDATE '.PLDR_Core::table('objects').' SET storage_name=%s,storage_scope=%s,encrypted_sha256=%s,key_id=%s,format_version=%s,verified_at=%s WHERE id=%d AND object_status=%s AND storage_name=%s AND storage_scope=%s AND key_id=%s AND sha256=%s AND encrypted_sha256=%s',
                (string)$allocation['name'],'pldr',(string)$meta['encrypted_sha256'],(string)$meta['key_id'],(string)$meta['format'],PLDR_Core::now(),$object_id,'available',(string)$object['storage_name'],(string)$object['storage_scope'],(string)$object['key_id'],(string)$object['sha256'],(string)$object['encrypted_sha256']
            ));
            if(1!==$updated){$wpdb->query('ROLLBACK');PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Object state changed during key rotation; no stale metadata was committed.','cas_conflict'=>true);continue;}
            if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');PLDR_Storage::delete((string)$allocation['path']);$results[]=array('object_id'=>$object_id,'ok'=>false,'error'=>'Key rotation commit failed.');continue;}
            $old_deleted=PLDR_Storage::delete((string)$old);
            PLDR_Core::audit('object',$object_id,'key_rotated',array('old_key'=>$object['key_id'],'new_key'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true,'old_ciphertext_deleted'=>$old_deleted));
            $results[]=array('object_id'=>$object_id,'ok'=>$old_deleted,'key_id'=>$meta['key_id'],'plaintext_integrity_verified'=>true,'post_rotation_verified'=>true,'cas_committed'=>true,'old_ciphertext_deleted'=>$old_deleted,'reconciliation_required'=>!$old_deleted);
        }
        return array('operation'=>'rotate-keys','active_key_id'=>$active,'results'=>$results);
    }

    public static function integrity_sample(int $limit = 3): array {
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

final class PLDR_Admin {
    public static function hooks():void { add_action('admin_menu',array(__CLASS__,'menu'));add_action('admin_post_pldr_rights_decision',array(__CLASS__,'decision'));add_action('admin_post_pldr_approve_document',array(__CLASS__,'approve'));add_action('admin_post_pldr_rescan_document',array(__CLASS__,'rescan'));add_action('admin_post_pldr_safe_repair',array(__CLASS__,'repair'));add_action('admin_notices',array(__CLASS__,'notices')); }
    public static function menu():void { add_menu_page('PDF Library','PDF Library','manage_pdf_library','pldr-library',array(__CLASS__,'dashboard'),'dashicons-book-alt',33);add_submenu_page('pldr-library','Rights Cases','Rights Cases','pldr_review_rights','pldr-rights',array(__CLASS__,'rights'));add_submenu_page('pldr-library','System Health','System Health','manage_pdf_library','pldr-health',array(__CLASS__,'health')); }
    public static function dashboard():void {
        global $wpdb;
        $wpdb->last_error='';
        $docs=$wpdb->get_results('SELECT d.*,e.id AS latest_edition_id,e.author_name,e.edition_label,e.pages,e.status AS edition_status FROM '.PLDR_Core::table('documents').' d LEFT JOIN '.PLDR_Core::table('editions').' e ON e.id=(SELECT MAX(e2.id) FROM '.PLDR_Core::table('editions').' e2 WHERE e2.document_id=d.id) ORDER BY d.updated_at DESC LIMIT 100',ARRAY_A);
        if(''!==(string)$wpdb->last_error)wp_die(esc_html__('PDF Library administration state could not be read reliably. Retry after database health is restored.','pdf-library-digital-reading'),array('response'=>503));
        $docs=is_array($docs)?$docs:array();
        ?><div class="wrap"><h1>File 12 — PDF Library and Digital Reading</h1><p>Canonical contract <?php echo esc_html(PLDR_CONTRACT_VERSION);?> · Runtime <?php echo esc_html(PLDR_VERSION);?></p><table class="widefat striped"><thead><tr><th>Document</th><th>Author / Edition</th><th>Document / Edition state</th><th>Access</th><th>Review action</th></tr></thead><tbody><?php foreach($docs as $d):?><tr><td><strong><?php echo esc_html($d['title']);?></strong><br><code><?php echo esc_html($d['public_id']);?></code></td><td><?php echo esc_html($d['author_name'].' · '.$d['edition_label'].' · '.$d['pages'].' pages');?></td><td><?php echo esc_html($d['status'].' / '.$d['edition_status']);?></td><td><?php echo esc_html($d['access_mode']);?></td><td><?php if('scan'===$d['edition_status']||'scan'===$d['status']):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="pldr_rescan_document"><input type="hidden" name="document_id" value="<?php echo absint((int)$d['id']);?>"><?php wp_nonce_field('pldr_rescan_document_'.$d['id']);?><button class="button">Rescan</button></form><?php endif;?><?php if(in_array($d['edition_status'],array('rights_review','scan'),true)):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="pldr_approve_document"><input type="hidden" name="document_id" value="<?php echo absint((int)$d['id']);?>"><input type="hidden" name="expected_version" value="<?php echo absint((int)$d['version']);?>"><?php wp_nonce_field('pldr_approve_document_'.$d['id']);?><button class="button button-primary">Approve clean edition</button></form><?php endif;?></td></tr><?php endforeach;?></tbody></table></div><?php
    }
    public static function rights():void { global $wpdb;$wpdb->last_error='';$rows=$wpdb->get_results('SELECT c.*,d.title FROM '.PLDR_Core::table('rights_cases').' c JOIN '.PLDR_Core::table('documents').' d ON d.id=c.document_id ORDER BY c.updated_at DESC LIMIT 200',ARRAY_A);if(''!==(string)$wpdb->last_error)wp_die(esc_html__('Rights-case administration state could not be read reliably. Retry after database health is restored.','pdf-library-digital-reading'),array('response'=>503));$rows=is_array($rows)?$rows:array();?><div class="wrap"><h1>File 12 Rights Cases</h1><table class="widefat striped"><thead><tr><th>Case</th><th>Document</th><th>Reason / State</th><th>Decision</th></tr></thead><tbody><?php foreach($rows as $r):?><tr><td><code><?php echo esc_html($r['case_key']);?></code></td><td><?php echo esc_html($r['title']);?></td><td><?php echo esc_html($r['reason'].' / '.$r['state']);?></td><td><?php if(!in_array($r['state'],array('closed'),true)):?><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="pldr_rights_decision"><input type="hidden" name="case_id" value="<?php echo absint((int)$r['id']);?>"><input type="hidden" name="expected_version" value="<?php echo absint((int)$r['version']);?>"><?php wp_nonce_field('pldr_rights_decision_'.$r['id']);?><select name="decision"><option value="restrict">Restrict</option><option value="remove">Remove</option><option value="restore">Restore</option><option value="dismiss">Dismiss</option><option value="request-evidence">Request evidence</option></select><textarea name="note" required placeholder="Reasoned decision note"></textarea><button class="button button-primary">Apply</button></form><?php else: echo esc_html($r['decision_note']); endif;?></td></tr><?php endforeach;?></tbody></table></div><?php }
    public static function decision():void { if(!PLDR_Core::authorize('rights'))wp_die('Denied',array('response'=>403));$id=absint($_POST['case_id']??0);check_admin_referer('pldr_rights_decision_'.$id);$result=PLDR_Rights::decide($id,sanitize_key((string)($_POST['decision']??'')),sanitize_textarea_field((string)($_POST['note']??'')),0,absint($_POST['expected_version']??0));if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),array('response'=>(int)($result->get_error_data()['status']??400)));wp_safe_redirect(admin_url('admin.php?page=pldr-rights'));exit; }
    public static function approve():void { if(!PLDR_Core::authorize('rights')&&!PLDR_Core::authorize('manage'))wp_die('Denied',array('response'=>403));$id=absint($_POST['document_id']??0);check_admin_referer('pldr_approve_document_'.$id);$result=PLDR_Rights::approve_document($id,0,absint($_POST['expected_version']??0));if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),array('response'=>(int)($result->get_error_data()['status']??400)));wp_safe_redirect(admin_url('admin.php?page=pldr-library'));exit; }
    public static function rescan():void { if(!PLDR_Core::authorize('repair')&&!PLDR_Core::authorize('rights'))wp_die('Denied',array('response'=>403));$id=absint($_POST['document_id']??0);check_admin_referer('pldr_rescan_document_'.$id);$result=PLDR_Ingest::rescan_document($id);if(is_wp_error($result))wp_die(esc_html($result->get_error_message()),array('response'=>(int)($result->get_error_data()['status']??400)));wp_safe_redirect(admin_url('admin.php?page=pldr-library'));exit; }
    public static function health():void { $report=PLDR_Health::report();?><div class="wrap"><h1>File 12 System Health</h1><div class="notice <?php echo $report['blockers']?'notice-error':'notice-success';?> inline"><p><strong><?php echo esc_html($report['status']);?></strong></p></div><?php if($report['blockers']):?><h2>Blockers</h2><ul><?php foreach($report['blockers'] as $b):?><li><?php echo esc_html($b);?></li><?php endforeach;?></ul><?php endif;?><h2>Warnings / staging gates</h2><ul><?php foreach($report['warnings'] as $w):?><li><?php echo esc_html($w);?></li><?php endforeach;?></ul><h2>Safe repair</h2><form method="post" action="<?php echo esc_url(admin_url('admin-post.php'));?>"><input type="hidden" name="action" value="pldr_safe_repair"><?php wp_nonce_field('pldr_safe_repair');?><select name="operation"><option value="schema">Schema reconciliation</option><option value="search-index">Search index rebuild</option><option value="tokens">Expired/revoked token cleanup</option><option value="outbox">Outbox dispatch</option><option value="integrity-sample">Integrity sample</option><option value="legacy-migration">Legacy 0.2.0 migration batch</option><option value="rescan-pending">Rescan pending documents</option><option value="rotate-keys">Rotate encrypted objects to active key</option></select><button class="button">Run safe repair</button></form><h2>Evidence snapshot</h2><pre><?php echo esc_html(wp_json_encode($report,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));?></pre></div><?php }
    public static function repair():void { if(!PLDR_Core::authorize('repair'))wp_die('Denied',array('response'=>403));check_admin_referer('pldr_safe_repair');$result=PLDR_Health::repair(sanitize_key((string)($_POST['operation']??'')));if(is_wp_error($result))wp_die(esc_html($result->get_error_message()));wp_safe_redirect(admin_url('admin.php?page=pldr-health'));exit; }
    public static function notices():void { if(!current_user_can('manage_pdf_library'))return;$report=PLDR_Health::report();if($report['blockers'])echo '<div class="notice notice-error"><p><strong>File 12 upload/readiness gate is blocked:</strong> '.esc_html(implode(' ',$report['blockers'])).'</p></div>'; }
}
