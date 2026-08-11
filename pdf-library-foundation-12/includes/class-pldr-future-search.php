<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Search {
    public static function heatmap(int $edition_id,string $query):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);
        $needle=PLDR_Core::normalize_search($query);
        $len=function_exists('mb_strlen')?mb_strlen($needle,'UTF-8'):strlen($needle);
        if($len<2)return array('error'=>PLDR_Core::machine_error('pldr_heatmap_query','Search heatmap query is too short.',400));
        if($len>160)return array('error'=>PLDR_Core::machine_error('pldr_heatmap_query_long','Search heatmap query is too long.',400));
        $scan_limit=(int)apply_filters('pldr_heatmap_page_scan_limit',5000,$edition_id,$edition);$scan_limit=max(100,min(10000,$scan_limit));
        $items=array();$total=0;$scanned=0;$offset=0;$batch=250;
        while($scanned<$scan_limit){
            $take=min($batch,$scan_limit-$scanned);$rows=PLDR_Future_Data::ocr_pages($edition_id,0,$take,$offset);if(!$rows)break;
            foreach($rows as $row){$text=(string)$row['normalized_text'];$count=substr_count($text,$needle);if($count){$items[]=array('page'=>(int)$row['page_number'],'matches'=>$count);$total+=$count;if(count($items)>=1000)break 2;}}
            $count=count($rows);$scanned+=$count;$offset+=$count;if($count<$take)break;
        }
        $truncated=$scanned<$edition['pages']||count($items)>=1000;
        return array('edition_id'=>$edition_id,'query'=>$query,'matches'=>$total,'pages'=>$items,'pages_scanned'=>$scanned,'scan_page_limit'=>$scan_limit,'truncated'=>$truncated,'entitlement_filtered'=>true);
    }
}
