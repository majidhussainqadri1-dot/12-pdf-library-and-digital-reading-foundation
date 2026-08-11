<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Preservation {
    private const PROVIDER_FINDINGS_LIMIT = 50;
    private const FINDING_LENGTH_LIMIT = 500;

    public static function assess(int $edition_id,bool $verify=false):array {
        global $wpdb;
        $system=(function_exists('wp_doing_cron')&&wp_doing_cron())||doing_action('pldr_future_preservation_scan');
        $edition=PLDR_Core::edition($edition_id);
        if(!$edition)return array('error'=>PLDR_Core::machine_error('pldr_preservation_edition','Edition not found.',404));
        $document_id=(int)$edition['document_id'];
        if(!$system&&!PLDR_Core::authorize('repair',$document_id)&&!PLDR_Core::authorize('manage',$document_id))return array('error'=>PLDR_Core::machine_error('pldr_preservation_forbidden','Preservation assessment authority is required for this document.',403));
        $object=PLDR_Core::object((int)$edition['object_id']);
        if(!$object)return array('error'=>PLDR_Core::machine_error('pldr_preservation_object','Object not found.',404));
        $path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);
        $integrity='unknown';$error='';$findings=array();$provider_failure=false;$provider_input_total=0;$provider_requested_quarantine=false;
        if($verify){
            if(is_wp_error($path)){
                $integrity='unavailable';
                $error=$path->get_error_message();
                $findings[]='Original object could not be opened for integrity verification.';
            }else{
                $integrity=PLDR_Crypto::verify_file((string)$path,(string)$object['sha256'],$error)?'verified':'failed';
            }
        }
        $health='healthy';
        if('unavailable'===$integrity)$health='needs-review';
        if('failed'===$integrity){
            $health='quarantined';$findings[]='Plaintext checksum verification failed.';
            if('quarantined'!==$object['object_status']){
                $updated=$wpdb->update(PLDR_Core::table('objects'),array('object_status'=>'quarantined','updated_at'=>PLDR_Core::now()),array('id'=>(int)$object['id'],'object_status'=>$object['object_status']));
                if(1!==$updated)return array('error'=>PLDR_Core::machine_error('pldr_preservation_quarantine_store','Integrity failure was detected but quarantine state could not be persisted; access state must be reconciled before proceeding.',500));
                $object['object_status']='quarantined';
                PLDR_Access::revoke_document($document_id,'preservation-integrity-failure');
                PLDR_Core::audit('object',(int)$object['id'],'preservation_integrity_quarantined',array('edition_id'=>$edition_id,'error'=>$error));
            }
        }
        if('quarantined'===$object['object_status']){$health='quarantined';$findings[]='Object is quarantined.';}
        try {
            $external=apply_filters('pldr_preservation_assessment',null,$edition_id,$object);
        } catch (Throwable $e) {
            $external=null;$provider_failure=true;
            if('quarantined'!==$health)$health='needs-review';
            $findings[]='External preservation assessment provider failed; local integrity evidence remains authoritative.';
            PLDR_Core::audit('edition',$edition_id,'preservation_provider_failed',array('document_id'=>$document_id,'error'=>self::limit($e->getMessage(),500)));
        }
        if(is_array($external)){
            $external_health=sanitize_key((string)($external['format_health']??$health));
            if('quarantined'===$external_health&&'quarantined'!==$object['object_status']){
                $provider_requested_quarantine=true;
                $external_health='needs-review';
                $findings[]='External provider requested quarantine; authoritative object quarantine requires local integrity/governed repair evidence.';
            }
            if(in_array($external_health,array('healthy','warning','needs-review'),true)&&'quarantined'!==$health){
                $rank=array('healthy'=>0,'warning'=>1,'needs-review'=>2);
                if(($rank[$external_health]??0)>($rank[$health]??0))$health=$external_health;
            }
            $provider_findings=(array)($external['findings']??array());$provider_input_total=count($provider_findings);
            foreach(array_slice($provider_findings,0,self::PROVIDER_FINDINGS_LIMIT) as $finding){$finding=self::limit(sanitize_text_field((string)$finding),self::FINDING_LENGTH_LIMIT);if(''!==$finding)$findings[]=$finding;}
        }
        if('failed'===$integrity||'quarantined'===$object['object_status'])$health='quarantined';
        $findings=array_values(array_unique(array_slice($findings,0,self::PROVIDER_FINDINGS_LIMIT+10)));
        $derivatives=$wpdb->get_results($wpdb->prepare('SELECT derivative_type,status,COUNT(*) count FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d GROUP BY derivative_type,status',$edition_id),ARRAY_A)?:array();
        $existing=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('preservation_records').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        $verified_now=$verify&&in_array($integrity,array('verified','failed'),true);
        $generation=max(1,(int)($existing['checksum_generation']??0)+($verified_now?1:0));
        $last_verified_at=$verified_now?PLDR_Core::now():($existing['last_verified_at']??null);
        $assessment=array('integrity'=>$integrity,'findings'=>$findings,'error'=>self::limit($error,1000),'provider_failure'=>$provider_failure,'provider_requested_quarantine'=>$provider_requested_quarantine,'provider_input_total'=>$provider_input_total,'provider_findings_limit'=>self::PROVIDER_FINDINGS_LIMIT,'provider_input_truncated'=>$provider_input_total>self::PROVIDER_FINDINGS_LIMIT);
        $assessment_json=wp_json_encode($assessment);
        if(!is_string($assessment_json))return array('error'=>PLDR_Core::machine_error('pldr_preservation_encode','Preservation assessment could not be encoded.',500));
        $stored=$wpdb->replace(PLDR_Core::table('preservation_records'),array('edition_id'=>$edition_id,'object_id'=>(int)$object['id'],'format_health'=>$health,'checksum_generation'=>$generation,'sha256'=>(string)$object['sha256'],'encrypted_sha256'=>(string)$object['encrypted_sha256'],'derivative_status_json'=>wp_json_encode($derivatives),'assessment_json'=>$assessment_json,'last_verified_at'=>$last_verified_at,'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return array('error'=>PLDR_Core::machine_error('pldr_preservation_store','Preservation assessment could not be stored.',500));
        return array('edition_id'=>$edition_id,'format_health'=>$health,'checksum_generation'=>$generation,'sha256'=>$object['sha256'],'encrypted_sha256'=>$object['encrypted_sha256'],'integrity'=>$integrity,'findings'=>$findings,'derivatives'=>$derivatives,'provider_failure'=>$provider_failure,'provider_requested_quarantine'=>$provider_requested_quarantine,'provider_findings_truncated'=>$provider_input_total>self::PROVIDER_FINDINGS_LIMIT,'original_immutable'=>true,'preservation_derivative_policy'=>'separate object only; never overwrite original');
    }

    public static function scheduled_scan():void {
        global $wpdb;
        $ids=$wpdb->get_col(
            'SELECT e.id FROM '.PLDR_Core::table('editions').' e LEFT JOIN '.PLDR_Core::table('preservation_records').' p ON p.edition_id=e.id WHERE e.status=\'published\' ORDER BY CASE WHEN p.last_verified_at IS NULL THEN 0 ELSE 1 END ASC,p.last_verified_at ASC,e.id ASC LIMIT 5'
        );
        foreach($ids as $id)self::assess((int)$id,true);
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
