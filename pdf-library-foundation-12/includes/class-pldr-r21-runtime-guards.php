<?php

defined('ABSPATH') || exit;

/**
 * R21 cross-cutting runtime guards discovered by fresh review rounds.
 * Each guard is fail-safe and bounded; domain ownership remains in native classes.
 */
final class PLDR_R21_Runtime_Guards {
    private static bool $idempotency_cleanup_ran = false;
    private const LEGACY_LOCK='pldr_r21_legacy_runtime_lock';
    private const LEGACY_RECOVERY_OPTION='pldr_r21_legacy_progress_recovery';

    public static function hooks(): void {
        add_filter('rest_pre_dispatch', array(__CLASS__, 'cleanup_stale_idempotency'), 1, 3);
        add_action('admin_post_pldr_safe_repair',array(__CLASS__,'admin_repair_guarded'),2);
        add_action('pldr_generate_derivatives',array(__CLASS__,'fingerprint_schedule_guard'),39,2);
    }

    public static function fingerprint_schedule_guard(int $edition_id,int $cursor=0):void {
        if(0!==$cursor)return;
        remove_action('pldr_generate_derivatives',array('PLDR_Future','after_derivatives'),40);
        $args=array($edition_id,0);
        if(!wp_next_scheduled('pldr_future_fingerprint_edition',$args)){
            wp_schedule_single_event(time()+90,'pldr_future_fingerprint_edition',$args);
        }
    }

