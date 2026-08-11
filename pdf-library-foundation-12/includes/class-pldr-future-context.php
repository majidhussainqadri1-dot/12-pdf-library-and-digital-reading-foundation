<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Context {
    private const PROVIDER_INPUT_LIMIT = 100;
    private const RESULT_LIMIT = 20;
    private const MAX_PROVIDER_CALLS_PER_HOUR = 240;
    private const EXPECTED_OWNERS = array('File 05','File 06','File 16');

    public static function lookup(int $edition_id,string $selection,int $page=0):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        $page=absint($page);
        if($page<1||$page>(int)$edition['pages'])return array('error'=>PLDR_Core::machine_error('pldr_context_page','Knowledge context must be bound to a valid page.',400));
        $selection=self::limit(trim(wp_strip_all_tags($selection)),1200);
        if(''===$selection)return array('items'=>array(),'source_bound'=>true);
        if(!self::selection_belongs($edition_id,$page,$selection,$edition))return array('error'=>PLDR_Core::machine_error('pldr_context_selection','The selected text could not be verified against this document page.',403));
        $rate=self::consume_rate_slot($edition_id);
        if(is_wp_error($rate))return array('error'=>$rate);
        $context=array('edition_id'=>$edition_id,'document_id'=>$edition['public_id'],'page'=>$page,'selection'=>$selection);
        try{$items=apply_filters('pldr_knowledge_context',array(),$context);}catch(Throwable $e){return array('error'=>PLDR_Core::machine_error('pldr_context_provider','Knowledge-context provider failed; no companion data was invented or copied.',503,array('degraded'=>true,'provider_failure'=>true)));}
        if(!is_array($items))return array('error'=>PLDR_Core::machine_error('pldr_context_provider','Knowledge-context provider returned an invalid response.',502,array('degraded'=>true)));
        $input_total=count($items);$safe=array();$provenance_rejected=0;
        foreach(array_slice(array_values($items),0,self::PROVIDER_INPUT_LIMIT) as $item){
            if(!is_array($item)||empty($item['url'])||empty($item['title']))continue;
            $owner=self::limit(sanitize_text_field((string)($item['owner']??'')),80);
            if(''===$owner||!in_array($owner,self::EXPECTED_OWNERS,true)||true!==($item['canonical']??false)){$provenance_rejected++;continue;}
            $url=esc_url_raw((string)$item['url']);if(''===$url)continue;
            $safe[]=array('owner'=>$owner,'title'=>self::limit(sanitize_text_field((string)$item['title']),180),'url'=>$url,'summary'=>self::limit(sanitize_text_field((string)($item['summary']??'')),500),'canonical'=>true);
            if(count($safe)>=self::RESULT_LIMIT)break;
        }
        return array('items'=>$safe,'selection'=>$selection,'copied_domain_data'=>false,'expected_owners'=>self::EXPECTED_OWNERS,'source_bound'=>true,'provider_input_total'=>$input_total,'provider_input_limit'=>self::PROVIDER_INPUT_LIMIT,'result_limit'=>self::RESULT_LIMIT,'provenance_rejected'=>$provenance_rejected,'provider_rate_limited'=>true,'truncated'=>$input_total>self::PROVIDER_INPUT_LIMIT||count($safe)>=self::RESULT_LIMIT);
    }

    private static function consume_rate_slot(int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();$ip=sanitize_text_field((string)($_SERVER['REMOTE_ADDR']??'unknown'));
        $identity=$uid>0?'u:'.$uid:'a:'.hash('sha256',$ip.'|'.wp_salt('auth'));
        $bucket='pldr_context_rate_'.substr(hash('sha256',$identity.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_context_'.substr(hash('sha256',$identity),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(1!==$locked)return PLDR_Core::machine_error('pldr_context_rate_lock','Knowledge-context provider capacity is temporarily unavailable; retry shortly.',503,array('retry_after'=>2));
        try{
            $count=(int)get_transient($bucket);
            try{$limit=(int)apply_filters('pldr_context_hourly_limit',self::MAX_PROVIDER_CALLS_PER_HOUR,$uid,$edition_id);}catch(Throwable $e){
                PLDR_Core::audit('edition',$edition_id,'context_rate_policy_provider_failed',array('provider_failure'=>true));
                return PLDR_Core::machine_error('pldr_context_rate_policy','Knowledge-context rate policy could not be verified; the provider call was not executed.',503,array('degraded'=>true));
            }
            $limit=max(20,min(2000,$limit));
            if($count>=$limit)return PLDR_Core::machine_error('pldr_context_rate_limit','Knowledge-context provider requests are temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return PLDR_Core::machine_error('pldr_context_rate_store','Knowledge-context rate-limit state could not be stored; the provider call was not executed.',503);
            return true;
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }

    private static function selection_belongs(int $edition_id,int $page,string $selection,array $edition):bool {
        $needle=PLDR_Core::normalize_search($selection);if(''===$needle)return false;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        foreach($rows as $row){$haystack=PLDR_Core::normalize_search((string)($row['text_content']??''));if(''!==$haystack&&false!==strpos($haystack,$needle))return true;}
        try{return (bool)apply_filters('pldr_knowledge_context_selection_allowed',false,$edition_id,$page,$selection,$edition);}catch(Throwable $e){
            PLDR_Core::audit('edition',$edition_id,'context_selection_provider_failed',array('page'=>$page,'provider_failure'=>true));
            return false;
        }
    }

    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
