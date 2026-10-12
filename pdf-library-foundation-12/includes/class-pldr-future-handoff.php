<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Handoff {
    public static function get(int $edition_id) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_handoff_login','Log in to synchronize reading sessions.',401);
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return $edition;
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('session_handoffs').' WHERE user_id=%d AND edition_id=%d',$uid,$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'handoff_read_failed',array('user_id'=>$uid));
            return PLDR_Core::machine_error('pldr_handoff_read','Cross-device reading-session state could not be read reliably.',503,array('degraded'=>true));
        }
        return $row?self::dto($row):array();
    }

    public static function save(int $edition_id,array $context,int $expected=0) {
        global $wpdb;
        $uid=get_current_user_id();
        if(!$uid)return PLDR_Core::machine_error('pldr_handoff_login','Log in to synchronize reading sessions.',401);
        $input=self::validate_handoff_input($context);
        if(is_wp_error($input))return $input;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        if($input['page']>(int)$edition['pages'])return PLDR_Core::machine_error('pldr_handoff_page','Reading-session handoff page exceeds this edition.',400);
        $current=self::get($edition_id);if(is_wp_error($current))return $current;
        if($current && $expected<1)return PLDR_Core::machine_error('pldr_handoff_precondition','expected_version is required when updating an existing cross-device reading session.',428,array('current'=>$current));
        if(!$current && $expected>0)return PLDR_Core::machine_error('pldr_handoff_conflict','No existing reading session matches the supplied expected_version.',409,array('current'=>array()));
        if($current&&(int)$current['version']!==$expected)return PLDR_Core::machine_error('pldr_handoff_conflict','A newer reading session exists on another device.',409,array('current'=>$current));
        $page=$input['page'];
        $layout=sanitize_key((string)($context['layout']??'single'));
        if(!in_array($layout,array('single','continuous','spread-ltr','spread-rtl','horizontal','presentation'),true))$layout='single';
        $zoom=self::limit(sanitize_text_field((string)($context['zoom']??'page-width')),30);
        $anchor=self::anchor((array)($context['anchor']??array()),$page);
        if(is_wp_error($anchor))return $anchor;
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

    /**
     * FUT-017: preserve cross-device page/anchor identity. Never convert
     * negative, fractional or structured pages into a different page before
     * owner and optimistic-concurrency reads.
     */
    private static function validate_handoff_input(array $context) {
        $value=$context['page']??1;
        if(is_int($value) && $value>=1)$page=$value;
        elseif(is_string($value) && preg_match('/^[1-9][0-9]*$/D',$value)
            && strlen($value)<=strlen((string)PHP_INT_MAX)
            && (string)(int)$value===$value)$page=(int)$value;
        else return PLDR_Core::machine_error('pldr_handoff_page_shape',
            'Reading-session page must be a positive integer without coercion.',400);
        foreach(array('layout','zoom','device') as $key){
            if(array_key_exists($key,$context)&&!is_string($context[$key]))
                return PLDR_Core::machine_error('pldr_handoff_text_shape',
                    'Reading-session text options must be strings.',400,array('field'=>$key));
        }
        if(array_key_exists('anchor',$context) && !is_array($context['anchor']))
            return PLDR_Core::machine_error('pldr_handoff_anchor_shape',
                'Reading-session anchor must be a structured array.',400);
        return array('page'=>$page);
    }

    /**
     * Region coordinates are normalized 0..1 real numbers, not arbitrary
     * payloads that PHP could silently cast to zero/one or clamp.
     */
    private static function valid_region_coordinate($value): bool {
        if(is_bool($value)||is_array($value)||is_object($value)||null===$value)return false;
        if(is_int($value)||is_float($value))$n=(float)$value;
        elseif(is_string($value) && preg_match('/^(?:0(?:\\.[0-9]{1,9})?|1(?:\\.0{1,9})?)$/D',$value))$n=(float)$value;
        else return false;
        return is_finite($n)&&$n>=0.0&&$n<=1.0;
    }

    private static function anchor(array $anchor,int $page) {
        foreach(array('selection','type','exact','prefix','suffix','value') as $key){
            if(array_key_exists($key,$anchor)&&!is_string($anchor[$key]))
                return PLDR_Core::machine_error('pldr_handoff_anchor_text_shape',
                    'Reading-session selector fields must be strings.',400,array('field'=>$key));
        }
        $out=array();
        if(isset($anchor['selection']))$out['selection']=self::limit(wp_strip_all_tags((string)$anchor['selection']),500);
        $type='';
        if(isset($anchor['type'])){
            $type=sanitize_text_field((string)$anchor['type']);
            $allowed=array('TextQuoteSelector','FragmentSelector','CssSelector');
            if('SvgSelector'===$type)return PLDR_Core::machine_error('pldr_handoff_svg_unsupported','SVG handoff selectors are rejected until a lossless, security-reviewed SVG selector representation is available.',400,array('supported_selector_types'=>$allowed));
            if(!in_array($type,$allowed,true))return PLDR_Core::machine_error('pldr_handoff_selector','Unsupported reading-session handoff selector.',400,array('supported_selector_types'=>$allowed));
            $out['type']=$type;
        }
        foreach(array('exact'=>500,'prefix'=>120,'suffix'=>120,'value'=>300) as $key=>$max){if(isset($anchor[$key]))$out[$key]=self::limit(wp_strip_all_tags((string)$anchor[$key]),$max);}
        if('TextQuoteSelector'===$type&&''===trim((string)($out['exact']??'')))return PLDR_Core::machine_error('pldr_handoff_exact','A text-quote handoff selector requires an exact excerpt.',400);
        if(in_array($type,array('FragmentSelector','CssSelector'),true)&&''===trim((string)($out['value']??''))&&!isset($anchor['region']))return PLDR_Core::machine_error('pldr_handoff_selector_value','This handoff selector requires a bounded selector value or region.',400);
        if('FragmentSelector'===$type&&!empty($out['value'])&&preg_match('/(?:^|[?&#;])page=(\d+)/',(string)$out['value'],$m)&&absint($m[1])!==$page)return PLDR_Core::machine_error('pldr_handoff_fragment_page','Handoff fragment selector page identity does not match the saved page.',409);
        if(array_key_exists('region',$anchor)){
            if(!is_array($anchor['region']))return PLDR_Core::machine_error('pldr_handoff_region_shape','Reading-session region must be a coordinate object.',400);
            $region=array();
            foreach(array('x'=>0,'y'=>0,'w'=>null,'h'=>null) as $axis=>$default){
                $raw=$anchor['region'][$axis]??$default;
                if(!self::valid_region_coordinate($raw))return PLDR_Core::machine_error('pldr_handoff_region_shape',
                    'Reading-session region coordinate must be a normalized 0..1 number.',400,array('coordinate'=>$axis));
                $region[$axis]=(float)$raw;
            }
            if($region['w']<=0||$region['h']<=0||($region['x']+$region['w'])>1||($region['y']+$region['h'])>1)return PLDR_Core::machine_error('pldr_handoff_region','Reading-session handoff region must remain inside the saved page with positive dimensions.',400);
            $out['region']=$region;
        }
        return $out;
    }

    private static function limit(string $value,int $length):string { return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length); }
}
