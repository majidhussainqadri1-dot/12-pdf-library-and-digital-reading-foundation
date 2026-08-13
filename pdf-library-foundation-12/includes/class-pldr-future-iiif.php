<?php

defined('ABSPATH') || exit;

final class PLDR_Future_IIIF {
    private const PREVIEW_GRANT_LIMIT = 50;

    public static function manifest(string $public_id) {
        global $wpdb;
        $wpdb->last_error='';$doc=PLDR_Core::document_by_public_id($public_id);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_iiif_document_read','IIIF document state could not be read reliably.',503,array('degraded'=>true));if(!$doc)return PLDR_Core::machine_error('pldr_iiif_forbidden','IIIF manifest is unavailable for this document.',404);
        $wpdb->last_error='';$edition=PLDR_Core::current_edition((int)$doc['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_iiif_edition_read','IIIF edition state could not be read reliably.',503,array('degraded'=>true));if(!$edition)return PLDR_Core::machine_error('pldr_iiif_forbidden','IIIF manifest is unavailable for this document.',404);
        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id());if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_iiif_access_read','IIIF authorization state could not be verified reliably; no delivery grants were issued.',503,array('degraded'=>true));if(!$allowed)return PLDR_Core::machine_error('pldr_iiif_forbidden','IIIF manifest is unavailable for this document.',404);
        try {
            $canvas_limit=(int)apply_filters('pldr_iiif_canvas_limit',500,(int)$edition['id']);
            $preview_grant_limit=(int)apply_filters('pldr_iiif_preview_grant_limit',self::PREVIEW_GRANT_LIMIT,(int)$edition['id']);
        } catch (Throwable $e) {
            PLDR_Core::audit('edition',(int)$edition['id'],'iiif_limit_policy_provider_failed',array('provider_failure'=>1));
            return PLDR_Core::machine_error('pldr_iiif_limit_policy','IIIF delivery-limit policy is temporarily unavailable; no preview grants were issued.',503,array('degraded'=>true,'provider_failure'=>true));
        }
        $canvas_limit=max(25,min(1000,$canvas_limit));
        $preview_grant_limit=max(1,min(100,$preview_grant_limit));
        $edition_pages=max(0,(int)$edition['pages']);$canvas_count=min($edition_pages,$canvas_limit);
        $wpdb->last_error='';
        $thumbs=$canvas_count>0?$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s AND page_number BETWEEN 1 AND %d ORDER BY page_number ASC LIMIT %d',(int)$edition['id'],'thumbnail','available',$canvas_count,$canvas_count),ARRAY_A):array();
        if(''!==(string)$wpdb->last_error){
            PLDR_Core::audit('edition',(int)$edition['id'],'iiif_derivative_read_failed',array('provider_failure'=>false));
            return PLDR_Core::machine_error('pldr_iiif_derivative_read','IIIF preview derivative state could not be verified; no manifest grants were issued.',503,array('degraded'=>true));
        }
        $thumbs=is_array($thumbs)?$thumbs:array();
        $thumb_by_page=array();foreach($thumbs as $row)$thumb_by_page[(int)$row['page_number']]=$row;
        $canvases=array();$preview_missing=0;$preview_grant_failed=0;$preview_grants_issued=0;$preview_grant_deferred=0;
        for($page=1;$page<=$canvas_count;$page++){
            $canvas_id=rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest').'#canvas-'.$page;$items=array();
            if(isset($thumb_by_page[$page])){
                if($preview_grants_issued<$preview_grant_limit){
                    $row=$thumb_by_page[$page];$grant=PLDR_Access::issue_token((int)$edition['id'],(int)$row['object_id'],'preview',get_current_user_id(),900);
                    if(is_array($grant)){$preview_grants_issued++;$items=array(array('id'=>$canvas_id.'/page','type'=>'AnnotationPage','items'=>array(array('id'=>$canvas_id.'/annotation','type'=>'Annotation','motivation'=>'painting','body'=>array('id'=>$grant['url'],'type'=>'Image','format'=>'image/jpeg','height'=>240,'width'=>180),'target'=>$canvas_id))));}else $preview_grant_failed++;
                }else $preview_grant_deferred++;
            }else $preview_missing++;
            $canvases[]=array('id'=>$canvas_id,'type'=>'Canvas','label'=>array('en'=>array('Page '.$page)),'height'=>240,'width'=>180,'items'=>$items);
        }
        $truncated=$edition_pages>$canvas_limit;$wpdb->last_error='';$policy=PLDR_Core::policy((int)$doc['id']);if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_iiif_policy_read','IIIF access-policy state could not be verified reliably; no partial manifest was returned.',503,array('degraded'=>true));if(!$policy)return PLDR_Core::machine_error('pldr_iiif_policy_missing','IIIF access policy is unavailable.',503,array('degraded'=>true));$render=null;$render_grant_failed=false;
        if(!empty($policy['download_allowed'])){$candidate=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'download',get_current_user_id(),900);if(is_array($candidate))$render=$candidate;else $render_grant_failed=true;}
        $rights=self::rights_uri((string)$edition['license_code']);
        $manifest=array('@context'=>'http://iiif.io/api/presentation/3/context.json','id'=>rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest'),'type'=>'Manifest','label'=>array('en'=>array((string)$doc['title'])),'metadata'=>array(array('label'=>array('en'=>array('Author')),'value'=>array('en'=>array((string)$edition['author_name']))),array('label'=>array('en'=>array('Edition')),'value'=>array('en'=>array((string)$edition['edition_label']))),array('label'=>array('en'=>array('License code')),'value'=>array('en'=>array((string)$edition['license_code'])))),'items'=>$canvases,'rendering'=>is_array($render)?array(array('id'=>$render['url'],'type'=>'Text','label'=>array('en'=>array('Rights-aware PDF download')),'format'=>'application/pdf')):array(),'requiredStatement'=>array('label'=>array('en'=>array('Rights / source')),'value'=>array('en'=>array((string)$edition['rights_basis'].' — '.(string)$edition['source_name']))),'file12ExpectedPages'=>$edition_pages,'file12CanvasLimit'=>$canvas_limit,'file12CanvasReturned'=>count($canvases),'file12CanvasTruncated'=>$truncated,'file12PreviewMissing'=>$preview_missing,'file12PreviewGrantFailed'=>$preview_grant_failed,'file12PreviewGrantLimit'=>$preview_grant_limit,'file12PreviewGrantsIssued'=>$preview_grants_issued,'file12PreviewGrantsDeferred'=>$preview_grant_deferred,'file12CanvasIdentityPreserved'=>true,'file12DownloadRenderingAllowed'=>is_array($render),'file12DownloadGrantFailed'=>$render_grant_failed);
        if(''!==$rights)$manifest['rights']=$rights;return $manifest;
    }

