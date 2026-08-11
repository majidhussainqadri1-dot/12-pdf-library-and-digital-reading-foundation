<?php

defined('ABSPATH') || exit;

final class PLDR_Future_OCR_Lab {
    private const CORRECTION_LIMIT = 500;
    private const HEATMAP_LIMIT = 2000;

    public static function report(int $edition_id): array {
        global $wpdb;
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return array('error' => $edition);
        $document_id = (int) $edition['document_id'];
        $can_review = PLDR_Core::authorize('manage', $document_id) || PLDR_Core::authorize('rights', $document_id);
        $row = $wpdb->get_row($wpdb->prepare('SELECT COUNT(*) pages,AVG(quality_score) avg_quality,MIN(quality_score) min_quality,MAX(quality_score) max_quality FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d', $edition_id), ARRAY_A) ?: array();
        $correction_total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('ocr_corrections').' WHERE edition_id=%d',$edition_id));
        if ($can_review) {
            $corrections = $wpdb->get_results($wpdb->prepare('SELECT id,page_number,status,original_text,corrected_text,review_note,submitted_by,reviewed_by,version,updated_at FROM ' . PLDR_Core::table('ocr_corrections') . ' WHERE edition_id=%d ORDER BY page_number ASC,id ASC LIMIT %d', $edition_id,self::CORRECTION_LIMIT), ARRAY_A) ?: array();
        } else {
            $corrections = $wpdb->get_results($wpdb->prepare('SELECT id,page_number,status,original_text,corrected_text,updated_at FROM ' . PLDR_Core::table('ocr_corrections') . ' WHERE edition_id=%d ORDER BY page_number ASC,id ASC LIMIT %d', $edition_id,self::CORRECTION_LIMIT), ARRAY_A) ?: array();
        }
        $heat_total=(int)($row['pages']??0);
        $heat = $wpdb->get_results($wpdb->prepare('SELECT page_number,quality_score FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d ORDER BY page_number ASC LIMIT %d', $edition_id,self::HEATMAP_LIMIT), ARRAY_A) ?: array();
        return array(
            'edition_id'=>$edition_id,
            'pages'=>$heat_total,
            'average_quality'=>round((float)($row['avg_quality']??0),2),
            'minimum_quality'=>(float)($row['min_quality']??0),
            'maximum_quality'=>(float)($row['max_quality']??0),
            'heatmap'=>$heat,
            'heatmap_meta'=>array('limit'=>self::HEATMAP_LIMIT,'returned'=>count($heat),'total'=>$heat_total,'truncated'=>$heat_total>count($heat)),
            'corrections'=>$corrections,
            'corrections_meta'=>array('limit'=>self::CORRECTION_LIMIT,'returned'=>count($corrections),'total'=>$correction_total,'truncated'=>$correction_total>count($corrections)),
            'review_metadata_visible'=>$can_review,
            'original_scan_immutable'=>true,
        );
    }

    public static function submit(int $edition_id, int $page, string $original, string $corrected) {
        global $wpdb;
        if (!is_user_logged_in()) return PLDR_Core::machine_error('pldr_ocr_correction_login', 'Log in to submit an OCR correction.', 401);
        $edition = PLDR_Future_Data::require_edition($edition_id);
        if (is_wp_error($edition)) return $edition;
        $original = self::limit(sanitize_textarea_field($original),4000); $corrected = self::limit(sanitize_textarea_field($corrected),4000);
        if ($page < 1 || $page > (int) $edition['pages'] || '' === trim($original) || '' === trim($corrected)) return PLDR_Core::machine_error('pldr_ocr_correction_input', 'A valid page, original OCR excerpt and corrected text are required.', 400);
        $source = (string) $wpdb->get_var($wpdb->prepare('SELECT text_content FROM ' . PLDR_Core::table('ocr_text') . ' WHERE edition_id=%d AND page_number=%d', $edition_id, $page));
        if ('' === $source || false === strpos($source, $original)) return PLDR_Core::machine_error('pldr_ocr_correction_stale', 'The submitted original excerpt does not match the current OCR source layer.', 409);
        $stored=$wpdb->insert(PLDR_Core::table('ocr_corrections'), array('edition_id'=>$edition_id,'page_number'=>$page,'original_text'=>$original,'corrected_text'=>$corrected,'status'=>'pending','submitted_by'=>get_current_user_id(),'reviewed_by'=>0,'review_note'=>'','version'=>1,'created_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()));
        if(false===$stored||!(int)$wpdb->insert_id)return PLDR_Core::machine_error('pldr_ocr_correction_store','OCR correction could not be stored.',500);
        $id=(int)$wpdb->insert_id; PLDR_Core::audit('ocr_correction',$id,'submitted',array('edition_id'=>$edition_id,'page'=>$page));
        return array('id'=>$id,'status'=>'pending','original_scan_immutable'=>true);
    }

    public static function review(int $correction_id, string $decision, string $note) {
        global $wpdb;
        if (!in_array($decision,array('approved','rejected'),true)) return PLDR_Core::machine_error('pldr_ocr_review_input','A valid review decision is required.',400);
        $note=self::limit(sanitize_textarea_field($note),2000);
        if (''===trim($note)) return PLDR_Core::machine_error('pldr_ocr_review_input','A decision and review note are required.',400);
        $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('ocr_corrections').' WHERE id=%d',$correction_id),ARRAY_A);
        if(!$row)return PLDR_Core::machine_error('pldr_ocr_correction_missing','OCR correction not found.',404);
        $edition=PLDR_Core::edition((int)$row['edition_id']);
        if(!$edition)return PLDR_Core::machine_error('pldr_ocr_review_edition','OCR correction edition is unavailable.',404);
        $document_id=(int)$edition['document_id'];
        if (!PLDR_Core::authorize('manage',$document_id) && !PLDR_Core::authorize('rights',$document_id)) return PLDR_Core::machine_error('pldr_ocr_review_forbidden', 'OCR correction review authority is required for this document.', 403);
        if('approved'===$decision){$source=(string)$wpdb->get_var($wpdb->prepare('SELECT text_content FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d AND page_number=%d',(int)$row['edition_id'],(int)$row['page_number']));if(''===$source||false===strpos($source,(string)$row['original_text']))return PLDR_Core::machine_error('pldr_ocr_review_stale','The base OCR source changed or no longer contains the submitted excerpt; re-submit the correction against current OCR.',409);}
        $updated=$wpdb->update(PLDR_Core::table('ocr_corrections'),array('status'=>$decision,'reviewed_by'=>get_current_user_id(),'review_note'=>$note,'version'=>(int)$row['version']+1,'updated_at'=>PLDR_Core::now()),array('id'=>$correction_id,'version'=>(int)$row['version']));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_ocr_review_conflict','OCR correction changed concurrently; refresh before reviewing.',409);
        PLDR_Core::audit('ocr_correction',$correction_id,'reviewed',array('decision'=>$decision,'document_id'=>$document_id));
        return array('id'=>$correction_id,'status'=>$decision,'original_scan_immutable'=>true,'base_ocr_immutable'=>true,'derived_correction_layer'=>true);
    }
    private static function limit(string $value,int $length):string { return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length); }
}
