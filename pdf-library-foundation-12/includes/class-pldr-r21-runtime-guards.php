<?php

defined('ABSPATH') || exit;

/**
 * R21 cross-cutting runtime guards discovered by fresh review rounds.
 * Each guard is fail-safe and bounded; domain ownership remains in native classes.
 */
final class PLDR_R21_Runtime_Guards {
    private static bool $idempotency_cleanup_ran = false;

    public static function hooks(): void {
        add_filter('rest_pre_dispatch', array(__CLASS__, 'cleanup_stale_idempotency'), 1, 3);
        add_filter('rest_pre_dispatch', array(__CLASS__, 'key_write_preflight'), 2, 3);
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
        $ids=array();
        $sources=array();
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
}
