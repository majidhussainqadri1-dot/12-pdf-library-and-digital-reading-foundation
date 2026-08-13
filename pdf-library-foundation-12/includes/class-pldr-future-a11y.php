<?php

defined('ABSPATH') || exit;

final class PLDR_Future_A11y {
    private const PROVIDER_FINDINGS_LIMIT = 50;

    public static function inspect(int $edition_id,bool $refresh=false):array {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);
        if(is_wp_error($edition))return array('error'=>$edition);
        $document_id=(int)$edition['document_id'];
        $can_refresh=PLDR_Core::authorize('manage',$document_id)||PLDR_Core::authorize('rights',$document_id);
        if($refresh&&!$can_refresh)return array('error'=>PLDR_Core::machine_error('pldr_a11y_refresh_forbidden','Refreshing this accessibility audit requires review authority for the document.',403));
        if(!$refresh){
            $wpdb->last_error='';
            $row=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.PLDR_Core::table('a11y_audits').' WHERE edition_id=%d',$edition_id),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_a11y_read','Stored accessibility state could not be read reliably.',503,array('degraded'=>true)));
            if($row)return self::dto($row);
        }

        $wpdb->last_error='';
        $ocr_stats=$wpdb->get_row($wpdb->prepare('SELECT COUNT(*) page_count,AVG(quality_score) avg_quality FROM '.PLDR_Core::table('ocr_text').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_a11y_ocr_read','Accessibility OCR evidence could not be read reliably; no score was projected.',503,array('degraded'=>true)));
        $ocr_stats=is_array($ocr_stats)?$ocr_stats:array();
        $ocr_pages=(int)($ocr_stats['page_count']??0);
        $ocr_avg=(float)($ocr_stats['avg_quality']??0);
        $findings=array();$score=30;
        if(!empty($edition['title']))$score+=10;else$findings[]='Document title metadata is missing.';
        if(!empty($edition['language']))$score+=10;else$findings[]='Document language metadata is missing.';
        if($ocr_pages>0)$score+=min(25,$ocr_avg/4);else$findings[]='No lawful OCR text is available for accessible text fallback.';
        $wpdb->last_error='';
        $thumbs=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s',$edition_id,'thumbnail','available'));
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_a11y_derivative_read','Accessibility derivative evidence could not be read reliably; no score was projected.',503,array('degraded'=>true)));
        if($thumbs>0)$score+=5;else$findings[]='Page preview derivatives are unavailable.';
        $provider='heuristic';$provider_failure=false;$provider_input_total=0;
        if($refresh){
            try {$external=apply_filters('pldr_accessibility_inspect',null,$edition_id,$edition);} catch (Throwable $e) {
                $external=null;$provider_failure=true;$findings[]='External accessibility inspection provider failed; local heuristic assessment remains available.';
                PLDR_Core::audit('edition',$edition_id,'accessibility_provider_failed',array('document_id'=>$document_id,'provider_failure'=>true,'error_class'=>sanitize_key(get_class($e))));
            }
            if(is_array($external)){
                $provider_name=self::limit(sanitize_text_field((string)($external['provider']??'')),80);
                if(''===$provider_name){$provider_failure=true;$findings[]='External accessibility findings were ignored because provider provenance was missing.';}
                else{$provider=$provider_name;$score=max(0,min(100,(float)($external['score']??$score)));$provider_findings=(array)($external['findings']??array());$provider_input_total=count($provider_findings);foreach(array_slice($provider_findings,0,self::PROVIDER_FINDINGS_LIMIT) as $finding){$finding=self::limit(sanitize_text_field((string)$finding),500);if(''!==$finding)$findings[]=$finding;}}
            }
        }
        $findings=array_values(array_unique(array_slice($findings,0,self::PROVIDER_FINDINGS_LIMIT+10)));
        $status=$score>=90?'excellent':($score>=75?'good':($score>=50?'partial':'needs-remediation'));
        $report=array('edition_id'=>$edition_id,'score'=>round($score,2),'status'=>$status,'findings'=>$findings,'provider'=>$provider,'provider_failure'=>$provider_failure,'provider_findings_truncated'=>$provider_input_total>self::PROVIDER_FINDINGS_LIMIT,'verified'=>false,'verified_at'=>null,'public_badge_allowed'=>false,'ocr_pages_assessed'=>$ocr_pages,'ocr_average_quality'=>round($ocr_avg,2),'persisted'=>false);
        if(!$refresh)return $report;
        $finding_json=wp_json_encode($findings);if(!is_string($finding_json))return array('error'=>PLDR_Core::machine_error('pldr_a11y_encode','Accessibility assessment could not be encoded.',500));
        $stored=$wpdb->replace(PLDR_Core::table('a11y_audits'),array('edition_id'=>$edition_id,'score'=>$score,'status'=>$status,'findings_json'=>$finding_json,'provider'=>$provider,'verified_by'=>0,'verified_at'=>null,'updated_at'=>PLDR_Core::now()));
        if(false===$stored)return array('error'=>PLDR_Core::machine_error('pldr_a11y_store','Accessibility assessment could not be stored.',500));
        $report['persisted']=true;return $report;
    }

    public static function verify(int $edition_id,string $note='') {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return $edition;
        $document_id=(int)$edition['document_id'];
        if(!PLDR_Core::authorize('manage',$document_id)&&!PLDR_Core::authorize('rights',$document_id))return PLDR_Core::machine_error('pldr_a11y_verify_forbidden','Accessibility verification authority is required for this document.',403);
        $note=self::limit(sanitize_textarea_field($note),2000);
        $report=self::inspect($edition_id,true);if(isset($report['error']))return $report['error'];
        if((float)$report['score']<75)return PLDR_Core::machine_error('pldr_a11y_verify_score','Accessibility status is below the verification threshold.',409);
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT score,status,findings_json,provider,verified_by,updated_at FROM '.PLDR_Core::table('a11y_audits').' WHERE edition_id=%d',$edition_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_a11y_verify_read','Fresh accessibility evidence could not be re-read for verification.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_a11y_verify_store','Fresh accessibility assessment disappeared before verification.',409);
        $verified_at=PLDR_Core::now();
        $updated=$wpdb->query($wpdb->prepare('UPDATE '.PLDR_Core::table('a11y_audits').' SET verified_by=%d,verified_at=%s,updated_at=%s WHERE edition_id=%d AND verified_by=0 AND score=%f AND status=%s AND findings_json=%s AND provider=%s AND updated_at=%s',get_current_user_id(),$verified_at,$verified_at,$edition_id,(float)$row['score'],(string)$row['status'],(string)$row['findings_json'],(string)$row['provider'],(string)$row['updated_at']));
        if(1!==$updated)return PLDR_Core::machine_error('pldr_a11y_verify_conflict','Accessibility assessment changed before verification; refresh and review the new assessment.',409);
        PLDR_Core::audit('edition',$edition_id,'accessibility_verified',array('document_id'=>$document_id,'note_present'=>''!==trim($note)));
        $report['verified']=true;$report['verified_at']=$verified_at;$report['public_badge_allowed']=true;return $report;
    }

    private static function dto(array $row):array {
        $findings=json_decode((string)$row['findings_json'],true);
        if(!is_array($findings)){
            PLDR_Core::audit('edition',(int)$row['edition_id'],'accessibility_audit_corrupt',array('provider'=>self::limit(sanitize_text_field((string)$row['provider']),80),'verified'=>(int)$row['verified_by']>0));
            return array('error'=>PLDR_Core::machine_error('pldr_a11y_corrupt','Stored accessibility findings failed integrity validation; verification/public badge state was not trusted.',500,array('edition_id'=>(int)$row['edition_id'])));
        }
        return array('edition_id'=>(int)$row['edition_id'],'score'=>(float)$row['score'],'status'=>$row['status'],'findings'=>$findings,'provider'=>$row['provider'],'verified'=>(int)$row['verified_by']>0,'verified_at'=>$row['verified_at'],'public_badge_allowed'=>(int)$row['verified_by']>0,'persisted'=>true);
    }
    private static function limit(string $value,int $length):string {return function_exists('mb_substr')?mb_substr($value,0,$length,'UTF-8'):substr($value,0,$length);}
}
