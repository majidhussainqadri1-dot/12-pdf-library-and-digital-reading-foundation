<?php

defined('ABSPATH') || exit;

final class PLDR_Core {
    public const DOCUMENT_TYPES = array(
        'book' => 'Book',
        'reference-book' => 'Reference Book',
        'research-paper' => 'Research Paper',
        'clinical-study' => 'Clinical Study',
        'educational-notes' => 'Educational Notes',
        'lecture-handout' => 'Lecture Handout',
        'materia-medica' => 'Materia Medica',
        'repertory' => 'Repertory',
        'philosophy' => 'Philosophy',
        'anatomy' => 'Anatomy',
        'pathology' => 'Pathology',
        'nutrition' => 'Nutrition',
        'public-health' => 'Public Health',
        'principles-hygiene' => 'Principles of Hygiene',
        'islamic-spiritual-healing' => 'Islamic Spiritual Healing',
        'historical-document' => 'Historical Document',
    );

    public const CATEGORIES = array(
        'classical-homeopathy' => 'Classical Homeopathy',
        'homeopathy-education' => 'Homeopathy Education',
        'materia-medica' => 'Materia Medica',
        'repertory' => 'Repertory',
        'clinical-education' => 'Clinical Education',
        'patient-cases' => 'Patient Cases',
        'homeopathy-philosophy' => 'Homeopathy Philosophy',
        'research' => 'Research',
        'nutrition' => 'Nutrition',
        'public-health-education' => 'Public Health Education',
        'pathology' => 'Pathology',
        'anatomy' => 'Anatomy',
        'principles-hygiene' => 'Principles of Hygiene',
        'islamic-spiritual-healing' => 'Islamic Spiritual Healing',
        'platform-publications' => 'Platform Publications',
        'historical-homeopathy' => 'Historical Homeopathy',
    );

    public static function table(string $name): string {
        global $wpdb;
        return $wpdb->prefix . 'pldr_' . sanitize_key($name);
    }

    public static function now(): string {
        return current_time('mysql', true);
    }

    public static function uuid(): string {
        return wp_generate_uuid4();
    }

    public static function trace_id(): string {
        try {
            return bin2hex(random_bytes(8));
        } catch (Throwable $e) {
            return substr(hash('sha256', uniqid('', true)), 0, 16);
        }
    }

    public static function founder(int $user_id = 0): bool {
        $user_id = $user_id ?: get_current_user_id();
        if (!$user_id) return false;
        $founder = absint(get_option('spf_founder_user_id', 0));
        return $founder > 0 && $user_id === $founder;
    }

    public static function authorize(string $action, int $object_id = 0, int $user_id = 0): bool {
        $user_id = $user_id ?: get_current_user_id();
        try {
            $external = apply_filters('pldr_authorize', null, $action, $user_id, $object_id);
        } catch (Throwable $e) {
            self::audit('authorization',$object_id,'authorization_provider_failed',array('action'=>$action,'provider_failure'=>true),$user_id);
            return false;
        }
        if (is_bool($external)) return $external;
        if (self::founder($user_id)) return true;
        $map = array(
            'manage' => 'manage_pdf_library',
            'publish' => 'pldr_publish_documents',
            'rights' => 'pldr_review_rights',
            'repair' => 'pldr_repair_library',
            'read_private' => 'pldr_read_restricted',
        );
        if (isset($map[$action]) && $user_id && user_can($user_id, $map[$action])) return true;
        if ('publish' === $action && $user_id) {
            try {
                $doctor = apply_filters('pldr_verified_doctor', null, $user_id);
                if (is_bool($doctor)) return $doctor;
                if (class_exists('SPD_Helpers') && method_exists('SPD_Helpers', 'is_doctor') && method_exists('SPD_Helpers', 'verification_status')) {
                    return SPD_Helpers::is_doctor($user_id) && 'verified' === SPD_Helpers::verification_status($user_id);
                }
            } catch (Throwable $e) {
                self::audit('authorization',$object_id,'doctor_verification_provider_failed',array('action'=>$action,'provider_failure'=>true),$user_id);
                return false;
            }
        }
        return false;
    }

