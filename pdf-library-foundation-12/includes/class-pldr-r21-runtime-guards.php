<?php

defined('ABSPATH') || exit;

/**
 * R21 cross-cutting runtime guards discovered by fresh review rounds.
 * Each guard is fail-safe and bounded; domain ownership remains in native classes.
 */
final class PLDR_R21_Runtime_Guards {
    private static bool $idempotency_cleanup_ran = false;
    private const LEGACY_LOCK='pldr_r21_legacy_runtime_lock';

    public static function hooks(): void {
        add_filter('rest_pre_dispatch', array(__CLASS__, 'cleanup_stale_idempotency'), 1, 3);
        add_filter('rest_pre_dispatch', array(__CLASS__, 'key_write_preflight'), 2, 3);
        add_action('admin_post_pldr_safe_repair',array(__CLASS__,'admin_repair_guarded'),2);
    }

    public static function admin_repair_guarded():void {
        $operation=sanitize_key((string)($_POST['operation']??''));
        if(!in_array($operation,array('schema','outbox','legacy-migration'),true))return;
        if(!PLDR_Core::authorize('repair'))wp_die('Denied',array('response'=>403));
        check_admin_referer('pldr_safe_repair');
        if('schema'===$operation){
            delete_transient('pldr_r21_core_schema_ready');
            $ok=PLDR_R21_Readiness::core_ready(true);
            PLDR_Core::audit('system',0,'schema_repair_r21',array('ok'=>$ok,'correction_revision'=>PLDR_Schema_Corrections::revision()));
            if(!$ok)wp_die(esc_html__('File 12 schema reconciliation did not reach a verified ready state.','pdf-library-digital-reading'),array('response'=>503));
        }elseif('outbox'===$operation){
            PLDR_R21_Outbox::dispatch();
        }else{
            self::legacy_migration_guarded();
        }
        wp_safe_redirect(admin_url('admin.php?page=pldr-health'));exit;
    }

    public static function key_write_preflight($result, $server, WP_REST_Request $request) {
        if (null !== $result) return $result;
        $route=(string)$request->get_route();
        if ('POST'!==strtoupper((string)$request->get_method())) return $result;
        $key_write='/pldr/v1/ingest'===$route;
        if ('/pldr/v1/repair'===$route) {
            $operation=sanitize_key((string)$request['operation']);
            $key_write='rotate-keys'===$operation;
        }
        if (!$key_write || !self::ambiguous_active_key()) return $result;
        PLDR_Core::audit('security',0,'ambiguous_active_encryption_key_blocked',array('route'=>$route));
        return PLDR_Core::machine_error('pldr_active_key_ambiguous','Multiple File 12 master keys are configured but no explicit active key ID is selected; encryption/key-rotation was blocked to prevent writing under an arbitrary key.',503,array('degraded'=>true));
    }

    private static function ambiguous_active_key(): bool {
        if (defined('PLDR_PDF_ACTIVE_KEY_ID') && ''!==sanitize_key((string)PLDR_PDF_ACTIVE_KEY_ID)) return false;
        if (defined('SPL_PDF_ACTIVE_KEY_ID') && ''!==sanitize_key((string)SPL_PDF_ACTIVE_KEY_ID)) return false;
        $ids=array();$sources=array();
        if (defined('SPL_PDF_MASTER_KEYS')) $sources[]=SPL_PDF_MASTER_KEYS;
        if (defined('SPL_PDF_MASTER_KEY')) $sources[]=array('legacy'=>SPL_PDF_MASTER_KEY);
        if (defined('PLDR_PDF_MASTER_KEYS')) $sources[]=PLDR_PDF_MASTER_KEYS;
        foreach($sources as $raw){
            if(is_string($raw)){ $decoded=json_decode($raw,true); $raw=is_array($decoded)?$decoded:array(); }
            if(!is_array($raw))continue;
            foreach($raw as $id=>$value){ if(''!==sanitize_key((string)$id) && is_string($value))$ids[sanitize_key((string)$id)]=true; }
        }
        return count($ids)>1;
    }

    public static function cleanup_stale_idempotency($result, $server, WP_REST_Request $request) {
        if (self::$idempotency_cleanup_ran || 0 !== strpos((string)$request->get_route(), '/pldr/v1/')) return $result;
        self::$idempotency_cleanup_ran = true;
        global $wpdb;
        $cutoff = gmdate('Y-m-d H:i:s', time() - 1800);
        $table = PLDR_Core::table('idempotency');
        $wpdb->last_error = '';
        $deleted = $wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE status_code=0 AND created_at<=%s ORDER BY created_at ASC LIMIT 100", $cutoff));
        if (false === $deleted || '' !== (string)$wpdb->last_error) {
            PLDR_Core::audit('mutation', 0, 'stale_idempotency_cleanup_failed', array('db_error'=>substr((string)$wpdb->last_error,0,500)));
        } elseif ($deleted > 0) {
            PLDR_Core::audit('mutation', 0, 'stale_idempotency_reservations_reaped', array('count'=>(int)$deleted,'stale_after_seconds'=>1800));
        }
        return $result;
    }

    public static function legacy_migration_guarded(): void {
        $lock=self::acquire_legacy_lock();
        if(!$lock){
            if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');
            PLDR_Core::audit('migration',0,'legacy_batch_concurrent_run_deferred',array());
            return;
        }
        try{
            $snapshots=self::capture_current_progress();
            if(isset($snapshots['error'])){
                PLDR_Core::audit('migration',0,'legacy_progress_snapshot_failed',array('scope'=>$snapshots['error']));
                if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');
                return;
            }
            PLDR_Schema::migrate_legacy_batch();
            self::restore_current_progress($snapshots['rows']);
        }finally{
            self::release_legacy_lock($lock);
        }
    }

