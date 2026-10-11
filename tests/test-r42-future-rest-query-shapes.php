<?php
/**
 * R42: actual Future-24 REST route registration and ten GET handlers reject
 * malformed page/limit/offset/text before a provider, DB or private read.
 * Reuses R36's isolated WP request/DB fixture; not a staging/live test.
 */
declare(strict_types=1);
require __DIR__.'/test-r36-rest-privacy-mutation.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-rest.php';
$checks=0;
$check=static function(bool $ok,string $label)use(&$checks):void{
    if(!$ok)throw new RuntimeException('R42 Future REST: '.$label);
    $checks++;
};
$GLOBALS['r42_routes']=array();
function register_rest_route($namespace,$route,$args):bool{
    $GLOBALS['r42_routes'][$namespace.$route]=$args;return true;
}
PLDR_Future_REST::register();
$strict_routes=array(
    array('pldr/v1/future/reflow/(?P<edition>\\d+)','page',false),
    array('pldr/v1/future/shelves/(?P<id>\\d+)/items','limit',true),
    array('pldr/v1/future/shelves/(?P<id>\\d+)/items','cursor',true),
    array('pldr/v1/future/corpus/(?P<edition>\\d+)','limit',false),
    array('pldr/v1/future/corpus/(?P<edition>\\d+)','offset',false),
    array('pldr/v1/future/corpus/(?P<edition>\\d+)','cursor',false),
);
foreach($strict_routes as [$name,$field,$nested]){
    $route=$GLOBALS['r42_routes'][$name]??null;
    $check(is_array($route),'Future route missing: '.$name);
    $method=$nested?$route[0]:$route;
    $arg=$method['args'][$field]??null;
    $check(is_array($arg),'query argument missing: '.$name.' '.$field);
    $check(!isset($arg['sanitize_callback']),
        'REST converted input before Future strict validation: '.$name.' '.$field);
}
$numeric=array(
    array('reflow',array('edition'=>42),'page'),
    array('compare',array(),'left'),
    array('compare',array('left'=>42),'right'),
    array('citation_export',array('edition'=>42),'page'),
    array('annotations_export',array('edition'=>42),'after_id'),
    array('annotations_export',array('edition'=>42),'limit'),
    array('shelf_items',array('id'=>4,'cursor'=>''),'limit'),
    array('insights',array(),'days'),
    array('context',array('edition'=>42,'selection'=>'text'),'page'),
    array('corpus',array('edition'=>42,'cursor'=>''),'limit'),
    array('corpus',array('edition'=>42,'cursor'=>''),'offset'),
);
$invalid=array(-3,'-3','1.8',1.8,true,false,array(3),(object)array('a'=>3),
    '999999999999999999999999999999999999');
foreach($numeric as [$handler,$params,$key]){
    foreach($invalid as $bad){
        $payload=$params;$payload[$key]=$bad;
        $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes;
        $result=PLDR_Future_REST::$handler(new WP_REST_Request($payload));
        $check(is_wp_error($result)&&$result->code==='pldr_future_query_input_shape',
            $handler.' silently accepted malformed '.$key.' ('.get_debug_type($bad).')');
        $check($GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes===$before,
            $handler.' contacted DB before rejecting '.$key);
    }
}
$text=array(
    array('citation_export',array('edition'=>42),'format'),
    array('heatmap',array('edition'=>42),'q'),
    array('preferences_get',array(),'key'),
    array('shelf_items',array('id'=>4),'cursor'),
    array('context',array('edition'=>42),'selection'),
    array('corpus',array('edition'=>42),'cursor'),
);
foreach($text as [$handler,$params,$key]){
    foreach(array(array('nested'),(object)array('nested'=>1),true,false) as $bad){
        $payload=$params;$payload[$key]=$bad;
        $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes;
        $result=PLDR_Future_REST::$handler(new WP_REST_Request($payload));
        $check(is_wp_error($result)&&$result->code==='pldr_future_query_input_shape',
            $handler.' coerced a non-scalar '.$key);
        $check($GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes===$before,
            $handler.' contacted DB before rejecting text field '.$key);
    }
}
// Positive guard acceptance, separated from external provider/WordPress E2E.
$ref=new ReflectionMethod(PLDR_Future_REST::class,'validate_query_shape');
foreach(array(
    array(array('page'=>0,'cursor'=>''),array('cursor'),array('page')),
    array(array('page'=>'3','limit'=>'100','cursor'=>'abc'),array('cursor'),array('page','limit')),
    array(array('page'=>3,'limit'=>20,'cursor'=>'abc'),array('cursor'),array('page','limit')),
    array(array(),array('cursor'),array('page','limit')),
) as [$params,$text,$numbers]){
    $result=$ref->invoke(null,new WP_REST_Request($params),$text,$numbers);
    $check(true===$result,'strict Future query guard rejected a valid input');
}
echo "PASS R42: {$checks} actual Future-24 route/handler/strict-input assertions\\n";
