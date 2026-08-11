<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Preferences {
    private const KEYS = array('reader');

    public static function get(string $key='reader'):array {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return array();
        $key=self::key($key);
        if(is_wp_error($key))return array();
        $row=$wpdb->get_row($wpdb->prepare('SELECT preference_json,version,updated_at FROM '.PLDR_Core::table('future_prefs').' WHERE user_id=%d AND preference_key=%s',$uid,$key),ARRAY_A);
        return $row?array('value'=>json_decode((string)$row['preference_json'],true)?:array(),'version'=>(int)$row['version'],'updated_at'=>$row['updated_at']):array('value'=>array(),'version'=>0);
    }

    public static function save(string $key,array $value,int $expected=0) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_future_pref_login','Log in to synchronize advanced reading preferences.',401);
        $key=self::key($key);
        if(is_wp_error($key))return $key;
        $current=self::get($key);
        if($expected&&(int)$current['version']!==$expected)return PLDR_Core::machine_error('pldr_future_pref_conflict','Reading preferences changed on another device.',409,array('current_version'=>(int)$current['version']));
        $clean=self::sanitize($value);
        $version=max(1,(int)$current['version']+1);
        $now=PLDR_Core::now();
        if((int)$current['version']>0){
            $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('future_prefs').' SET preference_json=%s,version=%d,updated_at=%s WHERE user_id=%d AND preference_key=%s AND version=%d',wp_json_encode($clean),$version,$now,$uid,$key,(int)$current['version']));
            if(false===$updated)return PLDR_Core::machine_error('pldr_future_pref_store','Reading preferences could not be stored.',500);
            if(1!==$updated)return PLDR_Core::machine_error('pldr_future_pref_conflict','Concurrent reading-preference update detected.',409,array('current_version'=>(int)(self::get($key)['version']??0)));
        }else{
            $inserted=$wpdb->insert(PLDR_Core::table('future_prefs'),array('user_id'=>$uid,'preference_key'=>$key,'preference_json'=>wp_json_encode($clean),'version'=>$version,'updated_at'=>$now));
            if(false===$inserted){
                $race=self::get($key);
                if((int)($race['version']??0)>0)return PLDR_Core::machine_error('pldr_future_pref_conflict','Reading preferences were created concurrently on another request.',409,array('current_version'=>(int)$race['version']));
                return PLDR_Core::machine_error('pldr_future_pref_store','Reading preferences could not be stored.',500);
            }
        }
        return array('value'=>$clean,'version'=>$version,'updated_at'=>$now);
    }

    private static function key(string $key) {
        $key=sanitize_key($key);
        if(!in_array($key,self::KEYS,true))return PLDR_Core::machine_error('pldr_future_pref_key','Unsupported advanced-reading preference key.',400);
        return $key;
    }

    private static function sanitize(array $value):array {
        $out=array();
        if(array_key_exists('layout',$value)){
            $layout=sanitize_key((string)$value['layout']);
            if(in_array($layout,array('single','continuous','spread-ltr','spread-rtl','horizontal','presentation'),true))$out['layout']=$layout;
        }
        if(array_key_exists('text_size',$value))$out['text_size']=max(90,min(180,(int)$value['text_size']));
        if(array_key_exists('line_height',$value))$out['line_height']=max(140,min(240,(int)$value['line_height']));
        if(array_key_exists('column_width',$value))$out['column_width']=max(45,min(100,(int)$value['column_width']));
        if(array_key_exists('contrast',$value)){
            $contrast=sanitize_key((string)$value['contrast']);
            if(in_array($contrast,array('default','high','soft','dark'),true))$out['contrast']=$contrast;
        }
        if(array_key_exists('data_saver',$value))$out['data_saver']=(bool)$value['data_saver'];
        if(array_key_exists('tts_rate',$value))$out['tts_rate']=round(max(0.5,min(2.0,(float)$value['tts_rate'])),2);
        if(array_key_exists('tts_voice',$value))$out['tts_voice']=self::limit(sanitize_text_field((string)$value['tts_voice']),120);
        if(array_key_exists('language',$value))$out['language']=self::limit(sanitize_text_field((string)$value['language']),35);
        return $out;
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
