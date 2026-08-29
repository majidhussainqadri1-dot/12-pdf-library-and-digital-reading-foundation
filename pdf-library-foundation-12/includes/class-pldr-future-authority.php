<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Authority {
    private const MAX_PROVIDER_CALLS_PER_HOUR = 120;
    public static function lookup(string $type, string $value, bool $force = false) {
        global $wpdb;
        $type = sanitize_key($type); $value = trim(sanitize_text_field($value)); $value = function_exists('mb_substr') ? mb_substr($value,0,190,'UTF-8') : substr($value,0,190);
        if (!in_array($type, array('doi','orcid','isbn'), true) || '' === $value) return PLDR_Core::machine_error('pldr_authority_identifier', 'Use a DOI, ORCID or ISBN identifier.', 400);
        if (!PLDR_Core::authorize('manage') && !PLDR_Core::authorize('publish')) return PLDR_Core::machine_error('pldr_authority_forbidden', 'Bibliographic enrichment requires publishing authority.', 403);
        if (!$force) {
            $wpdb->last_error='';
            $row = $wpdb->get_row($wpdb->prepare('SELECT * FROM ' . PLDR_Core::table('authority_cache') . ' WHERE identifier_type=%s AND identifier_value=%s AND expires_at>%s ORDER BY updated_at DESC LIMIT 1', $type, $value, PLDR_Core::now()), ARRAY_A);
            if ('' !== (string)$wpdb->last_error) {
                PLDR_Core::audit('authority',0,'authority_cache_read_failed',array('identifier_type'=>$type));
                return PLDR_Core::machine_error('pldr_authority_cache_read','Bibliographic authority cache state could not be verified; no external provider request was made.',503,array('degraded'=>true));
            }
            if ($row) {
                $data=json_decode((string)$row['result_json'],true);$provenance=json_decode((string)$row['provenance_json'],true);
                if(is_array($data)&&is_array($provenance))return array('status' => 'cached', 'provider' => $row['provider'], 'result' => $data, 'provenance' => $provenance, 'canonical_overwrite' => false);
                $deleted=$wpdb->delete(PLDR_Core::table('authority_cache'),array('id'=>(int)$row['id']),array('%d'));
                if(false===$deleted)return PLDR_Core::machine_error('pldr_authority_cache_repair','Corrupt bibliographic cache evidence could not be removed safely; provider refresh was not attempted.',503,array('degraded'=>true));
                PLDR_Core::audit('authority',(int)$row['id'],'corrupt_cache_discarded',array('identifier_type'=>$type));
            }
        }
        $slot=self::consume_provider_slot($type);
        if(is_wp_error($slot))return $slot;
        try{
            $result = apply_filters('pldr_authority_lookup', null, $type, $value);
        }catch(Throwable $e){
            return PLDR_Core::machine_error('pldr_authority_provider', 'Bibliographic authority provider failed; canonical metadata was left unchanged.', 503, array('degraded' => true, 'provider_failure'=>true));
        }
        if (!is_array($result) || empty($result['data'])) return PLDR_Core::machine_error('pldr_authority_provider', 'No bibliographic authority provider is configured for this identifier.', 503, array('degraded' => true));
        $provider = sanitize_text_field((string) ($result['provider'] ?? '')); $provider = function_exists('mb_substr') ? mb_substr($provider,0,80,'UTF-8') : substr($provider,0,80);
        if ('' === trim($provider)) {
            PLDR_Core::audit('authority', 0, 'anonymous_provider_rejected', array('identifier_type'=>$type));
            return PLDR_Core::machine_error('pldr_authority_provenance', 'Bibliographic authority output was rejected because provider provenance was missing.', 502, array('degraded'=>true,'provenance_missing'=>true));
        }
        $encoded = wp_json_encode($result['data']);
        if (!is_string($encoded) || strlen($encoded) > 524288) return PLDR_Core::machine_error('pldr_authority_payload', 'Bibliographic authority provider response exceeded the governed payload limit.', 502);
        $provenance = array('provider' => $provider, 'retrieved_at' => PLDR_Core::now(), 'identifier_type' => $type, 'identifier_value' => $value, 'external_enrichment' => true, 'canonical_overwrite' => false);
        $provenance_json=wp_json_encode($provenance);
        if(!is_string($provenance_json))return PLDR_Core::machine_error('pldr_authority_payload','Bibliographic authority provenance could not be encoded safely.',502);
        $stored=$wpdb->replace(PLDR_Core::table('authority_cache'), array('identifier_type'=>$type,'identifier_value'=>$value,'provider'=>$provider,'result_json'=>$encoded,'provenance_json'=>$provenance_json,'expires_at'=>gmdate('Y-m-d H:i:s', time()+30*DAY_IN_SECONDS),'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return PLDR_Core::machine_error('pldr_authority_cache_store','Bibliographic authority provenance could not be persisted.',500);
        PLDR_Core::audit('authority', 0, 'external_enrichment_cached', array('identifier_type'=>$type,'provider'=>$provider));
        return array('status' => 'fresh', 'provider' => $provider, 'result' => $result['data'], 'provenance' => $provenance, 'canonical_overwrite' => false);
    }

    private static function consume_provider_slot(string $type) {
        global $wpdb;
        $uid=get_current_user_id();
        $identity=$uid>0?'u:'.$uid:'a:'.hash('sha256',(string)($_SERVER['REMOTE_ADDR']??'unknown').'|'.wp_salt('auth'));
        $bucket='pldr_authority_rate_'.substr(hash('sha256',$identity.'|'.gmdate('YmdH')),0,32);
        $lock='pldr_authority_'.substr(hash('sha256',$identity),0,32);
        $locked=(int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,1)',$lock));
        if(1!==$locked)return PLDR_Core::machine_error('pldr_authority_rate_lock','Bibliographic authority capacity is temporarily unavailable; retry shortly.',503,array('retry_after'=>2));
        try{
            $count=(int)get_transient($bucket);
            try{$limit=(int)apply_filters('pldr_authority_hourly_limit',self::MAX_PROVIDER_CALLS_PER_HOUR,$uid,$type);}catch(Throwable $e){
                PLDR_Core::audit('authority',0,'rate_policy_provider_failed',array('identifier_type'=>$type));
                return PLDR_Core::machine_error('pldr_authority_rate_policy','Bibliographic authority rate policy could not be verified; no provider request was made.',503,array('degraded'=>true));
            }
            $limit=max(10,min(1000,$limit));
            if($count>=$limit)return PLDR_Core::machine_error('pldr_authority_rate_limit','Bibliographic authority requests are temporarily rate limited.',429,array('retry_after'=>60,'hourly_limit'=>$limit));
            if(!set_transient($bucket,$count+1,HOUR_IN_SECONDS+120))return PLDR_Core::machine_error('pldr_authority_rate_store','Bibliographic authority rate state could not be stored; no provider request was made.',503);
            return true;
        }finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }
}
