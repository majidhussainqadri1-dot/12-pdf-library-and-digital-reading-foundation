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
