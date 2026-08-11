<?php

defined('ABSPATH') || exit;

final class PLDR_Future_OCR_Lab {
    public static function report(int $edition_id): array {
        global $wpdb;
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $row = $wpdb->get_row($wpdb->prepare('SELECT COUNT(*) pages,AVG(quality_score) avg_quality,MIN(quality_score) min_quality,MAX(quality_score) max_quality FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d', $edition_id), ARRAY_A) ?: array();
        $corrections = $wpdb->get_results($wpdb->prepare('SELECT id,page_number,status,original_text,corrected_text,review_note,submitted_by,reviewed_by,updated_at FROM ' . PLDR_Core::table('ocr_corrections') . ' WHERE edition_id=%d ORDER BY page_number ASC,id ASC LIMIT 500', $edition_id), ARRAY_A) ?: array();
        $heat = $wpdb->get_results($wpdb->prepare('SELECT page_number,quality_score FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d ORDER BY page_number ASC', $edition_id), ARRAY_A) ?: array();
        return array('edition_id'=>$edition_id,'pages'=>(int)($row['pages']??0),'average_quality'=>round((float)($row['avg_quality']??0),2),'minimum_quality'=>(float)($row['min_quality']??0),'maximum_quality'=>(float)($row['max_quality']??0),'heatmap'=>$heat,'corrections'=>$corrections,'original_scan_immutable'=>true);
    }

    public static function submit(int $edition_id, int $page, string $original, string $corrected) {
        global $wpdb;
        if (!is_user_logged_in()) return PLDR_Core::machine_error('pldr_ocr_correction_login', 'Log in to submit an OCR correction.', 401);
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return $edition;
        if ($page < 1 || $page > (int) $edition['pages'] || '' === trim($corrected)) return PLDR_Core::machine_error('pldr_ocr_correction_input', 'A valid page and corrected text are required.', 400);
        $wpdb->insert(PLDR_Core::table('ocr_corrections'), array('edition_id'=>$edition_id,'page_number'=>$page,'original_text'=>sanitize_textarea_field($original),'corrected_text'=>sanitize_textarea_field($corrected),'status'=>'pending','submitted_by'=>get_current_user_id(),'reviewed_by'=>0,'review_note'=>'','version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        $id=(int)$wpdb->insert_id; PLDR_Core::audit('ocr_correction',$id,'submitted',array('edition_id'=>$edition_id,'page'=>$page));
        return array('id'=>$id,'status'=>'pending','original_scan_immutable'=>true);
    }

    public static function review(int $correction_id, string $decision, string $note) {
        global $wpdb;
        if (!PLDR_Core::authorize('manage') && !PLDR_Core::authorize('rights')) return PLDR_Core::machine_error('pldr_ocr_review_forbidden', 'OCR correction review authority is required.', 403);
        if (!in_array($decision,array('approved','rejected'),true) || ''===trim($note)) return PLDR_Core::machine_error('pldr_ocr_review_input','A decision and review note are required.',400);
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('ocr_corrections').' WHERE id=%d',$correction_id),ARRAY_A); if(!$row)return PLDR_Core::machine_error('pldr_ocr_correction_missing','OCR correction not found.',404);
        $wpdb->update(PLDR_Core::table('ocr_corrections'),array('status'=>$decision,'reviewed_by'=>get_current_user_id(),'review_note'=>sanitize_textarea_field($note),'version'=>(int)$row['version']+1,'updated_at'=>PLDR_Core::now()),array('id'=>$correction_id));
        if('approved'===$decision){$page=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d AND page_number=%d',(int)$row['edition_id'],(int)$row['page_number']),ARRAY_A);if($page){$derived=(string)$row['corrected_text'];$wpdb->update(PLDR_Core::table('ocr_text'),array('text_content'=>$derived,'normalized_text'=>PLDR_Core::normalize_search($derived),'updated_at'=>PLDR_Core::now()),array('edition_id'=>(int)$row['edition_id'],'page_number'=>(int)$row['page_number']));}}
        PLDR_Core::audit('ocr_correction',$correction_id,'reviewed',array('decision'=>$decision));
        return array('id'=>$correction_id,'status'=>$decision,'original_scan_immutable'=>true);
    }
}
