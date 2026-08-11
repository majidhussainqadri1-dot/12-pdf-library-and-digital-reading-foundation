<?php

defined('ABSPATH') || exit;

final class PLDR_Access {
    public static function can_access_edition(int $edition_id, string $operation = 'read', int $user_id = 0): bool {
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition) return false;
        $user_id = $user_id ?: get_current_user_id();
        if (isset($edition['status']) && 'published' !== $edition['status'] && !PLDR_Core::authorize('manage',(int)$edition['document_id'],$user_id) && !PLDR_Core::authorize('rights',(int)$edition['document_id'],$user_id)) return false;
        if (!in_array($edition['document_status'], array('published', 'restricted'), true)) {
            if (!PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id) && !PLDR_Core::authorize('rights', (int) $edition['document_id'], $user_id)) return false;
        }
        if ('restricted' === $edition['document_status'] && !PLDR_Core::authorize('read_private', (int) $edition['document_id'], $user_id)) return false;
        $policy = PLDR_Core::policy((int) $edition['document_id']);
        if (!$policy) return false;
        if (!empty($policy['embargo_until']) && strtotime((string) $policy['embargo_until']) > time() && !PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id)) return false;
        if ('download' === $operation && empty($policy['download_allowed'])) return false;
        if ('print' === $operation && empty($policy['print_allowed'])) return false;
        if ('offline' === $operation && empty($policy['offline_allowed'])) return false;
        if ('preview' === $operation) $operation = 'read';

        $audience = (string) $policy['audience'];
        if ('public' === $audience) return true;
        if (!$user_id) return false;
        if ('account' === $audience) return true;

        $entitlement_key = trim((string)($policy['entitlement_key'] ?? ''));
        if ('' === $entitlement_key) return false;
        try {
            $allowed = apply_filters('pldr_user_entitled', null, $user_id, $edition_id, $entitlement_key, $audience);
            if (is_bool($allowed)) return $allowed;
            if (function_exists('smc_has_entitlement')) {
                return (bool) smc_has_entitlement($user_id, $entitlement_key);
            }
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'entitlement_provider_failed',array('audience'=>$audience,'provider_failure'=>true),$user_id);
            return false;
        }
        return PLDR_Core::authorize('manage', (int) $edition['document_id'], $user_id);
    }

    public static function update_policy(int $document_id,array $changes,int $actor_id=0,int $expected_version=0) {
        global $wpdb; $actor_id=$actor_id?:get_current_user_id();
        if(!PLDR_Core::authorize('manage',$document_id,$actor_id)&&!PLDR_Core::authorize('rights',$document_id,$actor_id))return PLDR_Core::machine_error('pldr_policy_forbidden','Document access-policy authority is required.',403);
        $doc=PLDR_Core::document($document_id);$current=PLDR_Core::policy($document_id);if(!$doc||!$current)return PLDR_Core::machine_error('pldr_policy_missing','Document access policy was not found.',404);
        if($expected_version&&(int)$current['version']!==$expected_version)return PLDR_Core::machine_error('pldr_policy_conflict','Access policy changed; refresh before updating.',409,array('current_version'=>(int)$current['version']));

        $audience=sanitize_key((string)($changes['audience']??$current['audience']));
        if(!in_array($audience,array('public','account','education-entitled','assigned'),true))return PLDR_Core::machine_error('pldr_policy_audience','Invalid access audience.',400);
        $entitlement_key=sanitize_text_field((string)($changes['entitlement_key']??$current['entitlement_key']));
        if(in_array($audience,array('education-entitled','assigned'),true)&&''===trim($entitlement_key))return PLDR_Core::machine_error('pldr_policy_entitlement','This access audience requires a non-empty entitlement key.',400);

        $embargo=$current['embargo_until'];
        if(array_key_exists('embargo_until',$changes)){
            $embargo=self::parse_embargo($changes['embargo_until']);
            if(is_wp_error($embargo))return $embargo;
        }

        $row=array(
            'document_id'=>$document_id,
            'audience'=>$audience,
            'entitlement_key'=>$entitlement_key,
            'download_allowed'=>array_key_exists('download_allowed',$changes)?(empty($changes['download_allowed'])?0:1):(int)$current['download_allowed'],
            'print_allowed'=>array_key_exists('print_allowed',$changes)?(empty($changes['print_allowed'])?0:1):(int)$current['print_allowed'],
            'offline_allowed'=>array_key_exists('offline_allowed',$changes)?(empty($changes['offline_allowed'])?0:1):(int)$current['offline_allowed'],
            'embargo_until'=>$embargo,
            'version'=>(int)$current['version']+1,
            'created_at'=>PLDR_Core::now(),
            'updated_at'=>PLDR_Core::now(),
        );

        $wpdb->query('START TRANSACTION');
        $ok=$wpdb->insert(PLDR_Core::table('access_policies'),$row);
        if(false===$ok){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_policy_conflict','Access policy could not be versioned because the policy changed concurrently.',409,array('current_version'=>(int)(PLDR_Core::policy($document_id)['version']??$current['version'])));}
        $doc_updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('documents').' SET access_mode=%s,version=version+1,updated_at=%s WHERE id=%d AND version=%d',$audience,PLDR_Core::now(),$document_id,(int)$doc['version']));
        if(1!==$doc_updated){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_policy_document_conflict','Document changed concurrently; access-policy update was rolled back.',409,array('current_document_version'=>(int)(PLDR_Core::document($document_id)['version']??$doc['version'])));}
        if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');return PLDR_Core::machine_error('pldr_policy_commit','Access-policy update could not be committed atomically.',500);}

        PLDR_Access::revoke_document($document_id,'access-policy-change');
        PLDR_Core::audit('document',$document_id,'access_policy_updated',array('version'=>$row['version'],'audience'=>$audience),$actor_id);
        PLDR_Core::emit('PDFDocumentAccessChanged.v1','document',$document_id,array('document_id'=>$doc['public_id'],'policy_version'=>$row['version'],'audience'=>$audience));
        return array('document_id'=>$doc['public_id'],'policy_version'=>$row['version'],'audience'=>$audience);
    }

    private static function parse_embargo($value) {
        if(null===$value||''===trim((string)$value))return null;
        $raw=trim((string)$value);
        if(!preg_match('/^\d{4}-\d{2}-\d{2}(?:[ T]\d{2}:\d{2}(?::\d{2})?(?:Z|[+\-]\d{2}:\d{2})?)?$/',$raw))return PLDR_Core::machine_error('pldr_policy_embargo','Embargo date must be an explicit ISO-style date/time, not a relative or malformed value.',400);
        $ts=strtotime($raw);
        if(false===$ts)return PLDR_Core::machine_error('pldr_policy_embargo','Embargo date could not be parsed safely.',400);
        return gmdate('Y-m-d H:i:s',$ts);
    }

    public static function issue_token(int $edition_id, int $object_id, string $operation, int $user_id = 0, int $ttl = 600) {
        global $wpdb;
        if (!in_array($operation, array('read', 'download', 'print', 'offline', 'preview'), true)) {
            return PLDR_Core::machine_error('pldr_operation', 'Unsupported File 12 delivery operation.', 400);
        }
        $user_id = $user_id ?: get_current_user_id();
        if (!self::can_access_edition($edition_id, $operation, $user_id)) {
            return PLDR_Core::machine_error('pldr_access_denied', 'The requested document is unavailable for this operation.', 403);
        }
        $edition = PLDR_Core::edition($edition_id);
        $object = PLDR_Core::object($object_id);
        if (!$edition || !$object || 'available' !== $object['object_status']) {
            return PLDR_Core::machine_error('pldr_object_unavailable', 'The requested document object is unavailable.', 404);
        }
        if ((int) $edition['object_id'] !== $object_id && !self::derivative_belongs($edition_id, $object_id)) {
            return PLDR_Core::machine_error('pldr_object_mismatch', 'The requested object does not belong to this document edition.', 403);
        }
        try {
            $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        } catch (Throwable $e) {
            return PLDR_Core::machine_error('pldr_token_random', 'Secure access token generation failed.', 500);
        }
        $ttl = max(60, min(1800, $ttl));
        $expires = gmdate('Y-m-d H:i:s', time() + $ttl);
        $audience = $user_id ? 'user:' . $user_id : 'public';
        $audience_hash = hash('sha256', $audience . '|' . $edition_id . '|' . $operation);
        $max_uses = 'download' === $operation || 'offline' === $operation ? 4096 : 1024;
        $ok = $wpdb->insert(PLDR_Core::table('access_tokens'), array(
            'token_hash' => hash('sha256', $token),
            'user_id' => $user_id,
            'edition_id' => $edition_id,
            'object_id' => $object_id,
            'operation' => $operation,
            'audience_hash' => $audience_hash,
            'expires_at' => $expires,
            'revoked_at' => null,
            'used_count' => 0,
            'max_uses' => $max_uses,
            'created_at' => PLDR_Core::now(),
        ));
        if (false === $ok) {
            return PLDR_Core::machine_error('pldr_token_store', 'Secure access token could not be stored.', 500);
        }
        return array(
            'token' => $token,
            'url' => home_url('/library/delivery/' . rawurlencode($token) . '/'),
            'expires_at' => $expires,
            'operation' => $operation,
            'size' => (int) $object['byte_size'],
            'sha256' => (string) $object['sha256'],
            'filename' => sanitize_file_name((string) $object['original_name']),
            'mime_type' => (string) $object['mime_type'],
        );
    }

    private static function derivative_belongs(int $edition_id, int $object_id): bool {
        global $wpdb;
        return (bool) $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . PLDR_Core::table('derivatives') . ' WHERE edition_id=%d AND object_id=%d AND status=%s LIMIT 1',
            $edition_id,
            $object_id,
            'available'
        ));
    }

    public static function revoke_document(int $document_id, string $reason = 'policy-change'): int {
        global $wpdb;
        $edition_ids = $wpdb->get_col($wpdb->prepare('SELECT id FROM ' . PLDR_Core::table('editions') . ' WHERE document_id=%d', $document_id));
        if (!$edition_ids) return 0;
        $ids = implode(',', array_map('absint', $edition_ids));
        $count = $wpdb->query('UPDATE ' . PLDR_Core::table('access_tokens') . " SET revoked_at='" . esc_sql(PLDR_Core::now()) . "' WHERE revoked_at IS NULL AND edition_id IN ($ids)");
        PLDR_Core::audit('document', $document_id, 'access_revoked', array('reason' => $reason, 'tokens' => (int) $count));
        return (int) $count;
    }

    public static function deliver(string $token): void {
        global $wpdb;
        $hash = hash('sha256', $token);
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT * FROM ' . PLDR_Core::table('access_tokens') . ' WHERE token_hash=%s LIMIT 1',
            $hash
        ), ARRAY_A);
        if (!$row || !empty($row['revoked_at']) || strtotime((string) $row['expires_at']) <= time() || (int) $row['used_count'] >= (int) $row['max_uses']) {
            self::fail_delivery(410, 'Access grant expired or revoked.');
        }
        $user_id = get_current_user_id();
        if ((int) $row['user_id'] > 0 && $user_id !== (int) $row['user_id']) {
            self::fail_delivery(403, 'Access grant audience mismatch.');
        }
        $audience = (int) $row['user_id'] > 0 ? 'user:' . (int) $row['user_id'] : 'public';
        $expected_audience = hash('sha256', $audience . '|' . (int) $row['edition_id'] . '|' . $row['operation']);
        if (!hash_equals((string) $row['audience_hash'], $expected_audience)) {
            self::fail_delivery(403, 'Access grant binding failed.');
        }
        if (!self::can_access_edition((int) $row['edition_id'], (string) $row['operation'], (int) $row['user_id'])) {
            self::fail_delivery(403, 'Document access is no longer permitted.');
        }
        $object = PLDR_Core::object((int) $row['object_id']);
        if (!$object || 'available' !== $object['object_status']) {
            self::fail_delivery(404, 'Document object unavailable.');
        }
        $path = PLDR_Storage::path((string) $object['storage_name'], (string) $object['storage_scope']);
        if (is_wp_error($path)) {
            self::fail_delivery(503, $path->get_error_message());
        }

        $size = (int) $object['byte_size'];
        if ($size < 1) self::fail_delivery(503, 'Document object has an invalid byte size.');
        [$start, $end, $partial] = self::parse_range($size);
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        if ('HEAD' !== $method) {
            $consumed = $wpdb->query($wpdb->prepare(
                'UPDATE ' . PLDR_Core::table('access_tokens') . ' SET used_count=used_count+1 WHERE id=%d AND revoked_at IS NULL AND expires_at>%s AND used_count<max_uses',
                (int) $row['id'],
                PLDR_Core::now()
            ));
            if (1 !== $consumed) self::fail_delivery(410, 'Access grant was exhausted, expired or revoked before delivery began.');
        }

        while (ob_get_level()) ob_end_clean();
        nocache_headers();
        header('Accept-Ranges: bytes');
        header('X-Content-Type-Options: nosniff');
        header('Referrer-Policy: no-referrer');
        header('Content-Security-Policy: default-src \'none\'; sandbox');
        header('Content-Type: ' . self::safe_mime((string) $object['mime_type']));
        $disposition = in_array($row['operation'], array('download', 'offline'), true) ? 'attachment' : 'inline';
        header('Content-Disposition: ' . $disposition . '; filename="' . str_replace('"', '', sanitize_file_name((string) $object['original_name'])) . '"');
        if ($partial) {
            http_response_code(206);
            header('Content-Range: bytes ' . $start . '-' . $end . '/' . $size);
        } else {
            http_response_code(200);
        }
        header('Content-Length: ' . ($end - $start + 1));
        header('X-PLDR-SHA256: ' . (string) $object['sha256']);

        if ('HEAD' === $method) exit;

        $meta = array(); $error = '';
        $ok = PLDR_Crypto::stream_range($path, $start, $end, static function (string $chunk): void {
            echo $chunk;
            if (function_exists('fastcgi_finish_request')) {
            }
            flush();
        }, $meta, $error);
        if (!$ok) {
            error_log('[PLDR][' . PLDR_Core::trace_id() . '] delivery-integrity-failure ' . sanitize_text_field($error));
        }
        exit;
    }

    private static function parse_range(int $size): array {
        $header = (string) ($_SERVER['HTTP_RANGE'] ?? '');
        if (!$header) return array(0, $size - 1, false);
        $header = trim($header);
        if (false !== strpos($header, ',')) self::fail_delivery(416, 'Multiple byte ranges are not supported.');
        if (!preg_match('/^bytes=(\d*)-(\d*)$/', $header, $m)) self::fail_delivery(416, 'Invalid byte range.');
        if ('' === $m[1] && '' === $m[2]) self::fail_delivery(416, 'Invalid byte range.');
        if ('' === $m[1]) {
            $suffix = min($size, max(1, (int) $m[2]));
            return array($size - $suffix, $size - 1, true);
        }
        $start = (int) $m[1];
        $end = '' === $m[2] ? $size - 1 : min((int) $m[2], $size - 1);
        if ($start >= $size || $end < $start) self::fail_delivery(416, 'Requested byte range is unsatisfiable.');
        return array($start, $end, true);
    }

    private static function safe_mime(string $mime): string {
        $allowed = array('application/pdf', 'image/jpeg', 'image/png', 'image/webp', 'text/plain');
        return in_array($mime, $allowed, true) ? $mime : 'application/octet-stream';
    }

    private static function fail_delivery(int $status, string $message): void {
        while (ob_get_level()) ob_end_clean();
        nocache_headers();
        status_header($status);
        header('Content-Type: text/plain; charset=utf-8');
        echo esc_html($message);
        exit;
    }

    public static function cleanup_tokens(): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare('DELETE FROM ' . PLDR_Core::table('access_tokens') . ' WHERE expires_at<%s OR (revoked_at IS NOT NULL AND revoked_at<%s)', gmdate('Y-m-d H:i:s', time() - DAY_IN_SECONDS), gmdate('Y-m-d H:i:s', time() - 7 * DAY_IN_SECONDS)));
        $wpdb->query($wpdb->prepare('DELETE FROM ' . PLDR_Core::table('idempotency') . ' WHERE expires_at<%s', PLDR_Core::now()));
    }
}