    public static function sanitize_json_list($value): array {
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            $value = is_array($decoded) ? $decoded : preg_split('/\s*,\s*/u', $value, -1, PREG_SPLIT_NO_EMPTY);
        }
        if (!is_array($value)) return array();
        $clean = array();
        foreach ($value as $item) {
            $item = sanitize_text_field((string) $item);
            if ('' !== $item) $clean[] = $item;
        }
        return array_values(array_unique($clean));
    }

    public static function normalize_search(string $text): string {
        $text = wp_strip_all_tags($text);
        $text = remove_accents($text);
        $text = function_exists('mb_strtolower') ? mb_strtolower($text, 'UTF-8') : strtolower($text);
        $map = array(
            'ي' => 'ی', 'ى' => 'ی', 'ك' => 'ک', 'ۀ' => 'ہ', 'ة' => 'ہ', 'ؤ' => 'و', 'إ' => 'ا', 'أ' => 'ا', 'آ' => 'ا',
            'ـ' => '', 'َ' => '', 'ُ' => '', 'ِ' => '', 'ّ' => '', 'ْ' => '', 'ً' => '', 'ٌ' => '', 'ٍ' => '',
        );
        $text = strtr($text, $map);
        $text = preg_replace('/[^\p{L}\p{N}\s\-]+/u', ' ', $text) ?: $text;
        return trim((string) preg_replace('/\s+/u', ' ', $text));
    }

    public static function audit(string $object_type, int $object_id, string $action, array $context = array(), int $actor_id = 0): void {
        global $wpdb;
        $actor_id = $actor_id ?: get_current_user_id();
        $safe = array();
        foreach ($context as $key => $value) {
            if (preg_match('/secret|token|password|key|patient|note_text/i', (string) $key)) continue;
            $safe[sanitize_key((string) $key)] = is_scalar($value) ? (string) $value : wp_json_encode($value);
        }
        $wpdb->insert(self::table('audit'), array(
            'trace_id' => self::trace_id(),
            'object_type' => sanitize_key($object_type),
            'object_id' => $object_id,
            'action' => sanitize_key($action),
            'actor_id' => $actor_id,
            'context_json' => wp_json_encode($safe),
            'created_at' => self::now(),
        ), array('%s', '%s', '%d', '%s', '%d', '%s', '%s'));
    }

    public static function emit(string $event_name, string $aggregate_type, int $aggregate_id, array $payload): string {
        global $wpdb;
        $event_id = self::uuid();
        $wpdb->insert(self::table('outbox'), array(
            'event_id' => $event_id,
            'event_name' => sanitize_text_field($event_name),
            'aggregate_type' => sanitize_key($aggregate_type),
            'aggregate_id' => $aggregate_id,
            'payload_json' => wp_json_encode($payload),
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => self::now(),
            'created_at' => self::now(),
        ), array('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s'));
        return $event_id;
    }

    public static function document(int $id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('documents') . ' WHERE id=%d', $id), ARRAY_A);
        return $row ?: null;
    }

    public static function document_by_public_id(string $public_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('documents') . ' WHERE public_id=%s', $public_id), ARRAY_A);
        return $row ?: null;
    }

    public static function edition(int $edition_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare(
            'SELECT e.*, d.public_id, d.title, d.slug, d.status AS document_status, d.access_mode AS document_access_mode, d.version AS document_version FROM ' . self::table('editions') . ' e INNER JOIN ' . self::table('documents') . ' d ON d.id=e.document_id WHERE e.id=%d',
            $edition_id
        ), ARRAY_A);
        return $row ?: null;
    }

    public static function current_edition(int $document_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT e.* FROM '.self::table('editions').' e WHERE e.document_id=%d AND e.status=%s ORDER BY e.id DESC LIMIT 1',$document_id,'published'),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return null;
        if(!$row){
            $wpdb->last_error='';
            $row=$wpdb->get_row($wpdb->prepare('SELECT e.* FROM '.self::table('editions').' e WHERE e.document_id=%d ORDER BY e.id DESC LIMIT 1',$document_id),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return null;
        }
        return $row ?: null;
    }

    public static function latest_edition(int $document_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT e.* FROM '.self::table('editions').' e WHERE e.document_id=%d ORDER BY e.id DESC LIMIT 1',$document_id),ARRAY_A);
        return $row ?: null;
    }

    public static function policy(int $document_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('access_policies') . ' WHERE document_id=%d ORDER BY version DESC, id DESC LIMIT 1',$document_id), ARRAY_A);
        return $row ?: null;
    }

    public static function object(int $object_id): ?array {
        global $wpdb;
        $wpdb->last_error='';
        $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . self::table('objects') . ' WHERE id=%d', $object_id), ARRAY_A);
        return $row ?: null;
    }

    public static function public_document_dto(array $doc, ?array $edition = null, ?array $policy = null): array {
        $edition = $edition ?: self::current_edition((int) $doc['id']);
        $policy = $policy ?: self::policy((int) $doc['id']);
        return array(
            'id' => (string) $doc['public_id'],
            'title' => (string) $doc['title'],
            'slug' => (string) $doc['slug'],
            'type' => (string) $doc['document_type'],
            'category' => (string) $doc['category'],
            'language' => (string) $doc['language'],
            'subjects' => json_decode((string) $doc['subjects_json'], true) ?: array(),
            'collections' => json_decode((string) $doc['collections_json'], true) ?: array(),
            'status' => (string) $doc['status'],
            'access_mode' => (string) ($policy['audience'] ?? $doc['access_mode']),
            'edition' => $edition ? array(
                'id' => (int) $edition['id'],
                'label' => (string) $edition['edition_label'],
                'isbn' => (string) $edition['isbn'],
                'year' => (int) $edition['publication_year'],
                'pages' => (int) $edition['pages'],
                'author' => (string) $edition['author_name'],
                'translator' => (string) $edition['translator'],
                'publisher' => (string) $edition['publisher'],
                'source' => (string) $edition['source_name'],
                'license' => (string) $edition['license_code'],
                'sha256' => (string) $edition['sha256'],
                'version' => (int) $edition['version'],
            ) : null,
            'permissions' => array(
                'download' => !empty($policy['download_allowed']),
                'print' => !empty($policy['print_allowed']),
                'offline' => !empty($policy['offline_allowed']),
            ),
        );
    }

    public static function machine_error(string $code, string $message, int $status = 400, array $extra = array()): WP_Error {
        return new WP_Error($code, $message, array_merge(array('status' => $status, 'trace_id' => self::trace_id()), $extra));
    }

    private static function idempotency_identity(string $route,string $key): array {
        $route=substr(sanitize_text_field($route),0,120);
        $key=substr(sanitize_text_field($key),0,200);
        return array($route,hash('sha256',$key));
    }

    public static function idempotency_begin(string $route,string $key,int $actor_id): array {
        global $wpdb;
        if(''===$key)return array('state'=>'disabled');
        [$route,$hash]=self::idempotency_identity($route,$key);
        $now=self::now();
        $wpdb->last_error='';
        $expired_cleanup=$wpdb->query($wpdb->prepare('DELETE FROM '.self::table('idempotency').' WHERE actor_id=%d AND route=%s AND key_hash=%s AND expires_at<=%s',$actor_id,$route,$hash,$now));
        if(false===$expired_cleanup)return array('state'=>'error','db_error'=>(string)$wpdb->last_error,'phase'=>'expired-cleanup');
        $expires=gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS);
        $wpdb->last_error='';
        $inserted=$wpdb->insert(self::table('idempotency'),array(
            'actor_id'=>$actor_id,'route'=>$route,'key_hash'=>$hash,'response_json'=>'','status_code'=>0,'expires_at'=>$expires,'created_at'=>$now,
        ),array('%d','%s','%s','%s','%d','%s','%s'));
        if(1===$inserted)return array('state'=>'reserved');
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT response_json,status_code,expires_at FROM '.self::table('idempotency').' WHERE actor_id=%d AND route=%s AND key_hash=%s AND expires_at>%s LIMIT 1',$actor_id,$route,$hash,$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('state'=>'error','db_error'=>(string)$wpdb->last_error,'phase'=>'existing-read');
        if(!$row)return array('state'=>'error','db_error'=>'idempotency reservation could not be confirmed','phase'=>'existing-read');
        if(0===(int)$row['status_code'])return array('state'=>'pending');
        return array('state'=>'hit','body'=>json_decode((string)$row['response_json'],true),'status'=>(int)$row['status_code']);
    }

    public static function idempotency_complete(string $route,string $key,int $actor_id,$body,int $status=200): bool {
        global $wpdb;
        if(''===$key)return true;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $json=wp_json_encode($body);
        if(false===$json)return false;
        $expires=gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS);
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.self::table('idempotency').' SET response_json=%s,status_code=%d,expires_at=%s WHERE actor_id=%d AND route=%s AND key_hash=%s AND status_code=0',$json,max(100,min(599,$status)),$expires,$actor_id,$route,$hash));
        return 1===$updated;
    }

    public static function idempotency_abort(string $route,string $key,int $actor_id): bool {
        global $wpdb;
        if(''===$key)return true;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $deleted=$wpdb->delete(self::table('idempotency'),array('actor_id'=>$actor_id,'route'=>$route,'key_hash'=>$hash,'status_code'=>0),array('%d','%s','%s','%d'));
        return false!==$deleted;
    }

    public static function idempotency_lookup(string $route, string $key, int $actor_id): ?array {
        global $wpdb;
        if ('' === $key) return null;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $row = $wpdb->get_row($wpdb->prepare('SELECT response_json,status_code FROM ' . self::table('idempotency') . ' WHERE actor_id=%d AND route=%s AND key_hash=%s AND expires_at>%s AND status_code>0 LIMIT 1',$actor_id,$route,$hash,self::now()), ARRAY_A);
        if (!$row) return null;
        return array('body' => json_decode((string) $row['response_json'], true), 'status' => (int) $row['status_code']);
    }

    public static function idempotency_store(string $route, string $key, int $actor_id, $body, int $status = 200): bool {
        global $wpdb;
        if ('' === $key) return true;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $json=wp_json_encode($body);
        if(false===$json)return false;
        $expires = gmdate('Y-m-d H:i:s', time() + DAY_IN_SECONDS);
        $stored=$wpdb->replace(self::table('idempotency'),array(
            'actor_id' => $actor_id,
            'route' => $route,
            'key_hash' => $hash,
            'response_json' => $json,
            'status_code' => max(100,min(599,$status)),
            'expires_at' => $expires,
            'created_at' => self::now(),
        ),array('%d','%s','%s','%s','%d','%s','%s'));
        return false!==$stored;
    }

    public static function route_url(string $route, array $args = array()): string {
        $base = home_url('/library/');
        if ('document' === $route && !empty($args['id'])) return home_url('/library/document/' . rawurlencode((string) $args['id']) . '/' . rawurlencode((string) ($args['slug'] ?? 'document')) . '/');
        if ('read' === $route && !empty($args['id'])) return home_url('/library/read/' . rawurlencode((string) $args['id']) . '/');
        if ('reading' === $route) return home_url('/account/reading/');
        return $base;
    }
}
