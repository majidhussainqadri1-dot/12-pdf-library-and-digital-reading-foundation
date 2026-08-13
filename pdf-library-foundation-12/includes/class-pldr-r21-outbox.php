<?php

defined('ABSPATH') || exit;

/**
 * R21 outbox governance: explicit event contracts plus a bounded, fail-closed
 * dispatcher that never persists arbitrary consumer exception text.
 */
final class PLDR_R21_Outbox {
    private const CONTRACTS=array(
        'ReadingProgressUpdated.v1'=>array('privacy'=>'private-user','consumers'=>array('file-12-private','file-19-user-notifications'),'retention'=>'bounded'),
        'PDFDocumentIngested.v1'=>array('privacy'=>'internal-operations','consumers'=>array('file-24-assurance'),'retention'=>'audit'),
        'PDFDocumentOCRReady.v1'=>array('privacy'=>'internal-derived','consumers'=>array('file-06-knowledge','file-26-search-projection'),'retention'=>'audit'),
        'PDFReadingRoomRequested.v1'=>array('privacy'=>'private-user','consumers'=>array('file-17-communication-adapter'),'retention'=>'bounded'),
        'PDFDocumentAccessChanged.v1'=>array('privacy'=>'internal-policy','consumers'=>array('file-19-notifications','file-26-search-projection'),'retention'=>'audit'),
        'RightsReportFiled.v1'=>array('privacy'=>'confidential-rights','consumers'=>array('file-19-notifications','file-24-assurance'),'retention'=>'rights-case'),
        'PDFRightsCaseDecided.v1'=>array('privacy'=>'internal-rights','consumers'=>array('file-19-notifications','file-24-assurance'),'retention'=>'rights-case'),
        'PDFRightsCaseAppealed.v1'=>array('privacy'=>'internal-rights','consumers'=>array('file-19-notifications','file-24-assurance'),'retention'=>'rights-case'),
        'PDFDocumentPublished.v1'=>array('privacy'=>'public-metadata','consumers'=>array('file-19-notifications','file-21-discovery','file-26-search-projection'),'retention'=>'audit'),
        'PDFDocumentStatusChanged.v1'=>array('privacy'=>'internal-policy','consumers'=>array('file-19-notifications','file-21-discovery','file-26-search-projection'),'retention'=>'audit'),
        'PDFDocumentUnpublished.v1'=>array('privacy'=>'internal-policy','consumers'=>array('file-19-notifications','file-21-discovery','file-26-search-projection'),'retention'=>'audit'),
        'PDFDocumentRestricted.v1'=>array('privacy'=>'internal-policy','consumers'=>array('file-19-notifications','file-21-discovery','file-26-search-projection'),'retention'=>'audit'),
        'PDFBookPackRegistered.v1'=>array('privacy'=>'public-metadata','consumers'=>array('file-05-learning','file-06-knowledge'),'retention'=>'audit'),
        'PDFLibraryLegacyMigrationCompleted.v1'=>array('privacy'=>'internal-operations','consumers'=>array('file-24-assurance'),'retention'=>'audit'),
        'PDFLegacyInteractionMigrationRequested.v1'=>array('privacy'=>'internal-operations','consumers'=>array('file-24-assurance'),'retention'=>'bounded'),
    );

    public static function hooks():void {
        add_action('init',array(__CLASS__,'register_contracts'),2);
    }

    public static function register_contracts():void {
        foreach(self::CONTRACTS as $name=>$meta){
            do_action('spf_register_event_schema',array(
                'owner'=>'12','event'=>$name,'version'=>1,'privacy_class'=>$meta['privacy'],
                'retention'=>$meta['retention'],'consumers'=>$meta['consumers'],'delivery'=>'at-least-once',
                'consumer_requirement'=>'idempotent-by-event-id','authorization'=>'event-is-fact-not-command',
            ));
        }
    }

    public static function dispatch():void {
        global $wpdb;$now=PLDR_Core::now();$table=PLDR_Core::table('outbox');
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE status IN (%s,%s,%s) AND available_at<=%s ORDER BY id ASC LIMIT 50",'pending','retry','processing',$now),ARRAY_A);
        if(''!==(string)$wpdb->last_error){PLDR_Core::audit('system',0,'outbox_read_failed',array('db_error'=>substr((string)$wpdb->last_error,0,500)));return;}
        foreach(is_array($rows)?$rows:array() as $row){
            $lease_until=gmdate('Y-m-d H:i:s',time()+10*MINUTE_IN_SECONDS);
            $claimed=$wpdb->query($wpdb->prepare("UPDATE {$table} SET status=%s,available_at=%s WHERE id=%d AND status IN (%s,%s,%s) AND available_at<=%s",'processing',$lease_until,(int)$row['id'],'pending','retry','processing',$now));
            if(1!==$claimed)continue;
            $event_name=(string)$row['event_name'];
            if(!isset(self::CONTRACTS[$event_name])){
                self::dead_letter((int)$row['id'],$lease_until,(int)$row['attempts'],'unknown-event-contract',(string)$row['event_id']);
                continue;
            }
            $payload=json_decode((string)$row['payload_json'],true);
            if(!is_array($payload)||JSON_ERROR_NONE!==json_last_error()){
                self::dead_letter((int)$row['id'],$lease_until,(int)$row['attempts'],'invalid-payload-json',(string)$row['event_id']);
                continue;
            }
            try{
                $accepted=apply_filters('pldr_dispatch_event',true,$event_name,$payload,(string)$row['event_id']);
                if(false===$accepted)throw new RuntimeException('consumer-requested-retry');
                do_action('sabri_domain_event',$event_name,$payload,(string)$row['event_id'],'file-12');
                do_action('pldr_event',$event_name,$payload,(string)$row['event_id']);
                $stored=$wpdb->update($table,array('status'=>'sent','sent_at'=>PLDR_Core::now(),'last_error'=>null),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                if(1!==$stored)throw new RuntimeException('lease-state-persist-failed');
            }catch(Throwable $e){
                $attempts=(int)$row['attempts']+1;$status=$attempts>=8?'dead-letter':'retry';$delay=min(3600,30*(2**min($attempts,6)));
                $safe_code='consumer-dispatch-failed';
                $retry=$wpdb->update($table,array('status'=>$status,'attempts'=>$attempts,'available_at'=>gmdate('Y-m-d H:i:s',time()+$delay),'last_error'=>$safe_code),array('id'=>(int)$row['id'],'status'=>'processing','available_at'=>$lease_until));
                PLDR_Core::audit('outbox',(int)$row['id'],'outbox_dispatch_failed',array('event_id'=>(string)$row['event_id'],'event_name'=>$event_name,'attempts'=>$attempts,'next_state'=>$status,'error_class'=>sanitize_key(get_class($e)),'persisted'=>false!==$retry));
            }
        }
    }

    private static function dead_letter(int $id,string $lease_until,int $attempts,string $code,string $event_id):void {
        global $wpdb;$table=PLDR_Core::table('outbox');
        $stored=$wpdb->update($table,array('status'=>'dead-letter','attempts'=>max(8,$attempts+1),'last_error'=>$code),array('id'=>$id,'status'=>'processing','available_at'=>$lease_until));
        PLDR_Core::audit('outbox',$id,'outbox_dead_lettered',array('event_id'=>$event_id,'reason'=>$code,'persisted'=>1===$stored));
    }
}
