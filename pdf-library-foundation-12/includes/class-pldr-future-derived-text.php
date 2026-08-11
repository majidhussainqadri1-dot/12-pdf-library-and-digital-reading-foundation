<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Derived_Text {
    private const MAX_PROVIDER_CALLS_PER_HOUR = 120;

    public static function derive(int $edition_id,int $page,string $text,string $mode,string $target):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        $page=absint($page);
        if($page<1||$page>(int)$edition['pages'])return array('error'=>PLDR_Core::machine_error('pldr_derive_page','Derived text must be bound to a valid page in this edition.',400));
        $doc=PLDR_Core::document((int)$edition['document_id']);
        if($doc && 'patient-cases'===$doc['category'] && !apply_filters('pldr_derived_text_patient_case_allowed',false,$edition_id,$doc))return array('error'=>PLDR_Core::machine_error('pldr_derive_patient_case','Patient-case text is not sent to translation/transliteration providers without separate privacy approval.',403));
        $mode=sanitize_key($mode);
        if(!in_array($mode,array('translate','transliterate'),true))return array('error'=>PLDR_Core::machine_error('pldr_derive_mode','Use translate or transliterate mode.',400));
        $text=self::limit(trim(wp_strip_all_tags($text)),5000);
        if(''===$text)return array('error'=>PLDR_Core::machine_error('pldr_derive_text','Text selection is required.',400));
        $target=self::limit(sanitize_text_field($target),60);
        if(''===$target)return array('error'=>PLDR_Core::machine_error('pldr_derive_target','Target language or script is required.',400));
        if(!self::selection_belongs($edition_id,$page,$text,$edition))return array('error'=>PLDR_Core::machine_error('pldr_derive_selection','The selected text could not be verified against the requested document page.',403));
        $rate=self::consume_rate_slot($edition_id);
        if(is_wp_error($rate))return array('error'=>$rate);
        $filter='translate'===$mode?'pldr_translate_text':'pldr_transliterate_text';
        try{
            $result=apply_filters($filter,null,$text,array('edition_id'=>$edition_id,'page'=>$page,'source_language'=>$edition['language'],'target_language'=>$target));
        }catch(Throwable $e){
            return array('error'=>PLDR_Core::machine_error('pldr_derive_provider','The approved translation/transliteration provider failed; no derived text was substituted.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        if(!is_array($result)||empty($result['text']))return array('error'=>PLDR_Core::machine_error('pldr_derive_provider','No approved translation/transliteration provider is configured.',503,array('degraded'=>true)));
        $provider=self::limit(sanitize_text_field((string)($result['provider']??'')),80);
        if(''===$provider)return array('error'=>PLDR_Core::machine_error('pldr_derive_provenance','Derived text provider identity is required; anonymous provider output was rejected.',502,array('degraded'=>true)));
        $derived=self::limit(wp_strip_all_tags((string)$result['text']),10000);
        if(''===trim($derived))return array('error'=>PLDR_Core::machine_error('pldr_derive_provider','The approved provider returned no usable derived text.',502));
        return array('mode'=>$mode,'text'=>$derived,'provider'=>$provider,'source_language'=>self::limit(sanitize_text_field((string)$edition['language']),35),'target_language'=>$target,'derived'=>true,'not_authorial_text'=>true,'original_unchanged'=>true,'source_bound'=>true,'provider_generated'=>true,'provider_rate_limited'=>true);
    }

    private static function consume_rate_slot(int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        $ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
        $identity=$uid>0?'u:'.$uid:'a:'.hash('sha256',$ip.'|'.wp_salt('auth'));
        $bucket='pldr_derive_rate_'.substr(hash('sha256',$identity.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_derive_'.substr(hash('sha256',$identity),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(1!==$locked)return PLDR_Core::machine_error('pldr_derive_rate_lock','Derived-text provider capacity is temporarily unavailable; retry shortly.',503,array('retry_after'=>2));
        try{
            $count=(int)get_transient($bucket);
            $limit=(int)apply_filters('pldr_derived_text_hourly_limit',self::MAX_PROVIDER_CALLS_PER_HOUR,$uid,$edition_id);
            $limit=max(10,min(1000,$limit));
            if($count>=$limit)return PLDR_Core::machine_error('pldr_derive_rate_limit','Derived-text provider requests are temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return PLDR_Core::machine_error('pldr_derive_rate_store','Derived-text rate-limit state could not be stored; the provider call was not executed.',503);
            return true;
        }finally{
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));
        }
    }

    private static function selection_belongs(int $edition_id,int $page,string $text,array $edition):bool {
        $needle=PLDR_Core::normalize_search($text);
        if(''===$needle)return false;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if($rows){
            $haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));
            if(''!==$haystack&&false!==strpos($haystack,$needle))return true;
        }
        return (bool)apply_filters('pldr_derived_text_selection_allowed',false,$edition_id,$page,$text,$edition);
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
