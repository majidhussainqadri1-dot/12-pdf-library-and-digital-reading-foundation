<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Derived_Text {
    public static function derive(int $edition_id,int $page,string $text,string $mode,string $target):array {
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        $page=absint($page);
        if($page<1||$page>(int)$edition['pages'])return array('error'=>PLDR_Core::machine_error('pldr_derive_page','Derived text must be bound to a valid page in this edition.',400));
        $doc=PLDR_Core::document((int)$edition['document_id']);
        if($doc && 'patient-cases'===$doc['category'] && !apply_filters('pldr_derived_text_patient_case_allowed',false,$edition_id,$doc))return array('error'=>PLDR_Core::machine_error('pldr_derive_patient_case','Patient-case text is not sent to translation/transliteration providers without separate privacy approval.',403));
        $mode=sanitize_key($mode);
        if(!in_array($mode,array('translate','transliterate'),true))return array('error'=>PLDR_Core::machine_error('pldr_derive_mode','Use translate or transliterate mode.',400));
        $text=self::limit(trim(wp_strip_all_tags($text)),5000);
        if(''===$text)return array('error'=>PLDR_Core::machine_error('pldr_derive_text','Text selection is required.',400));
        $target=self::limit(sanitize_text_field($target),60);
        if(''===$target)return array('error'=>PLDR_Core::machine_error('pldr_derive_target','Target language or script is required.',400));
        if(!self::selection_belongs($edition_id,$page,$text,$edition))return array('error'=>PLDR_Core::machine_error('pldr_derive_selection','The selected text could not be verified against the requested document page.',403));
        $filter='translate'===$mode?'pldr_translate_text':'pldr_transliterate_text';
        $result=apply_filters($filter,null,$text,array('edition_id'=>$edition_id,'page'=>$page,'source_language'=>$edition['language'],'target_language'=>$target));
        if(!is_array($result)||empty($result['text']))return array('error'=>PLDR_Core::machine_error('pldr_derive_provider','No approved translation/transliteration provider is configured.',503,array('degraded'=>true)));
        return array('mode'=>$mode,'text'=>wp_strip_all_tags((string)$result['text']),'provider'=>sanitize_text_field((string)($result['provider']??'adapter')),'target_language'=>$target,'derived'=>true,'not_authorial_text'=>true,'original_unchanged'=>true,'source_bound'=>true);
    }

    private static function selection_belongs(int $edition_id,int $page,string $text,array $edition):bool {
        $needle=PLDR_Core::normalize_search($text);
        if(''===$needle)return false;
        foreach(PLDR_Future_Data::ocr_pages($edition_id) as $row){
            if((int)($row['page_number']??0)!==$page)continue;
            $haystack=PLDR_Core::normalize_search((string)($row['text_content']??''));
            if(''!==$haystack&&false!==strpos($haystack,$needle))return true;
            break;
        }
        return (bool)apply_filters('pldr_derived_text_selection_allowed',false,$edition_id,$page,$text,$edition);
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
