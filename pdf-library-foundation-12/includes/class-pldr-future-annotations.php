<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Annotations {
    private const EXPORT_LIMIT = 1000;
    private const IMPORT_LIMIT = 500;

    public static function export(int $edition_id): array {
        global $wpdb;
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $uid=get_current_user_id();
        if (!$uid) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to export private annotations.',401));
        $rows=$wpdb->get_results($wpdb->prepare(
            'SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM '.PLDR_Core::table('reading_items').' WHERE user_id=%d AND edition_id=%d ORDER BY page_number ASC,id ASC LIMIT %d',
            $uid,$edition_id,self::EXPORT_LIMIT+1
        ),ARRAY_A)?:array();
        $truncated=count($rows)>self::EXPORT_LIMIT;
        if($truncated)$rows=array_slice($rows,0,self::EXPORT_LIMIT);
        $source=self::canonical_source($edition,$edition_id);
        $annotations = array();
        foreach ($rows as $item) {
            $target = array('source' => $source, 'selector' => array(array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>'page='.(int)$item['page_number'])));
            $anchor = (string) $item['anchor_text'];
            $decoded = json_decode($anchor, true);
            if (is_array($decoded)) $target['selector'][] = $decoded;
            elseif ('' !== $anchor) $target['selector'][] = array('type'=>'TextQuoteSelector','exact'=>self::limit($anchor,500));
            $motivation='bookmark'===$item['item_type']?'bookmarking':('highlight'===$item['item_type']?'highlighting':'commenting');
            $annotations[] = array('@context'=>'http://www.w3.org/ns/anno.jsonld','id'=>self::annotation_id($uid,$edition_id,(int)$item['id']),'type'=>'Annotation','motivation'=>$motivation,'body'=>array('type'=>'TextualBody','value'=>self::limit((string)$item['note_text'],4000),'purpose'=>'commenting'),'target'=>$target,'created'=>$item['created_at'],'modified'=>$item['updated_at']);
        }
        return array('@context'=>'http://www.w3.org/ns/anno.jsonld','type'=>'AnnotationPage','items'=>$annotations,'private'=>true,'portable'=>true,'stable_annotation_ids'=>true,'document_id'=>$edition['public_id'],'edition_id'=>$edition_id,'source'=>$source,'export_limit'=>self::EXPORT_LIMIT,'returned'=>count($annotations),'truncated'=>$truncated);
    }

    public static function import(int $edition_id, array $page): array {
        global $wpdb;
        if (!is_user_logged_in()) return array('error' => PLDR_Core::machine_error('pldr_annotations_login','Log in to import annotations.',401));
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error'=>$edition);
        $uid=get_current_user_id();
        $all_items = isset($page['items']) && is_array($page['items']) ? array_values($page['items']) : array();
        $input_total=count($all_items);
        $items = array_slice($all_items,0,self::IMPORT_LIMIT);
        $canonical=self::canonical_source($edition,$edition_id);
        $imported=0;$rejected=0;$duplicates=0;
        foreach($items as $annotation){
            if(!is_array($annotation)){$rejected++;continue;}
            $target=(array)($annotation['target']??array());
            $source=isset($target['source'])?esc_url_raw((string)$target['source']):'';
            if(''===$source||untrailingslashit($source)!==untrailingslashit($canonical)){$rejected++;continue;}
            $selectors=(array)($target['selector']??array());$page_no=0;$anchor='';$quote='';
            foreach(array_slice($selectors,0,8) as $selector){
                if(!is_array($selector))continue;
                $type=sanitize_text_field((string)($selector['type']??''));
                if('FragmentSelector'===$type&&preg_match('/(?:^|[?&#;])?page=(\d+)/',(string)($selector['value']??''),$m))$page_no=absint($m[1]);
                if(in_array($type,array('TextQuoteSelector','SvgSelector','CssSelector'),true)){
                    $clean=self::selector($selector);$encoded=wp_json_encode($clean,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    if(is_string($encoded)&&strlen($encoded)<=640){$anchor=$encoded;if('TextQuoteSelector'===$type)$quote=(string)($clean['exact']??'');}
                }
            }
            if($page_no<1||$page_no>(int)$edition['pages']){$rejected++;continue;}
            if(''!==$quote&&!self::quote_belongs($edition_id,$page_no,$quote,$edition)){$rejected++;continue;}
            $body_node=$annotation['body']??array();
            $body=is_array($body_node)?self::limit(sanitize_textarea_field((string)($body_node['value']??'')),4000):'';
            $motivation=sanitize_key((string)($annotation['motivation']??''));
            if(!in_array($motivation,array('bookmarking','commenting','highlighting'),true)){$rejected++;continue;}
            $item_type='bookmarking'===$motivation?'bookmark':('commenting'===$motivation?'note':'highlight');
            if('note'===$item_type&&''===trim($body)){$rejected++;continue;}
            $incoming_id=self::limit(trim((string)($annotation['id']??'')),300);
            $identity=$incoming_id!==''?$incoming_id:hash('sha256',$canonical.'|'.$page_no.'|'.$motivation.'|'.$anchor.'|'.$body);
            $identity_tag='w3c-id-'.substr(hash('sha256',$identity),0,24);
            $existing=$wpdb->get_var($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('reading_items').' WHERE user_id=%d AND edition_id=%d AND tags_json LIKE %s LIMIT 1',$uid,$edition_id,'%'.$wpdb->esc_like($identity_tag).'%'));
            if($existing){$duplicates++;continue;}
            $result=PLDR_Reading::add_item($edition_id,array('type'=>$item_type,'page'=>$page_no,'anchor'=>$anchor,'note'=>$body,'tags'=>array('w3c-import',$identity_tag)),$uid);
            if(is_wp_error($result))$rejected++;else$imported++;
        }
        return array('imported'=>$imported,'rejected'=>$rejected,'duplicates_skipped'=>$duplicates,'private'=>true,'edition_bound'=>true,'source_required'=>true,'selector_source_verified'=>true,'stable_identity_dedupe'=>true,'input_total'=>$input_total,'input_limit'=>self::IMPORT_LIMIT,'input_truncated'=>$input_total>self::IMPORT_LIMIT);
    }

    private static function canonical_source(array $edition,int $edition_id):string {
        return add_query_arg('edition',$edition_id,PLDR_Core::route_url('read',array('id'=>$edition['public_id'])));
    }

    private static function annotation_id(int $uid,int $edition_id,int $item_id):string {
        return 'urn:pldr:annotation:'.hash_hmac('sha256',$uid.'|'.$edition_id.'|'.$item_id,wp_salt('auth'));
    }

    private static function selector(array $selector):array {
        $type=sanitize_text_field((string)($selector['type']??''));$out=array('type'=>$type);
        foreach(array('exact'=>260,'prefix'=>80,'suffix'=>80,'value'=>300) as $key=>$limit){if(isset($selector[$key]))$out[$key]=self::limit(wp_strip_all_tags((string)$selector[$key]),$limit);}
        if(isset($selector['refinedBy'])&&is_array($selector['refinedBy'])){
            $ref=(array)$selector['refinedBy'];$ref_type=sanitize_text_field((string)($ref['type']??''));
            if('FragmentSelector'===$ref_type){
                $value=self::limit(wp_strip_all_tags((string)($ref['value']??'')),300);
                if(''!==$value)$out['refinedBy']=array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>$value);
            }
        }
        return $out;
    }

    private static function quote_belongs(int $edition_id,int $page,string $exact,array $edition):bool {
        $needle=PLDR_Core::normalize_search($exact);if(''===$needle)return false;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if($rows){$haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));if(''!==$haystack&&false!==strpos($haystack,$needle))return true;}
        return (bool)apply_filters('pldr_annotation_import_source_allowed',false,$edition_id,$page,$exact,$edition);
    }

    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
