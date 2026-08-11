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
        if (isset($selector['exact'])) { $value=wp_strip_all_tags((string)$selector['exact']); $clean['exact']=self::limit($value,260); }
        if (isset($selector['prefix'])) { $value=wp_strip_all_tags((string)$selector['prefix']); $clean['prefix']=self::limit($value,80); }
        if (isset($selector['suffix'])) { $value=wp_strip_all_tags((string)$selector['suffix']); $clean['suffix']=self::limit($value,80); }
        if (isset($selector['value'])) { $value=wp_strip_all_tags((string)$selector['value']); $clean['value']=self::limit($value,300); }
        if ('TextQuoteSelector' === $type) {
            if ('' === trim((string)($clean['exact'] ?? ''))) return PLDR_Core::machine_error('pldr_anchor_exact', 'A text-quote anchor requires an exact source excerpt.', 400);
            $source_match=self::quote_belongs($edition_id, $page, (string)$clean['exact'], $edition);
            if (is_wp_error($source_match)) return $source_match;
            if (!$source_match) return PLDR_Core::machine_error('pldr_anchor_source', 'The text-quote anchor does not match the requested edition page.', 403);
        } else {
            if ('' === trim((string)($clean['value'] ?? '')) && !isset($selector['region'])) return PLDR_Core::machine_error('pldr_anchor_selector_value', 'This scholarly selector requires a bounded selector value or region.', 400);
            if ('FragmentSelector' === $type && !empty($clean['value']) && preg_match('/(?:^|[?&#;])page=(\d+)/', (string)$clean['value'], $m) && absint($m[1]) !== $page) {
                return PLDR_Core::machine_error('pldr_anchor_fragment_page', 'Fragment selector page identity does not match the requested edition page.', 409);
            }
        }
        if (isset($selector['region']) && is_array($selector['region'])) {
            $x = round(max(0, min(1, (float) ($selector['region']['x'] ?? 0))) * 100, 3);
            $y = round(max(0, min(1, (float) ($selector['region']['y'] ?? 0))) * 100, 3);
            $w = round(max(0, min(1, (float) ($selector['region']['w'] ?? 0))) * 100, 3);
            $h = round(max(0, min(1, (float) ($selector['region']['h'] ?? 0))) * 100, 3);
            if ($w <= 0 || $h <= 0) return PLDR_Core::machine_error('pldr_anchor_region', 'A scholarly anchor region must have a positive width and height.', 400);
            if (($x + $w) > 100.0 || ($y + $h) > 100.0) return PLDR_Core::machine_error('pldr_anchor_region_bounds', 'A scholarly anchor region must remain fully inside the page boundary.', 400, array('x'=>$x,'y'=>$y,'w'=>$w,'h'=>$h));
            $clean['refinedBy'] = array('type'=>'FragmentSelector','conformsTo'=>'https://www.w3.org/TR/media-frags/','value'=>'xywh=percent:' . $x . ',' . $y . ',' . $w . ',' . $h);
        }
        $json = wp_json_encode($clean, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || strlen($json) > 640) return PLDR_Core::machine_error('pldr_anchor_size', 'Scholarly anchor selector is too large.', 400);
        $note=self::limit(sanitize_textarea_field($note),4000);
        $result = PLDR_Reading::add_item($edition_id, array('type'=>'highlight','page'=>$page,'anchor'=>$json,'note'=>$note,'tags'=>array('precise-anchor','portable-annotation')), get_current_user_id());
        if (is_wp_error($result)) return $result;
        PLDR_Core::audit('edition', $edition_id, 'precise_anchor_saved', array('page'=>$page,'selector_type'=>$type));
        return array('id'=>(int)$result['id'],'page'=>$page,'selector'=>$clean,'private'=>true,'portable'=>true,'edition_version'=>(int)$edition['version'],'source_verified'=>'TextQuoteSelector'===$type,'selector_value_preserved'=>isset($clean['value']));
    }

    private static function quote_belongs(int $edition_id,int $page,string $exact,array $edition) {
        $needle=PLDR_Core::normalize_search($exact);
        if(''===$needle)return false;
        $rows=PLDR_Future_Data::ocr_pages($edition_id,$page,1,0);
        if($rows){
            $haystack=PLDR_Core::normalize_search((string)($rows[0]['text_content']??''));
            if(''!==$haystack&&false!==strpos($haystack,$needle))return true;
        }
        try {
            return (bool)apply_filters('pldr_precise_anchor_source_allowed',false,$edition_id,$page,$exact,$edition);
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',$edition_id,'precise_anchor_source_provider_failed',array('page'=>$page,'provider_failure'=>1));
            return PLDR_Core::machine_error('pldr_anchor_source_provider','Precise-anchor source validation is temporarily unavailable; the anchor was not accepted.',503,array('degraded'=>true,'provider_failure'=>true));
        }
    }

    private static function limit(string $value,int $length):string {
        return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);
    }
}
