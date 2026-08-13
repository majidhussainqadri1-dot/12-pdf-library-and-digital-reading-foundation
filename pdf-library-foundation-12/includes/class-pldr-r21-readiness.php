<?php

defined('ABSPATH') || exit;

/**
 * R21 runtime readiness gate.
 *
 * Repository version markers are not sufficient runtime evidence. This gate
 * verifies the complete physical core schema needed by runtime code, attempts
 * one canonical repair when drift is detected, requires physically verified
 * core correction postconditions, and keeps domain workflows fail-closed while
 * the database is not ready.
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
        $corrections_current = !class_exists('PLDR_Schema_Corrections') || PLDR_Schema_Corrections::is_core_current();
        if ('1' === $cached && $marker_current && $corrections_current && !get_option('pldr_schema_error')) {
            delete_option('pldr_core_readiness_error');
            return true;
        }

        $health = self::inspect_core_schema();
        if ($health['ok'] && $marker_current) {
            if (class_exists('PLDR_Schema_Corrections') && !PLDR_Schema_Corrections::is_core_current()) {
                PLDR_Schema_Corrections::maybe_apply();
            }
            if (!class_exists('PLDR_Schema_Corrections') || PLDR_Schema_Corrections::is_core_current()) {
                delete_option('pldr_schema_error');
                delete_option('pldr_core_readiness_error');
                set_transient('pldr_r21_core_schema_ready', '1', self::HEALTH_TTL);
                return true;
            }
            $health['correction_revision'] = 'core-postconditions-not-current';
        }

        update_option('pldr_schema_error', array_merge($health, array('reason'=>'runtime_schema_drift','at'=>PLDR_Core::now())), false);
        delete_transient('pldr_r21_core_schema_ready');
        if ($repair) {
            if (class_exists('PLDR_Schema_Corrections')) PLDR_Schema_Corrections::invalidate_health_cache();
            $repaired = PLDR_Schema::upgrade();
            if ($repaired) {
                if (class_exists('PLDR_Schema_Corrections')) {
                    PLDR_Schema_Corrections::invalidate_health_cache();
                    PLDR_Schema_Corrections::maybe_apply();
                }
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
            'documents'=>array(
                'columns'=>array('id','public_id','title','slug','document_type','category','language','subjects_json','collections_json','search_text','status','access_mode','created_by','version','created_at','updated_at'),
                'indexes'=>array('PRIMARY','public_id','slug','status_type','category','created_by'),
            ),
            'editions'=>array(
                'columns'=>array('id','document_id','edition_label','isbn','publication_year','pages','language','author_name','translator','publisher','source_name','license_code','rights_basis','territory','rights_expires_at','takedown_contact','sha256','object_id','status','supersedes_edition_id','version','created_at','updated_at'),
                'indexes'=>array('PRIMARY','document_id','sha256','status','isbn','rights_expires_at'),
            ),
            'objects'=>array(
                'columns'=>array('id','storage_name','storage_scope','original_name','mime_type','byte_size','sha256','encrypted_sha256','key_id','format_version','scan_status','object_status','created_at','verified_at','deleted_at'),
                'indexes'=>array('PRIMARY','storage_name','sha256','object_status'),
            ),
            'access_policies'=>array(
                'columns'=>array('id','document_id','audience','entitlement_key','download_allowed','print_allowed','offline_allowed','embargo_until','version','created_at','updated_at'),
                'indexes'=>array('PRIMARY','document_version','audience','embargo_until'),
            ),
            'reading_state'=>array(
                'columns'=>array('user_id','edition_id','last_page','percent','edition_version','updated_at'),
                'indexes'=>array('PRIMARY','edition_id','updated_at'),
            ),
            'reading_items'=>array(
                'columns'=>array('id','user_id','edition_id','item_type','page_number','anchor_text','note_text','tags_json','version','created_at','updated_at'),
                'indexes'=>array('PRIMARY','user_edition','item_type','updated_at'),
            ),
            'rights_cases'=>array(
                'columns'=>array('id','case_key','document_id','reporter_id','parent_case_id','state','reason','evidence_json','decision_note','assigned_to','version','created_at','updated_at','closed_at'),
                'indexes'=>array('PRIMARY','case_key','document_state','reporter_id','assigned_to'),
            ),
            'derivatives'=>array(
                'columns'=>array('id','edition_id','derivative_type','page_number','object_id','language','quality_score','lawful_basis','status','source_version','created_at','updated_at'),
                'indexes'=>array('PRIMARY','edition_type_page','object_id','status'),
            ),
            'ocr_text'=>array(
                'columns'=>array('edition_id','page_number','language','quality_score','text_content','normalized_text','created_at','updated_at'),
                'indexes'=>array('PRIMARY','language'),
            ),
            'access_tokens'=>array(
                'columns'=>array('id','token_hash','user_id','edition_id','object_id','operation','audience_hash','expires_at','revoked_at','used_count','max_uses','created_at'),
                'indexes'=>array('PRIMARY','token_hash','edition_operation','expires_at','user_id'),
            ),
            'outbox'=>array(
                'columns'=>array('id','event_id','event_name','aggregate_type','aggregate_id','payload_json','status','attempts','available_at','last_error','created_at','sent_at'),
                'indexes'=>array('PRIMARY','event_id','dispatch','aggregate'),
            ),
            'audit'=>array(
                'columns'=>array('id','trace_id','object_type','object_id','action','actor_id','context_json','created_at'),
                'indexes'=>array('PRIMARY','object_lookup','actor_id','created_at'),
            ),
            'book_packs'=>array(
                'columns'=>array('id','pack_key','pack_version','title','author','translator','rights_basis','manifest_sha256','metadata_json','status','created_at','updated_at'),
                'indexes'=>array('PRIMARY','pack_version','status'),
            ),
            'idempotency'=>array(
                'columns'=>array('actor_id','route','key_hash','response_json','status_code','expires_at','created_at'),
                'indexes'=>array('PRIMARY','expires_at'),
            ),
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
