<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Corpus {
    public static function manifest(int $edition_id,int $offset=0,int $limit=250):array {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);
        $doc=PLDR_Core::document((int)$edition['document_id']);if(!$doc)return array('error'=>PLDR_Core::machine_error('pldr_corpus_document','Document not found.',404));
        try{
            $patient_case_allowed='patient-cases'!==$doc['category']||(bool)apply_filters('pldr_ai_patient_case_allowed',false,$edition_id,$doc);
            $allowed=(bool)apply_filters('pldr_ai_corpus_allowed',false,$edition_id,$edition,$doc);
            $consumer_allowed=(bool)apply_filters('pldr_ai_corpus_consumer_allowed',false,get_current_user_id(),$edition_id,$edition,$doc);
        }catch(Throwable $e){
            PLDR_Core::audit('edition',$edition_id,'ai_corpus_policy_provider_failed',array('document_id'=>(int)$edition['document_id'],'provider_failure'=>true));
            return array('error'=>PLDR_Core::machine_error('pldr_corpus_policy_provider','AI corpus policy/consumer authorization could not be verified; corpus access was denied.',503,array('degraded'=>true,'provider_failure'=>true)));
        }
        if(!$patient_case_allowed)return array('error'=>PLDR_Core::machine_error('pldr_corpus_patient_case','Patient-case documents are excluded from AI corpus manifests unless separately approved.',403));
        if(!$allowed)return array('error'=>PLDR_Core::machine_error('pldr_corpus_not_allowed','This edition is not allowlisted for AI corpus use.',403));
        if(!$consumer_allowed)return array('error'=>PLDR_Core::machine_error('pldr_corpus_consumer_forbidden','AI corpus manifests are available only through the approved File 16 consumer contract.',403,array('owner'=>'File 12','consumer'=>'File 16')));
        $offset=max(0,min(100000,$offset));$limit=max(1,min(500,$limit));
        $wpdb->last_error='';
        $rows=PLDR_Future_Data::ocr_pages($edition_id,0,$limit+1,$offset);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'ai_corpus_source_read_failed',array('offset'=>$offset,'limit'=>$limit));
            return array('error'=>PLDR_Core::machine_error('pldr_corpus_source_read','AI corpus source text could not be read reliably; no partial manifest was returned.',503,array('degraded'=>true)));
        }
        $has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        $chunks=array();
        foreach($rows as $row){$text=trim((string)$row['text_content']);if(''===$text)continue;$chunks[]=array('chunk_id'=>'f12:'.$edition['public_id'].':e'.$edition_id.':p'.(int)$row['page_number'],'page'=>(int)$row['page_number'],'citation_anchor'=>PLDR_Reader::citation($edition,(int)$row['page_number'],'sabri'),'rights'=>(string)$edition['rights_basis'],'quality'=>(float)$row['quality_score'],'text_sha256'=>hash('sha256',$text),'retrieval_url'=>rest_url('pldr/v1/future/reflow/'.$edition_id.'?page='.(int)$row['page_number']));}
        return array('manifest_version'=>'1.1','owner'=>'File 12','consumer'=>'File 16 only through approved contract','consumer_authorized'=>true,'document_id'=>$edition['public_id'],'edition_id'=>$edition_id,'rights'=>(string)$edition['rights_basis'],'license'=>(string)$edition['license_code'],'entitlement_rechecked'=>true,'offset'=>$offset,'limit'=>$limit,'next_offset'=>$has_more?$offset+$limit:null,'has_more'=>$has_more,'chunks'=>$chunks,'contains_text'=>false,'diagnosis_or_prescription_authority'=>false);
    }
}
