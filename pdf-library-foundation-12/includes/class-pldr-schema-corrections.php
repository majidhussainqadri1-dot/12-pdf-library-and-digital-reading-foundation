<?php

defined('ABSPATH') || exit;

/**
 * Forward-only physical schema corrections that remain verifiable after the
 * normal File 12 and Future-24 schema upgraders have run.
 *
 * R21 deliberately distinguishes the core correction gate from the complete
 * core+Future gate so a fresh install cannot deadlock before Future-24 creates
 * its own tables. A stored revision marker is never sufficient by itself.
 */
final class PLDR_Schema_Corrections {
    private const REVISION='2026-08-13-r20-17';
    private const HEALTH_TTL=300;
    private const CORE_TABLES=array(
        'documents','editions','objects','access_policies','reading_state','reading_items','rights_cases','derivatives','ocr_text','access_tokens','outbox','audit','book_packs','idempotency',
    );
    private const FUTURE_TABLES=array(
        'future_prefs','shelves','shelf_items','reading_events','session_handoffs','ocr_corrections','authority_cache','a11y_audits','room_contexts','preservation_records','scan_fingerprints',
    );

    public static function hooks():void {
        add_action('plugins_loaded',array(__CLASS__,'maybe_apply'),61);
    }

    public static function revision():string { return self::REVISION; }

    public static function invalidate_health_cache():void {
        delete_transient('pldr_schema_corrections_core_health');
        delete_transient('pldr_schema_corrections_full_health');
    }

    public static function is_core_current():bool {
        if(self::REVISION!==(string)get_option('pldr_schema_corrections_revision',''))return false;
        $health=self::physical_health(false);
        return !empty($health['ok']);
    }

    public static function is_current():bool {
        if(self::REVISION!==(string)get_option('pldr_schema_corrections_revision',''))return false;
        $health=self::physical_health(true);
        return !empty($health['ok']);
    }

    private static function future_schema_expected():bool {
        return class_exists('PLDR_Future_Schema')
            && defined('PLDR_Future_Schema::DB_VERSION')
            && (string)get_option('pldr_future_db_version','')===PLDR_Future_Schema::DB_VERSION;
    }

    private static function physical_health(bool $include_future):array {
        global $wpdb;
        $cache=$include_future?'pldr_schema_corrections_full_health':'pldr_schema_corrections_core_health';
        if('1'===get_transient($cache))return array('ok'=>true,'cached'=>true);

        $errors=array();
        $outbox=PLDR_Core::table('outbox');$outbox_safe='`'.str_replace('`','',$outbox).'`';
        $wpdb->last_error='';
        $column=$wpdb->get_row("SHOW COLUMNS FROM {$outbox_safe} LIKE 'last_error'",ARRAY_A);
        if(''!==(string)$wpdb->last_error||!is_array($column))$errors[]='outbox.last_error:unreadable';
        elseif('YES'!==(string)($column['Null']??''))$errors[]='outbox.last_error:not-nullable';

        $future_expected=$include_future&&self::future_schema_expected();
        $tables=self::CORE_TABLES;
        if($include_future)$tables=array_merge($tables,self::FUTURE_TABLES);
        foreach($tables as $suffix){
            $name=PLDR_Core::table($suffix);$is_future=in_array($suffix,self::FUTURE_TABLES,true);
            $wpdb->last_error='';
            $exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$name));
            if(''!==(string)$wpdb->last_error){$errors[]=$suffix.':table-read';continue;}
            if($exists!==$name){
                if(!$is_future||$future_expected)$errors[]=$suffix.':missing';
                continue;
            }
            $wpdb->last_error='';
            $status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$name),ARRAY_A);
            if(''!==(string)$wpdb->last_error||!is_array($status)){$errors[]=$suffix.':engine-read';continue;}
            if('InnoDB'!==(string)($status['Engine']??''))$errors[]=$suffix.':engine='.(string)($status['Engine']??'unknown');
        }
        $ok=!$errors;
        if($ok)set_transient($cache,'1',self::HEALTH_TTL);
        return array('ok'=>$ok,'errors'=>$errors,'future_expected'=>$future_expected);
    }

    public static function maybe_apply():void {
        if(self::is_current())return;
        self::invalidate_health_cache();
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

        $converted=array();$future_expected=self::future_schema_expected();
        foreach(array_merge(self::CORE_TABLES,self::FUTURE_TABLES) as $suffix){
            $name=PLDR_Core::table($suffix);$quoted='`'.str_replace('`','',$name).'`';$is_future=in_array($suffix,self::FUTURE_TABLES,true);
            $wpdb->last_error='';$exists=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$name));
            if(''!==(string)$wpdb->last_error){PLDR_Core::audit('schema',0,'schema_correction_table_read_failed',array('table'=>$suffix,'db_error'=>substr((string)$wpdb->last_error,0,500)));return;}
            if($exists!==$name){
                if(!$is_future||$future_expected){PLDR_Core::audit('schema',0,'schema_correction_required_table_missing',array('table'=>$suffix,'future_expected'=>$future_expected));return;}
                continue;
            }
            $wpdb->last_error='';$status=$wpdb->get_row($wpdb->prepare('SHOW TABLE STATUS LIKE %s',$name),ARRAY_A);
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
        }

        update_option('pldr_schema_corrections_revision',self::REVISION,false);
        self::invalidate_health_cache();
        $verified_current=$future_expected?self::is_current():self::is_core_current();
        if(!$verified_current){
            PLDR_Core::audit('schema',0,'schema_correction_engine_unverified',array('revision'=>self::REVISION,'future_expected'=>$future_expected,'physical_postcondition'=>true));
            return;
        }
        PLDR_Core::audit('schema',0,'schema_correction_applied',array('revision'=>self::REVISION,'outbox_last_error_nullable'=>true,'transaction_engine'=>'InnoDB','converted_tables'=>$converted,'future_expected'=>$future_expected));
    }
}
