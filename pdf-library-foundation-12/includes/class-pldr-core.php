<?php

defined('ABSPATH') || exit;

final class PLDR_Core {
    private const AUDIT_CONTEXT_MAX_BYTES = 16384;
    private const AUDIT_CONTEXT_MAX_DEPTH = 4;
    private const AUDIT_CONTEXT_MAX_ITEMS = 50;
    private const AUDIT_CONTEXT_MAX_STRING = 1000;
    private const OUTBOX_PAYLOAD_MAX_BYTES = 65536;
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

    private static function sanitize_audit_value($value, int $depth = 0) {
        if ($depth > self::AUDIT_CONTEXT_MAX_DEPTH) return '[depth-limit]';
        if (null === $value || is_bool($value) || is_int($value) || is_float($value)) return $value;
        if (is_string($value)) {
            $value = sanitize_text_field($value);
            if (function_exists('mb_substr')) return mb_substr($value, 0, self::AUDIT_CONTEXT_MAX_STRING, 'UTF-8');
            return substr($value, 0, self::AUDIT_CONTEXT_MAX_STRING);
        }
        if (is_array($value)) {
            $safe = array(); $seen = 0;
            foreach ($value as $key => $nested) {
                if ($seen >= self::AUDIT_CONTEXT_MAX_ITEMS) { $safe['_items_truncated'] = true; break; }
                $key_string = (string) $key;
                if (preg_match('/secret|token|password|key|patient|note_text|authorization|cookie|session|credential|nonce/i', $key_string)) continue;
                $safe_key = is_int($key) ? $key : sanitize_key($key_string);
                if ('' === (string) $safe_key) continue;
                $safe[$safe_key] = self::sanitize_audit_value($nested, $depth + 1);
                $seen++;
            }
            return $safe;
        }
        return '[non-scalar-context]';
    }

    public static function audit(string $object_type, int $object_id, string $action, array $context = array(), int $actor_id = 0): bool {
        global $wpdb;
        $actor_id = $actor_id ?: get_current_user_id();
        $safe = self::sanitize_audit_value($context, 0);
        if (!is_array($safe)) $safe = array('_context'=>'invalid');
        $context_json=wp_json_encode($safe);
        if(!is_string($context_json)){error_log('[PLDR]['.self::trace_id().'] audit-context-encode-failed '.sanitize_key($action));return false;}
        if(strlen($context_json)>self::AUDIT_CONTEXT_MAX_BYTES){
            $context_json=wp_json_encode(array('_context_truncated'=>'size-limit','max_bytes'=>self::AUDIT_CONTEXT_MAX_BYTES));
            if(!is_string($context_json))return false;
        }
        $ok=$wpdb->insert(self::table('audit'), array(
            'trace_id' => self::trace_id(),
            'object_type' => sanitize_key($object_type),
            'object_id' => $object_id,
            'action' => sanitize_key($action),
            'actor_id' => $actor_id,
            'context_json' => $context_json,
            'created_at' => self::now(),
        ), array('%s', '%s', '%d', '%s', '%d', '%s', '%s'));
        if(false===$ok){error_log('[PLDR]['.self::trace_id().'] audit-store-failed '.sanitize_key($action));return false;}
        return true;
    }

