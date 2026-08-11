<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Context {
    public static function lookup(int $edition_id,string $selection,int $page=0):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        $page=absint($page);
        if($page<1||$page>(int)$edition['pages'])return array('error'=>PLDR_Core::machine_error('pldr_context_page','Knowledge context must be bound to a valid page.',400));
        $selection=self::limit(trim(wp_strip_all_tags($selection)),1200);
        if(''===$selection)return array('items'=>array());
        if(!self::selection_belongs($edition_id,$page,$selection,$edition))return array('error'=>PLDR_Core::machine_error('pldr_context_selection','The selected text could not be verified against this document page.',403));
        $context=array('edition_id'=>$edition_id,'document_id'=>$edition['public_id'],'page'=>$page,'selection'=>$selection);
        $items=apply_filters('pldr_knowledge_context',array(),$context);
        $safe=array();
        foreach((array)$items as $item){
            if(!is_array($item)||empty($item['url'])||empty($item['title']))continue;
            $safe[]=array('owner'=>self::limit(sanitize_text_field((string)($item['owner']??'companion')),80),'title'=>self::limit(sanitize_text_field((string)$item['title']),180),'url'=>esc_url_raw((string)$item['url']),'summary'=>self::limit(sanitize_text_field((string)($item['summary']??'')),500),'canonical'=>true);
        }
        return array('items'=>array_slice($safe,0,20),'selection'=>$selection,'copied_domain_data'=>false,'expected_owners'=>array('File 05','File 06','File 16'),'source_bound'=>true);
    }

    private static function selection_belongs(int $edition_id,int $page,string $selection,array $edition):bool {
        $needle=PLDR_Core::normalize_search($selection);
        if(''===$needle)return false;
        foreach(PLDR_Future_Data::ocr_pages($edition_id) as $row){
            if((int)($row['page_number']??0)!==$page)continue;
            $haystack=PLDR_Core::normalize_search((string)($row['text_content']??''));
            if(''!==$haystack&&false!==strpos($haystack,$needle))return true;
            break;
        }
        return (bool)apply_filters('pldr_knowledge_context_selection_allowed',false,$edition_id,$page,$selection,$edition);
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
