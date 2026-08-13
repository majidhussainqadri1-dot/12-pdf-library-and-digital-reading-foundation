<?php

defined('ABSPATH') || exit;

/**
 * Small forward-only schema corrections that must run after the normal File 12
 * schema upgrader. Each correction is idempotent and verifies its postcondition.
 */
final class PLDR_Schema_Corrections {
    private const REVISION='2026-08-13-r20-04';

    public static function hooks():void {
        add_action('plugins_loaded',array(__CLASS__,'maybe_apply'),61);
    }

    public static function maybe_apply():void {
        if(self::REVISION===(string)get_option('pldr_schema_corrections_revision',''))return;
        global $wpdb;
        $table=PLDR_Core::table('outbox');
        $wpdb->last_error='';
        $column=$wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'last_error'",ARRAY_A);
        if(''!==(string)$wpdb->last_error||!is_array($column)){
            PLDR_Core::audit('schema',0,'schema_correction_outbox_column_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
            return;
        }
        if('NO'===(string)($column['Null']??'')){
            $changed=$wpdb->query("ALTER TABLE {$table} MODIFY last_error text NULL");
            if(false===$changed){
                PLDR_Core::audit('schema',0,'schema_correction_outbox_nullable_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
                return;
            }
        }
        $wpdb->last_error='';
        $verified=$wpdb->get_row("SHOW COLUMNS FROM {$table} LIKE 'last_error'",ARRAY_A);
        if(''!==(string)$wpdb->last_error||!is_array($verified)||'YES'!==(string)($verified['Null']??'')){
            PLDR_Core::audit('schema',0,'schema_correction_outbox_nullable_unverified',array('db_error'=>substr((string)$wpdb->last_error,0,500)));
            return;
        }
        update_option('pldr_schema_corrections_revision',self::REVISION,false);
        PLDR_Core::audit('schema',0,'schema_correction_applied',array('revision'=>self::REVISION,'outbox_last_error_nullable'=>true));
    }
}
