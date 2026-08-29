<?php

defined('ABSPATH') || exit;

final class PLDR_Schema {
    private const MIGRATION_LOCK_OPTION = 'pldr_migration_lock';
    private const MIGRATION_LOCK_TTL = 300;
    private const LEGACY_INTERACTION_BATCH = 500;

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

    private static function acquire_migration_lock(): ?string {
        global $wpdb;
        $token = self::uuid_lock_token();
        $payload = wp_json_encode(array('token'=>$token,'acquired_at'=>time()));
        if (!is_string($payload) || '' === $payload) return null;
        if (add_option(self::MIGRATION_LOCK_OPTION, $payload, '', false)) return $payload;

        $wpdb->last_error='';
        $row = $wpdb->get_var($wpdb->prepare(
            "SELECT option_value FROM {$wpdb->options} WHERE option_name=%s LIMIT 1",
            self::MIGRATION_LOCK_OPTION
        ));
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'migration_lock_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));return null;}
        if (!is_string($row) || '' === $row) return null;
        $decoded = json_decode($row, true);
        $acquired_at = is_array($decoded) ? absint($decoded['acquired_at'] ?? 0) : absint($row);
        if ($acquired_at && (time() - $acquired_at) < self::MIGRATION_LOCK_TTL) return null;

        $updated = $wpdb->query($wpdb->prepare(
            "UPDATE {$wpdb->options} SET option_value=%s WHERE option_name=%s AND option_value=%s",
            $payload, self::MIGRATION_LOCK_OPTION, $row
        ));
        if (1 !== $updated) { if(false===$updated||''!==(string)$wpdb->last_error)PLDR_Core::audit('system',0,'migration_lock_takeover_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500))); return null; }
        wp_cache_delete(self::MIGRATION_LOCK_OPTION, 'options');
        return $payload;
    }

    private static function release_migration_lock(string $payload): void {
        global $wpdb;
        $released=$wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name=%s AND option_value=%s",
            self::MIGRATION_LOCK_OPTION, $payload
        ));
        if(false===$released)PLDR_Core::audit('system',0,'migration_lock_release_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
        wp_cache_delete(self::MIGRATION_LOCK_OPTION, 'options');
    }

    private static function uuid_lock_token(): string {
        return function_exists('wp_generate_uuid4') ? wp_generate_uuid4() : PLDR_Core::uuid();
    }

    public static function upgrade(): bool {
        global $wpdb;
        $lock_payload = self::acquire_migration_lock();
        if (null === $lock_payload) return false;

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
        $required_schema = array(
            'documents'=>array('columns'=>array('id','public_id','status','access_mode','version','updated_at'),'indexes'=>array('PRIMARY','public_id','status_type')),
            'editions'=>array('columns'=>array('id','document_id','pages','sha256','object_id','status','version'),'indexes'=>array('PRIMARY','document_id','sha256')),
            'objects'=>array('columns'=>array('id','storage_name','sha256','encrypted_sha256','key_id','scan_status','object_status'),'indexes'=>array('PRIMARY','storage_name','object_status')),
            'access_policies'=>array('columns'=>array('id','document_id','audience','entitlement_key','download_allowed','offline_allowed','version'),'indexes'=>array('PRIMARY','document_version')),
            'reading_state'=>array('columns'=>array('user_id','edition_id','last_page','percent','edition_version'),'indexes'=>array('PRIMARY','edition_id')),
            'reading_items'=>array('columns'=>array('id','user_id','edition_id','item_type','page_number','version'),'indexes'=>array('PRIMARY','user_edition')),
            'rights_cases'=>array('columns'=>array('id','case_key','document_id','reporter_id','state','version'),'indexes'=>array('PRIMARY','case_key','document_state')),
            'derivatives'=>array('columns'=>array('id','edition_id','derivative_type','page_number','object_id','status'),'indexes'=>array('PRIMARY','edition_type_page')),
            'ocr_text'=>array('columns'=>array('edition_id','page_number','quality_score','text_content','normalized_text'),'indexes'=>array('PRIMARY')),
            'access_tokens'=>array('columns'=>array('id','token_hash','user_id','edition_id','object_id','operation','expires_at','revoked_at','used_count','max_uses'),'indexes'=>array('PRIMARY','token_hash','edition_operation')),
            'outbox'=>array('columns'=>array('id','event_id','event_name','status','attempts','available_at','sent_at'),'indexes'=>array('PRIMARY','event_id','dispatch')),
            'audit'=>array('columns'=>array('id','trace_id','object_type','object_id','action','actor_id','created_at'),'indexes'=>array('PRIMARY','object_lookup')),
            'book_packs'=>array('columns'=>array('id','pack_key','pack_version','manifest_sha256','status'),'indexes'=>array('PRIMARY','pack_version')),
            'idempotency'=>array('columns'=>array('actor_id','route','key_hash','response_json','status_code','expires_at'),'indexes'=>array('PRIMARY','expires_at')),
        );
        $missing_tables=array();$missing_columns=array();$missing_indexes=array();$read_errors=array();
        foreach($required_schema as $suffix=>$spec){
            $table=PLDR_Core::table($suffix);$wpdb->last_error='';
            $exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
            if(''!==(string)$wpdb->last_error){$read_errors[]=$suffix.'.table';continue;}
            if($exists!==$table){$missing_tables[]=$suffix;continue;}
            $safe='`'.str_replace('`','',$table).'`';$wpdb->last_error='';
            $columns=$wpdb->get_col("SHOW COLUMNS FROM {$safe}");
            if(''!==(string)$wpdb->last_error){$read_errors[]=$suffix.'.columns';continue;}
            $wpdb->last_error='';$indexes=$wpdb->get_col("SHOW INDEX FROM {$safe}",2);
            if(''!==(string)$wpdb->last_error){$read_errors[]=$suffix.'.indexes';continue;}
            $columns=is_array($columns)?$columns:array();$indexes=is_array($indexes)?$indexes:array();
            foreach($spec['columns'] as $column)if(!in_array($column,$columns,true))$missing_columns[]=$suffix.'.'.$column;
            foreach($spec['indexes'] as $index)if(!in_array($index,$indexes,true))$missing_indexes[]=$suffix.'.'.$index;
        }
        if($read_errors||$missing_tables||$missing_columns||$missing_indexes){
            update_option('pldr_schema_error',array(
                'read_errors'=>$read_errors,
                'missing_tables'=>$missing_tables,
                'missing_columns'=>$missing_columns,
                'missing_indexes'=>$missing_indexes,
                'last_error'=>(string)$wpdb->last_error,
                'at'=>PLDR_Core::now(),
            ),false);
            self::release_migration_lock($lock_payload);
            return false;
        }
        delete_option('pldr_schema_error');

        update_option('pldr_db_version', PLDR_DB_VERSION, false);
        update_option('pldr_contract_version', PLDR_CONTRACT_VERSION, false);
        self::release_migration_lock($lock_payload);

        if (self::legacy_present()) {
            $legacy_state=get_option('pldr_legacy_migration_state',null);
            if(!is_array($legacy_state)||empty($legacy_state['status'])){
                update_option('pldr_legacy_migration_state', array('status'=>'pending','last_legacy_id'=>0,'processed'=>0,'started_at'=>PLDR_Core::now()), false);
                $legacy_state=(array)get_option('pldr_legacy_migration_state',array());
            }
            if('complete'!==($legacy_state['status']??'')&&!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+20,'pldr_legacy_migration');
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
        $wpdb->last_error='';
        $count = (int) $wpdb->get_var("SELECT COUNT(ID) FROM {$wpdb->posts} WHERE post_type='spl_document'");
        if(''!==(string)$wpdb->last_error){
            update_option('pldr_legacy_migration_state',array('status'=>'error','phase'=>'legacy-presence-read','last_error'=>substr((string)$wpdb->last_error,0,500),'updated_at'=>PLDR_Core::now()),false);
            PLDR_Core::audit('migration',0,'legacy_presence_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
            if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');
            return false;
        }
        return $count > 0;
    }

    public static function migrate_legacy_batch(): void {
        $state=(array)get_option('pldr_legacy_migration_state',array());
        if('complete'===($state['status']??''))return;
        global $wpdb;
        $last_id=absint($state['last_legacy_id']??0);
        if(!isset($state['last_legacy_id'])&&!empty($state['offset'])){$state['checkpoint_restarted_from_legacy_offset']=absint($state['offset']);$state['last_legacy_id']=0;$last_id=0;}
        $wpdb->last_error='';
        $ids=$wpdb->get_col($wpdb->prepare("SELECT ID FROM {$wpdb->posts} WHERE post_type='spl_document' AND ID>%d ORDER BY ID ASC LIMIT 25",$last_id));
        if(''!==(string)$wpdb->last_error){
            $state['status']='retry';$state['last_error']='legacy-batch-read';$state['updated_at']=PLDR_Core::now();self::persist_legacy_migration_state($state);
            PLDR_Core::audit('migration',0,'legacy_batch_read_failed',array('last_legacy_id'=>$last_id,'db_error'=>substr((string)$wpdb->last_error,0,500)));
            if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');return;
        }
        $ids=is_array($ids)?$ids:array();
        if(!$ids){
            $state['status']='complete';$state['completed_at']=PLDR_Core::now();$state['updated_at']=PLDR_Core::now();unset($state['last_error'],$state['current_legacy_id']);
            if(false===$wpdb->query('START TRANSACTION')){PLDR_Core::audit('migration',0,'legacy_completion_transaction_failed',array());return;}
            if(!self::persist_legacy_migration_state($state)){$wpdb->query('ROLLBACK');wp_cache_delete('pldr_legacy_migration_state','options');PLDR_Core::audit('migration',0,'legacy_completion_state_failed',array());return;}
            $event=PLDR_Core::emit('PDFLibraryLegacyMigrationCompleted.v1','migration',0,array('source'=>'SPL-0.2.0','last_legacy_id'=>$last_id));
            if(is_wp_error($event)){$wpdb->query('ROLLBACK');wp_cache_delete('pldr_legacy_migration_state','options');PLDR_Core::audit('migration',0,'legacy_completion_event_atomic_rollback',array());return;}
            if(false===$wpdb->query('COMMIT')){$wpdb->query('ROLLBACK');wp_cache_delete('pldr_legacy_migration_state','options');PLDR_Core::audit('migration',0,'legacy_completion_commit_failed',array());return;}
            return;
        }
        foreach($ids as $legacy_id){
            $legacy_id=(int)$legacy_id;$result=self::migrate_one_legacy($legacy_id);
            if('error'===$result){$state['status']='retry';$state['last_error']='legacy-item-failed';$state['current_legacy_id']=$legacy_id;$state['updated_at']=PLDR_Core::now();self::persist_legacy_migration_state($state);if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');return;}
            if('pending'===$result){$state['status']='running';$state['current_legacy_id']=$legacy_id;$state['updated_at']=PLDR_Core::now();unset($state['last_error']);self::persist_legacy_migration_state($state);if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+5,'pldr_legacy_migration');return;}
            $last_id=$legacy_id;$state['last_legacy_id']=$last_id;$state['processed']=absint($state['processed']??0)+1;unset($state['current_legacy_id'],$state['last_error']);
        }
        $state['status']='running';$state['updated_at']=PLDR_Core::now();
        if(!self::persist_legacy_migration_state($state)){PLDR_Core::audit('migration',0,'legacy_checkpoint_persist_failed',array('last_legacy_id'=>$last_id));if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+60,'pldr_legacy_migration');return;}
        if(!wp_next_scheduled('pldr_legacy_migration'))wp_schedule_single_event(time()+5,'pldr_legacy_migration');
    }

    private static function persist_legacy_migration_state(array $state):bool {
        if(update_option('pldr_legacy_migration_state',$state,false))return true;
        wp_cache_delete('pldr_legacy_migration_state','options');$confirmed=get_option('pldr_legacy_migration_state',null);return is_array($confirmed)&&$confirmed==$state;
    }

    private static function migrate_one_legacy(int $legacy_id): string {
        global $wpdb;
        $wpdb->last_error='';
        $already = $wpdb->get_var($wpdb->prepare(
            'SELECT id FROM ' . PLDR_Core::table('documents') . ' WHERE search_text LIKE %s LIMIT 1',
            '% legacy:' . $legacy_id
        ));
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('migration',$legacy_id,'legacy_existing_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));return 'error';}
        if($already){
            $wpdb->last_error='';$edition=PLDR_Core::latest_edition((int)$already);
            if(''!==(string)$wpdb->last_error||!$edition){PLDR_Core::audit('migration',$legacy_id,'legacy_existing_edition_missing',array('document_id'=>(int)$already,'db_error'=>substr((string)$wpdb->last_error,0,500)));return 'error';}
            if(false===$wpdb->query('START TRANSACTION'))return 'error';
            try{
                $reconcile=self::migrate_legacy_interactions($legacy_id,(int)$edition['id'],(int)$already);
                if('error'===$reconcile)throw new RuntimeException('Legacy interaction reconciliation failed.');
                if(false===$wpdb->query('COMMIT'))throw new RuntimeException('Legacy reconciliation transaction could not be committed.');
                PLDR_Core::audit('migration',$legacy_id,'legacy_existing_reconciliation_'.('complete'===$reconcile?'complete':'pending'),array('document_id'=>(int)$already,'edition_id'=>(int)$edition['id']));
                return $reconcile;
            }catch(Throwable $e){$wpdb->query('ROLLBACK');wp_cache_delete(self::legacy_reconcile_option($legacy_id),'options');PLDR_Core::audit('migration',$legacy_id,'legacy_existing_reconciliation_failed',array('document_id'=>(int)$already,'error'=>substr($e->getMessage(),0,500)));return 'error';}
        }
        $post = get_post($legacy_id);
        if (!$post) {
            PLDR_Core::audit('migration',$legacy_id,'legacy_source_post_missing',array());
            return 'complete';
        }
        $storage = (string) get_post_meta($legacy_id, '_spl_storage_name', true);
        $sha = strtolower((string)get_post_meta($legacy_id,'_spl_sha256',true));
        $legacy_path = $storage ? PLDR_Storage::path($storage,'spl') : null;
        if (!preg_match('/^[a-f0-9]{64}$/',$sha) && is_string($legacy_path) && is_readable($legacy_path)) {
            $error=''; $computed=PLDR_Crypto::plaintext_sha256($legacy_path,$error);
            if(is_string($computed)) $sha=$computed;
            else PLDR_Core::audit('migration',$legacy_id,'legacy_checksum_failed',array('error'=>$error));
        }
        $checksum_verified=(bool)preg_match('/^[a-f0-9]{64}$/',$sha);
        $legacy_path_readable=is_string($legacy_path)&&is_readable($legacy_path);
        $legacy_object_ready=$checksum_verified&&''!==$storage&&$legacy_path_readable;
        if(!$checksum_verified){
            $sha=hash('sha256','legacy-unverified:'.$legacy_id.':'.$storage);
            PLDR_Core::audit('migration',$legacy_id,'legacy_checksum_unverified',array('storage_present'=>''!==$storage,'path_readable'=>$legacy_path_readable));
        }
        $legacy_publishable='publish'===$post->post_status&&$legacy_object_ready;

        $legacy_type='book'; $legacy_category='homeopathy-education';
        $type_terms=get_the_terms($legacy_id,'spl_document_type'); if(is_array($type_terms)&&!empty($type_terms[0]->slug)&&isset(PLDR_Core::DOCUMENT_TYPES[$type_terms[0]->slug])) $legacy_type=$type_terms[0]->slug;
        $cat_terms=get_the_terms($legacy_id,'spl_category'); if(is_array($cat_terms)&&!empty($cat_terms[0]->slug)&&isset(PLDR_Core::CATEGORIES[$cat_terms[0]->slug])) $legacy_category=$cat_terms[0]->slug;

        if(false===$wpdb->query('START TRANSACTION')){PLDR_Core::audit('migration',$legacy_id,'legacy_transaction_start_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));return 'error';}
        try {
            $object_stored=$wpdb->insert(PLDR_Core::table('objects'), array(
                'storage_name' => $storage ?: ('legacy-missing-' . $legacy_id),
                'storage_scope' => 'spl',
                'original_name' => (string) get_post_meta($legacy_id, '_spl_original_name', true),
                'mime_type' => 'application/pdf',
                'byte_size' => absint(get_post_meta($legacy_id, '_spl_file_size', true)),
                'sha256' => $sha,
                'encrypted_sha256' => '',
                'key_id' => (string) get_post_meta($legacy_id, '_spl_crypto_key_id', true),
                'format_version' => (string) get_post_meta($legacy_id, '_spl_crypto_format', true) ?: 'SPL2',
                'scan_status' => $legacy_object_ready ? 'legacy-imported' : 'legacy-unverified',
                'object_status' => $legacy_object_ready ? 'available' : 'quarantined',
                'created_at' => PLDR_Core::now(),
            ));
            if(false===$object_stored||($object_id=(int)$wpdb->insert_id)<1)throw new RuntimeException('Legacy object record could not be stored.');
            $title = (string) $post->post_title;
            $public_id = PLDR_Core::uuid();
            $search = PLDR_Core::normalize_search($title . ' ' . get_post_meta($legacy_id, '_spl_author_name', true)) . ' legacy:' . $legacy_id;
            $document_stored=$wpdb->insert(PLDR_Core::table('documents'), array(
                'public_id' => $public_id,
                'title' => $title,
                'slug' => sanitize_title($post->post_name ?: $title),
                'document_type' => $legacy_type,
                'category' => $legacy_category,
                'language' => (string) get_post_meta($legacy_id, '_spl_language', true) ?: 'en-US',
                'subjects_json' => '[]',
                'collections_json' => '[]',
                'search_text' => $search,
                'status' => $legacy_publishable ? 'published' : 'rights_review',
                'access_mode' => 'public',
                'created_by' => (int) $post->post_author,
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            if(false===$document_stored||($document_id=(int)$wpdb->insert_id)<1)throw new RuntimeException('Legacy document record could not be stored.');
            $edition_stored=$wpdb->insert(PLDR_Core::table('editions'), array(
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
                'status' => $legacy_publishable ? 'published' : 'rights_review',
                'version' => 1,
                'created_at' => PLDR_Core::now(),
                'updated_at' => PLDR_Core::now(),
            ));
            if(false===$edition_stored||($edition_id=(int)$wpdb->insert_id)<1)throw new RuntimeException('Legacy edition record could not be stored.');
            $download = '1' === (string) get_post_meta($legacy_id, '_spl_download_allowed', true) ? 1 : 0;
            $policy_stored=$wpdb->insert(PLDR_Core::table('access_policies'), array(
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
            if(false===$policy_stored)throw new RuntimeException('Legacy access policy could not be stored.');
            $reconcile=self::migrate_legacy_interactions($legacy_id,$edition_id,$document_id);
            if('error'===$reconcile)throw new RuntimeException('Legacy private interaction data could not be reconciled.');
            $migration_event=PLDR_Core::emit('PDFLegacyInteractionMigrationRequested.v1','document',$document_id,array('legacy_document_id'=>$legacy_id,'document_public_id'=>$public_id,'reconciliation_status'=>$reconcile));
            if(is_wp_error($migration_event))throw new RuntimeException('Legacy migration reliable event could not be persisted atomically.');
            if(false===$wpdb->query('COMMIT'))throw new RuntimeException('Legacy migration transaction could not be committed.');
            PLDR_Core::audit('document',$document_id,'legacy_import_'.('complete'===$reconcile?'complete':'pending'),array('legacy_id'=>$legacy_id,'edition_id'=>$edition_id));
            return $reconcile;
        } catch (Throwable $e) {
            $wpdb->query('ROLLBACK');
            PLDR_Core::audit('migration', $legacy_id, 'legacy_import_failed', array('error' => substr($e->getMessage(),0,500)));
            wp_cache_delete(self::legacy_reconcile_option($legacy_id),'options');
            return 'error';
        }
    }

    private static function legacy_reconcile_option(int $legacy_id):string { return 'pldr_legacy_reconcile_'.$legacy_id; }

    private static function persist_legacy_reconcile_state(int $legacy_id,array $state):bool {
        $option=self::legacy_reconcile_option($legacy_id);if(update_option($option,$state,false))return true;wp_cache_delete($option,'options');$confirmed=get_option($option,null);return is_array($confirmed)&&$confirmed==$state;
    }

    private static function migrate_legacy_interactions(int $legacy_id,int $edition_id,int $document_id):string {
        $state=(array)get_option(self::legacy_reconcile_option($legacy_id),array('user_cursor'=>0,'report_cursor'=>0,'status'=>'pending'));
        $user=self::migrate_legacy_user_data($legacy_id,$edition_id,absint($state['user_cursor']??0));if('error'===($user['status']??''))return 'error';$state['user_cursor']=absint($user['cursor']??0);
        if('pending'===($user['status']??'')){$state['status']='pending';$state['phase']='user-data';$state['updated_at']=PLDR_Core::now();return self::persist_legacy_reconcile_state($legacy_id,$state)?'pending':'error';}
        $reports=self::migrate_legacy_reports($legacy_id,$document_id,absint($state['report_cursor']??0));if('error'===($reports['status']??''))return 'error';$state['report_cursor']=absint($reports['cursor']??0);
        if('pending'===($reports['status']??'')){$state['status']='pending';$state['phase']='reports';$state['updated_at']=PLDR_Core::now();return self::persist_legacy_reconcile_state($legacy_id,$state)?'pending':'error';}
        $state['status']='complete';$state['phase']='complete';$state['completed_at']=PLDR_Core::now();$state['updated_at']=PLDR_Core::now();return self::persist_legacy_reconcile_state($legacy_id,$state)?'complete':'error';
    }

    private static function migrate_legacy_user_data(int $legacy_id,int $edition_id,int $after_id=0):array {
        global $wpdb;$table=$wpdb->prefix.'spl_user_data';$after_id=max(0,$after_id);$batch=self::LEGACY_INTERACTION_BATCH;
        $wpdb->last_error='';$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
        if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);if($exists!==$table)return array('status'=>'complete','cursor'=>$after_id);
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE document_id=%d AND id>%d ORDER BY id ASC LIMIT %d",$legacy_id,$after_id,$batch+1),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);$rows=is_array($rows)?$rows:array();$has_more=count($rows)>$batch;if($has_more)$rows=array_slice($rows,0,$batch);$cursor=$after_id;
        foreach($rows as $r){$legacy_row=absint($r['id']??0);if($legacy_row<1)continue;$cursor=max($cursor,$legacy_row);$uid=absint($r['user_id']??0);if(!$uid)continue;$type=(string)($r['data_type']??'');$page=max(1,absint($r['page_number']??$r['progress']??1));
            if('progress'===$type){$stored=$wpdb->replace(PLDR_Core::table('reading_state'),array('user_id'=>$uid,'edition_id'=>$edition_id,'last_page'=>$page,'percent'=>0,'edition_version'=>1,'updated_at'=>(string)($r['updated_at']??PLDR_Core::now())));if(false===$stored)return array('status'=>'error','cursor'=>$after_id);}
            elseif(in_array($type,array('bookmark','note'),true)){$tag='legacy-spl-user-data-'.$legacy_row;$match='%'.$wpdb->esc_like('"'.$tag.'"').'%';$wpdb->last_error='';$existing=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('reading_items').' WHERE user_id=%d AND edition_id=%d AND tags_json LIKE %s LIMIT 1',$uid,$edition_id,$match));if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);if($existing)continue;$note=sanitize_textarea_field((string)($r['note']??''));$note=function_exists('mb_substr')?mb_substr($note,0,4000,'UTF-8'):substr($note,0,4000);$stored=$wpdb->insert(PLDR_Core::table('reading_items'),array('user_id'=>$uid,'edition_id'=>$edition_id,'item_type'=>$type,'page_number'=>$page,'anchor_text'=>'','note_text'=>$note,'tags_json'=>wp_json_encode(array($tag)),'version'=>1,'created_at'=>(string)($r['updated_at']??PLDR_Core::now()),'updated_at'=>(string)($r['updated_at']??PLDR_Core::now())));if(false===$stored)return array('status'=>'error','cursor'=>$after_id);}
        }
        return array('status'=>$has_more?'pending':'complete','cursor'=>$cursor);
    }

    private static function migrate_legacy_reports(int $legacy_id,int $document_id,int $after_id=0):array {
        global $wpdb;$table=$wpdb->prefix.'spl_reports';$after_id=max(0,$after_id);$batch=self::LEGACY_INTERACTION_BATCH;
        $wpdb->last_error='';$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
        if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);if($exists!==$table)return array('status'=>'complete','cursor'=>$after_id);
        $wpdb->last_error='';$rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE document_id=%d AND id>%d ORDER BY id ASC LIMIT %d",$legacy_id,$after_id,$batch+1),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);$rows=is_array($rows)?$rows:array();$has_more=count($rows)>$batch;if($has_more)$rows=array_slice($rows,0,$batch);$cursor=$after_id;
        foreach($rows as $r){$legacy_report_id=absint($r['id']??0);if($legacy_report_id<1)continue;$cursor=max($cursor,$legacy_report_id);$match='%'.$wpdb->esc_like('"legacy_report_id":'.$legacy_report_id.',').'%';$wpdb->last_error='';$existing=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('rights_cases').' WHERE document_id=%d AND evidence_json LIKE %s LIMIT 1',$document_id,$match));if(''!==(string)$wpdb->last_error)return array('status'=>'error','cursor'=>$after_id);if($existing)continue;$reason=sanitize_key((string)($r['reason']??'other'));$state=in_array((string)($r['status']??''),array('resolved','dismissed'),true)?'closed':'reported';$details=sanitize_textarea_field((string)($r['details']??''));$details=function_exists('mb_substr')?mb_substr($details,0,12000,'UTF-8'):substr($details,0,12000);$evidence=wp_json_encode(array('legacy_report_id'=>$legacy_report_id,'details'=>$details));if(!is_string($evidence))return array('status'=>'error','cursor'=>$after_id);$stored=$wpdb->insert(PLDR_Core::table('rights_cases'),array('case_key'=>PLDR_Core::uuid(),'document_id'=>$document_id,'reporter_id'=>absint($r['user_id']??0),'parent_case_id'=>null,'state'=>$state,'reason'=>$reason?:'other','evidence_json'=>$evidence,'decision_note'=>'','assigned_to'=>0,'version'=>1,'created_at'=>(string)($r['created_at']??PLDR_Core::now()),'updated_at'=>(string)($r['updated_at']??PLDR_Core::now()),'closed_at'=>'closed'===$state?(string)($r['updated_at']??PLDR_Core::now()):null));if(false===$stored)return array('status'=>'error','cursor'=>$after_id);}
        return array('status'=>$has_more?'pending':'complete','cursor'=>$cursor);
    }


}
