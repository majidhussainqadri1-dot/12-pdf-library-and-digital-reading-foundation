<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Search {
    private const MAX_REQUESTS_PER_HOUR = 180;

    public static function heatmap(int $edition_id,string $query):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);
        $needle=PLDR_Core::normalize_search($query);
        $len=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);
        if($len<2)return array('error'=>PLDR_Core::machine_error('pldr_heatmap_query','Search heatmap query is too short.',400));
        if($len>160)return array('error'=>PLDR_Core::machine_error('pldr_heatmap_query_long','Search heatmap query is too long.',400));
        try {
            $scan_limit=(int)apply_filters('pldr_heatmap_page_scan_limit',5000,$edition_id,$edition);
            $result_limit=(int)apply_filters('pldr_heatmap_result_page_limit',1000,$edition_id,$edition);
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'heatmap_limit_policy_provider_failed',array('provider_failure'=>1));
            return array('error'=>PLDR_Core::machine_error('pldr_heatmap_limit_policy','Search-heatmap scan policy is temporarily unavailable; the scan was not executed.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        $scan_limit=max(100,min(10000,$scan_limit));
        $result_limit=max(50,min(2000,$result_limit));
        $rate=self::consume_rate_slot($edition_id);
        if(is_wp_error($rate))return array('error'=>$rate);
        $items=array();$total=0;$scanned=0;$offset=0;$batch=250;$result_capped=false;$source_exhausted=false;
        while($scanned<$scan_limit){
            $take=min($batch,$scan_limit-$scanned);
            $rows=PLDR_Future_Data::ocr_pages($edition_id,0,$take,$offset);
            if(!$rows){$source_exhausted=true;break;}
            $count=count($rows);
            foreach($rows as $row){
                $text=(string)$row['normalized_text'];$matches=substr_count($text,$needle);
                if($matches){$total+=$matches;if(count($items)<$result_limit)$items[]=array('page'=>(int)$row['page_number'],'matches'=>$matches);else$result_capped=true;}
            }
            $scanned+=$count;$offset+=$count;
            if($count<$take){$source_exhausted=true;break;}
        }
        $edition_pages=max(0,(int)($edition['pages']??0));
        $scan_capped=!$source_exhausted&&$scanned>=$scan_limit&&($edition_pages===0||$scanned<$edition_pages);
        $truncated=$scan_capped||$result_capped;
        return array('edition_id'=>$edition_id,'query'=>self::limit($query,160),'matches'=>$total,'pages'=>$items,'pages_scanned'=>$scanned,'scan_page_limit'=>$scan_limit,'result_page_limit'=>$result_limit,'result_pages_returned'=>count($items),'scan_truncated'=>$scan_capped,'results_truncated'=>$result_capped,'truncated'=>$truncated,'entitlement_filtered'=>true,'request_rate_limited'=>true,'hourly_request_limit'=>self::MAX_REQUESTS_PER_HOUR);
    }

    private static function consume_rate_slot(int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        $ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
        $identity=$uid>0?'u:'.$uid:'a:'.hash('sha256',$ip.'|'.wp_salt('auth'));
        $bucket='pldr_heatmap_rate_'.substr(hash('sha256',$identity.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_heatmap_'.substr(hash('sha256',$identity),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(1!==$locked)return PLDR_Core::machine_error('pldr_heatmap_rate_lock','Search-heatmap capacity is temporarily unavailable; retry shortly.',503,array('retry_after'=>2));
        try {
            $count=(int)get_transient($bucket);
            if($count>=self::MAX_REQUESTS_PER_HOUR)return PLDR_Core::machine_error('pldr_heatmap_rate_limit','Search-heatmap requests are temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>self::MAX_REQUESTS_PER_HOUR));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return PLDR_Core::machine_error('pldr_heatmap_rate_store','Search-heatmap rate state could not be stored; the expensive scan was not executed.',503);
            return true;
        } finally {
            $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));
        }
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
