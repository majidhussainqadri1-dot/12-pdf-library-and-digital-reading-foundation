<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Context {
    public static function lookup(int $edition_id,string $selection,int $page=0):array { $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);$selection=trim(sanitize_text_field($selection));if(''===$selection)return array('items'=>array());$context=array('edition_id'=>$edition_id,'document_id'=>$edition['public_id'],'page'=>max(0,$page),'selection'=>$selection);$items=apply_filters('pldr_knowledge_context',array(),$context);$safe=array();foreach((array)$items as $item){if(!is_array($item)||empty($item['url'])||empty($item['title']))continue;$safe[]=array('owner'=>sanitize_text_field((string)($item['owner']??'companion')),'title'=>sanitize_text_field((string)$item['title']),'url'=>esc_url_raw((string)$item['url']),'summary'=>sanitize_text_field((string)($item['summary']??'')),'canonical'=>true);}return array('items'=>array_slice($safe,0,20),'selection'=>$selection,'copied_domain_data'=>false,'expected_owners'=>array('File 05','File 06','File 16')); }
}
