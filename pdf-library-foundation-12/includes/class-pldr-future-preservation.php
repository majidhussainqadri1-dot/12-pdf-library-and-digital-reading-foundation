<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Preservation {
    public static function assess(int $edition_id,bool $verify=false):array {
        global $wpdb;
        $system=(function_exists('wp_doing_cron')&&wp_doing_cron())||doing_action('pldr_future_preservation_scan');
        $edition=$system?PLDR_Core::edition($edition_id):PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition)&&!PLDR_Core::authorize('repair'))return array('error'=>$edition);
        if(is_wp_error($edition))$edition=PLDR_Core::edition($edition_id);
        if(!$edition)return array('error'=>PLDR_Core::machine_error('pldr_preservation_edition','Edition not found.',404));
        $object=PLDR_Core::object((int)$edition['object_id']);
        if(!$object)return array('error'=>PLDR_Core::machine_error('pldr_preservation_object','Object not found.',404));
        $path=PLDR_Storage::path((string)$object['storage_name'],(string)$object['storage_scope']);
        $integrity='unknown';$error='';
        if($verify&&!is_wp_error($path))$integrity=PLDR_Crypto::verify_file((string)$path,(string)$object['sha256'],$error)?'verified':'failed';
        $external=apply_filters('pldr_preservation_assessment',null,$edition_id,$object);
        $health='healthy';$findings=array();
        if('failed'===$integrity){
            $health='quarantined';$findings[]='Plaintext checksum verification failed.';
            if('quarantined'!==$object['object_status']){
                $wpdb->update(PLDR_Core::table('objects'),array('object_status'=>'quarantined','updated_at'=>PLDR_Core::now()),array('id'=>(int)$object['id']));
                PLDR_Access::revoke_document((int)$edition['document_id'],'preservation-integrity-failure');
                PLDR_Core::audit('object',(int)$object['id'],'preservation_integrity_quarantined',array('edition_id'=>$edition_id,'error'=>$error));
                $object['object_status']='quarantined';
            }
        }
        if('quarantined'===$object['object_status']){$health='quarantined';$findings[]='Object is quarantined.';}
        if(is_array($external)){
            $external_health=sanitize_key((string)($external['format_health']??$health));
            if(in_array($external_health,array('healthy','warning','needs-review','quarantined'),true))$health=$external_health;
            $findings=array_merge($findings,array_map('sanitize_text_field',(array)($external['findings']??array())));
        }
        if('failed'===$integrity)$health='quarantined';
        $derivatives=$wpdb->get_results($wpdb->prepare('SELECT derivative_type,status,COUNT(*) count FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d GROUP BY derivative_type,status',$edition_id),ARRAY_A)?:array();
        $existing=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('preservation_records').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        $generation=max(1,(int)($existing['checksum_generation']??0)+($verify?1:0));
        $stored=$wpdb->replace(PLDR_Core::table('preservation_records'),array('edition_id'=>$edition_id,'object_id'=>(int)$object['id'],'format_health'=>$health,'checksum_generation'=>$generation,'sha256'=>(string)$object['sha256'],'encrypted_sha256'=>(string)$object['encrypted_sha256'],'derivative_status_json'=>wp_json_encode($derivatives),'assessment_json'=>wp_json_encode(array('integrity'=>$integrity,'findings'=>$findings,'error'=>$error)),'last_verified_at'=>$verify?PLDR_Core::now():($existing['last_verified_at']??null),'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return array('error'=>PLDR_Core::machine_error('pldr_preservation_store','Preservation assessment could not be stored.',500));
        return array('edition_id'=>$edition_id,'format_health'=>$health,'checksum_generation'=>$generation,'sha256'=>$object['sha256'],'encrypted_sha256'=>$object['encrypted_sha256'],'integrity'=>$integrity,'findings'=>array_values(array_unique($findings)),'derivatives'=>$derivatives,'original_immutable'=>true,'preservation_derivative_policy'=>'separate object only; never overwrite original');
    }

    public static function scheduled_scan():void {
        global $wpdb;
        $ids=$wpdb->get_col(
            'SELECT e.id FROM '.PLDR_Core::table('editions').' e LEFT JOIN '.PLDR_Core::table('preservation_records').' p ON p.edition_id=e.id WHERE e.status=\'published\' ORDER BY CASE WHEN p.last_verified_at IS NULL THEN 0 ELSE 1 END ASC,p.last_verified_at ASC,e.id ASC LIMIT 5'
        );
        foreach($ids as $id)self::assess((int)$id,true);
    }
}
