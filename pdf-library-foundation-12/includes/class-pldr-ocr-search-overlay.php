<?php

defined('ABSPATH') || exit;

/**
 * Approved-correction-aware OCR search projection.
 *
 * Base OCR remains immutable. This service scans the current derived OCR layer
 * and intercepts the public File 12 OCR-search route so approved corrections
 * are searchable without rewriting the source OCR table/index.
 */
final class PLDR_OCR_Search_Overlay {
    private const DEFAULT_SCAN_LIMIT=2000;
    private const MAX_SCAN_LIMIT=10000;

    public static function hooks():void {
        add_filter('rest_pre_dispatch',array(__CLASS__,'intercept'),8,3);
    }

    public static function intercept($result,$server,WP_REST_Request $request) {
        if(null!==$result)return $result;
        $route=(string)$request->get_route();
        if(!preg_match('#^/pldr/v1/ocr-search/(?P<edition>\d+)$#',$route,$m))return null;
        $search=self::search((int)$m['edition'],(string)$request['q'],get_current_user_id(),(string)$request['cursor'],absint($request['limit']?:50));
        if(isset($search['error'])&&is_wp_error($search['error']))return $search['error'];
        return rest_ensure_response($search);
    }

    public static function search(int $edition_id,string $query,int $user_id=0,string $cursor_token='',int $limit=50):array {
        global $wpdb;
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id,'read',$user_id);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_overlay_access_read','Document text-search authorization state could not be verified reliably.',503,array('degraded'=>true)));
        if(!$allowed)return array('error'=>PLDR_Core::machine_error('pldr_ocr_forbidden','Document text search is unavailable.',404));
        if(!class_exists('PLDR_Future_Data'))return array('error'=>PLDR_Core::machine_error('pldr_ocr_overlay_unavailable','The approved-correction OCR layer is unavailable; stale base OCR was not searched.',503,array('degraded'=>true)));
        $needle=PLDR_Core::normalize_search($query);
        $length=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);
        if($length<2)return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_short','Document text search requires at least two characters.',400));
        if($length>160)return array('error'=>PLDR_Core::machine_error('pldr_ocr_query_long','Document text search query is too long.',400,array('max_characters'=>160)));
        $limit=max(1,min(100,$limit));
        $context=hash('sha256',$edition_id.'|'.$needle.'|'.$user_id);
        $after_page=self::decode_cursor($cursor_token,$context);
        if(is_wp_error($after_page))return array('error'=>$after_page);
        try {
            $variants=apply_filters('pldr_search_variants',array($needle),$needle,$edition_id);
            $scan_limit=(int)apply_filters('pldr_ocr_search_page_scan_limit',self::DEFAULT_SCAN_LIMIT,$edition_id,$user_id,$needle);
        } catch(Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'ocr_overlay_search_policy_provider_failed',array('provider_failure'=>true),$user_id);
            return array('error'=>PLDR_Core::machine_error('pldr_ocr_variant_provider','Document text-search expansion/scan policy is temporarily unavailable.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        $variants=array_values(array_unique(array_filter(array_map(static function($value):string {
            $value=PLDR_Core::normalize_search((string)$value);
            return function_exists('mb_substr')?mb_substr($value,0,160,'UTF-8'):substr($value,0,160);
        },(array)$variants))));
        $variants=array_slice($variants,0,5);
        if(!$variants)return array('error'=>PLDR_Core::machine_error('pldr_ocr_variants','No safe document text-search variant was available.',400));
        $scan_limit=max(100,min(self::MAX_SCAN_LIMIT,$scan_limit));
        $items=array();$scanned=0;$last_scanned=$after_page;$source_exhausted=false;$batch=200;$extra_found=false;
        while($scanned<$scan_limit&&!$extra_found){
            $take=min($batch,$scan_limit-$scanned);
            $wpdb->last_error='';
            $rows=PLDR_Future_Data::ocr_pages($edition_id,0,$take,0,$last_scanned);
            if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_ocr_overlay_read','Document OCR/correction search state could not be read reliably.',503,array('degraded'=>true,'pages_scanned'=>$scanned)));
            if(!$rows){$source_exhausted=true;break;}
            $count=count($rows);
            foreach($rows as $row){
                $last_scanned=(int)$row['page_number'];$scanned++;
                $text=(string)($row['text_content']??'');
                $normalized=(string)($row['normalized_text']??PLDR_Core::normalize_search($text));
                $matched=false;
                foreach($variants as $variant){if(false!==strpos($normalized,$variant)){$matched=true;break;}}
                if(!$matched)continue;
                if(count($items)>=$limit){$extra_found=true;break;}
                $pos=function_exists('mb_stripos')?mb_stripos($text,$query,0,'UTF-8'):stripos($text,$query);
                $start=false===$pos?0:max(0,$pos-90);
                $snippet=function_exists('mb_substr')?mb_substr($text,$start,240,'UTF-8'):substr($text,$start,240);
                $items[]=array('page'=>(int)$row['page_number'],'language'=>(string)$row['language'],'quality'=>(float)$row['quality_score'],'snippet'=>$snippet,'derived_correction_layer'=>!empty($row['derived_correction_layer']));
            }
            if($extra_found)break;
            if($count<$take){$source_exhausted=true;break;}
        }
        $scan_truncated=!$source_exhausted&&!$extra_found&&$scanned>=$scan_limit;
        $has_more=$extra_found||$scan_truncated;
        $cursor_page=0;
        if($extra_found&&$items)$cursor_page=(int)$items[count($items)-1]['page'];
        elseif($scan_truncated)$cursor_page=$last_scanned;
        return array('items'=>$items,'limit'=>$limit,'has_more'=>$has_more,'next_cursor'=>$has_more&&$cursor_page>0?self::encode_cursor($cursor_page,$context):null,'cursor_supported'=>true,'approved_correction_overlay'=>true,'pages_scanned'=>$scanned,'scan_page_limit'=>$scan_limit,'scan_truncated'=>$scan_truncated,'source_exhausted'=>$source_exhausted);
    }

    private static function encode_cursor(int $page,string $context):string {
        $json=wp_json_encode(array('p'=>$page,'c'=>$context));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
    }

    private static function decode_cursor(string $token,string $context){
        $token=trim($token);if(''===$token)return 0;
        if(strlen($token)>500||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));
        if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($decoded)||!isset($decoded['p'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['p'])<1)return PLDR_Core::machine_error('pldr_ocr_cursor','OCR search cursor does not match this query/audience or is invalid.',400);
        return absint($decoded['p']);
    }
}
