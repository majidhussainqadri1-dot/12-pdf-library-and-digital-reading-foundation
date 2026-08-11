<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Search {
    public static function heatmap(int $edition_id,string $query):array { $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);$needle=PLDR_Core::normalize_search($query);$len=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);if($len<2)return array('error'=>PLDR_Core::machine_error('pldr_heatmap_query','Search heatmap query is too short.',400));$items=array();$total=0;foreach(PLDR_Future_Data::ocr_pages($edition_id) as $row){$text=(string)$row['normalized_text'];$count=substr_count($text,$needle);if($count){$items[]=array('page'=>(int)$row['page_number'],'matches'=>$count);$total+=$count;}}return array('edition_id'=>$edition_id,'query'=>$query,'matches'=>$total,'pages'=>$items,'entitlement_filtered'=>true); }
}
