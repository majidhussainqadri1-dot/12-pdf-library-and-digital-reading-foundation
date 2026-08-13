<?php

defined('ABSPATH') || exit;

/**
 * File 12 response/cache/privacy projection policy.
 *
 * Keeps cache semantics and public OCR projection outside domain writers while
 * ensuring conditional/private API responses cannot be stored by shared caches.
 */
final class PLDR_Response_Policy {
    private static bool $hooked=false;

    public static function hooks():void {
        if(self::$hooked)return;
        self::$hooked=true;
        add_filter('rest_post_dispatch',array(__CLASS__,'filter_response'),20,3);
    }

    public static function filter_response($response,$server,WP_REST_Request $request) {
        if(!($response instanceof WP_REST_Response))return $response;
        $route=(string)$request->get_route();
        if(0!==strpos($route,'/pldr/v1/'))return $response;

        self::apply_cache_headers($response,$route);
        if(preg_match('#^/pldr/v1/future/ocr-quality/(?P<edition>\d+)$#',$route,$m)){
            self::project_ocr_quality($response,(int)$m['edition']);
        }
        return $response;
    }

    private static function apply_cache_headers(WP_REST_Response $response,string $route):void {
        $public_cacheable='/pldr/v1/library'===$route&&!is_user_logged_in();
        if($public_cacheable){
            $response->header('Cache-Control','public, max-age=60, stale-while-revalidate=30');
            $response->header('Vary','Accept-Encoding');
            return;
        }
        $response->header('Cache-Control','private, no-store, max-age=0');
        $response->header('Pragma','no-cache');
        $response->header('X-Robots-Tag','noindex, nofollow, noarchive');
        $response->header('Vary','Cookie, Authorization');
    }

    private static function project_ocr_quality(WP_REST_Response $response,int $edition_id):void {
        $data=$response->get_data();
        if(!is_array($data)||isset($data['code'])||!isset($data['corrections']))return;
        $edition=PLDR_Core::edition($edition_id);
        if(!$edition)return;
        $document_id=(int)$edition['document_id'];
        $can_review=PLDR_Core::authorize('manage',$document_id)||PLDR_Core::authorize('rights',$document_id);
        if($can_review)return;

        global $wpdb;
        $limit=max(1,min(500,(int)($data['corrections_meta']['limit']??500)));
        $table=PLDR_Core::table('ocr_corrections');
        $wpdb->last_error='';
        $total=(int)$wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM '.$table.' WHERE edition_id=%d AND status=%s',$edition_id,'approved'));
        if(''!==(string)$wpdb->last_error){
            $response->set_status(503);
            $response->set_data(array('code'=>'pldr_ocr_public_projection_read','message'=>'Approved OCR correction projection could not be read reliably.','data'=>array('status'=>503,'degraded'=>true)));
            return;
        }
        $wpdb->last_error='';
        $approved=$wpdb->get_results($wpdb->prepare('SELECT id,page_number,status,corrected_text,updated_at FROM '.$table.' WHERE edition_id=%d AND status=%s ORDER BY page_number ASC,id ASC LIMIT %d',$edition_id,'approved',$limit),ARRAY_A);
        if(''!==(string)$wpdb->last_error){
            $response->set_status(503);
            $response->set_data(array('code'=>'pldr_ocr_public_projection_read','message'=>'Approved OCR correction projection could not be read reliably.','data'=>array('status'=>503,'degraded'=>true)));
            return;
        }
        $approved=is_array($approved)?$approved:array();
        $data['corrections']=$approved;
        $data['corrections_meta']=array(
            'limit'=>$limit,
            'returned'=>count($approved),
            'total'=>$total,
            'truncated'=>$total>count($approved),
            'public_projection'=>'approved-corrections-only',
        );
        $data['review_metadata_visible']=false;
        $data['public_projection']='approved-corrections-only';
        $response->set_data($data);
    }
}
