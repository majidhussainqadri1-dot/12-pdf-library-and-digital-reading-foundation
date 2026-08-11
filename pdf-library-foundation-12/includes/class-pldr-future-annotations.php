<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Annotations {
    public static function export(int $edition_id): array {
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        if (!is_user_logged_in()) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to export private annotations.',401));
        $items = PLDR_Reading::items($edition_id);
        $annotations = array();
        foreach ($items as $item) {
            $target = array('source' => PLDR_Core::route_url('read', array('id'=>$edition['public_id'])), 'selector' => array(array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>'page='.(int)$item['page_number'])));
            $anchor = (string) $item['anchor_text'];
            $decoded = json_decode($anchor, true);
            if (is_array($decoded)) $target['selector'][] = $decoded;
            elseif ('' !== $anchor) $target['selector'][] = array('type'=>'TextQuoteSelector','exact'=>$anchor);
            $annotations[] = array('@context'=>'http://www.w3.org/ns/anno.jsonld','id'=>'urn:uuid:'.PLDR_Core::uuid(),'type'=>'Annotation','motivation'=>('bookmark'===$item['item_type']?'bookmarking':'commenting'),'body'=>array('type'=>'TextualBody','value'=>(string)$item['note_text'],'purpose'=>'commenting'),'target'=>$target,'created'=>$item['created_at'],'modified'=>$item['updated_at']);
        }
        return array('@context'=>'http://www.w3.org/ns/anno.jsonld','type'=>'AnnotationPage','items'=>$annotations,'private'=>true,'portable'=>true);
    }

    public static function import(int $edition_id, array $page): array {
        if (!is_user_logged_in()) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to import annotations.',401));
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error'=>$edition);
        $items = isset($page['items']) && is_array($page['items']) ? array_slice($page['items'],0,500) : array();
        $imported=0;$rejected=0;
        foreach($items as $annotation){$selectors=(array)($annotation['target']['selector']??array());$page_no=0;$anchor='';foreach($selectors as $selector){if(($selector['type']??'')==='FragmentSelector'&&preg_match('/page=(\d+)/',(string)($selector['value']??''),$m))$page_no=absint($m[1]);if(in_array(($selector['type']??''),array('TextQuoteSelector','FragmentSelector','SvgSelector'),true)&&($selector['type']??'')!=='FragmentSelector')$anchor=wp_json_encode($selector);}if($page_no<1||$page_no>(int)$edition['pages']){$rejected++;continue;}$body=(string)($annotation['body']['value']??'');$result=PLDR_Reading::add_item($edition_id,array('type'=>'highlight','page'=>$page_no,'anchor'=>$anchor,'note'=>$body,'tags'=>array('w3c-import')),get_current_user_id());if(is_wp_error($result))$rejected++;else$imported++;}
        return array('imported'=>$imported,'rejected'=>$rejected,'private'=>true);
    }
}
