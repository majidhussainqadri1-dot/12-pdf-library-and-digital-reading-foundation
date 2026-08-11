<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Data {
    public static function require_edition(int $edition_id, string $operation = 'read') {
        $edition = PLDR_Core::edition($edition_id);
        if (!$edition || !PLDR_Access::can_access_edition($edition_id, $operation, get_current_user_id())) {
            return PLDR_Core::machine_error('pldr_future_forbidden', 'This document edition is unavailable for the requested advanced reading operation.', 403);
        }
        return $edition;
    }

    public static function ocr_pages(int $edition_id): array {
        global $wpdb;
        return $wpdb->get_results($wpdb->prepare(
            'SELECT page_number,language,quality_score,text_content,normalized_text FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d ORDER BY page_number ASC',
            $edition_id
        ), ARRAY_A) ?: array();
    }

    public static function reflow(int $edition_id): array {
        $edition = self::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $pages = self::ocr_pages($edition_id);
        $provider = 'lawful-ocr';
        if (!$pages) {
            $external = apply_filters('pldr_reflow_extract', null, $edition_id, $edition);
            if (is_array($external) && !empty($external['pages'])) {
                $pages = (array) $external['pages'];
                $provider = sanitize_text_field((string) ($external['provider'] ?? 'adapter'));
            }
        }
        $items = array();
        foreach ($pages as $row) {
            $page = absint($row['page_number'] ?? 0);
            $text = wp_strip_all_tags((string) ($row['text_content'] ?? $row['text'] ?? ''));
            if ($page > 0 && '' !== trim($text)) $items[] = array('page' => $page, 'text' => $text, 'language' => sanitize_text_field((string) ($row['language'] ?? $edition['language'])), 'quality' => (float) ($row['quality_score'] ?? 0));
        }
        return array('edition_id' => $edition_id, 'provider' => $provider, 'pages' => $items, 'available' => (bool) $items, 'derived' => true, 'original_immutable' => true);
    }

    public static function outline(int $edition_id): array {
        $edition = self::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $external = apply_filters('pldr_outline_extract', null, $edition_id, $edition);
        if (is_array($external) && !empty($external['items'])) return array('items' => array_values($external['items']), 'provider' => sanitize_text_field((string) ($external['provider'] ?? 'adapter')), 'derived' => true);
        $items = array();
        foreach (self::ocr_pages($edition_id) as $row) {
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
        return array('items' => $items, 'provider' => 'ocr-heading-heuristic', 'derived' => true, 'original_immutable' => true);
    }

    public static function compare(int $left, int $right): array {
        $a = self::require_edition($left); $b = self::require_edition($right);
        if (is_wp_error($a)) return array('error' => $a);
        if (is_wp_error($b)) return array('error' => $b);
        $pa = array_column(self::ocr_pages($left), null, 'page_number');
        $pb = array_column(self::ocr_pages($right), null, 'page_number');
        $max = max((int) ($a['pages'] ?? 0), (int) ($b['pages'] ?? 0), count($pa), count($pb));
        $max = min(5000, $max);
        $changed = array(); $same = 0; $missing = 0;
        for ($p = 1; $p <= $max; $p++) {
            $ta = PLDR_Core::normalize_search((string) ($pa[$p]['text_content'] ?? ''));
            $tb = PLDR_Core::normalize_search((string) ($pb[$p]['text_content'] ?? ''));
            if ('' === $ta || '' === $tb) { $missing++; continue; }
            if (hash_equals(hash('sha256', $ta), hash('sha256', $tb))) { $same++; continue; }
            similar_text(substr($ta, 0, 20000), substr($tb, 0, 20000), $pct);
            $changed[] = array('page' => $p, 'similarity' => round((float) $pct, 2), 'left_excerpt' => self::excerpt($ta), 'right_excerpt' => self::excerpt($tb));
            if (count($changed) >= 500) break;
        }
        return array('left' => $left, 'right' => $right, 'pages_compared' => $max, 'same_pages' => $same, 'pages_without_comparable_ocr' => $missing, 'changed' => $changed, 'derived_from_ocr' => true);
    }

    private static function excerpt(string $text): string {
        return function_exists('mb_substr') ? mb_substr($text, 0, 280, 'UTF-8') : substr($text, 0, 280);
    }
}