    public static function admin_repair_guarded():void {
        $operation=sanitize_key((string)($_POST['operation']??''));
        if(!in_array($operation,array('schema','outbox','legacy-migration'),true))return;
        if(!PLDR_Core::authorize('repair'))wp_die('Denied',array('response'=>403));
        check_admin_referer('pldr_safe_repair');
        if('schema'===$operation){
            delete_transient('pldr_r21_core_schema_ready');
            PLDR_Schema_Corrections::invalidate_health_cache();
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

    /**
     * Reap only this authenticated actor's stale pending reservations. This is
     * maintenance, not authorization: anonymous/unauthorized traffic must not
     * be able to mutate another actor's replay state through rest_pre_dispatch.
     */
    public static function cleanup_stale_idempotency($result, $server, WP_REST_Request $request) {
        $method=strtoupper((string)$request->get_method());
        if(in_array($method,array('GET','HEAD','OPTIONS'),true))return $result;
        $actor=get_current_user_id();
        if($actor<1)return $result;
        $throttle='pldr_r21_idempotency_reap_'.$actor;
        if(self::$idempotency_cleanup_ran||get_transient($throttle)||0!==strpos((string)$request->get_route(),'/pldr/v1/'))return $result;
        self::$idempotency_cleanup_ran=true;
        global $wpdb;
        $cutoff=gmdate('Y-m-d H:i:s',time()-2*HOUR_IN_SECONDS);
        $table=PLDR_Core::table('idempotency');$wpdb->last_error='';
        $deleted=$wpdb->query($wpdb->prepare("DELETE FROM {$table} WHERE actor_id=%d AND status_code=0 AND created_at<=%s ORDER BY created_at ASC LIMIT 100",$actor,$cutoff));
        if(false===$deleted||''!==(string)$wpdb->last_error){
            PLDR_Core::audit('mutation',0,'stale_idempotency_cleanup_failed',array('actor_scope'=>true,'db_error'=>substr((string)$wpdb->last_error,0,500)),$actor);
        }else{
            set_transient($throttle,'1',MINUTE_IN_SECONDS);
            if($deleted>0)PLDR_Core::audit('mutation',0,'stale_idempotency_reservations_reaped',array('count'=>(int)$deleted,'stale_after_seconds'=>2*HOUR_IN_SECONDS,'actor_scope'=>true),$actor);
        }
        return $result;
    }

    public static function legacy_migration_guarded(): void {
        $lock=self::acquire_legacy_lock();
        if(!$lock){
            self::reschedule_legacy(60);
            PLDR_Core::audit('migration',0,'legacy_batch_concurrent_run_deferred',array());
            return;
        }
        try{
            $pending=(array)get_option(self::LEGACY_RECOVERY_OPTION,array());
            $pending_rows=is_array($pending['rows']??null)?$pending['rows']:array();
            if($pending_rows){
                $recovered=self::restore_current_progress($pending_rows);
                if(empty($recovered['ok'])){
                    self::reschedule_legacy(60);
                    PLDR_Core::audit('migration',0,'legacy_progress_recovery_pending',array('rows'=>count($pending_rows),'failed'=>(int)($recovered['failed']??count($pending_rows))));
                    return;
                }
                delete_option(self::LEGACY_RECOVERY_OPTION);
                PLDR_Core::audit('migration',0,'legacy_progress_recovery_completed',array('rows'=>count($pending_rows)));
            }

            $snapshots=self::capture_current_progress();
            if(isset($snapshots['error'])){
                PLDR_Core::audit('migration',0,'legacy_progress_snapshot_failed',array('scope'=>$snapshots['error']));
                self::reschedule_legacy(60);
                return;
            }
            $rows=is_array($snapshots['rows']??null)?$snapshots['rows']:array();
            if($rows&&!self::persist_recovery_journal($rows)){
                PLDR_Core::audit('migration',0,'legacy_progress_recovery_journal_failed',array('rows'=>count($rows)));
                self::reschedule_legacy(60);
                return;
            }

            try{
                PLDR_Schema::migrate_legacy_batch();
            }catch(Throwable $e){
                PLDR_Core::audit('migration',0,'legacy_batch_exception',array('recovery_journal'=>!empty($rows),'exception_class'=>sanitize_key(get_class($e))));
                self::reschedule_legacy(60);
                return;
            }

            if($rows){
                $restored=self::restore_current_progress($rows);
                if(empty($restored['ok'])){
                    PLDR_Core::audit('migration',0,'legacy_native_progress_restore_incomplete',array('snapshots'=>count($rows),'failed'=>(int)($restored['failed']??0),'journal_retained'=>true));
                    self::reschedule_legacy(60);
                    return;
                }
                delete_option(self::LEGACY_RECOVERY_OPTION);
            }
        }finally{
            self::release_legacy_lock($lock);
        }
    }

    private static function reschedule_legacy(int $delay):void {
        if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+max(30,$delay),'pldr_legacy_migration');
    }

    private static function persist_recovery_journal(array $rows):bool {
        if(!$rows)return true;
        $json=wp_json_encode($rows);
        if(!is_string($json))return false;
        $journal=array('rows'=>$rows,'sha256'=>hash('sha256',$json),'captured_at'=>PLDR_Core::now());
        update_option(self::LEGACY_RECOVERY_OPTION,$journal,false);
        $stored=(array)get_option(self::LEGACY_RECOVERY_OPTION,array());
        if(!is_array($stored['rows']??null)||!hash_equals((string)$journal['sha256'],(string)($stored['sha256']??'')))return false;
        $stored_json=wp_json_encode($stored['rows']);
        return is_string($stored_json)&&hash_equals((string)$journal['sha256'],hash('sha256',$stored_json));
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

    private static function restore_current_progress(array $rows):array {
        global $wpdb;$restored=0;$failed_rows=array();$table=PLDR_Core::table('reading_state');
        foreach($rows as $row){
            $wpdb->last_error='';
            $sql=$wpdb->prepare(
                "INSERT INTO {$table} (user_id,edition_id,last_page,percent,edition_version,updated_at) VALUES (%d,%d,%d,%s,%d,%s) ON DUPLICATE KEY UPDATE last_page=IF(updated_at<=VALUES(updated_at),VALUES(last_page),last_page),percent=IF(updated_at<=VALUES(updated_at),VALUES(percent),percent),edition_version=IF(updated_at<=VALUES(updated_at),VALUES(edition_version),edition_version),updated_at=GREATEST(updated_at,VALUES(updated_at))",
                (int)$row['user_id'],(int)$row['edition_id'],(int)$row['last_page'],(string)$row['percent'],(int)$row['edition_version'],(string)$row['updated_at']
            );
            $ok=$wpdb->query($sql);
            if(false===$ok||''!==(string)$wpdb->last_error)$failed_rows[]=$row;else$restored++;
        }
        if($rows)PLDR_Core::audit('migration',0,'legacy_native_progress_preserved',array('snapshots'=>count($rows),'restored'=>$restored,'failed'=>count($failed_rows),'journal_available'=>(bool)get_option(self::LEGACY_RECOVERY_OPTION)));
        return array('ok'=>!$failed_rows,'restored'=>$restored,'failed'=>count($failed_rows),'failed_rows'=>$failed_rows);
    }
}
