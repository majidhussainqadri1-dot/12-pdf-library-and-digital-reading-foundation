<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Annotations {
    public static function export(int $edition_id): array {
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        if (!is_user_logged_in()) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to export private annotations.',401));
        $items = PLDR_Reading::items($edition_id);
        $annotations = array();
        foreach (array_slice($items,0,1000) as $item) {
            $target = array('source' => PLDR_Core::route_url('read', array('id'=>$edition['public_id'])), 'selector' => array(array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>'page='.(int)$item['page_number'])));
            $anchor = (string) $item['anchor_text'];
            $decoded = json_decode($anchor, true);
            if (is_array($decoded)) $target['selector'][] = $decoded;
            elseif ('' !== $anchor) $target['selector'][] = array('type'=>'TextQuoteSelector','exact'=>self::limit($anchor,500));
            $annotations[] = array('@context'=>'http://www.w3.org/ns/anno.jsonld','id'=>'urn:uuid:'.PLDR_Core::uuid(),'type'=>'Annotation','motivation'=>('bookmark'===$item['item_type']?'bookmarking':'commenting'),'body'=>array('type'=>'TextualBody','value'=>self::limit((string)$item['note_text'],4000),'purpose'=>'commenting'),'target'=>$target,'created'=>$item['created_at'],'modified'=>$item['updated_at']);
        }
        return array('@context'=>'http://www.w3.org/ns/anno.jsonld','type'=>'AnnotationPage','items'=>$annotations,'private'=>true,'portable'=>true,'export_limit'=>1000);
    }

    public static function import(int $edition_id, array $page): array {
        if (!is_user_logged_in()) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to import annotations.',401));
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error'=>$edition);
        $items = isset($page['items']) && is_array($page['items']) ? array_slice($page['items'],0,500) : array();
        $canonical=PLDR_Core::route_url('read',array('id'=>$edition['public_id']));
        $imported=0;$rejected=0;
        foreach($items as $annotation){
            if(!is_array($annotation)){$rejected++;continue;}
            $target=(array)($annotation['target']??array());
            $source=isset($target['source'])?esc_url_raw((string)$target['source']):'';
            if(''!==$source&&untrailingslashit($source)!==untrailingslashit($canonical)){$rejected++;continue;}
            $selectors=(array)($target['selector']??array());$page_no=0;$anchor='';
            foreach(array_slice($selectors,0,8) as $selector){
                if(!is_array($selector))continue;
                $type=sanitize_text_field((string)($selector['type']??''));
                if('FragmentSelector'===$type&&preg_match('/(?:^|[?&#;])?page=(\d+)/',(string)($selector['value']??''),$m))$page_no=absint($m[1]);
                if(in_array($type,array('TextQuoteSelector','SvgSelector','CssSelector'),true)){
                    $clean=self::selector($selector);$encoded=wp_json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    if(is_string($encoded)&&strlen($encoded)<=480)$anchor=$encoded;
                }
            }
            if($page_no<1||$page_no>(int)$edition['pages']){$rejected++;continue;}
            $body=self::limit(sanitize_textarea_field((string)($annotation['body']['value']??'')),4000);
            $result=PLDR_Reading::add_item($edition_id,array('type'=>'highlight','page'=>$page_no,'anchor'=>$anchor,'note'=>$body,'tags'=>array('w3c-import')),get_current_user_id());
            if(is_wp_error($result))$rejected++;else$imported++;
        }
        return array('imported'=>$imported,'rejected'=>$rejected,'private'=>true,'edition_bound'=>true,'input_limit'=>500);
    }

    private static function selector(array $selector):array {
        $type=sanitize_text_field((string)($selector['type']??''));$out=array('type'=>$type);
        foreach(array('exact'=>260,'prefix'=>80,'suffix'=>80,'value'=>260) as $key=>$limit){if(isset($selector[$key]))$out[$key]=self::limit(wp_strip_all_tags((string)$selector[$key]),$limit);}
        return $out;
    }
    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
