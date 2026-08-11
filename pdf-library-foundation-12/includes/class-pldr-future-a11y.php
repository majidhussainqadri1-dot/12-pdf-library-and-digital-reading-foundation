<?php

defined('ABSPATH') || exit;

final class PLDR_Future_A11y {
    public static function inspect(int $edition_id,bool $refresh=false):array {
        global $wpdb;
        if($refresh&&!PLDR_Core::authorize('manage')&&!PLDR_Core::authorize('rights'))return array('error'=>PLDR_Core::machine_error('pldr_a11y_refresh_forbidden','Refreshing an accessibility audit requires review authority.',403));
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        if(!$refresh){$row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('a11y_audits').' WHERE edition_id=%d',$edition_id),ARRAY_A);if($row)return self::dto($row);}
        $ocr=PLDR_Future_Data::ocr_pages($edition_id);$findings=array();$score=30;
        if(!empty($edition['title']))$score+=10;else$findings[]='Document title metadata is missing.';
        if(!empty($edition['language']))$score+=10;else$findings[]='Document language metadata is missing.';
        if($ocr){$avg=array_sum(array_map(static fn($r)=>(float)$r['quality_score'],$ocr))/count($ocr);$score+=min(25,$avg/4);}else$findings[]='No lawful OCR text is available for accessible text fallback.';
        $thumbs=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s',$edition_id,'thumbnail','available'));
        if($thumbs>0)$score+=5;else$findings[]='Page preview derivatives are unavailable.';
        $external=apply_filters('pldr_accessibility_inspect',null,$edition_id,$edition);$provider='heuristic';
        if(is_array($external)){$provider=self::limit(sanitize_text_field((string)($external['provider']??'adapter')),80);$score=max(0,min(100,(float)($external['score']??$score)));foreach((array)($external['findings']??array()) as $finding){$finding=self::limit(sanitize_text_field((string)$finding),500);if(''!==$finding)$findings[]=$finding;}}
        $findings=array_values(array_unique($findings));
        $status=$score>=90?'excellent':($score>=75?'good':($score>=50?'partial':'needs-remediation'));
        $stored=$wpdb->replace(PLDR_Core::table('a11y_audits'),array('edition_id'=>$edition_id,'score'=>$score,'status'=>$status,'findings_json'=>wp_json_encode($findings),'provider'=>$provider,'verified_by'=>0,'verified_at'=>null,'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return array('error'=>PLDR_Core::machine_error('pldr_a11y_store','Accessibility assessment could not be stored.',500));
        return array('edition_id'=>$edition_id,'score'=>round($score,2),'status'=>$status,'findings'=>$findings,'provider'=>$provider,'verified'=>false,'public_badge_allowed'=>false);
    }

    public static function verify(int $edition_id,string $note='') {
        global $wpdb;
        if(!PLDR_Core::authorize('manage')&&!PLDR_Core::authorize('rights'))return PLDR_Core::machine_error('pldr_a11y_verify_forbidden','Accessibility verification authority is required.',403);
        $report=self::inspect($edition_id,false);if(isset($report['error']))return $report['error'];
        if((float)$report['score']<75)return PLDR_Core::machine_error('pldr_a11y_verify_score','Accessibility status is below the verification threshold.',409);
        $updated=$wpdb->update(PLDR_Core::table('a11y_audits'),array('verified_by'=>get_current_user_id(),'verified_at'=>PLDR_Core::now(),'updated_at'=>PLDR_Core::now()),array('edition_id'=>$edition_id));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_a11y_verify_store','Accessibility verification could not be persisted.',500);
        PLDR_Core::audit('edition',$edition_id,'accessibility_verified',array('note'=>sanitize_textarea_field($note)));
        $report['verified']=true;$report['public_badge_allowed']=true;return $report;
    }

    private static function dto(array $row):array {return array('edition_id'=>(int)$row['edition_id'],'score'=>(float)$row['score'],'status'=>$row['status'],'findings'=>json_decode((string)$row['findings_json'],true)?:array(),'provider'=>$row['provider'],'verified'=>(int)$row['verified_by']>0,'verified_at'=>$row['verified_at'],'public_badge_allowed'=>(int)$row['verified_by']>0);}
    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