    private static function rights_uri(string $license):string {
        $license=strtolower(trim($license));$license=preg_replace('/[^a-z0-9\.\-]+/','-',$license)?:$license;
        $map=array('cc0'=>'https://creativecommons.org/publicdomain/zero/1.0/','cc0-1.0'=>'https://creativecommons.org/publicdomain/zero/1.0/','public-domain'=>'https://creativecommons.org/publicdomain/mark/1.0/','public-domain-mark'=>'https://creativecommons.org/publicdomain/mark/1.0/','cc-by'=>'https://creativecommons.org/licenses/by/4.0/','cc-by-4.0'=>'https://creativecommons.org/licenses/by/4.0/','cc-by-sa'=>'https://creativecommons.org/licenses/by-sa/4.0/','cc-by-sa-4.0'=>'https://creativecommons.org/licenses/by-sa/4.0/','cc-by-nd'=>'https://creativecommons.org/licenses/by-nd/4.0/','cc-by-nd-4.0'=>'https://creativecommons.org/licenses/by-nd/4.0/','cc-by-nc'=>'https://creativecommons.org/licenses/by-nc/4.0/','cc-by-nc-4.0'=>'https://creativecommons.org/licenses/by-nc/4.0/','cc-by-nc-sa'=>'https://creativecommons.org/licenses/by-nc-sa/4.0/','cc-by-nc-sa-4.0'=>'https://creativecommons.org/licenses/by-nc-sa/4.0/','cc-by-nc-nd'=>'https://creativecommons.org/licenses/by-nc-nd/4.0/','cc-by-nc-nd-4.0'=>'https://creativecommons.org/licenses/by-nc-nd/4.0/');
        return $map[$license]??'';
    }
}
