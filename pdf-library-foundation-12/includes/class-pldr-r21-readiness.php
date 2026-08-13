<?php

defined('ABSPATH') || exit;

/**
 * R21 runtime readiness gate.
 *
 * Repository version markers are not sufficient runtime evidence. This gate
 * verifies the physical core schema (including required columns/indexes),
 * attempts the canonical schema repair once when drift is detected, requires
 * the forward schema-correction revision, and keeps public/domain workflows
 * fail-closed while the database is not ready.
 */
final class PLDR_R21_Readiness {
    private const HEALTH_TTL = 300;

    public static function hooks(): void {
        add_action('admin_notices', array(__CLASS__, 'notice'));
    }

    public static function notice(): void {
        if (!current_user_can('manage_pdf_library')) return;
        $error = get_option('pldr_core_readiness_error');
        if (!$error) return;
        echo '<div class="notice notice-error"><p><strong>File 12 runtime is fail-closed until its core schema is reconciled.</strong> ' . esc_html(wp_json_encode($error)) . '</p></div>';
    }

    public static function core_ready(bool $repair = true): bool {
        $cached = get_transient('pldr_r21_core_schema_ready');
        $marker_current = PLDR_DB_VERSION === (string) get_option('pldr_db_version', '');
        $corrections_current = !class_exists('PLDR_Schema_Corrections') || PLDR_Schema_Corrections::is_current();
        if ('1' === $cached && $marker_current && $corrections_current && !get_option('pldr_schema_error')) {
            delete_option('pldr_core_readiness_error');
            return true;
        }

        $health = self::inspect_core_schema();
        if ($health['ok'] && $marker_current) {
            if (class_exists('PLDR_Schema_Corrections') && !PLDR_Schema_Corrections::is_current()) {
                PLDR_Schema_Corrections::maybe_apply();
            }
            if (!class_exists('PLDR_Schema_Corrections') || PLDR_Schema_Corrections::is_current()) {
                delete_option('pldr_schema_error');
                delete_option('pldr_core_readiness_error');
                set_transient('pldr_r21_core_schema_ready', '1', self::HEALTH_TTL);
                return true;
            }
            $health['correction_revision'] = 'not-current';
        }

        update_option('pldr_schema_error', array_merge($health, array('reason'=>'runtime_schema_drift','at'=>PLDR_Core::now())), false);
        delete_transient('pldr_r21_core_schema_ready');
        if ($repair) {
            $repaired = PLDR_Schema::upgrade();
            if ($repaired) {
                if (class_exists('PLDR_Schema_Corrections')) PLDR_Schema_Corrections::maybe_apply();
                return self::core_ready(false);
            }
        }
        update_option('pldr_core_readiness_error', array_merge($health, array('repair_attempted'=>$repair,'at'=>PLDR_Core::now())), false);
        PLDR_Core::audit('system', 0, 'runtime_schema_not_ready', array('repair_attempted'=>$repair,'health'=>$health));
        return false;
    }

    private static function inspect_core_schema(): array {
        global $wpdb;
        $required = array(
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
        foreach ($required as $suffix=>$spec) {
            $table=PLDR_Core::table($suffix);$wpdb->last_error='';
            $exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
            if (''!==(string)$wpdb->last_error) {$read_errors[]=$suffix.'.table';continue;}
            if ($exists!==$table) {$missing_tables[]=$suffix;continue;}
            $safe='`'.str_replace('`','',$table).'`';$wpdb->last_error='';
            $columns=$wpdb->get_col("SHOW COLUMNS FROM {$safe}");
            if (''!==(string)$wpdb->last_error) {$read_errors[]=$suffix.'.columns';continue;}
            $wpdb->last_error='';$indexes=$wpdb->get_col("SHOW INDEX FROM {$safe}",2);
            if (''!==(string)$wpdb->last_error) {$read_errors[]=$suffix.'.indexes';continue;}
            $columns=is_array($columns)?$columns:array();$indexes=is_array($indexes)?$indexes:array();
            foreach ($spec['columns'] as $column) if(!in_array($column,$columns,true)) $missing_columns[]=$suffix.'.'.$column;
            foreach ($spec['indexes'] as $index) if(!in_array($index,$indexes,true)) $missing_indexes[]=$suffix.'.'.$index;
        }
        return array(
            'ok'=>!$read_errors&&!$missing_tables&&!$missing_columns&&!$missing_indexes,
            'read_errors'=>$read_errors,
            'missing_tables'=>$missing_tables,
            'missing_columns'=>$missing_columns,
            'missing_indexes'=>$missing_indexes,
            'db_version'=>(string)get_option('pldr_db_version',''),
        );
    }
}
