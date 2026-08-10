<?php

defined('ABSPATH') || exit;

final class PLDR_Schema {
    public static function activate(): void {
        self::install_caps();
        self::upgrade();
        self::schedule();
        PLDR_Book_Packs::scan_bundled_manifests();
        update_option('pldr_rewrite_flush_needed', '1', false);
    }

    public static function deactivate(): void {
        foreach (array('pldr_dispatch_outbox','pldr_cleanup_tokens','pldr_integrity_sample','pldr_rights_expiry','pldr_generate_derivatives','pldr_legacy_migration') as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        flush_rewrite_rules(false);
    }

    public static function install_caps(): void {
        $admin = get_role('administrator');
        if (!$admin) {
            return;
        }
        foreach (array('manage_pdf_library', 'pldr_publish_documents', 'pldr_review_rights', 'pldr_repair_library', 'pldr_read_restricted') as $cap) {
            $admin->add_cap($cap);
        }
    }

    public static function maybe_upgrade(): void {
        if (PLDR_DB_VERSION !== (string) get_option('pldr_db_version', '')) {
            self::upgrade();
        }
    }

    public static function upgrade(): bool {
        global $wpdb;
        if (!add_option('pldr_migration_lock', (string) time(), '', false)) {
            $lock = absint(get_option('pldr_migration_lock', 0));
            if ($lock && (time() - $lock) < 300) {
                return false;
            }
            update_option('pldr_migration_lock', (string) time(), false);
        }

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        $charset = $wpdb->get_charset_collate();

        $sql = array();
        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('documents') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            public_id char(36) NOT NULL,
            title text NOT NULL,
            slug varchar(200) NOT NULL,
            document_type varchar(60) NOT NULL,
            category varchar(80) NOT NULL,
            language varchar(20) NOT NULL,
            subjects_json longtext NOT NULL,
            collections_json longtext NOT NULL,
            search_text longtext NOT NULL,
            status varchar(24) NOT NULL,
            access_mode varchar(32) NOT NULL,
            created_by bigint unsigned NOT NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY public_id (public_id),
            KEY slug (slug),
            KEY status_type (status,document_type),
            KEY category (category),
            KEY created_by (created_by)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('editions') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint unsigned NOT NULL,
            edition_label varchar(120) NOT NULL,
            isbn varchar(32) NOT NULL,
            publication_year smallint unsigned NOT NULL DEFAULT 0,
            pages int unsigned NOT NULL DEFAULT 0,
            language varchar(20) NOT NULL,
            author_name text NOT NULL,
            translator text NOT NULL,
            publisher text NOT NULL,
            source_name text NOT NULL,
            license_code varchar(80) NOT NULL,
            rights_basis varchar(80) NOT NULL,
            territory varchar(180) NOT NULL,
            rights_expires_at datetime NULL,
            takedown_contact text NOT NULL,
            sha256 char(64) NOT NULL,
            object_id bigint unsigned NOT NULL,
            status varchar(24) NOT NULL DEFAULT 'rights_review',
            supersedes_edition_id bigint unsigned NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY document_id (document_id),
            KEY sha256 (sha256),
            KEY status (status),
            KEY isbn (isbn),
            KEY rights_expires_at (rights_expires_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('objects') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            storage_name varchar(190) NOT NULL,
            storage_scope varchar(20) NOT NULL DEFAULT 'pldr',
            original_name text NOT NULL,
            mime_type varchar(100) NOT NULL,
            byte_size bigint unsigned NOT NULL,
            sha256 char(64) NOT NULL,
            encrypted_sha256 char(64) NOT NULL,
            key_id varchar(64) NOT NULL,
            format_version varchar(20) NOT NULL,
            scan_status varchar(30) NOT NULL,
            object_status varchar(30) NOT NULL,
            created_at datetime NOT NULL,
            verified_at datetime NULL,
            deleted_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY storage_name (storage_name),
            KEY sha256 (sha256),
            KEY object_status (object_status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('access_policies') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            document_id bigint unsigned NOT NULL,
            audience varchar(32) NOT NULL,
            entitlement_key varchar(120) NOT NULL,
            download_allowed tinyint(1) NOT NULL DEFAULT 0,
            print_allowed tinyint(1) NOT NULL DEFAULT 0,
            offline_allowed tinyint(1) NOT NULL DEFAULT 0,
            embargo_until datetime NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY document_version (document_id,version),
            KEY audience (audience),
            KEY embargo_until (embargo_until)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('reading_state') . " (
            user_id bigint unsigned NOT NULL,
            edition_id bigint unsigned NOT NULL,
            last_page int unsigned NOT NULL DEFAULT 1,
            percent decimal(5,2) NOT NULL DEFAULT 0,
            edition_version bigint unsigned NOT NULL DEFAULT 1,
            updated_at datetime NOT NULL,
            PRIMARY KEY (user_id,edition_id),
            KEY edition_id (edition_id),
            KEY updated_at (updated_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('reading_items') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            user_id bigint unsigned NOT NULL,
            edition_id bigint unsigned NOT NULL,
            item_type varchar(20) NOT NULL,
            page_number int unsigned NOT NULL DEFAULT 0,
            anchor_text varchar(500) NOT NULL,
            note_text longtext NOT NULL,
            tags_json longtext NOT NULL,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY user_edition (user_id,edition_id),
            KEY item_type (item_type),
            KEY updated_at (updated_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('rights_cases') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            case_key char(36) NOT NULL,
            document_id bigint unsigned NOT NULL,
            reporter_id bigint unsigned NOT NULL,
            parent_case_id bigint unsigned NULL,
            state varchar(24) NOT NULL,
            reason varchar(60) NOT NULL,
            evidence_json longtext NOT NULL,
            decision_note longtext NOT NULL,
            assigned_to bigint unsigned NOT NULL DEFAULT 0,
            version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            closed_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY case_key (case_key),
            KEY document_state (document_id,state),
            KEY reporter_id (reporter_id),
            KEY assigned_to (assigned_to)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('derivatives') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            edition_id bigint unsigned NOT NULL,
            derivative_type varchar(24) NOT NULL,
            page_number int unsigned NOT NULL DEFAULT 0,
            object_id bigint unsigned NOT NULL DEFAULT 0,
            language varchar(20) NOT NULL,
            quality_score decimal(5,2) NOT NULL DEFAULT 0,
            lawful_basis varchar(80) NOT NULL,
            status varchar(24) NOT NULL,
            source_version bigint unsigned NOT NULL DEFAULT 1,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY edition_type_page (edition_id,derivative_type,page_number),
            KEY object_id (object_id),
            KEY status (status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('ocr_text') . " (
            edition_id bigint unsigned NOT NULL,
            page_number int unsigned NOT NULL,
            language varchar(20) NOT NULL,
            quality_score decimal(5,2) NOT NULL DEFAULT 0,
            text_content longtext NOT NULL,
            normalized_text longtext NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (edition_id,page_number),
            KEY language (language)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('access_tokens') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            token_hash char(64) NOT NULL,
            user_id bigint unsigned NOT NULL DEFAULT 0,
            edition_id bigint unsigned NOT NULL,
            object_id bigint unsigned NOT NULL,
            operation varchar(20) NOT NULL,
            audience_hash char(64) NOT NULL,
            expires_at datetime NOT NULL,
            revoked_at datetime NULL,
            used_count int unsigned NOT NULL DEFAULT 0,
            max_uses int unsigned NOT NULL DEFAULT 500,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY token_hash (token_hash),
            KEY edition_operation (edition_id,operation),
            KEY expires_at (expires_at),
            KEY user_id (user_id)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('outbox') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            event_id char(36) NOT NULL,
            event_name varchar(120) NOT NULL,
            aggregate_type varchar(40) NOT NULL,
            aggregate_id bigint unsigned NOT NULL,
            payload_json longtext NOT NULL,
            status varchar(20) NOT NULL,
            attempts smallint unsigned NOT NULL DEFAULT 0,
            available_at datetime NOT NULL,
            last_error text NOT NULL,
            created_at datetime NOT NULL,
            sent_at datetime NULL,
            PRIMARY KEY (id),
            UNIQUE KEY event_id (event_id),
            KEY dispatch (status,available_at),
            KEY aggregate (aggregate_type,aggregate_id)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('audit') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            trace_id varchar(32) NOT NULL,
            object_type varchar(40) NOT NULL,
            object_id bigint unsigned NOT NULL,
            action varchar(80) NOT NULL,
            actor_id bigint unsigned NOT NULL DEFAULT 0,
            context_json longtext NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY object_lookup (object_type,object_id),
            KEY actor_id (actor_id),
            KEY created_at (created_at)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('book_packs') . " (
            id bigint unsigned NOT NULL AUTO_INCREMENT,
            pack_key varchar(120) NOT NULL,
            pack_version varchar(40) NOT NULL,
            title text NOT NULL,
            author text NOT NULL,
            translator text NOT NULL,
            rights_basis varchar(80) NOT NULL,
            manifest_sha256 char(64) NOT NULL,
            metadata_json longtext NOT NULL,
            status varchar(24) NOT NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY pack_version (pack_key,pack_version),
            KEY status (status)
        ) $charset;";

        $sql[] = 'CREATE TABLE ' . PLDR_Core::table('idempotency') . " (
            actor_id bigint unsigned NOT NULL DEFAULT 0,
            route varchar(120) NOT NULL,
            key_hash char(64) NOT NULL,
            response_json longtext NOT NULL,
            status_code smallint unsigned NOT NULL DEFAULT 200,
            expires_at datetime NOT NULL,
            created_at datetime NOT NULL,
            PRIMARY KEY (actor_id,route,key_hash),
            KEY expires_at (expires_at)
        ) $charset;";

        foreach ($sql as $statement) dbDelta($statement);
        $required_tables=array('documents','editions','objects','access_policies','reading_state','reading_items','rights_cases','derivatives','ocr_text','access_tokens','outbox','audit','book_packs','idempotency');
        $missing=array();
        foreach($required_tables as $suffix){$table=PLDR_Core::table($suffix);if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)$missing[]=$suffix;}
        if($missing){update_option('pldr_schema_error',array('missing'=>$missing,'last_error'=>(string)$wpdb->last_error,'at'=>PLDR_Core::now()),false);delete_option('pldr_migration_lock');return false;}
        delete_option('pldr_schema_error');

        update_option('pldr_db_version', PLDR_DB_VERSION, false);
        update_option('pldr_contract_version', PLDR_CONTRACT_VERSION, false);
        delete_option('pldr_migration_lock');

        if (self::legacy_present()) {
            update_option('pldr_legacy_migration_state', array('status' => 'pending', 'offset' => 0, 'started_at' => PLDR_Core::now()), false);
            if (!wp_next_scheduled('pldr_legacy_migration')) {
                wp_schedule_single_event(time() + 20, 'pldr_legacy_migration');
            }
        }

        return true;
    }

    public static function schedule(): void {
        add_filter('cron_schedules', array(__CLASS__, 'cron_schedules'));
        $jobs = array('pldr_dispatch_outbox'=>'pldr_five_minutes','pldr_cleanup_tokens'=>'hourly','pldr_integrity_sample'=>'daily','pldr_rights_expiry'=>'hourly');
        foreach ($jobs as $hook=>$recurrence) if(!wp_next_scheduled($hook)) wp_schedule_event(time()+60,$recurrence,$hook);
    }

    public static function cron_schedules(array $schedules): array {
        if (!isset($schedules['pldr_five_minutes'])) $schedules['pldr_five_minutes']=array('interval'=>300,'display'=>'Every five minutes (File 12)');
        return $schedules;
    }

    private static function legacy_present(): bool {
        global $wpdb;
        $count = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type='spl_document'");
        return $count > 0;
    }

    public static function migrate_legacy_batch(): void {
        $state = (array) get_option('pldr_legacy_migration_state', array());
        if ('complete' === ($state['status'] ?? '')) {
            return;
        }
        global $wpdb;
        $offset = absint($state['offset'] ?? 0);
        $ids = $wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='spl_document' ORDER BY ID ASC LIMIT 25 OFFSET %d", $offset));
        if (!$ids) {
            $state['status'] = 'complete';
            $state['completed_at'] = PLDR_Core::now();
            update_option('pldr_legacy_migration_state', $state, false);
            PLDR_Core::emit('PDFLibraryLegacyMigrationCompleted.v1', 'migration', 0, array('source' => 'SPL-0.2.0'));
            return;
        }

        foreach ($ids as $legacy_id) {
            self::migrate_one_legacy((int) $legacy_id);
            $offset++;
        }
        $state['status'] = 'running';
        $state['offset'] = $offset;
        $state['updated_at'] = PLDR_Core::now();
        update_option('pldr_legacy_migration_state', $state, false);
        wp_schedule_single_event(time() + 5, 'pldr_legacy_migration');
    }

    private static function migrate_one_legacy(int $legacy_id): void {
        global $wpdb;
        $already = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . PLDR_Core::table('documents') . ' WHERE search_text LIKE %s LIMIT 1',
            '%legacy:' . $legacy_id . '%'
        ));
        if ($already) {
            return;
        }
        $post = get_post($legacy_id);
        if (!$post) {
            return;
        }
        $storage = (string) get_post_meta($legacy_id, '_spl_storage_name', true);
        $sha = strtolower((string)get_post_meta($legacy_id,'_spl_sha256',true));
        $legacy_path = $storage ? PLDR_Storage::path($storage,'spl') : null;
        if (!preg_match('/^[a-f0-9]{64}$/',$sha) && is_string($legacy_path) && is_readable($legacy_path)) {
            $error=''; $computed=PLDR_Crypto::plaintext_sha256($legacy_path,$error);
            if(is_string($computed)) $sha=$computed;
            else PLDR_Core::audit('migration',$legacy_id,'legacy_checksum_failed',array('error'=>$error));
        }
        if (!preg_match('/^[a-f0-9]{64}$/',$sha)) $sha=hash('sha256','legacy-unverified:'.$legacy_id.':'.$storage);

        $legacy_type='book'; $legacy_category='homeopathy-education';
        $type_terms=get_the_terms($legacy_id,'spl_document_type'); if(is_array($type_terms)&&!empty($type_terms[0]->slug)&&isset(PLDR_Core::DOCUMENT_TYPES[$type_terms[0]->slug])) $legacy_type=$type_terms[0]->slug;
        $cat_terms=get_the_terms($legacy_id,'spl_category'); if(is_array($cat_terms)&&!empty($cat_terms[0]->slug)&&isset(PLDR_Core::CATEGORIES[$cat_terms[0]->slug])) $legacy_category=$cat_terms[0]->slug;

        $wpdb->query('START TRANSACTION');
        try {
            $wpdb->insert(PLDR_Core::table('objects'), array(
                'storage_name' => $storage ?: ('legacy-missing-' . $legacy_id),
                'storage_scope' => 'spl',
                'original_name' => (string) get_post_meta($legacy_id, '_spl_original_name', true),
                'mime_type' => 'application/pdf',
                'byte_size' => absint(get_post_meta($legacy_id, '_spl_file_size', true)),
                'sha256' => $sha,
                'encrypted_sha256' => '',
                'key_id' => (string) get_post_meta($legacy_id, '_spl_crypto_key_id', true),
                'format_version' => (string) get_post_meta($legacy_id, '_spl_crypto_format', true) ?: 'SPL2',
                'scan_status' => 'legacy-imported',
                'object_status' => $storage ? 'available' : 'quarantined',
                'created_at' => PLDR_Core::now(),
            ));
            $object_id = (int) $wpdb->insert_id;
            $title = (string) $post->post_title;
            $public_id = PLDR_Core::uuid();
            $search = PLDR_Core::normalize_search($title . ' ' . get_post_meta($legacy_id, '_spl_author_name', true)) . ' legacy:' . $legacy_id;
            $wpdb->insert(PLDR_Core::table('documents'), array(
                'public_id' => $public_id,
                'title' => $title,
                'slug' => sanitize_title($post->post_name ?: $title),
                'document_type' => $legacy_type,
                'category' => $legacy_category,
                'language' => (string) get_post_meta($legacy_id, '_spl_language', true) ?: 'en-US',
                'subjects_json' => '[]',
                'collections_json' => '[]',
                'search_text' => $search,
                'status' => 'publish' === $post->post_status ? 'published' : 'rights_review',
                'access_mode' => 'public',
                'created_by' => (int) $post->post_author,
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            $document_id = (int) $wpdb->insert_id;
            $wpdb->insert(PLDR_Core::table('editions'), array(
                'document_id' => $document_id,
                'edition_label' => (string) get_post_meta($legacy_id, '_spl_edition', true),
                'isbn' => (string) get_post_meta($legacy_id, '_spl_isbn', true),
                'publication_year' => absint(get_post_meta($legacy_id, '_spl_publication_year', true)),
                'pages' => absint(get_post_meta($legacy_id, '_spl_pages', true)),
                'language' => (string) get_post_meta($legacy_id, '_spl_language', true) ?: 'en-US',
                'author_name' => (string) get_post_meta($legacy_id, '_spl_author_name', true),
                'translator' => '',
                'publisher' => (string) get_post_meta($legacy_id, '_spl_publisher', true),
                'source_name' => 'Legacy File 12 0.2.0',
                'license_code' => (string) get_post_meta($legacy_id, '_spl_copyright_status', true),
                'rights_basis' => (string) get_post_meta($legacy_id, '_spl_copyright_status', true),
                'territory' => '',
                'rights_expires_at' => null,
                'takedown_contact' => '',
                'sha256' => $sha,
                'object_id' => $object_id,
                'status' => 'publish' === $post->post_status ? 'published' : 'rights_review',
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            $download = '1' === (string) get_post_meta($legacy_id, '_spl_download_allowed', true) ? 1 : 0;
            $edition_id = (int)$wpdb->insert_id;
            $wpdb->insert(PLDR_Core::table('access_policies'), array(
                'document_id' => $document_id,
                'audience' => 'public',
                'entitlement_key' => '',
                'download_allowed' => $download,
                'print_allowed' => $download,
                'offline_allowed' => $download,
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            $wpdb->query('COMMIT');
            self::migrate_legacy_user_data($legacy_id,$edition_id);
            self::migrate_legacy_reports($legacy_id,$document_id);
            PLDR_Core::emit('PDFLegacyInteractionMigrationRequested.v1','document',$document_id,array('legacy_document_id'=>$legacy_id,'document_public_id'=>$public_id));
            PLDR_Core::audit('document', $document_id, 'legacy_imported', array('legacy_id' => $legacy_id,'edition_id'=>$edition_id));
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            PLDR_Core::audit('migration', $legacy_id, 'legacy_import_failed', array('error' => $e->getMessage()));
        }
    }

    private static function migrate_legacy_user_data(int $legacy_id,int $edition_id): void {
        global $wpdb; $table=$wpdb->prefix.'spl_user_data';
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)return;
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE document_id=%d ORDER BY id ASC",$legacy_id),ARRAY_A);
        foreach($rows as $r){$uid=absint($r['user_id']??0);if(!$uid)continue;$type=(string)($r['data_type']??'');$page=max(1,absint($r['page_number']??$r['progress']??1));
            if('progress'===$type){$wpdb->replace(PLDR_Core::table('reading_state'),array('user_id'=>$uid,'edition_id'=>$edition_id,'last_page'=>$page,'percent'=>0,'edition_version'=>1,'updated_at'=>(string)($r['updated_at']??PLDR_Core::now())));}
            elseif(in_array($type,array('bookmark','note'),true)){$wpdb->insert(PLDR_Core::table('reading_items'),array('user_id'=>$uid,'edition_id'=>$edition_id,'item_type'=>$type,'page_number'=>$page,'anchor_text'=>'','note_text'=>(string)($r['note']??''),'tags_json'=>'[]','version'=>1,'created_at'=>(string)($r['updated_at']??PLDR_Core::now()),'updated_at'=>(string)($r['updated_at']??PLDR_Core::now())));}
        }
    }

    private static function migrate_legacy_reports(int $legacy_id,int $document_id): void {
        global $wpdb; $table=$wpdb->prefix.'spl_reports';
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table))!==$table)return;
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE document_id=%d ORDER BY id ASC",$legacy_id),ARRAY_A);
        foreach($rows as $r){$reason=sanitize_key((string)($r['reason']??'other'));$state=in_array((string)($r['status']??''),array('resolved','dismissed'),true)?'closed':'reported';$wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>PLDR_Core::uuid(),'document_id'=>$document_id,'reporter_id'=>absint($r['user_id']??0),'parent_case_id'=>null,'state'=>$state,'reason'=>$reason?:'other','evidence_json'=>wp_json_encode(array('legacy_report_id'=>absint($r['id']??0),'details'=>sanitize_textarea_field((string)($r['details']??'')))),'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>(string)($r['created_at']??PLDR_Core::now()),'updated_at'=>(string)($r['updated_at']??PLDR_Core::now()),'closed_at'=>'closed'===$state?(string)($r['updated_at']??PLDR_Core::now()):null));}
    }

}
