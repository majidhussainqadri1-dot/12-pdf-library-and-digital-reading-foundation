<?php

defined('ABSPATH') || exit;

/**
 * Privacy coverage for durable File 12 review/governance records that remain
 * distinct from the private reading-state exporter/eraser.
 */
final class PLDR_Privacy_Extension {
    private const BATCH=50;

    public static function hooks():void {
        add_filter('wp_privacy_personal_data_exporters',array(__CLASS__,'exporters'),20);
        add_filter('wp_privacy_personal_data_erasers',array(__CLASS__,'erasers'),20);
    }

    public static function exporters(array $exporters):array {
        $exporters['pldr-review-records']=array(
            'exporter_friendly_name'=>__('PDF Library review and rights records','pdf-library-digital-reading'),
            'callback'=>array(__CLASS__,'export'),
        );
        return $exporters;
    }

    public static function erasers(array $erasers):array {
        $erasers['pldr-review-records']=array(
            'eraser_friendly_name'=>__('PDF Library retained review and rights records','pdf-library-digital-reading'),
            'callback'=>array(__CLASS__,'erase'),
        );
        return $erasers;
    }

    private static function user_id(string $email):int {
        $user=get_user_by('email',$email);return $user?(int)$user->ID:0;
    }

    private static function exists(string $suffix):bool {
        global $wpdb;$table=PLDR_Core::table($suffix);$wpdb->last_error='';$found=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$table));
        return ''===(string)$wpdb->last_error&&$found===$table;
    }

    private static function fail(array $data,string $scope,int $user_id):array {
        PLDR_Core::audit('privacy',0,'privacy_review_export_read_failed',array('subject_ref'=>substr(hash_hmac('sha256',(string)$user_id,wp_salt('auth')),0,24),'scope'=>$scope),$user_id);
        return array('data'=>$data,'done'=>false);
    }

    public static function export(string $email,int $page=1):array {
        global $wpdb;$user_id=self::user_id($email);if(!$user_id)return array('data'=>array(),'done'=>true);
        $limit=self::BATCH;$offset=(max(1,$page)-1)*$limit;$data=array();$counts=array();
        $specs=array(
            'ocr_corrections'=>array('where'=>'submitted_by=%d OR reviewed_by=%d','args'=>array($user_id,$user_id),'id'=>'id','order'=>'id','group'=>'pldr-ocr-review-records','label'=>__('PDF Library OCR correction/review records','pdf-library-digital-reading'),'fields'=>array('edition_id','page_number','status','original_text','corrected_text','submitted_by','reviewed_by','created_at','updated_at')),
            'rights_cases'=>array('where'=>'reporter_id=%d','args'=>array($user_id),'id'=>'id','order'=>'id','group'=>'pldr-rights-cases','label'=>__('PDF Library rights/takedown reports','pdf-library-digital-reading'),'fields'=>array('case_key','document_id','reason','evidence_json','state','decision_note','created_at','updated_at')),
            'a11y_audits'=>array('where'=>'verified_by=%d','args'=>array($user_id),'id'=>'edition_id','order'=>'edition_id','group'=>'pldr-accessibility-verification','label'=>__('PDF Library accessibility verification records','pdf-library-digital-reading'),'fields'=>array('edition_id','score','status','provider','verified_at','updated_at')),
        );
        foreach($specs as $suffix=>$spec){
            $exists=self::exists($suffix);if(''!==(string)$wpdb->last_error)return self::fail($data,$suffix.'_table',$user_id);
            if(!$exists){$counts[]=0;continue;}
            $table=PLDR_Core::table($suffix);$wpdb->last_error='';$args=array_merge($spec['args'],array($limit,$offset));
            $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} WHERE {$spec['where']} ORDER BY {$spec['order']} ASC LIMIT %d OFFSET %d",$args),ARRAY_A);
            if(''!==(string)$wpdb->last_error)return self::fail($data,$suffix,$user_id);
            $rows=is_array($rows)?$rows:array();$counts[]=count($rows);
            foreach($rows as $row){$fields=array();foreach($spec['fields'] as $field)$fields[]=array('name'=>$field,'value'=>is_scalar($row[$field]??'')?(string)($row[$field]??''):wp_json_encode($row[$field]??null));$data[]=array('group_id'=>$spec['group'],'group_label'=>$spec['label'],'item_id'=>$spec['group'].'-'.sanitize_key((string)($row[$spec['id']]??'0')),'data'=>$fields);}
        }
        return array('data'=>$data,'done'=>max($counts?:array(0))<$limit);
    }

    public static function erase(string $email,int $page=1):array {
        global $wpdb;$user_id=self::user_id($email);if(!$user_id)return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);
        if(!self::exists('rights_cases')){
            if(''!==(string)$wpdb->last_error)return array('items_removed'=>false,'items_retained'=>true,'messages'=>array(__('File 12 retained rights-case state could not be reconciled; retry is required.','pdf-library-digital-reading')),'done'=>false);
            return array('items_removed'=>false,'items_retained'=>false,'messages'=>array(),'done'=>true);
        }
        $table=PLDR_Core::table('rights_cases');$wpdb->last_error='';$open=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$table} WHERE reporter_id=%d AND state<>%s",$user_id,'closed'));
        if(''!==(string)$wpdb->last_error)return array('items_removed'=>false,'items_retained'=>true,'messages'=>array(__('File 12 retained rights-case state could not be reconciled; retry is required.','pdf-library-digital-reading')),'done'=>false);
        return array('items_removed'=>false,'items_retained'=>$open>0,'messages'=>$open>0?array(__('Open File 12 rights/dispute records remain retained until their governed case lifecycle is closed.','pdf-library-digital-reading')):array(),'done'=>true);
    }
}
