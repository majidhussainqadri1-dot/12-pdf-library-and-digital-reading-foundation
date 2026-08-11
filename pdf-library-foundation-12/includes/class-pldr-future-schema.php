<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Schema {
    public const DB_VERSION = '1.1.0';

    public static function maybe_upgrade(): void {
        if ((string) get_option('pldr_future_db_version', '') === self::DB_VERSION) return;
        self::upgrade();
    }

    public static function upgrade(): bool {
        global $wpdb;
        if (!add_option('pldr_future_migration_lock', (string) time(), '', false)) {
            $locked = absint(get_option('pldr_future_migration_lock', 0));
            if ($locked && (time() - $locked) < 300) return false;
            update_option('pldr_future_migration_lock', (string) time(), false);
        }
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
        $required = array('future_prefs','shelves','shelf_items','reading_events','session_handoffs','ocr_corrections','authority_cache','a11y_audits','room_contexts','preservation_records','scan_fingerprints');
        $missing = array();
        foreach ($required as $suffix) {
            $table = PLDR_Core::table($suffix);
            if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $table)) !== $table) $missing[] = $suffix;
        }
        if ($missing) {
            update_option('pldr_future_schema_error', array('missing'=>$missing,'last_error'=>(string)$wpdb->last_error,'at'=>PLDR_Core::now()), false);
            PLDR_Core::audit('system', 0, 'future_24_schema_failed', array('missing'=>$missing));
            delete_option('pldr_future_migration_lock');
            return false;
        }
        delete_option('pldr_future_schema_error');
        update_option('pldr_future_db_version', self::DB_VERSION, false);
        delete_option('pldr_future_migration_lock');
        PLDR_Core::audit('system', 0, 'future_24_schema_upgraded', array('version' => self::DB_VERSION));
        return true;
    }
}
