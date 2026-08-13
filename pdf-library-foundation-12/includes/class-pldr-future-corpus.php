<?php

defined('ABSPATH') || exit;

final class PLDR_Future_Corpus {
    public static function manifest(int $edition_id,string $cursor='',int $limit=250):array {
        global $wpdb;
        $edition=PLDR_Future_Data::require_edition($edition_id);if(is_wp_error($edition))return array('error'=>$edition);
        $doc=PLDR_Core::document((int)$edition['document_id']);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_corpus_document_read','AI corpus document classification could not be read reliably; corpus access was denied.',503,array('degraded'=>true)));
        if(!$doc)return array('error'=>PLDR_Core::machine_error('pldr_corpus_document','Document not found.',404));
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
        $limit=max(1,min(500,$limit));$context=hash('sha256',$edition_id.'|'.(string)$edition['public_id'].'|'.get_current_user_id().'|file16-corpus-v1');
        $after_page=self::decode_cursor($cursor,$context);if(is_wp_error($after_page))return array('error'=>$after_page);
        $wpdb->last_error='';
        $rows=PLDR_Future_Data::ocr_pages($edition_id,0,$limit+1,0,$after_page);
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',$edition_id,'ai_corpus_source_read_failed',array('after_page'=>$after_page,'limit'=>$limit));
            return array('error'=>PLDR_Core::machine_error('pldr_corpus_source_read','AI corpus source text could not be read reliably; no partial manifest was returned.',503,array('degraded'=>true)));
        }
        $has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        $chunks=array();
        foreach($rows as $row){$text=trim((string)$row['text_content']);if(''===$text)continue;$chunks[]=array('chunk_id'=>'f12:'.$edition['public_id'].':e'.$edition_id.':p'.(int)$row['page_number'],'page'=>(int)$row['page_number'],'citation_anchor'=>PLDR_Reader::citation($edition,(int)$row['page_number'],'sabri'),'rights'=>(string)$edition['rights_basis'],'quality'=>(float)$row['quality_score'],'text_sha256'=>hash('sha256',$text),'retrieval_url'=>rest_url('pldr/v1/future/reflow/'.$edition_id.'?page='.(int)$row['page_number']));}
        $last=$rows?(int)$rows[count($rows)-1]['page_number']:0;$next=$has_more&&$last>0?self::encode_cursor($last,$context):null;
        return array('manifest_version'=>'1.1','owner'=>'File 12','consumer'=>'File 16 only through approved contract','consumer_authorized'=>true,'document_id'=>$edition['public_id'],'edition_id'=>$edition_id,'rights'=>(string)$edition['rights_basis'],'license'=>(string)$edition['license_code'],'entitlement_rechecked'=>true,'limit'=>$limit,'next_cursor'=>$next,'has_more'=>$has_more,'cursor_supported'=>true,'chunks'=>$chunks,'contains_text'=>false,'diagnosis_or_prescription_authority'=>false);
    }

    private static function encode_cursor(int $page,string $context):string {
        $json=wp_json_encode(array('p'=>$page,'c'=>$context));if(!is_string($json))return '';
        $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
    }
    private static function decode_cursor(string $token,string $context) {
        $token=trim($token);if(''===$token)return 0;
        if(strlen($token)>500||1!==substr_count($token,'.'))return PLDR_Core::machine_error('pldr_corpus_cursor','AI corpus cursor is malformed.',400);
        [$payload,$sig]=explode('.',$token,2);$expected=hash_hmac('sha256',$payload,wp_salt('auth'));if(!hash_equals($expected,$sig))return PLDR_Core::machine_error('pldr_corpus_cursor','AI corpus cursor signature is invalid.',400);
        $padded=$payload.str_repeat('=',(4-strlen($payload)%4)%4);$raw=base64_decode(strtr($padded,'-_','+/'),true);$decoded=is_string($raw)?json_decode($raw,true):null;
        if(!is_array($decoded)||!isset($decoded['p'],$decoded['c'])||!hash_equals($context,(string)$decoded['c'])||absint($decoded['p'])<1)return PLDR_Core::machine_error('pldr_corpus_cursor','AI corpus cursor does not match this edition/consumer or is invalid.',400);
        return absint($decoded['p']);
    }
}
