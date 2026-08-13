<?php

defined('ABSPATH') || exit;

/**
 * Small forward-only schema corrections that must run after the normal File 12
 * and Future-24 schema upgraders. Every correction verifies its postcondition.
 */
final class PLDR_Schema_Corrections {
    private const REVISION='2026-08-13-r20-17';
    private const TRANSACTIONAL_TABLES=array(
        'documents','editions','objects','access_policies','reading_state','reading_items','rights_cases','derivatives','ocr_text','access_tokens','outbox','audit','book_packs','idempotency',
        'future_prefs','shelves','shelf_items','reading_events','session_handoffs','ocr_corrections','authority_cache','a11y_audits','room_contexts','preservation_records','scan_fingerprints',
    );

    public static function hooks():void {
        add_action('plugins_loaded',array(__CLASS__,'maybe_apply'),61);
    }

    public static function maybe_apply():void {
        if(self::REVISION===(string)get_option('pldr_schema_corrections_revision',''))return;
        global $wpdb;
        $table=PLDR_Core::table('outbox');$safe='`'.str_replace('`','',$table).'`';
        $wpdb->last_error='';
        $column=$wpdb->get_row("SHOW COLUMNS FROM {$safe} LIKE 'last_error'",ARRAY_A);
        if(''!==(string)$wpdb->last_error||!is_array($column)){
            PLDR_Core::audit('schema',0,'schema_correction_outbox_column_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
            return;
        }
        if('NO'===(string)($column['Null']??'')){
            $changed=$wpdb->query("ALTER TABLE {$safe} MODIFY last_error text NULL");
            if(false===$changed){
                PLDR_Core::audit('schema',0,'schema_correction_outbox_nullable_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
                return;
            }
        }
        $wpdb->last_error='';
        $verified=$wpdb->get_row("SHOW COLUMNS FROM {$safe} LIKE 'last_error'",ARRAY_A);
        if(''!==(string)$wpdb->last_error||!is_array($verified)||'YES'!==(string)($verified['Null']??'')){
            PLDR_Core::audit('schema',0,'schema_correction_outbox_nullable_unverified',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
            return;
        }

        $converted=array();
        foreach(self::TRANSACTIONAL_TABLES as $suffix){
            $name=PLDR_Core::table($suffix);$quoted='`'.str_replace('`','',$name).'`';$wpdb->last_error='';
            $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$name),ARRAY_A);
            if(''!==(string)$wpdb->last_error||!is_array($status)){
                PLDR_Core::audit('schema',0,'schema_correction_engine_read_failed',array('table'=>$suffix,'db_error'=>substr((string)$wpdb->last_error,0,500)));
                return;
            }
            if('InnoDB'!==(string)($status['Engine']??'')){
                $changed=$wpdb->query("ALTER TABLE {$quoted} ENGINE=InnoDB");
                if(false===$changed){
                    PLDR_Core::audit('schema',0,'schema_correction_engine_failed',array('table'=>$suffix,'previous_engine'=>(string)($status['Engine']??''),'db_error'=>substr((string)$wpdb->last_error,0,500)));
                    return;
                }
                $converted[]=$suffix;
            }
            $wpdb->last_error='';$after=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$name),ARRAY_A);
            if(''!==(string)$wpdb->last_error||!is_array($after)||'InnoDB'!==(string)($after['Engine']??'')){
                PLDR_Core::audit('schema',0,'schema_correction_engine_unverified',array('table'=>$suffix,'engine'=>(string)($after['Engine']??''),'db_error'=>substr((string)$wpdb->last_error,0,500)));
                return;
            }
        }

        update_option('pldr_schema_corrections_revision',self::REVISION,false);
        if(self::REVISION!==(string)get_option('pldr_schema_corrections_revision','')){
            PLDR_Core::audit('schema',0,'schema_correction_revision_store_failed',array('revision'=>self::REVISION));
            return;
        }
        PLDR_Core::audit('schema',0,'schema_correction_applied',array('revision'=>self::REVISION,'outbox_last_error_nullable'=>true,'transaction_engine'=>'InnoDB','converted_tables'=>$converted));
    }
}
