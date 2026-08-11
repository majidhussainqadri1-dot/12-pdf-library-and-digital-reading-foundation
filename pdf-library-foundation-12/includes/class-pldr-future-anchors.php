<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Anchors {
    public static function save(int $edition_id, int $page, array $selector, string $note = '') {
        if (!is_user_logged_in()) return PLDR_Core::machine_error('pldr_anchor_login', 'Log in to save a private scholarly anchor.', 401);
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return $edition;
        if ($page < 1 || $page > (int) $edition['pages']) return PLDR_Core::machine_error('pldr_anchor_page', 'Scholarly anchor page is outside this edition.', 400);
        $allowed_types = array('TextQuoteSelector','FragmentSelector','SvgSelector','CssSelector');
        $type = sanitize_text_field((string) ($selector['type'] ?? 'TextQuoteSelector'));
        if (!in_array($type, $allowed_types, true)) return PLDR_Core::machine_error('pldr_anchor_selector', 'Unsupported scholarly anchor selector.', 400);
        $clean = array('type' => $type);
        if (isset($selector['exact'])) { $value=wp_strip_all_tags((string)$selector['exact']); $clean['exact']=function_exists('mb_substr')?mb_substr($value,0,260,'UTF-8'):substr($value,0,260); }
        if (isset($selector['prefix'])) { $value=wp_strip_all_tags((string)$selector['prefix']); $clean['prefix']=function_exists('mb_substr')?mb_substr($value,0,80,'UTF-8'):substr($value,0,80); }
        if (isset($selector['suffix'])) { $value=wp_strip_all_tags((string)$selector['suffix']); $clean['suffix']=function_exists('mb_substr')?mb_substr($value,0,80,'UTF-8'):substr($value,0,80); }
        if ('TextQuoteSelector' === $type) {
            if ('' === trim((string)($clean['exact'] ?? ''))) return PLDR_Core::machine_error('pldr_anchor_exact', 'A text-quote anchor requires an exact source excerpt.', 400);
            if (!self::quote_belongs($edition_id, $page, (string)$clean['exact'], $edition)) return PLDR_Core::machine_error('pldr_anchor_source', 'The text-quote anchor does not match the requested edition page.', 403);
        }
        if (isset($selector['region']) && is_array($selector['region'])) {
            $x = round(max(0, min(1, (float) ($selector['region']['x'] ?? 0))) * 100, 3);
            $y = round(max(0, min(1, (float) ($selector['region']['y'] ?? 0))) * 100, 3);
            $w = round(max(0, min(1, (float) ($selector['region']['w'] ?? 0))) * 100, 3);
            $h = round(max(0, min(1, (float) ($selector['region']['h'] ?? 0))) * 100, 3);
            $clean['refinedBy'] = array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>'xywh=percent:' . $x . ',' . $y . ',' . $w . ',' . $h);
        }
        $json = wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || strlen($json) > 480) return PLDR_Core::machine_error('pldr_anchor_size', 'Scholarly anchor selector is too large.', 400);
        $result = PLDR_Reading::add_item($edition_id, array('type'=>'highlight','page'=>$page,'anchor'=>$json,'note'=>$note,'tags'=>array('precise-anchor','portable-annotation')), get_current_user_id());
        if (is_wp_error($result)) return $result;
        PLDR_Core::audit('edition', $edition_id, 'precise_anchor_saved', array('page'=>$page,'selector_type'=>$type));
        return array('id'=>(int)$result['id'],'page'=>$page,'selector'=>$clean,'private'=>true,'portable'=>true,'edition_version'=>(int)$edition['version'],'source_verified'=>'TextQuoteSelector'===$type);
    }

    private static function quote_belongs(int $edition_id,int $page,string $exact,array $edition):bool {
        $needle=PLDR_Core::normalize_search($exact);
        if(''===$needle)return false;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if($rows){
            $haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));
            if(''!==$haystack&&false!==strpos($haystack,$needle))return true;
        }
        return (bool)apply_filters('pldr_precise_anchor_source_allowed',false,$edition_id,$page,$exact,$edition);
    }
}
