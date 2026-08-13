<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Rooms {
    public static function create(int $edition_id,int $page,array $anchor=array()) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_room_login','Log in to create a scholarly reading room.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $wpdb->last_error='';$doc=PLDR_Core::document((int)$edition['document_id']);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_room_document_read','Document privacy classification could not be read reliably; reading-room creation was denied.',503,array('degraded'=>true));
        if(!$doc)return PLDR_Core::machine_error('pldr_room_document','Document privacy classification is unavailable; reading-room creation was denied.',503,array('degraded'=>true));
        if('patient-cases'===$doc['category']){
            try{$patient_case_allowed=(bool)apply_filters('pldr_patient_case_reading_room_allowed',false,$edition_id,$doc);}
            catch(Throwable $e){PLDR_Core::audit('edition',$edition_id,'reading_room_patient_policy_provider_failed',array('provider_failure'=>1),$uid);return PLDR_Core::machine_error('pldr_room_policy_provider','Reading-room patient-case policy could not be verified; room creation was denied.',503,array('degraded'=>true,'provider_failure'=>true));}
            if(!$patient_case_allowed)return PLDR_Core::machine_error('pldr_room_patient_case','Patient-case documents require separate privacy approval before a reading room can be created.',403);
        }
        $page=max(1,min((int)$edition['pages'],$page));
        $anchor=self::anchor($anchor,$page);
        if(is_wp_error($anchor))return $anchor;
        $anchor_match=self::anchor_belongs($edition_id,$page,$anchor,$edition);
        if(is_wp_error($anchor_match))return $anchor_match;
        if(!$anchor_match)return PLDR_Core::machine_error('pldr_room_anchor_source','Reading-room text anchors must match the requested document page.',403);
        $encoded=wp_json_encode($anchor,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(false===$encoded||strlen($encoded)>1800)return PLDR_Core::machine_error('pldr_room_anchor','Reading-room anchor is too large.',400);
        $room_key=PLDR_Core::uuid();
        $now=PLDR_Core::now();
        $stored=$wpdb->insert(PLDR_Core::table('room_contexts'),array('room_key'=>$room_key,'edition_id'=>$edition_id,'created_by'=>$uid,'page_number'=>$page,'anchor_json'=>$encoded,'provider_ref'=>'','status'=>'pending-provider','created_at'=>$now,'updated_at'=>$now));
        if(false===$stored||!(int)$wpdb->insert_id)return PLDR_Core::machine_error('pldr_room_store','Reading-room context could not be stored.',500);
        $context_id=(int)$wpdb->insert_id;
        $context=array('file'=>12,'room_key'=>$room_key,'edition_id'=>$edition_id,'document_id'=>$edition['public_id'],'page'=>$page,'anchor'=>$anchor,'source_url'=>add_query_arg('edition',$edition_id,PLDR_Core::route_url('read',array('id'=>$edition['public_id']))));
        try{
            $provider=apply_filters('pldr_create_reading_room_provider',null,$uid,$context);
        }catch(Throwable $e){
            $message=self::limit(sanitize_text_field($e->getMessage()),300);
            $failed_state=$wpdb->update(PLDR_Core::table('room_contexts'),array('status'=>'provider-error','updated_at'=>PLDR_Core::now()),array('room_key'=>$room_key,'created_by'=>$uid));
            if(false===$failed_state)PLDR_Core::audit('room_context',$context_id,'provider_failure_state_persist_failed',array('room_key'=>$room_key),$uid);
            PLDR_Core::audit('room_context',$context_id,'provider_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'error'=>$message),$uid);
            return PLDR_Core::machine_error('pldr_room_provider','Reading-room provider failed; the local context remains for safe retry/reconciliation.',503,array('room_key'=>$room_key,'degraded'=>true));
        }
        $provider_ref=is_array($provider)?self::limit(sanitize_text_field((string)($provider['reference']??'')),190):'';
        if(''===$provider_ref){
            $pending=$wpdb->update(PLDR_Core::table('room_contexts'),array('status'=>'pending-provider','updated_at'=>PLDR_Core::now()),array('room_key'=>$room_key,'created_by'=>$uid));
            if(false===$pending)return PLDR_Core::machine_error('pldr_room_pending_store','Reading-room provider state could not be persisted; reconciliation is required.',500,array('room_key'=>$room_key));
            return PLDR_Core::machine_error('pldr_room_provider','No approved reading-room provider accepted the request; the local context remains pending.',503,array('room_key'=>$room_key,'degraded'=>true));
        }
        $wpdb->last_error='';
        $still_allowed=PLDR_Access::can_access_edition($edition_id,'read',$uid);
        $access_read_failed=''!==(string)$wpdb->last_error;
        if($access_read_failed||!$still_allowed){
            $reason=$access_read_failed?'post-provider-access-read-failed':'post-provider-access-revoked';
            $state=$wpdb->update(PLDR_Core::table('room_contexts'),array('status'=>'provider-error','updated_at'=>PLDR_Core::now()),array('room_key'=>$room_key,'created_by'=>$uid,'status'=>'pending-provider'));
            if(false===$state)PLDR_Core::audit('room_context',$context_id,'post_provider_access_state_persist_failed',array('room_key'=>$room_key,'reason'=>$reason),$uid);
            $compensation_failed=false;
            try{do_action('pldr_reading_room_provider_compensate',$provider_ref,$uid,$context,$reason);}catch(Throwable $e){$compensation_failed=true;PLDR_Core::audit('room_context',$context_id,'provider_compensation_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'provider_ref'=>$provider_ref,'reason'=>$reason),$uid);}
            if($access_read_failed)return PLDR_Core::machine_error('pldr_room_access_recheck','Reading-room access could not be revalidated after the provider call; provider compensation was requested and activation was denied.',503,array('room_key'=>$room_key,'degraded'=>true,'compensation_failed'=>$compensation_failed));
            return PLDR_Core::machine_error('pldr_room_access_revoked','Document access changed while the reading room was being created; provider compensation was requested and activation was denied.',403,array('room_key'=>$room_key,'compensation_failed'=>$compensation_failed));
        }
        if(false===$wpdb->query('START TRANSACTION')){
            try{do_action('pldr_reading_room_provider_compensate',$provider_ref,$uid,$context,'local-transaction-start-failed');}catch(Throwable $e){PLDR_Core::audit('room_context',$context_id,'provider_compensation_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'provider_ref'=>$provider_ref),$uid);}
            return PLDR_Core::machine_error('pldr_room_transaction','Reading-room finalization transaction could not be started; provider compensation was requested.',500,array('room_key'=>$room_key));
        }
        $updated=$wpdb->update(PLDR_Core::table('room_contexts'),array('provider_ref'=>$provider_ref,'status'=>'active','updated_at'=>PLDR_Core::now()),array('room_key'=>$room_key,'created_by'=>$uid,'status'=>'pending-provider'));
        if(1!==$updated){
            $wpdb->query('ROLLBACK');
            $compensation_failed=false;
            try{do_action('pldr_reading_room_provider_compensate',$provider_ref,$uid,$context,'local-provider-reference-persistence-failed');}
            catch(Throwable $e){$compensation_failed=true;PLDR_Core::audit('room_context',$context_id,'provider_compensation_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'provider_ref'=>$provider_ref),$uid);}
            return PLDR_Core::machine_error('pldr_room_provider_store','Reading-room provider reference could not be persisted; provider compensation was requested and the local context requires reconciliation.',500,array('room_key'=>$room_key,'compensation_failed'=>$compensation_failed));
        }
        $event=PLDR_Core::emit('PDFReadingRoomRequested.v1','edition',$edition_id,array('room_key'=>$room_key,'document_id'=>$edition['public_id'],'page'=>$page,'provider_ref'=>$provider_ref));
        if(is_wp_error($event)){
            $wpdb->query('ROLLBACK');
            $compensation_failed=false;
            try{do_action('pldr_reading_room_provider_compensate',$provider_ref,$uid,$context,'atomic-event-persistence-failed');}
            catch(Throwable $e){$compensation_failed=true;PLDR_Core::audit('room_context',$context_id,'provider_compensation_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'provider_ref'=>$provider_ref),$uid);}
            return PLDR_Core::machine_error('pldr_room_event_atomic','Reading-room activation was rolled back because its reliable event could not be persisted atomically; provider compensation was requested.',503,array('committed'=>false,'room_key'=>$room_key,'compensation_failed'=>$compensation_failed));
        }
        if(false===$wpdb->query('COMMIT')){
            $wpdb->query('ROLLBACK');
            $compensation_failed=false;
            try{do_action('pldr_reading_room_provider_compensate',$provider_ref,$uid,$context,'atomic-local-commit-failed');}
            catch(Throwable $e){$compensation_failed=true;PLDR_Core::audit('room_context',$context_id,'provider_compensation_failed',array('edition_id'=>$edition_id,'room_key'=>$room_key,'provider_ref'=>$provider_ref),$uid);}
            return PLDR_Core::machine_error('pldr_room_commit','Reading-room activation could not be committed atomically; provider compensation was requested.',500,array('room_key'=>$room_key,'compensation_failed'=>$compensation_failed));
        }
        return array('room_key'=>$room_key,'status'=>'active','provider_ref'=>$provider_ref,'source_bound'=>true,'selector_value_preserved'=>isset($anchor['value']),'messaging_owner'=>'File 17 / shared communication contract','file_12_owns_only_anchor_context'=>true);
    }

    private static function anchor(array $anchor,int $page) {
        $out=array();
        $type=sanitize_text_field((string)($anchor['type']??''));
        if(in_array($type,array('TextQuoteSelector','FragmentSelector','SvgSelector','CssSelector'),true))$out['type']=$type;
        foreach(array('exact'=>500,'prefix'=>120,'suffix'=>120,'selection'=>500,'value'=>300) as $key=>$limit){if(isset($anchor[$key]))$out[$key]=self::limit(wp_strip_all_tags((string)$anchor[$key]),$limit);}
        if(in_array($type,array('FragmentSelector','SvgSelector','CssSelector'),true)&&''===trim((string)($out['value']??''))&&!isset($anchor['region']))return PLDR_Core::machine_error('pldr_room_selector_value','This reading-room selector requires a bounded selector value or region.',400);
        if('FragmentSelector'===$type&&!empty($out['value'])&&preg_match('/(?:^|[?&#;])page=(\d+)/',(string)$out['value'],$m)&&absint($m[1])!==$page)return PLDR_Core::machine_error('pldr_room_fragment_page','Reading-room fragment selector page identity does not match the requested page.',409);
        if(isset($anchor['region'])&&is_array($anchor['region'])){
            $region=array('x'=>max(0,min(1,(float)($anchor['region']['x']??0))),'y'=>max(0,min(1,(float)($anchor['region']['y']??0))),'w'=>max(0,min(1,(float)($anchor['region']['w']??0))),'h'=>max(0,min(1,(float)($anchor['region']['h']??0))));
            if($region['w']<=0||$region['h']<=0)return PLDR_Core::machine_error('pldr_room_region','Reading-room anchor region must have a positive width and height.',400);
            if(($region['x']+$region['w'])>1||($region['y']+$region['h'])>1)return PLDR_Core::machine_error('pldr_room_region_bounds','Reading-room anchor region must remain fully inside the requested page.',400);
            $out['region']=$region;
        }
        return $out;
    }

    private static function anchor_belongs(int $edition_id,int $page,array $anchor,array $edition) {
        $texts=array();
        foreach(array('exact','selection') as $key){if(!empty($anchor[$key]))$texts[]=PLDR_Core::normalize_search((string)$anchor[$key]);}
        $texts=array_values(array_filter($texts,static fn(string $value):bool=>''!==$value));
        if(!$texts)return true;
        global $wpdb;$wpdb->last_error='';
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_room_anchor_source_read','Reading-room source text could not be read reliably; external validation was not attempted.',503,array('degraded'=>true));
        if($rows){
            $haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));
            if(''!==$haystack){
                foreach($texts as $needle){if(false===strpos($haystack,$needle))return self::external_anchor_allowed($edition_id,$page,$anchor,$edition);}
                return true;
            }
        }
        return self::external_anchor_allowed($edition_id,$page,$anchor,$edition);
    }

    private static function external_anchor_allowed(int $edition_id,int $page,array $anchor,array $edition) {
        try{return (bool)apply_filters('pldr_reading_room_anchor_allowed',false,$edition_id,$page,$anchor,$edition);}
        catch(Throwable $e){PLDR_Core::audit('edition',$edition_id,'reading_room_anchor_provider_failed',array('page'=>$page,'provider_failure'=>1));return PLDR_Core::machine_error('pldr_room_anchor_provider','Reading-room anchor validation is temporarily unavailable; the room was not created.',503,array('degraded'=>true,'provider_failure'=>true));}
    }

    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
