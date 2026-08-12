<?php

defined('ABSPATH') || exit;

final class PLDR_REST {
    private const READER_THUMB_LIMIT = 300;
    private const READER_PREVIEW_GRANT_LIMIT = 50;

    public static function register(): void {
        register_rest_route('pldr/v1','/library',array('methods'=>'GET','callback'=>array(__CLASS__,'library'),'permission_callback'=>'__return_true','args'=>array('q'=>array('sanitize_callback'=>'sanitize_text_field'),'type'=>array('sanitize_callback'=>'sanitize_key'),'category'=>array('sanitize_callback'=>'sanitize_key'),'language'=>array('sanitize_callback'=>'sanitize_text_field'),'page'=>array('sanitize_callback'=>'absint'),'per_page'=>array('sanitize_callback'=>'absint'),'cursor'=>array('sanitize_callback'=>'sanitize_text_field'))));
        register_rest_route('pldr/v1','/document/(?P<id>[a-f0-9\-]{36})',array('methods'=>'GET','callback'=>array(__CLASS__,'document'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/ingest',array('methods'=>'POST','callback'=>array(__CLASS__,'ingest'),'permission_callback'=>array(__CLASS__,'can_publish')));
        register_rest_route('pldr/v1','/documents/(?P<id>[a-f0-9\-]{36})/approve',array('methods'=>'POST','callback'=>array(__CLASS__,'approve_document'),'permission_callback'=>array(__CLASS__,'can_rights')));
        register_rest_route('pldr/v1','/documents/(?P<id>[a-f0-9\-]{36})/access-policy',array('methods'=>'POST','callback'=>array(__CLASS__,'access_policy'),'permission_callback'=>array(__CLASS__,'can_manage')));
        register_rest_route('pldr/v1','/reader-access',array('methods'=>'POST','callback'=>array(__CLASS__,'reader_access'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/reader-manifest/(?P<edition>\d+)',array('methods'=>'GET','callback'=>array(__CLASS__,'reader_manifest'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/ocr-search/(?P<edition>\d+)',array('methods'=>'GET','callback'=>array(__CLASS__,'ocr_search'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/reading/progress',array('methods'=>'POST','callback'=>array(__CLASS__,'save_progress'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/reading/items',array('methods'=>'GET','callback'=>array(__CLASS__,'reading_items'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/reading/items',array('methods'=>'POST','callback'=>array(__CLASS__,'add_reading_item'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/reading/items/(?P<id>\d+)',array('methods'=>'DELETE','callback'=>array(__CLASS__,'delete_reading_item'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/citation/(?P<edition>\d+)',array('methods'=>'GET','callback'=>array(__CLASS__,'citation'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/downloads/session',array('methods'=>'POST','callback'=>array(__CLASS__,'download_session'),'permission_callback'=>'__return_true'));
        register_rest_route('pldr/v1','/rights/cases',array('methods'=>'POST','callback'=>array(__CLASS__,'rights_case'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/rights/cases/(?P<id>\d+)/decision',array('methods'=>'POST','callback'=>array(__CLASS__,'rights_decision'),'permission_callback'=>array(__CLASS__,'can_rights')));
        register_rest_route('pldr/v1','/rights/cases/(?P<id>\d+)/appeal',array('methods'=>'POST','callback'=>array(__CLASS__,'rights_appeal'),'permission_callback'=>array(__CLASS__,'logged_in')));
        register_rest_route('pldr/v1','/book-packs',array('methods'=>'POST','callback'=>array(__CLASS__,'book_pack'),'permission_callback'=>array(__CLASS__,'can_manage')));
        register_rest_route('pldr/v1','/health',array('methods'=>'GET','callback'=>array(__CLASS__,'health'),'permission_callback'=>array(__CLASS__,'can_manage')));
        register_rest_route('pldr/v1','/repair',array('methods'=>'POST','callback'=>array(__CLASS__,'repair'),'permission_callback'=>array(__CLASS__,'can_repair')));
    }

    public static function logged_in(): bool { return is_user_logged_in(); }
    public static function can_publish(): bool { return PLDR_Core::authorize('publish'); }
    public static function can_manage(): bool { return PLDR_Core::authorize('manage'); }
    public static function can_rights(): bool { return PLDR_Core::authorize('rights'); }
    public static function can_repair(): bool { return PLDR_Core::authorize('repair'); }

    private static function idempotent(WP_REST_Request $request,string $route,callable $callback) {
        $key=substr(sanitize_text_field((string)$request->get_header('Idempotency-Key')),0,200);
        if(''===$key)return PLDR_Core::machine_error('pldr_idempotency_required','This mutation requires an Idempotency-Key.',428);
        $actor=get_current_user_id();
        if(!$actor)$key=PLDR_Core::scope_anonymous_idempotency_key($key);
        $request_hash=PLDR_Core::request_fingerprint($request);
        if(''===$request_hash)return PLDR_Core::machine_error('pldr_idempotency_fingerprint','The mutation request could not be fingerprinted safely; it was not executed.',503);
        $claim=PLDR_Core::idempotency_begin($route,$key,$actor,$request_hash);
        if('hit'===($claim['state']??''))return new WP_REST_Response($claim['body'],$claim['status']);
        if('conflict'===($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_conflict','This Idempotency-Key was already used for a different request payload.',409);
        if('pending'===($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_in_progress','A request with this Idempotency-Key is already in progress.',409,array('retry_after'=>2));
        if('reserved'!==($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_unavailable','Idempotency protection could not be reserved; the mutation was not executed.',503);
        $rate=PLDR_Core::consume_mutation_rate('core-'.$route,$actor,600);
        if(is_wp_error($rate)){if(!PLDR_Core::idempotency_abort($route,$key,$actor))PLDR_Core::audit('mutation',0,'idempotency_abort_after_rate_failure',array('route'=>$route),$actor);return $rate;}
        try {
            $result=$callback();
        } catch (Throwable $e) {
            PLDR_Core::idempotency_abort($route,$key,$actor);
            PLDR_Core::audit('mutation',0,'rest_mutation_exception',array('route'=>$route,'provider_failure'=>false),$actor);
            return PLDR_Core::machine_error('pldr_mutation_exception','The mutation could not be completed safely. No idempotency result was retained.',500,array('retry_safe'=>true));
        }
        if(is_array($result)&&isset($result['error'])&&is_wp_error($result['error']))$result=$result['error'];
        $response=is_wp_error($result)?rest_convert_error_to_response($result):rest_ensure_response($result);
        $status=$response instanceof WP_REST_Response?$response->get_status():200;
        $body=$response instanceof WP_REST_Response?$response->get_data():$result;
        if(!PLDR_Core::idempotency_complete($route,$key,$actor,$body,$status,$request_hash))return PLDR_Core::machine_error('pldr_idempotency_persist','The operation completed but its idempotency result could not be finalized; retry with a new key only after reconciliation.',503,array('original_status'=>$status));
        return is_wp_error($result)?$result:$response;
    }

    public static function library(WP_REST_Request $request) { $result=PLDR_Search::search($request->get_params(),get_current_user_id());if(isset($result['error'])&&is_wp_error($result['error']))return $result['error'];return rest_ensure_response($result); }
    public static function document(WP_REST_Request $request) { global $wpdb;$wpdb->last_error='';$doc=PLDR_Core::document_by_public_id((string)$request['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_document_read','Document state could not be read reliably.',503,array('degraded'=>true));if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);$wpdb->last_error='';$edition=PLDR_Core::current_edition((int)$doc['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_document_edition_read','Current edition state could not be read reliably.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_document_unavailable','Document is unavailable.',404);$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id());if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_document_access_read','Document authorization state could not be verified reliably.',503,array('degraded'=>true));if(!$allowed)return PLDR_Core::machine_error('pldr_document_unavailable','Document is unavailable.',404);$wpdb->last_error='';$dto=PLDR_Core::public_document_dto($doc,$edition);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_document_policy_read','Document access-policy state could not be projected reliably.',503,array('degraded'=>true));return rest_ensure_response($dto); }
    public static function ingest(WP_REST_Request $request) { return self::idempotent($request,'ingest',static fn()=>PLDR_Ingest::ingest($request->get_params(),$request->get_file_params())); }
    public static function approve_document(WP_REST_Request $request) { global $wpdb;$doc=PLDR_Core::document_by_public_id((string)$request['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_approve_document_read','Document state could not be read reliably before approval.',503,array('degraded'=>true));if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404); return self::idempotent($request,'approve-document',static fn()=>PLDR_Rights::approve_document((int)$doc['id'],0,absint($request['expected_version']))); }
    public static function access_policy(WP_REST_Request $request) { global $wpdb;$doc=PLDR_Core::document_by_public_id((string)$request['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_policy_document_read','Document state could not be read reliably before policy mutation.',503,array('degraded'=>true));if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);$data=$request->get_json_params()?:$request->get_params();return self::idempotent($request,'access-policy',static fn()=>PLDR_Access::update_policy((int)$doc['id'],$data,0,absint($data['expected_version']??0))); }

    public static function reader_access(WP_REST_Request $request) {
        global $wpdb;$edition_id=absint($request['edition_id']);$operation=sanitize_key((string)($request['operation']?:'read'));$edition=PLDR_Core::edition($edition_id);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_access_edition_read','Edition state could not be read reliably before grant issue.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_edition_missing','Edition not found.',404);
        $object_id=(int)$edition['object_id'];if(!empty($request['object_id']))$object_id=absint($request['object_id']);
        return self::idempotent($request,'reader-access',static fn()=>PLDR_Access::issue_token($edition_id,$object_id,$operation,get_current_user_id(),absint($request['ttl']?:600)));
    }

    public static function reader_manifest(WP_REST_Request $request) {
        global $wpdb;
        $edition_id=absint($request['edition']);
        $edition=PLDR_Core::edition($edition_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_edition_read','Reader edition state could not be read reliably.',503,array('degraded'=>true));
        if(!$edition)return PLDR_Core::machine_error('pldr_reader_forbidden','Reader manifest is unavailable.',404);
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id,'read',get_current_user_id());
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_access_read','Reader authorization state could not be verified reliably.',503,array('degraded'=>true));
        if(!$allowed)return PLDR_Core::machine_error('pldr_reader_forbidden','Reader manifest is unavailable.',404);
        $wpdb->last_error='';
        $object=PLDR_Core::object((int)$edition['object_id']);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_object_read','Reader object state could not be read reliably.',503,array('degraded'=>true));
        if(!$object)return PLDR_Core::machine_error('pldr_object_missing','Document object not found.',404);
        $grant=PLDR_Access::issue_token($edition_id,(int)$object['id'],'read',get_current_user_id(),900);
        if(is_wp_error($grant))return $grant;

        $thumb_table=PLDR_Core::table('derivatives');
        $wpdb->last_error='';
        $thumb_total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$thumb_table.' WHERE edition_id=%d AND derivative_type=%s AND status=%s',$edition_id,'thumbnail','available'));
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_thumbnail_count','Reader thumbnail state could not be counted reliably.',503,array('degraded'=>true));
        $wpdb->last_error='';
        $thumb_rows=$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.$thumb_table.' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT %d',$edition_id,'thumbnail','available',self::READER_THUMB_LIMIT),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_thumbnail_read','Reader thumbnail state could not be read reliably.',503,array('degraded'=>true));
        $thumb_rows=is_array($thumb_rows)?$thumb_rows:array();
        $thumbs=array();$preview_grants_issued=0;$preview_grants_failed=0;$preview_grants_deferred=0;
        foreach($thumb_rows as $row){
            if($preview_grants_issued>=self::READER_PREVIEW_GRANT_LIMIT){$preview_grants_deferred++;continue;}
            $g=PLDR_Access::issue_token($edition_id,(int)$row['object_id'],'preview',get_current_user_id(),900);
            if(is_array($g)){$thumbs[(int)$row['page_number']]=$g['url'];$preview_grants_issued++;}
            else $preview_grants_failed++;
        }
        $wpdb->last_error='';
        $policy=PLDR_Core::policy((int)$edition['document_id']);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_reader_policy_read','Reader access-policy state could not be read reliably.',503,array('degraded'=>true));
        if(!$policy)return PLDR_Core::machine_error('pldr_reader_policy_missing','Reader access policy is unavailable.',503,array('degraded'=>true));
        $reading=PLDR_Reading::state($edition_id);
        if(isset($reading['error'])&&is_wp_error($reading['error']))return $reading['error'];
        $accessibility=self::accessibility_metadata($edition_id,$edition);
        if(isset($accessibility['error'])&&is_wp_error($accessibility['error']))return $accessibility['error'];
        return rest_ensure_response(array(
            'edition_id'=>$edition_id,
            'title'=>$edition['title'],
            'pages'=>(int)$edition['pages'],
            'language'=>$edition['language'],
            'version'=>(int)$edition['version'],
            'delivery'=>$grant,
            'thumbnails'=>$thumbs,
            'thumbnails_meta'=>array('limit'=>self::READER_THUMB_LIMIT,'returned'=>count($thumbs),'total'=>$thumb_total,'truncated'=>$thumb_total>self::READER_THUMB_LIMIT,'preview_grant_limit'=>self::READER_PREVIEW_GRANT_LIMIT,'preview_grants_issued'=>$preview_grants_issued,'preview_grants_failed'=>$preview_grants_failed,'preview_grants_deferred'=>$preview_grants_deferred+max(0,count($thumb_rows)-$preview_grants_issued-$preview_grants_failed-$preview_grants_deferred)),
            'reading'=>$reading,
            'permissions'=>array('download'=>!empty($policy['download_allowed']),'print'=>!empty($policy['print_allowed']),'offline'=>!empty($policy['offline_allowed'])),
            'accessibility'=>$accessibility,
        ));
    }

    private static function accessibility_metadata(int $edition_id,array $edition):array {
        global $wpdb;
        $wpdb->last_error='';
        $ocr=$wpdb->get_row($wpdb->prepare('SELECT status,quality_score,language FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s LIMIT 1',$edition_id,'ocr-status'),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_reader_a11y_ocr_read','Reader accessibility OCR state could not be read reliably.',503,array('degraded'=>true)));
        $wpdb->last_error='';
        $thumbs=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s',$edition_id,'thumbnail','available'));
        if(''!==(string)$wpdb->last_error)return array('error'=>PLDR_Core::machine_error('pldr_reader_a11y_thumbnail_read','Reader accessibility preview state could not be read reliably.',503,array('degraded'=>true)));
        return array('title'=>$edition['title'],'author'=>$edition['author_name'],'language'=>$edition['language'],'pages'=>(int)$edition['pages'],'ocr_status'=>$ocr['status']??'unknown','ocr_quality'=>isset($ocr['quality_score'])?(float)$ocr['quality_score']:0,'ocr_language'=>$ocr['language']??$edition['language'],'thumbnail_pages'=>$thumbs,'screen_reader_fallback'=>$ocr&&'available'===$ocr['status']?'ocr-text':'document-metadata');
    }
    public static function ocr_search(WP_REST_Request $request) { $result=PLDR_Search::ocr(absint($request['edition']),(string)$request['q'],get_current_user_id());if(isset($result['error'])&&is_wp_error($result['error']))return $result['error'];return rest_ensure_response(array('items'=>$result)); }
    public static function save_progress(WP_REST_Request $request) { return self::idempotent($request,'reading-progress',static fn()=>PLDR_Reading::save_progress(absint($request['edition_id']),absint($request['page']))); }
    public static function reading_items(WP_REST_Request $request) {
        global $wpdb;
        $uid=get_current_user_id();$edition_id=absint($request['edition_id']);
        if(!$uid)return PLDR_Core::machine_error('pldr_items_forbidden','Private reading items are unavailable.',403);
        $wpdb->last_error='';
        $allowed=PLDR_Access::can_access_edition($edition_id,'read',$uid);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_items_access_read','Private reading-item authorization state could not be verified reliably.',503,array('degraded'=>true));
        if(!$allowed)return PLDR_Core::machine_error('pldr_items_forbidden','Private reading items are unavailable.',403);
        $limit=max(1,min(200,absint($request['limit']?:100)));$offset=max(0,min(100000,absint($request['offset'])));
        $table=PLDR_Core::table('reading_items');
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare('SELECT id,item_type,page_number,anchor_text,note_text,tags_json,version,created_at,updated_at FROM '.$table.' WHERE user_id=%d AND edition_id=%d ORDER BY page_number ASC,id ASC LIMIT %d OFFSET %d',$uid,$edition_id,$limit+1,$offset),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_items_read','Private reading items could not be read reliably.',503,array('degraded'=>true));
        $rows=is_array($rows)?$rows:array();
        $has_more=count($rows)>$limit;if($has_more)$rows=array_slice($rows,0,$limit);
        foreach($rows as &$row){
            $row['id']=(int)$row['id'];$row['page_number']=(int)$row['page_number'];$row['version']=(int)$row['version'];
            $tags=json_decode((string)$row['tags_json'],true);
            if(!is_array($tags)){
                PLDR_Core::audit('reading_item',(int)$row['id'],'reading_item_tags_corrupt',array('edition_id'=>$edition_id),$uid);
                return PLDR_Core::machine_error('pldr_items_corrupt','Stored private reading-item tags failed integrity validation; no partial item page was returned.',500,array('item_id'=>(int)$row['id']));
            }
            $row['tags']=$tags;unset($row['tags_json']);
        }unset($row);
        return rest_ensure_response(array('items'=>$rows,'limit'=>$limit,'offset'=>$offset,'has_more'=>$has_more,'next_offset'=>$has_more?$offset+$limit:null));
    }
    public static function add_reading_item(WP_REST_Request $request) { return self::idempotent($request,'reading-item',static fn()=>PLDR_Reading::add_item(absint($request['edition_id']),$request->get_json_params()?:$request->get_params())); }

    private static function delete_reading_item_owned(int $item_id) {
        global $wpdb;
        $user_id=get_current_user_id();
        if(!$user_id)return PLDR_Core::machine_error('pldr_item_login','Log in to delete a private reading item.',401);
        $wpdb->last_error='';
        $row=$wpdb->get_row($wpdb->prepare('SELECT id FROM '.PLDR_Core::table('reading_items').' WHERE id=%d AND user_id=%d',$item_id,$user_id),ARRAY_A);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_item_read','Private reading-item state could not be read reliably.',503,array('degraded'=>true));
        if(!$row)return PLDR_Core::machine_error('pldr_item_missing','Private reading item was not found.',404);
        $deleted=$wpdb->delete(PLDR_Core::table('reading_items'),array('id'=>$item_id,'user_id'=>$user_id),array('%d','%d'));
        if(1!==$deleted)return PLDR_Core::machine_error('pldr_item_delete','Private reading item could not be deleted.',500);
        return array('deleted'=>true,'id'=>$item_id);
    }

    public static function delete_reading_item(WP_REST_Request $request) { return self::idempotent($request,'reading-item-delete',static fn()=>self::delete_reading_item_owned(absint($request['id']))); }
    public static function citation(WP_REST_Request $request) { global $wpdb;$wpdb->last_error='';$edition=PLDR_Core::edition(absint($request['edition']));if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_citation_edition_read','Citation edition state could not be read reliably.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_citation_forbidden','Citation is unavailable.',404);$wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id());if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_citation_access_read','Citation authorization state could not be verified reliably.',503,array('degraded'=>true));if(!$allowed)return PLDR_Core::machine_error('pldr_citation_forbidden','Citation is unavailable.',404);$page=absint($request['page']);if($page>(int)$edition['pages'])return PLDR_Core::machine_error('pldr_citation_page','Citation page is outside this document edition.',400,array('pages'=>(int)$edition['pages']));$style=sanitize_key((string)($request['style']?:'sabri'));return rest_ensure_response(array('citation'=>PLDR_Reader::citation($edition,$page,$style),'style'=>$style,'page'=>$page)); }
    public static function download_session(WP_REST_Request $request) {
        return self::idempotent($request,'download-session',static function() use($request){
            global $wpdb;$wpdb->last_error='';$edition=PLDR_Core::edition(absint($request['edition_id']));
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_download_edition_read','Download edition state could not be read reliably.',503,array('degraded'=>true));
            if(!$edition)return PLDR_Core::machine_error('pldr_edition_missing','Edition not found.',404);
            $grant=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'download',get_current_user_id(),900);if(is_wp_error($grant))return $grant;
            PLDR_Core::audit('edition',(int)$edition['id'],'download_session_issued',array('size'=>$grant['size'],'sha256'=>$grant['sha256']));
            return array('job_id'=>PLDR_Core::uuid(),'delivery'=>$grant,'range_bytes'=>2*MB_IN_BYTES,'checksum'=>'sha256:'.$grant['sha256'],'resume_supported'=>true,'revocation_rechecked'=>true);
        });
    }
    public static function rights_case(WP_REST_Request $request) { global $wpdb;$doc=PLDR_Core::document_by_public_id(sanitize_text_field((string)$request['document_id']));if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_case_document_read','Rights-report document state could not be read reliably.',503,array('degraded'=>true));if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);return self::idempotent($request,'rights-case',static fn()=>PLDR_Rights::file_case((int)$doc['id'],(string)$request['reason'],(array)($request['evidence']?:array()))); }
    public static function rights_decision(WP_REST_Request $request) { return self::idempotent($request,'rights-decision',static fn()=>PLDR_Rights::decide(absint($request['id']),(string)$request['decision'],(string)$request['note'],0,absint($request['expected_version']))); }
    public static function rights_appeal(WP_REST_Request $request) { return self::idempotent($request,'rights-appeal',static fn()=>PLDR_Rights::appeal(absint($request['id']),(string)$request['reason'],(array)($request['evidence']?:array()))); }
    public static function book_pack(WP_REST_Request $request) { return self::idempotent($request,'book-pack',static fn()=>PLDR_Book_Packs::register($request->get_json_params()?:$request->get_params())); }
    public static function health(WP_REST_Request $request) { return rest_ensure_response(PLDR_Health::report()); }
    public static function repair(WP_REST_Request $request) { return self::idempotent($request,'repair',static fn()=>PLDR_Health::repair(sanitize_key((string)$request['operation']))); }
}
