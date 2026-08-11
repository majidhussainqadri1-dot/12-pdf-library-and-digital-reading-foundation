<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Data {
    private const BULK_OCR_LIMIT = 1000;
    private const REFLOW_WINDOW_LIMIT = 100;

    public static function require_edition(int $edition_id, string $operation = 'read') {
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition || !PLDR_Access::can_access_edition($edition_id, $operation, get_current_user_id())) {
            return PLDR_Core::machine_error('pldr_future_forbidden', 'This document edition is unavailable for the requested advanced reading operation.', 403);
        }
        return $edition;
    }

    public static function ocr_pages(int $edition_id,int $page=0,int $limit=0,int $offset=0): array {
        global $wpdb;
        $page=max(0,$page);$offset=max(0,$offset);$limit=max(0,min(self::BULK_OCR_LIMIT,$limit));
        if(0===$page && 0===$limit)$limit=self::BULK_OCR_LIMIT;
        $sql='SELECT page_number,language,quality_score,text_content,normalized_text FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d';
        $params=array($edition_id);
        if($page>0){$sql.=' AND page_number=%d';$params[]=$page;}
        $sql.=' ORDER BY page_number ASC';
        if($limit>0){$sql.=' LIMIT %d OFFSET %d';$params[]=$limit;$params[]=$offset;}
        $rows=$wpdb->get_results($wpdb->prepare($sql,$params),ARRAY_A)?:array();
        if(!$rows)return array();
        $page_ids=array_values(array_unique(array_map(static fn(array $row):int=>(int)$row['page_number'],$rows)));
        $placeholders=implode(',',array_fill(0,count($page_ids),'%d'));
        $correction_sql='SELECT page_number,original_text,corrected_text FROM '.PLDR_Core::table('ocr_corrections').' WHERE edition_id=%d AND status=%s AND page_number IN ('.$placeholders.') ORDER BY page_number ASC,id ASC';
        $corrections=$wpdb->get_results($wpdb->prepare($correction_sql,array_merge(array($edition_id,'approved'),$page_ids)),ARRAY_A)?:array();
        $by_page=array();
        foreach($corrections as $correction)$by_page[(int)$correction['page_number']][]=$correction;
        foreach($rows as &$row){
            $row_page=(int)$row['page_number'];$text=(string)$row['text_content'];$applied=0;
            foreach($by_page[$row_page]??array() as $correction){
                $original=(string)$correction['original_text'];$replacement=(string)$correction['corrected_text'];
                if(''===$original)continue;$pos=strpos($text,$original);if(false===$pos)continue;
                $text=substr_replace($text,$replacement,$pos,strlen($original));$applied++;
            }
            if($applied){$row['text_content']=$text;$row['normalized_text']=PLDR_Core::normalize_search($text);$row['approved_corrections_applied']=$applied;$row['derived_correction_layer']=true;}
        }
        unset($row);
        return $rows;
    }

    public static function reflow(int $edition_id,int $page=0): array {
        global $wpdb;
        $edition = self::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $page=max(0,$page);
        if($page>(int)$edition['pages'])return array('error'=>PLDR_Core::machine_error('pldr_reflow_page','Requested reflow page is outside this edition.',400));
        $limit=$page>0?1:self::REFLOW_WINDOW_LIMIT;
        $wpdb->last_error='';
        $pages = self::ocr_pages($edition_id,$page,$limit,0);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'reflow_source_read_failed',array('page'=>$page,'limit'=>$limit));
            return array('error'=>PLDR_Core::machine_error('pldr_reflow_source_read','Reflow OCR source state could not be read reliably; no external provider fallback was attempted.',503,array('degraded'=>true)));
        }
        $provider = 'lawful-ocr';
        $external_input_total=0;
        $external_used=false;
        if (!$pages) {
            try {
                $external = apply_filters('pldr_reflow_extract', null, $edition_id, $edition, $page);
            } catch (Throwable $e) {
                PLDR_Core::audit('edition',$edition_id,'reflow_provider_failed',array('error'=>self::limit_text(sanitize_text_field($e->getMessage()),500)));
                return array('error'=>PLDR_Core::machine_error('pldr_reflow_provider','The approved reflow provider failed; no derived reflow text was substituted.',503,array('degraded'=>true,'provider_failure'=>true)));
            }
            if (is_array($external) && !empty($external['pages'])) {
                $provider = self::limit_text(sanitize_text_field((string) ($external['provider'] ?? '')),80);
                if(''===$provider)return array('error'=>PLDR_Core::machine_error('pldr_reflow_provenance','Reflow provider identity is required; anonymous derived text was rejected.',502,array('degraded'=>true)));
                $external_pages=(array)$external['pages'];
                $external_input_total=count($external_pages);
                $pages = array_slice($external_pages,0,$limit);
                $external_used=true;
            } elseif(null!==$external) {
                return array('error'=>PLDR_Core::machine_error('pldr_reflow_provider','The approved reflow provider returned an invalid response.',502,array('degraded'=>true)));
            }
        }
        $items = array();
        foreach ($pages as $row) {
            if(!is_array($row))continue;
            $item_page = absint($row['page_number'] ?? $row['page'] ?? 0);
            if($page>0&&$item_page!==$page)continue;
            if($item_page<1||$item_page>(int)$edition['pages'])continue;
            $text = self::limit_text(wp_strip_all_tags((string) ($row['text_content'] ?? $row['text'] ?? '')),200000);
            if ('' !== trim($text)) $items[] = array('page' => $item_page, 'text' => $text, 'language' => self::limit_text(sanitize_text_field((string) ($row['language'] ?? $edition['language'])),35), 'quality' => max(0,min(100,(float) ($row['quality_score'] ?? 0))));
        }
        if($external_used)$ocr_total=$external_input_total;
        elseif($page>0)$ocr_total=count($items);
        else{
            $wpdb->last_error='';
            $ocr_total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d',$edition_id));
            if(''!==(string)$wpdb->last_error){
                PLDR_Core::audit('edition',$edition_id,'reflow_source_count_failed',array());
                return array('error'=>PLDR_Core::machine_error('pldr_reflow_source_count','Reflow OCR source total could not be verified; no misleading completeness metadata was returned.',503,array('degraded'=>true)));
            }
        }
        $truncated=$external_used?$external_input_total>count($items):($page===0&&$ocr_total>count($items));
        return array('edition_id' => $edition_id,'requested_page'=>$page ?: null,'provider' => $provider,'pages' => $items,'available' => (bool) $items,'derived' => true,'original_immutable' => true,'page_window_limit'=>$page>0?1:self::REFLOW_WINDOW_LIMIT,'ocr_pages_total'=>$ocr_total,'provider_input_total'=>$external_input_total,'provider_input_truncated'=>$external_used&&$external_input_total>$limit,'truncated'=>$truncated);
    }

    public static function outline(int $edition_id): array {
        global $wpdb;
        $edition = self::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $external_failure=false;$external_error='';
        try {
            $external = apply_filters('pldr_outline_extract', null, $edition_id, $edition);
        } catch (Throwable $e) {
            $external=null;$external_failure=true;$external_error=self::limit_text(sanitize_text_field($e->getMessage()),500);
            PLDR_Core::audit('edition',$edition_id,'outline_provider_failed',array('error'=>$external_error));
        }
        if (is_array($external) && isset($external['items']) && is_array($external['items'])) {
            $provider=self::limit_text(sanitize_text_field((string)($external['provider']??'')),80);
            if(''===$provider){$external_failure=true;$external_error='provider-provenance-missing';}
            else {
                $input=array_values($external['items']);$projected=array();
                foreach(array_slice($input,0,301) as $item){
                    if(!is_array($item))continue;
                    $page=absint($item['page']??$item['page_number']??0);
                    if($page<1||$page>(int)$edition['pages'])continue;
                    $title=self::limit_text(trim(wp_strip_all_tags((string)($item['title']??$item['label']??''))),200);
                    if(''===$title)continue;
                    $projected[]=array('page'=>$page,'title'=>$title,'level'=>max(1,min(6,absint($item['level']??1))));
                    if(count($projected)>=300)break;
                }
                if($projected)return array('items'=>$projected,'provider'=>$provider,'derived'=>true,'original_immutable'=>true,'input_count'=>count($input),'returned'=>count($projected),'truncated'=>count($input)>300,'provider_failure'=>false);
            }
        } elseif(null!==$external) {
            $external_failure=true;$external_error='invalid-provider-response';
        }
        $items = array();
        $wpdb->last_error='';
        $rows=self::ocr_pages($edition_id,0,1000,0);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'outline_source_read_failed',array('limit'=>1000));
            return array('error'=>PLDR_Core::machine_error('pldr_outline_source_read','Outline OCR source state could not be read reliably; no empty heuristic outline was returned.',503,array('degraded'=>true,'provider_failure'=>$external_failure)));
        }
        foreach ($rows as $row) {
            $lines = preg_split('/\R/u', (string) $row['text_content']) ?: array();
            foreach (array_slice($lines, 0, 80) as $line) {
                $line = trim(wp_strip_all_tags($line));
                if ('' === $line || (function_exists('mb_strlen') ? mb_strlen($line, 'UTF-8') : strlen($line)) > 120) continue;
                if (preg_match('/^(chapter|section|part|باب|فصل|حصہ|کتاب)\b[\s\p{P}\d\p{L}]*/iu', $line) || preg_match('/^\d{1,3}[\.\-:]\s+\S/u', $line)) {
                    $items[] = array('page' => (int) $row['page_number'], 'title' => $line, 'level' => 1);
                }
                if (count($items) >= 300) break 2;
            }
        }
        $wpdb->last_error='';
        $ocr_total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d',$edition_id));
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'outline_source_count_failed',array());
            return array('error'=>PLDR_Core::machine_error('pldr_outline_source_count','Outline OCR source total could not be verified; no misleading completeness metadata was returned.',503,array('degraded'=>true,'provider_failure'=>$external_failure)));
        }
        return array('items' => $items, 'provider' => 'ocr-heading-heuristic', 'derived' => true, 'original_immutable' => true,'scan_page_limit'=>1000,'ocr_pages_scanned'=>count($rows),'ocr_pages_total'=>$ocr_total,'truncated'=>$ocr_total>count($rows)||count($items)>=300,'provider_failure'=>$external_failure,'provider_error'=>$external_error);
    }

    public static function compare(int $left, int $right): array {
        global $wpdb;
        $a = self::require_edition($left); $b = self::require_edition($right);
        if (is_wp_error($a)) return array('error' => $a);
        if (is_wp_error($b)) return array('error' => $b);
        $wpdb->last_error='';
        $left_pages=self::ocr_pages($left,0,1000,0);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$left,'edition_compare_left_read_failed',array('right'=>$right,'limit'=>1000));
            return array('error'=>PLDR_Core::machine_error('pldr_compare_left_read','Left-edition OCR comparison evidence could not be read reliably; no partial comparison was returned.',503,array('degraded'=>true)));
        }
        $pa = array_column($left_pages, null, 'page_number');
        $pb = array_column(self::ocr_pages($right,0,1000,0), null, 'page_number');
        $declared_max=max((int)($a['pages']??0),(int)($b['pages']??0));
        $candidate_max=min(1000,max($declared_max,count($pa),count($pb)));
        $changed = array(); $same = 0; $missing = 0; $processed=0; $result_capped=false;
        for ($p = 1; $p <= $candidate_max; $p++) {
            $processed++;
            $ta = PLDR_Core::normalize_search((string) ($pa[$p]['text_content'] ?? ''));
            $tb = PLDR_Core::normalize_search((string) ($pb[$p]['text_content'] ?? ''));
            if ('' === $ta || '' === $tb) { $missing++; continue; }
            if (hash_equals(hash('sha256', $ta), hash('sha256', $tb))) { $same++; continue; }
            similar_text(substr($ta, 0, 20000), substr($tb, 0, 20000), $pct);
            $changed[] = array('page' => $p, 'similarity' => round((float) $pct, 2), 'left_excerpt' => self::excerpt($ta), 'right_excerpt' => self::excerpt($tb));
            if (count($changed) >= 500) { $result_capped=true; break; }
        }
        $page_scan_capped=$declared_max>1000;
        $truncated=$result_capped||$page_scan_capped||$processed<$candidate_max;
        return array('left' => $left, 'right' => $right, 'pages_compared' => $processed, 'candidate_pages' => $candidate_max, 'declared_page_span'=>$declared_max, 'same_pages' => $same, 'pages_without_comparable_ocr' => $missing, 'changed' => $changed, 'changed_page_limit'=>500, 'results_truncated'=>$result_capped, 'page_scan_truncated'=>$page_scan_capped, 'truncated'=>$truncated, 'derived_from_ocr' => true,'comparison_page_limit'=>1000);
    }

    private static function excerpt(string $text): string {
        return function_exists('mb_substr') ? mb_substr($text, 0, 280, 'UTF-8') : substr($text, 0, 280);
    }

    private static function limit_text(string $text,int $limit):string {
        return function_exists('mb_substr')?mb_substr($text,0,$limit,'UTF-8'):substr($text,0,$limit);
    }
}
