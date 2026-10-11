<?php
/**
 * R41 actual REST route registration, actual reader-manifest guard, native
 * private-note parser and REST/private note reads, plus catalog query behavior.
 * Reuses R36's isolated WordPress/DB fixture; no live integration claim.
 */
declare(strict_types=1);
require __DIR__.'/test-r36-rest-privacy-mutation.php';
$assertions=0;
$check=static function(bool $pass,string $name)use(&$assertions):void{
    if(!$pass)throw new RuntimeException('R41 source/REST regression: '.$name);
    $assertions++;
};

// register_rest_route records actual WordPress registration data passed by
// PLDR_REST::register; this is behavior, not a text-grep assertion.
$GLOBALS['r41_routes']=array();
function register_rest_route($namespace,$route,$options):bool{
    $GLOBALS['r41_routes'][$namespace.$route]=$options;
    return true;
}
PLDR_REST::register();
$library_route=$GLOBALS['r41_routes']['pldr/v1/library']??null;
$check(is_array($library_route),'actual catalog REST route not registered');
$check(isset($library_route['args']['page']),'page input is not registered');
$check(isset($library_route['args']['per_page']),'per_page input is not registered');
foreach(array('page','per_page') as $field){
    $argument=$library_route['args'][$field]??null;
    $check(is_array($argument),'catalog pagination argument was not an array');
    $check(!isset($argument['sanitize_callback']),
        'WordPress route sanitizer coerces malformed '.$field.' before the strict catalog validator');
    foreach(array('-2','1.8',0,-3) as $invalid){
        $reads=$GLOBALS['wpdb']->reads;
        $payload=array('page'=>1,'per_page'=>24);
        $payload[$field]=$invalid;
        $result=PLDR_REST::library(new WP_REST_Request($payload));
        $check(is_wp_error($result)&&$result->code==='pldr_catalog_pagination_invalid',
            'actual REST catalog silently normalized '.$field.'='.var_export($invalid,true));
        $check($GLOBALS['wpdb']->reads===$reads,
            'catalog queried DB after malformed '.$field);
    }
}
// Reader manifest receives only positive edition IDs and must validate them
// before PLDR_Core::edition() or any private object lookup.
foreach(array(array(42),-42,42.5,'42.5','-42',true,false,(object)array('id'=>42)) as $bad){
    $before=$GLOBALS['wpdb']->reads;
    $request=new WP_REST_Request(array('edition'=>$bad));
    $result=PLDR_REST::reader_manifest($request);
    $check(is_wp_error($result)&&$result->code==='pldr_query_input_shape',
        'reader manifest coerced invalid edition shape: '.get_debug_type($bad));
    $check($GLOBALS['wpdb']->reads===$before,
        'reader manifest queried DB before rejecting invalid edition');
}
// Empty object {} is NOT a private tag-list [] even though both would decode
// to [] under PHP json_decode(...,true).
foreach(array('{}','{"0":"tag"}','{"tag":"value"}','[["nested"]]','42','null') as $bad){
    $check(null===PLDR_Reading::parse_stored_tags($bad),
        'canonical native tag parser accepted object/nonlist: '.$bad);
    $GLOBALS['wpdb']->rows=array(array_replace($base,array('tags_json'=>$bad)));
    $result=PLDR_REST::reading_items($request=new WP_REST_Request(
        array('edition_id'=>42,'offset'=>0,'limit'=>2,'cursor'=>'')));
    $check(is_wp_error($result)&&$result->code==='pldr_items_corrupt',
        'actual REST private reading projected corrupt historical tags: '.$bad);
    $native=PLDR_Reading::items(42,77);
    $check(is_array($native)&&is_wp_error($native['error']??null)
        &&$native['error']->code==='pldr_items_corrupt',
        'actual native private reading leaked historical JSON object tags: '.$bad);
}
foreach(array('[]','["tag"]','["tag","two"]') as $good){
    $tag=PLDR_Reading::parse_stored_tags($good);
    $check(is_array($tag)&&count($tag)===count(json_decode($good,true)),
        'valid JSON string-list tags became unavailable');
    $GLOBALS['wpdb']->rows=array(array_replace($base,array('tags_json'=>$good)));
    $actual=PLDR_REST::reading_items(new WP_REST_Request(
        array('edition_id'=>42,'offset'=>0,'limit'=>2,'cursor'=>'')));
    $check(is_array($actual)&&is_array($actual['items'][0]['tags']??null),
        'valid private REST reading tags denied');
    $check($actual['items'][0]['tags']===$tag,
        'REST private reading canonical tags diverged from native parser');
}
echo "PASS R41: {$assertions} real REST route/strict catalog query, reader-manifest pre-read and private tag-list integrity checks\n";