    private static function acquire_legacy_lock(): ?string {
        global $wpdb;
        try{$nonce=bin2hex(random_bytes(12));}catch(Throwable $e){$nonce=wp_generate_password(24,false,false);}
        $token=time().':'.$nonce;
        if(add_option(self::LEGACY_LOCK,$token,'',false))return $token;
        $current=(string)get_option(self::LEGACY_LOCK,'');$parts=explode(':',$current,2);$at=absint($parts[0]??0);
        if($at&&time()-$at<900)return null;
        $wpdb->last_error='';
        $updated=$wpdb->query($wpdb->prepare("UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",$token,self::LEGACY_LOCK,$current));
        wp_cache_delete(self::LEGACY_LOCK,'options');
        return 1===$updated&&''===(string)$wpdb->last_error?$token:null;
    }

    private static function release_legacy_lock(string $token):void {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",self::LEGACY_LOCK,$token));
        wp_cache_delete(self::LEGACY_LOCK,'options');
    }

    private static function capture_current_progress():array {
        global $wpdb;
        $state=(array)get_option('pldr_legacy_migration_state',array());$last=absint($state['last_legacy_id']??0);
        $wpdb->last_error='';
        $legacy_ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='spl_document' AND ID>%d ORDER BY ID ASC LIMIT 25",$last));
        if(''!==(string)$wpdb->last_error)return array('error'=>'legacy-id-read','rows'=>array());
        $legacy_ids=is_array($legacy_ids)?$legacy_ids:array();$snapshots=array();$legacy_user_table=$wpdb->prefix.'spl_user_data';
        $wpdb->last_error='';$legacy_user_exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$legacy_user_table));
        if(''!==(string)$wpdb->last_error)return array('error'=>'legacy-user-table-read','rows'=>array());
        if($legacy_user_exists!==$legacy_user_table)return array('rows'=>array());
        foreach($legacy_ids as $legacy_id){
            $legacy_id=absint($legacy_id);$wpdb->last_error='';
            $document_id=absint($wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('documents').' WHERE search_text LIKE %s LIMIT 1','% legacy:'.$legacy_id)));
            if(''!==(string)$wpdb->last_error)return array('error'=>'mapped-document-read','rows'=>array());
            if(!$document_id)continue;
            $edition=PLDR_Core::latest_edition($document_id);if(!$edition)continue;$edition_id=(int)$edition['id'];
            $reconcile=(array)get_option('pldr_legacy_reconcile_'.$legacy_id,array());$cursor=absint($reconcile['user_cursor']??0);
            $wpdb->last_error='';
            $legacy_rows=$wpdb->get_results($wpdb->prepare("SELECT id,user_id,data_type FROM {$legacy_user_table} WHERE document_id=%d AND id>%d ORDER BY id ASC LIMIT 101",$legacy_id,$cursor),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return array('error'=>'legacy-user-batch-read','rows'=>array());
            $uids=array_values(array_unique(array_filter(array_map(static fn($r)=>'progress'===(string)($r['data_type']??'')?absint($r['user_id']??0):0,is_array($legacy_rows)?array_slice($legacy_rows,0,100):array()))));
            if(!$uids)continue;$in=implode(',',array_map('absint',$uids));$wpdb->last_error='';
            $rows=$wpdb->get_results("SELECT user_id,edition_id,last_page,percent,edition_version,updated_at FROM ".PLDR_Core::table('reading_state')." WHERE edition_id={$edition_id} AND user_id IN ({$in})",ARRAY_A);
            if(''!==(string)$wpdb->last_error)return array('error'=>'native-progress-read','rows'=>array());
            foreach((array)$rows as $row)$snapshots[(int)$row['user_id'].':'.(int)$row['edition_id']]=$row;
        }
        return array('rows'=>array_values($snapshots));
    }

    private static function restore_current_progress(array $rows):void {
        global $wpdb;$restored=0;$failed=0;$table=PLDR_Core::table('reading_state');
        foreach($rows as $row){
            $wpdb->last_error='';
            $sql=$wpdb->prepare(
                "INSERT INTO {$table} (user_id,edition_id,last_page,percent,edition_version,updated_at) VALUES (%d,%d,%d,%s,%d,%s) ON DUPLICATE KEY UPDATE last_page=IF(updated_at<=VALUES(updated_at),VALUES(last_page),last_page),percent=IF(updated_at<=VALUES(updated_at),VALUES(percent),percent),edition_version=IF(updated_at<=VALUES(updated_at),VALUES(edition_version),edition_version),updated_at=GREATEST(updated_at,VALUES(updated_at))",
                (int)$row['user_id'],(int)$row['edition_id'],(int)$row['last_page'],(string)$row['percent'],(int)$row['edition_version'],(string)$row['updated_at']
            );
            $ok=$wpdb->query($sql);
            if(false===$ok||''!==(string)$wpdb->last_error)$failed++;else$restored++;
        }
        if($rows)PLDR_Core::audit('migration',0,'legacy_native_progress_preserved',array('snapshots'=>count($rows),'restored'=>$restored,'failed'=>$failed));
        if($failed&&!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');
    }
}
