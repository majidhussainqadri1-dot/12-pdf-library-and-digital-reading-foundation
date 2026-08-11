<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Rooms {
    public static function create(int $edition_id,int $page,array $anchor=array()) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_room_login','Log in to create a scholarly reading room.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $doc=PLDR_Core::document((int)$edition['document_id']);
        if($doc && 'patient-cases'===$doc['category'] && !apply_filters('pldr_patient_case_reading_room_allowed',false,$edition_id,$doc))return PLDR_Core::machine_error('pldr_room_patient_case','Patient-case documents require separate privacy approval before a reading room can be created.',403);
        $page=max(1,min((int)$edition['pages'],$page));
        $anchor=self::anchor($anchor);
        if(!self::anchor_belongs($edition_id,$page,$anchor,$edition))return PLDR_Core::machine_error('pldr_room_anchor_source','Reading-room text anchors must match the requested document page.',403);
        $encoded=wp_json_encode($anchor,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(false===$encoded||strlen($encoded)>1600)return PLDR_Core::machine_error('pldr_room_anchor','Reading-room anchor is too large.',400);
        $room_key=PLDR_Core::uuid();
        $now=PLDR_Core::now();
        $stored=$wpdb->insert(PLDR_Core::table('room_contexts'),array('room_key'=>$room_key,'edition_id'=>$edition_id,'created_by'=>$uid,'page_number'=>$page,'anchor_json'=>$encoded,'provider_ref'=>'','status'=>'pending-provider','created_at'=>$now,'updated_at'=>$now));
        if(false===$stored)return PLDR_Core::machine_error('pldr_room_store','Reading-room context could not be stored.',500);
        $context=array('file'=>12,'room_key'=>$room_key,'edition_id'=>$edition_id,'document_id'=>$edition['public_id'],'page'=>$page,'anchor'=>$anchor,'source_url'=>PLDR_Core::route_url('read',array('id'=>$edition['public_id'])));
        $provider=apply_filters('pldr_create_reading_room_provider',null,$uid,$context);
        $provider_ref=is_array($provider)?self::limit(sanitize_text_field((string)($provider['reference']??'')),190):'';
        $status=$provider_ref?'active':'pending-provider';
        if($provider_ref){
            $updated=$wpdb->update(PLDR_Core::table('room_contexts'),array('provider_ref'=>$provider_ref,'status'=>'active','updated_at'=>PLDR_Core::now()),array('room_key'=>$room_key,'created_by'=>$uid));
            if(1!==$updated)return PLDR_Core::machine_error('pldr_room_provider_store','Reading-room provider reference could not be persisted; the local pending context remains for reconciliation.',500,array('room_key'=>$room_key));
        }
        PLDR_Core::emit('PDFReadingRoomRequested.v1','edition',$edition_id,array('room_key'=>$room_key,'document_id'=>$edition['public_id'],'page'=>$page,'provider_ref'=>$provider_ref));
        return array('room_key'=>$room_key,'status'=>$status,'provider_ref'=>$provider_ref,'source_bound'=>true,'messaging_owner'=>'File 17 / shared communication contract','file_12_owns_only_anchor_context'=>true);
    }

    private static function anchor(array $anchor):array {
        $out=array();
        $type=sanitize_text_field((string)($anchor['type']??''));
        if(in_array($type,array('TextQuoteSelector','FragmentSelector','SvgSelector','CssSelector'),true))$out['type']=$type;
        foreach(array('exact'=>500,'prefix'=>120,'suffix'=>120,'selection'=>500) as $key=>$limit){if(isset($anchor[$key]))$out[$key]=self::limit(wp_strip_all_tags((string)$anchor[$key]),$limit);}
        if(isset($anchor['region'])&&is_array($anchor['region']))$out['region']=array('x'=>max(0,min(1,(float)($anchor['region']['x']??0))),'y'=>max(0,min(1,(float)($anchor['region']['y']??0))),'w'=>max(0,min(1,(float)($anchor['region']['w']??0))),'h'=>max(0,min(1,(float)($anchor['region']['h']??0))));
        return $out;
    }

    private static function anchor_belongs(int $edition_id,int $page,array $anchor,array $edition):bool {
        $texts=array();
        foreach(array('exact','selection') as $key){if(!empty($anchor[$key]))$texts[]=PLDR_Core::normalize_search((string)$anchor[$key]);}
        $texts=array_values(array_filter($texts,static fn(string $value):bool=>''!==$value));
        if(!$texts)return true;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if($rows){
            $haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));
            if(''!==$haystack){
                foreach($texts as $needle){if(false===strpos($haystack,$needle))return (bool)apply_filters('pldr_reading_room_anchor_allowed',false,$edition_id,$page,$anchor,$edition);}
                return true;
            }
        }
        return (bool)apply_filters('pldr_reading_room_anchor_allowed',false,$edition_id,$page,$anchor,$edition);
    }

    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
