<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Handoff {
    public static function get(int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_handoff_login','Log in to synchronize reading sessions.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return $edition;
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('session_handoffs').' WHERE user_id=%d AND edition_id=%d',$uid,$edition_id),ARRAY_A);
        return $row?self::dto($row):array();
    }

    public static function save(int $edition_id,array $context,int $expected=0) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_handoff_login','Log in to synchronize reading sessions.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $current=self::get($edition_id);if(is_wp_error($current))return $current;
        if($current && $expected<1)return PLDR_Core::machine_error('pldr_handoff_precondition','expected_version is required when updating an existing cross-device reading session.',428,array('current'=>$current));
        if(!$current && $expected>0)return PLDR_Core::machine_error('pldr_handoff_conflict','No existing reading session matches the supplied expected_version.',409,array('current'=>array()));
        if($current&&(int)$current['version']!==$expected)return PLDR_Core::machine_error('pldr_handoff_conflict','A newer reading session exists on another device.',409,array('current'=>$current));
        $page=max(1,min((int)$edition['pages'],absint($context['page']??1)));
        $layout=sanitize_key((string)($context['layout']??'single'));
        if(!in_array($layout,array('single','continuous','spread-ltr','spread-rtl','horizontal','presentation'),true))$layout='single';
        $zoom=self::limit(sanitize_text_field((string)($context['zoom']??'page-width')),30);
        $anchor=self::anchor((array)($context['anchor']??array()));
        $anchor_json=wp_json_encode($anchor,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
        if(false===$anchor_json||strlen($anchor_json)>1600)return PLDR_Core::machine_error('pldr_handoff_anchor','Reading-session anchor is too large.',400);
        $version=max(1,(int)($current['version']??0)+1);
        $row=array('user_id'=>$uid,'edition_id'=>$edition_id,'page_number'=>$page,'zoom'=>$zoom,'layout_mode'=>$layout,'anchor_json'=>$anchor_json,'device_hint'=>self::limit(sanitize_text_field((string)($context['device']??'')),80),'version'=>$version,'updated_at'=>PLDR_Core::now());
        if($current){
            $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('session_handoffs').' SET page_number=%d,zoom=%s,layout_mode=%s,anchor_json=%s,device_hint=%s,version=%d,updated_at=%s WHERE user_id=%d AND edition_id=%d AND version=%d',$row['page_number'],$row['zoom'],$row['layout_mode'],$row['anchor_json'],$row['device_hint'],$version,$row['updated_at'],$uid,$edition_id,$expected));
            if(false===$updated)return PLDR_Core::machine_error('pldr_handoff_store','Reading-session handoff could not be stored.',500);
            if(1!==$updated){$fresh=self::get($edition_id);if(is_wp_error($fresh))return $fresh;return PLDR_Core::machine_error('pldr_handoff_conflict','Concurrent reading-session handoff update detected.',409,array('current'=>$fresh));}
        }else{
            $inserted=$wpdb->insert(PLDR_Core::table('session_handoffs'),$row);
            if(false===$inserted){
                $race=self::get($edition_id);
                if(is_wp_error($race))return $race;
                if(is_array($race)&&$race)return PLDR_Core::machine_error('pldr_handoff_conflict','Reading-session handoff was created concurrently on another request.',409,array('current'=>$race));
                return PLDR_Core::machine_error('pldr_handoff_store','Reading-session handoff could not be stored.',500);
            }
        }
        return self::dto($row);
    }

    private static function dto(array $row) {
        $raw=(string)($row['anchor_json']??'');
        $anchor=json_decode($raw,true);
        if(''!==trim($raw)&&!is_array($anchor)){
            PLDR_Core::audit('edition',(int)($row['edition_id']??0),'handoff_anchor_corrupt',array('user_id'=>(int)($row['user_id']??0),'version'=>(int)($row['version']??0)));
            return PLDR_Core::machine_error('pldr_handoff_corrupt','Stored cross-device reading anchor failed integrity validation and was not silently discarded.',500,array('version'=>(int)($row['version']??0)));
        }
        return array(
            'edition_id'=>(int)($row['edition_id']??0),
            'page'=>(int)($row['page_number']??1),
            'zoom'=>(string)($row['zoom']??'page-width'),
            'layout'=>(string)($row['layout_mode']??'single'),
            'anchor'=>is_array($anchor)?$anchor:array(),
            'device'=>(string)($row['device_hint']??''),
            'version'=>(int)($row['version']??0),
            'updated_at'=>$row['updated_at']??null,
        );
    }

    private static function anchor(array $anchor):array {
        $out=array();
        if(isset($anchor['selection']))$out['selection']=self::limit(wp_strip_all_tags((string)$anchor['selection']),500);
        if(isset($anchor['type'])){$type=sanitize_text_field((string)$anchor['type']);if(in_array($type,array('TextQuoteSelector','FragmentSelector','SvgSelector','CssSelector'),true))$out['type']=$type;}
        if(isset($anchor['exact']))$out['exact']=self::limit(wp_strip_all_tags((string)$anchor['exact']),500);
        return $out;
    }

    private static function limit(string $value,int $length):string { return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length); }
}