    public static function emit(string $event_name, string $aggregate_type, int $aggregate_id, array $payload) {
        global $wpdb;
        $event_id = self::uuid();
        $payload_json = wp_json_encode($payload);
        if (!is_string($payload_json)) {
            error_log('[PLDR][' . self::trace_id() . '] outbox-payload-encode-failed ' . sanitize_text_field($event_name));
            return self::machine_error('pldr_outbox_encode','Reliable event payload could not be encoded; reconciliation is required.',500,array('event_name'=>sanitize_text_field($event_name)));
        }
        if (strlen($payload_json) > self::OUTBOX_PAYLOAD_MAX_BYTES) {
            self::audit('outbox',0,'outbox_payload_rejected',array('event_name'=>$event_name,'payload_bytes'=>strlen($payload_json),'max_bytes'=>self::OUTBOX_PAYLOAD_MAX_BYTES));
            return self::machine_error('pldr_outbox_payload_size','Reliable event payload exceeds the bounded File 12 event contract; it was not persisted.',413,array('max_bytes'=>self::OUTBOX_PAYLOAD_MAX_BYTES));
        }
        $ok=$wpdb->insert(self::table('outbox'), array(
            'event_id' => $event_id,
            'event_name' => sanitize_text_field($event_name),
            'aggregate_type' => sanitize_key($aggregate_type),
            'aggregate_id' => $aggregate_id,
            'payload_json' => $payload_json,
            'status' => 'pending',
            'attempts' => 0,
            'available_at' => self::now(),
            'created_at' => self::now(),
        ), array('%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s'));
        if(false===$ok){
            error_log('[PLDR][' . self::trace_id() . '] outbox-store-failed ' . sanitize_text_field($event_name));
            return self::machine_error('pldr_outbox_store','Reliable event could not be persisted; reconciliation is required.',503,array('event_name'=>sanitize_text_field($event_name)));
        }
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
        $row=$wpdb->get_row($wpdb->prepare('SELECT e.* FROM '.self::table('editions').' e WHERE e.document_id=%d AND e.status=%s ORDER BY e.id DESC LIMIT 1',$document_id,'published'),ARRAY_A);
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

    public static function consume_mutation_rate(string $route,int $actor_id=0,int $default_limit=600) {
        global $wpdb;
        $route=substr(sanitize_key(str_replace('/','-',$route)),0,120);
        if(''===$route)$route='mutation';
        $actor_id=$actor_id?:get_current_user_id();
        if($actor_id>0)$identity='u:'.$actor_id;
        else{
            $ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
            $ua=substr(sanitize_text_field((string)($_SERVER['HTTP_USER_AGENT']??'unknown')),0,300);
            $identity='a:'.hash_hmac('sha256',$ip.'|'.$ua,wp_salt('auth'));
        }
        $scope=hash('sha256',$identity.'|'.$route);
        $bucket='pldr_mut_rate_'.substr(hash('sha256',$scope.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_mut_rate_'.substr($scope,0,32);
        $wpdb->last_error='';$locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(''!==(string)$wpdb->last_error||1!==$locked)return self::machine_error('pldr_mutation_rate_lock','Mutation abuse-protection state is temporarily unavailable; no mutation was executed.',503,array('retry_after'=>2));
        try{
            $count=(int)get_transient($bucket);
            try{$limit=(int)apply_filters('pldr_mutation_hourly_limit',$default_limit,$route,$actor_id);}
            catch(Throwable $e){self::audit('mutation',0,'mutation_rate_policy_provider_failed',array('route'=>$route,'provider_failure'=>true),$actor_id);return self::machine_error('pldr_mutation_rate_policy','Mutation rate policy could not be verified; no mutation was executed.',503,array('degraded'=>true,'provider_failure'=>true));}
            $limit=max(60,min(5000,$limit));
            if($count>=$limit)return self::machine_error('pldr_mutation_rate_limit','This mutation is temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return self::machine_error('pldr_mutation_rate_store','Mutation rate state could not be stored; no mutation was executed.',503);
            return array('allowed'=>true,'hourly_limit'=>$limit,'remaining'=>max(0,$limit-$count-1));
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

    public static function request_fingerprint(WP_REST_Request $request): string {
        $files=array();
        foreach((array)$request->get_file_params() as $name=>$file){
            if(!is_array($file))continue;
            $entry=array(
                'name'=>sanitize_file_name((string)($file['name']??'')),
                'type'=>sanitize_mime_type((string)($file['type']??'')),
                'size'=>absint($file['size']??0),
                'error'=>absint($file['error']??0),
            );
            $tmp=(string)($file['tmp_name']??'');
            if(''!==$tmp&&is_file($tmp)&&is_readable($tmp)){
                $digest=hash_file('sha256',$tmp);
                if(is_string($digest))$entry['sha256']=$digest;
            }
            $files[sanitize_key((string)$name)]=$entry;
        }
        ksort($files);
        $payload=array(
            'method'=>strtoupper((string)$request->get_method()),
            'route'=>(string)$request->get_route(),
            'params'=>self::canonicalize_idempotency_value($request->get_params()),
            'body_sha256'=>hash('sha256',(string)$request->get_body()),
            'files'=>$files,
        );
        $json=wp_json_encode($payload,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        return is_string($json)?hash('sha256',$json):'';
    }

    private static function canonicalize_idempotency_value($value){
        if(is_array($value)){
            if(array_is_list($value))return array_map(array(__CLASS__,'canonicalize_idempotency_value'),$value);
            ksort($value);
            foreach($value as $key=>$item)$value[$key]=self::canonicalize_idempotency_value($item);
            return $value;
        }
        if(is_object($value))return self::canonicalize_idempotency_value((array)$value);
        if(is_bool($value)||is_int($value)||is_float($value)||null===$value)return $value;
        return (string)$value;
    }

    public static function scope_anonymous_idempotency_key(string $key): string {
        $ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
        $ua=substr(sanitize_text_field((string)($_SERVER['HTTP_USER_AGENT']??'unknown')),0,300);
        $client=hash_hmac('sha256',$ip.'|'.$ua,wp_salt('auth'));
        return hash('sha256',$client.'|'.substr($key,0,200));
    }

    private static function idempotency_identity(string $route,string $key): array {
        $route=substr(sanitize_text_field($route),0,120);
        $key=substr(sanitize_text_field($key),0,200);
        return array($route,hash('sha256',$key));
    }

    public static function idempotency_begin(string $route,string $key,int $actor_id,string $request_hash=''): array {
        global $wpdb;
        if(''===$key)return array('state'=>'disabled');
        [$route,$hash]=self::idempotency_identity($route,$key);
        $request_hash=preg_match('/^[a-f0-9]{64}$/',$request_hash)?$request_hash:'';
        $now=self::now();
        $wpdb->last_error='';
        $expired_cleanup=$wpdb->query($wpdb->prepare('DELETE FROM '.self::table('idempotency').' WHERE actor_id=%d AND route=%s AND key_hash=%s AND expires_at<=%s',$actor_id,$route,$hash,$now));
        if(false===$expired_cleanup)return array('state'=>'error','db_error'=>(string)$wpdb->last_error,'phase'=>'expired-cleanup');
        $expires=gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS);
        $pending_json=wp_json_encode(array('_request_hash'=>$request_hash));
        if(!is_string($pending_json))return array('state'=>'error','db_error'=>'idempotency request fingerprint could not be encoded','phase'=>'fingerprint-encode');
        $wpdb->last_error='';
        $inserted=$wpdb->insert(self::table('idempotency'),array(
            'actor_id'=>$actor_id,'route'=>$route,'key_hash'=>$hash,'response_json'=>$pending_json,'status_code'=>0,'expires_at'=>$expires,'created_at'=>$now,
        ),array('%d','%s','%s','%s','%d','%s','%s'));
        if(1===$inserted)return array('state'=>'reserved','request_hash'=>$request_hash);
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT response_json,status_code,expires_at FROM '.self::table('idempotency').' WHERE actor_id=%d AND route=%s AND key_hash=%s AND expires_at>%s LIMIT 1',$actor_id,$route,$hash,$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('state'=>'error','db_error'=>(string)$wpdb->last_error,'phase'=>'existing-read');
        if(!$row)return array('state'=>'error','db_error'=>'idempotency reservation could not be confirmed','phase'=>'existing-read');
        $stored=json_decode((string)$row['response_json'],true);
        $stored_hash=is_array($stored)?(string)($stored['_request_hash']??''):'';
        if(''!==$request_hash&&(''===$stored_hash||!hash_equals($stored_hash,$request_hash)))return array('state'=>'conflict','reason'=>'request-fingerprint-mismatch');
        if(0===(int)$row['status_code'])return array('state'=>'pending');
        $body=is_array($stored)&&array_key_exists('response',$stored)?$stored['response']:$stored;
        return array('state'=>'hit','body'=>$body,'status'=>(int)$row['status_code']);
    }

    public static function idempotency_complete(string $route,string $key,int $actor_id,$body,int $status=200,string $request_hash=''): bool {
        global $wpdb;
        if(''===$key)return true;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $request_hash=preg_match('/^[a-f0-9]{64}$/',$request_hash)?$request_hash:'';
        $json=wp_json_encode(array('_request_hash'=>$request_hash,'response'=>$body));
        if(false===$json)return false;
        $expires=gmdate('Y-m-d H:i:s',time()+DAY_IN_SECONDS);
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.self::table('idempotency').' SET response_json=%s,status_code=%d,expires_at=%s WHERE actor_id=%d AND route=%s AND key_hash=%s AND status_code=0',$json,max(100,min(599,$status)),$expires,$actor_id,$route,$hash));
        return 1===$updated;
    }

    public static function idempotency_abort(string $route,string $key,int $actor_id): bool {
        global $wpdb;
        if(''===$key)return true;
        [$route,$hash]=self::idempotency_identity($route,$key);
        $deleted=$wpdb->query($wpdb->prepare('DELETE FROM '.self::table('idempotency').' WHERE actor_id=%d AND route=%s AND key_hash=%s AND status_code=0',$actor_id,$route,$hash));
        return false!==$deleted;
    }

    public static function route_url(string $route, array $args = array()): string {
        $base = home_url('/library/');
        if ('document' === $route && !empty($args['id'])) return home_url('/library/document/' . rawurlencode((string) $args['id']) . '/' . rawurlencode((string) ($args['slug'] ?? 'document')) . '/');
        if ('read' === $route && !empty($args['id'])) return home_url('/library/read/' . rawurlencode((string) $args['id']) . '/');
        if ('reading' === $route) return home_url('/account/reading/');
        if ('manage' === $route) return home_url('/library/manage/');
        return $base;
    }
}
