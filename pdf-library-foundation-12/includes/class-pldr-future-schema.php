<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Schema {
    public const DB_VERSION = '1.1.0';
    private const SCHEMA_REVISION = '2026-08-11-r10';
    private const LOCK_OPTION = 'pldr_future_migration_lock';

    public static function maybe_upgrade(): void {
        $version_current = (string) get_option('pldr_future_db_version', '') === self::DB_VERSION;
        $revision_current = (string) get_option('pldr_future_schema_revision', '') === self::SCHEMA_REVISION;
        if ($version_current && $revision_current) {
            $health_key = 'pldr_future_schema_health_' . md5(self::SCHEMA_REVISION);
            if (get_transient($health_key)) return;
            $health = self::verify_schema();
            if ($health['ok']) {
                set_transient($health_key, '1', 15 * MINUTE_IN_SECONDS);
                return;
            }
            update_option('pldr_future_schema_error', array_merge($health, array('reason'=>'schema_drift','at'=>PLDR_Core::now())), false);
            PLDR_Core::audit('system', 0, 'future_24_schema_drift_detected', $health);
        }
        self::upgrade();
    }

    private static function acquire_lock(): ?string {
        global $wpdb;
        try { $nonce = bin2hex(random_bytes(12)); } catch (Throwable $e) { $nonce = wp_generate_password(24, false, false); }
        $token = time() . ':' . $nonce;
        if (add_option(self::LOCK_OPTION, $token, '', false)) return $token;
        $current = (string) get_option(self::LOCK_OPTION, '');
        $parts = explode(':', $current, 2); $locked_at = absint($parts[0] ?? 0);
        if ($locked_at && (time() - $locked_at) < 300) return null;
        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",
            $token,
            self::LOCK_OPTION,
            $current
        ));
        wp_cache_delete(self::LOCK_OPTION, 'options');
        return 1 === $updated ? $token : null;
    }

    private static function release_lock(string $token): void {
        global $wpdb;
        $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s", self::LOCK_OPTION, $token));
        wp_cache_delete(self::LOCK_OPTION, 'options');
    }

    public static function upgrade(): bool {
        global $wpdb;
        $lock = self::acquire_lock();
        if (!$lock) return false;
        try {
            require_once ABSPATH . 'wp-admin/includes/upgrade.php';
            $charset = $wpdb->get_charset_collate();
            $sql = array();
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('future_prefs') . " (
                user_id bigint unsigned NOT NULL,
                preference_key varchar(80) NOT NULL,
                preference_json longtext NOT NULL,
                version bigint unsigned NOT NULL DEFAULT 1,
                updated_at datetime NOT NULL,
                PRIMARY KEY (user_id,preference_key)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('shelves') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                shelf_key char(36) NOT NULL,
                user_id bigint unsigned NOT NULL,
                name varchar(120) NOT NULL,
                shelf_type varchar(32) NOT NULL DEFAULT 'custom',
                sort_order int NOT NULL DEFAULT 0,
                version bigint unsigned NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY shelf_key (shelf_key),
                KEY user_id (user_id)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('shelf_items') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                shelf_id bigint unsigned NOT NULL,
                edition_id bigint unsigned NOT NULL,
                added_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY shelf_edition (shelf_id,edition_id),
                KEY edition_id (edition_id)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('reading_events') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                event_id char(36) NOT NULL,
                user_id bigint unsigned NOT NULL,
                edition_id bigint unsigned NOT NULL,
                event_type varchar(24) NOT NULL,
                page_number int unsigned NOT NULL DEFAULT 1,
                duration_seconds int unsigned NOT NULL DEFAULT 0,
                context_json longtext NOT NULL,
                created_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY event_id (event_id),
                KEY user_created (user_id,created_at),
                KEY edition_id (edition_id)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('session_handoffs') . " (
                user_id bigint unsigned NOT NULL,
                edition_id bigint unsigned NOT NULL,
                page_number int unsigned NOT NULL DEFAULT 1,
                zoom varchar(30) NOT NULL,
                layout_mode varchar(30) NOT NULL,
                anchor_json longtext NOT NULL,
                device_hint varchar(80) NOT NULL,
                version bigint unsigned NOT NULL DEFAULT 1,
                updated_at datetime NOT NULL,
                PRIMARY KEY (user_id,edition_id),
                KEY updated_at (updated_at)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('ocr_corrections') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                edition_id bigint unsigned NOT NULL,
                page_number int unsigned NOT NULL,
                original_text text NOT NULL,
                corrected_text text NOT NULL,
                status varchar(24) NOT NULL DEFAULT 'pending',
                submitted_by bigint unsigned NOT NULL,
                reviewed_by bigint unsigned NOT NULL DEFAULT 0,
                review_note text NOT NULL,
                version bigint unsigned NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                KEY edition_page (edition_id,page_number),
                KEY status (status)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('authority_cache') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                identifier_type varchar(20) NOT NULL,
                identifier_value varchar(190) NOT NULL,
                provider varchar(80) NOT NULL,
                result_json longtext NOT NULL,
                provenance_json longtext NOT NULL,
                expires_at datetime NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY authority_key (identifier_type,identifier_value,provider),
                KEY expires_at (expires_at)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('a11y_audits') . " (
                edition_id bigint unsigned NOT NULL,
                score decimal(5,2) NOT NULL DEFAULT 0,
                status varchar(24) NOT NULL,
                findings_json longtext NOT NULL,
                provider varchar(80) NOT NULL,
                verified_by bigint unsigned NOT NULL DEFAULT 0,
                verified_at datetime NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (edition_id),
                KEY status (status)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('room_contexts') . " (
                id bigint unsigned NOT NULL AUTO_INCREMENT,
                room_key char(36) NOT NULL,
                edition_id bigint unsigned NOT NULL,
                created_by bigint unsigned NOT NULL,
                page_number int unsigned NOT NULL DEFAULT 1,
                anchor_json longtext NOT NULL,
                provider_ref varchar(190) NOT NULL,
                status varchar(24) NOT NULL,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY room_key (room_key),
                KEY edition_id (edition_id),
                KEY created_by (created_by)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('preservation_records') . " (
                edition_id bigint unsigned NOT NULL,
                object_id bigint unsigned NOT NULL,
                format_health varchar(24) NOT NULL,
                checksum_generation bigint unsigned NOT NULL DEFAULT 1,
                sha256 char(64) NOT NULL,
                encrypted_sha256 char(64) NOT NULL,
                derivative_status_json longtext NOT NULL,
                assessment_json longtext NOT NULL,
                last_verified_at datetime NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (edition_id),
                KEY object_id (object_id),
                KEY format_health (format_health)
            ) $charset;";
            $sql[] = 'CREATE TABLE ' . PLDR_Core::table('scan_fingerprints') . " (
                edition_id bigint unsigned NOT NULL,
                fingerprint_type varchar(24) NOT NULL,
                fingerprint_value varchar(190) NOT NULL,
                metadata_hash char(64) NOT NULL,
                version bigint unsigned NOT NULL DEFAULT 1,
                created_at datetime NOT NULL,
                updated_at datetime NOT NULL,
                PRIMARY KEY (edition_id,fingerprint_type),
                KEY fingerprint_value (fingerprint_value),
                KEY metadata_hash (metadata_hash)
            ) $charset;";
            foreach ($sql as $statement) dbDelta($statement);
            $health = self::verify_schema();
            if (!$health['ok']) {
                update_option('pldr_future_schema_error', array_merge($health, array('last_error'=>(string)$wpdb->last_error,'at'=>PLDR_Core::now())), false);
                PLDR_Core::audit('system', 0, 'future_24_schema_failed', $health);
                return false;
            }
            delete_option('pldr_future_schema_error');
            update_option('pldr_future_db_version', self::DB_VERSION, false);
            update_option('pldr_future_schema_revision', self::SCHEMA_REVISION, false);
            PLDR_Core::audit('system', 0, 'future_24_schema_upgraded', array('version' => self::DB_VERSION, 'revision' => self::SCHEMA_REVISION));
            return true;
        } finally {
            self::release_lock($lock);
        }
    }

    private static function verify_schema(): array {
        global $wpdb;
        $expected = array(
            'future_prefs'=>array('user_id','preference_key','preference_json','version','updated_at'),
            'shelves'=>array('id','shelf_key','user_id','name','shelf_type','sort_order','version','created_at','updated_at'),
            'shelf_items'=>array('id','shelf_id','edition_id','added_at'),
            'reading_events'=>array('id','event_id','user_id','edition_id','event_type','page_number','duration_seconds','context_json','created_at'),
            'session_handoffs'=>array('user_id','edition_id','page_number','zoom','layout_mode','anchor_json','device_hint','version','updated_at'),
            'ocr_corrections'=>array('id','edition_id','page_number','original_text','corrected_text','status','submitted_by','reviewed_by','review_note','version','created_at','updated_at'),
            'authority_cache'=>array('id','identifier_type','identifier_value','provider','result_json','provenance_json','expires_at','created_at','updated_at'),
            'a11y_audits'=>array('edition_id','score','status','findings_json','provider','verified_by','verified_at','updated_at'),
            'room_contexts'=>array('id','room_key','edition_id','created_by','page_number','anchor_json','provider_ref','status','created_at','updated_at'),
            'preservation_records'=>array('edition_id','object_id','format_health','checksum_generation','sha256','encrypted_sha256','derivative_status_json','assessment_json','last_verified_at','updated_at'),
            'scan_fingerprints'=>array('edition_id','fingerprint_type','fingerprint_value','metadata_hash','version','created_at','updated_at'),
        );
        $required_indexes = array(
            'future_prefs'=>array('PRIMARY'),
            'shelves'=>array('PRIMARY','shelf_key','user_id'),
            'shelf_items'=>array('PRIMARY','shelf_edition','edition_id'),
            'reading_events'=>array('PRIMARY','event_id','user_created','edition_id'),
            'session_handoffs'=>array('PRIMARY','updated_at'),
            'ocr_corrections'=>array('PRIMARY','edition_page','status'),
            'authority_cache'=>array('PRIMARY','authority_key','expires_at'),
            'a11y_audits'=>array('PRIMARY','status'),
            'room_contexts'=>array('PRIMARY','room_key','edition_id','created_by'),
            'preservation_records'=>array('PRIMARY','object_id','format_health'),
            'scan_fingerprints'=>array('PRIMARY','fingerprint_value','metadata_hash'),
        );
        $missing_tables=array();$missing_columns=array();$missing_indexes=array();
        foreach($expected as $suffix=>$columns){
            $table=PLDR_Core::table($suffix);
            if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table){$missing_tables[]=$suffix;continue;}
            $safe='`'.str_replace('`','',$table).'`';
            $found=$wpdb->get_col("SHOW COLUMNS FROM {$safe}");
            foreach($columns as $column)if(!in_array($column,$found,true))$missing_columns[]=$suffix.'.'.$column;
            $indexes=$wpdb->get_results("SHOW INDEX FROM {$safe}",ARRAY_A)?:array();$index_names=array_values(array_unique(array_map(static fn($row)=>(string)$row['Key_name'],$indexes)));
            foreach($required_indexes[$suffix]??array() as $index)if(!in_array($index,$index_names,true))$missing_indexes[]=$suffix.'.'.$index;
        }
        return array('ok'=>!$missing_tables&&!$missing_columns&&!$missing_indexes,'missing_tables'=>$missing_tables,'missing_columns'=>$missing_columns,'missing_indexes'=>$missing_indexes);
    }
}
