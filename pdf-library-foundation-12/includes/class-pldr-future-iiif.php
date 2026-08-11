<?php

defined('ABSPATH') || exit;

final class PLDR_Future_IIIF {
    public static function manifest(string $public_id) {
        global $wpdb;
        $doc=PLDR_Core::document_by_public_id($public_id);if(!$doc)return PLDR_Core::machine_error('pldr_iiif_missing','Document not found.',404);
        $edition=PLDR_Core::current_edition((int)$doc['id']);if(!$edition||!PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id()))return PLDR_Core::machine_error('pldr_iiif_forbidden','IIIF manifest is unavailable for this document.',404);
        $canvas_limit=(int)apply_filters('pldr_iiif_canvas_limit',500,(int)$edition['id']);$canvas_limit=max(25,min(1000,$canvas_limit));
        $edition_pages=max(0,(int)$edition['pages']);
        $canvas_count=min($edition_pages,$canvas_limit);
        $thumbs=$canvas_count>0?$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s AND page_number BETWEEN 1 AND %d ORDER BY page_number ASC LIMIT %d',(int)$edition['id'],'thumbnail','available',$canvas_count,$canvas_count),ARRAY_A):array();
        $thumb_by_page=array();foreach($thumbs?:array() as $row)$thumb_by_page[(int)$row['page_number']]=$row;
        $canvases=array();$preview_missing=0;$preview_grant_failed=0;
        for($page=1;$page<=$canvas_count;$page++){
            $canvas_id=rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest').'#canvas-'.$page;
            $items=array();
            if(isset($thumb_by_page[$page])){
                $row=$thumb_by_page[$page];$grant=PLDR_Access::issue_token((int)$edition['id'],(int)$row['object_id'],'preview',get_current_user_id(),900);
                if(is_array($grant))$items=array(array('id'=>$canvas_id.'/page','type'=>'AnnotationPage','items'=>array(array('id'=>$canvas_id.'/annotation','type'=>'Annotation','motivation'=>'painting','body'=>array('id'=>$grant['url'],'type'=>'Image','format'=>'image/jpeg','height'=>240,'width'=>180),'target'=>$canvas_id))));
                else $preview_grant_failed++;
            }else $preview_missing++;
            $canvases[]=array('id'=>$canvas_id,'type'=>'Canvas','label'=>array('en'=>array('Page '.$page)),'height'=>240,'width'=>180,'items'=>$items);
        }
        $truncated=$edition_pages>$canvas_limit;
        $policy=PLDR_Core::policy((int)$doc['id']);
        $render=null;
        if(!empty($policy['download_allowed'])){
            $candidate=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'download',get_current_user_id(),900);
            if(is_array($candidate))$render=$candidate;
        }
        $rights=self::rights_uri((string)$edition['license_code']);
        $manifest=array('@context'=>'http://iiif.io/api/presentation/3/context.json','id'=>rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest'),'type'=>'Manifest','label'=>array('en'=>array((string)$doc['title'])),'metadata'=>array(array('label'=>array('en'=>array('Author')),'value'=>array('en'=>array((string)$edition['author_name']))),array('label'=>array('en'=>array('Edition')),'value'=>array('en'=>array((string)$edition['edition_label']))),array('label'=>array('en'=>array('License code')),'value'=>array('en'=>array((string)$edition['license_code'])))),'items'=>$canvases,'rendering'=>is_array($render)?array(array('id'=>$render['url'],'type'=>'Text','label'=>array('en'=>array('Rights-aware PDF download')),'format'=>'application/pdf')):array(),'requiredStatement'=>array('label'=>array('en'=>array('Rights / source')),'value'=>array('en'=>array((string)$edition['rights_basis'].' — '.(string)$edition['source_name']))),'file12ExpectedPages'=>$edition_pages,'file12CanvasLimit'=>$canvas_limit,'file12CanvasReturned'=>count($canvases),'file12CanvasTruncated'=>$truncated,'file12PreviewMissing'=>$preview_missing,'file12PreviewGrantFailed'=>$preview_grant_failed,'file12CanvasIdentityPreserved'=>true,'file12DownloadRenderingAllowed'=>is_array($render));
        if(''!==$rights)$manifest['rights']=$rights;
        return $manifest;
    }

    private static function rights_uri(string $license):string {
        $license=strtolower(trim($license));
        $license=preg_replace('/[^a-z0-9\.\-]+/','-',$license)?:$license;
        $map=array(
            'cc0'=>'https://creativecommons.org/publicdomain/zero/1.0/',
            'cc0-1.0'=>'https://creativecommons.org/publicdomain/zero/1.0/',
            'public-domain'=>'https://creativecommons.org/publicdomain/mark/1.0/',
            'public-domain-mark'=>'https://creativecommons.org/publicdomain/mark/1.0/',
            'cc-by'=>'https://creativecommons.org/licenses/by/4.0/',
            'cc-by-4.0'=>'https://creativecommons.org/licenses/by/4.0/',
            'cc-by-sa'=>'https://creativecommons.org/licenses/by-sa/4.0/',
            'cc-by-sa-4.0'=>'https://creativecommons.org/licenses/by-sa/4.0/',
            'cc-by-nd'=>'https://creativecommons.org/licenses/by-nd/4.0/',
            'cc-by-nd-4.0'=>'https://creativecommons.org/licenses/by-nd/4.0/',
            'cc-by-nc'=>'https://creativecommons.org/licenses/by-nc/4.0/',
            'cc-by-nc-4.0'=>'https://creativecommons.org/licenses/by-nc/4.0/',
            'cc-by-nc-sa'=>'https://creativecommons.org/licenses/by-nc-sa/4.0/',
            'cc-by-nc-sa-4.0'=>'https://creativecommons.org/licenses/by-nc-sa/4.0/',
            'cc-by-nc-nd'=>'https://creativecommons.org/licenses/by-nc-nd/4.0/',
            'cc-by-nc-nd-4.0'=>'https://creativecommons.org/licenses/by-nc-nd/4.0/',
        );
        return $map[$license]??'';
    }
}
