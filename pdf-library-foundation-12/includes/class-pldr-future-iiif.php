<?php

defined('ABSPATH') || exit;

final class PLDR_Future_IIIF {
    public static function manifest(string $public_id) {
        global $wpdb;
        $doc=PLDR_Core::document_by_public_id($public_id);if(!$doc)return PLDR_Core::machine_error('pldr_iiif_missing','Document not found.',404);
        $edition=PLDR_Core::current_edition((int)$doc['id']);if(!$edition||!PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id()))return PLDR_Core::machine_error('pldr_iiif_forbidden','IIIF manifest is unavailable for this document.',404);
        $thumbs=$wpdb->get_results($wpdb->prepare('SELECT page_number,object_id FROM '.PLDR_Core::table('derivatives').' WHERE edition_id=%d AND derivative_type=%s AND status=%s ORDER BY page_number ASC LIMIT 500',(int)$edition['id'],'thumbnail','available'),ARRAY_A)?:array();
        $canvases=array();foreach($thumbs as $row){$page=absint($row['page_number']);$grant=PLDR_Access::issue_token((int)$edition['id'],(int)$row['object_id'],'preview',get_current_user_id(),900);if(!is_array($grant))continue;$canvas_id=rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest').'#canvas-'.$page;$canvases[]=array('id'=>$canvas_id,'type'=>'Canvas','label'=>array('en'=>array('Page '.$page)),'height'=>240,'width'=>180,'items'=>array(array('id'=>$canvas_id.'/page','type'=>'AnnotationPage','items'=>array(array('id'=>$canvas_id.'/annotation','type'=>'Annotation','motivation'=>'painting','body'=>array('id'=>$grant['url'],'type'=>'Image','format'=>'image/jpeg','height'=>240,'width'=>180),'target'=>$canvas_id)))));}
        $render=PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'read',get_current_user_id(),900);
        return array('@context'=>'http://iiif.io/api/presentation/3/context.json','id'=>rest_url('pldr/v1/future/iiif/'.rawurlencode($public_id).'/manifest'),'type'=>'Manifest','label'=>array('en'=>array((string)$doc['title'])),'metadata'=>array(array('label'=>array('en'=>array('Author')),'value'=>array('en'=>array((string)$edition['author_name']))),array('label'=>array('en'=>array('Edition')),'value'=>array('en'=>array((string)$edition['edition_label'])))),'items'=>$canvases,'rendering'=>is_array($render)?array(array('id'=>$render['url'],'type'=>'Text','label'=>array('en'=>array('Rights-aware PDF reader source')),'format'=>'application/pdf')):array(),'rights'=>self::rights_uri((string)$edition['license_code']),'requiredStatement'=>array('label'=>array('en'=>array('Rights / source')),'value'=>array('en'=>array((string)$edition['rights_basis'].' — '.(string)$edition['source_name']))));
    }
    private static function rights_uri(string $license):string { $license=strtolower($license);if(str_contains($license,'public'))return'https://creativecommons.org/publicdomain/mark/1.0/';if(str_contains($license,'cc-by'))return'https://creativecommons.org/licenses/by/4.0/';return ''; }
}
