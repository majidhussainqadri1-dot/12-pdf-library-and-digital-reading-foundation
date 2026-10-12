<?php
/**
 * R47 — genuine File12 Future Room native handler input-integrity checks,
 * using the established R46 isolated WP/DB fixture (not staging/live).
 */
declare(strict_types=1);
require __DIR__.'/test-r46-handoff-room-selector-safety.php';
$checks=0;
$check=static function(bool $ok,string $reason)use(&$checks):void {
    if(!$ok)throw new RuntimeException('R47 File17 room page/region: '.$reason);
    $checks++;
};
foreach(array(-99,-12,-1,0,13,14,1000,PHP_INT_MAX) as $page){
    $db=$GLOBALS['wpdb'];
    $before=$db->writes;
    $result=PLDR_Future_Rooms::create(42,$page,array());
    $check(is_wp_error($result)&&$result->code==='pldr_room_page',
        'invalid room page silently clamped: '.$page);
    $check($db->writes===$before,
        'invalid page created a local room before File17 provider');
}
$method=new ReflectionMethod(PLDR_Future_Rooms::class,'anchor');
$wrong=array(
    array('region'=>false),
    array('region'=>null),
    array('region'=>'not-coordinates'),
    array('region'=>new stdClass()),
    array('region'=>array('x'=>array(0),'y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>true,'y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>-0.1,'y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>1.1,'y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>'bad','y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>'0.123456789123','y'=>0,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>NAN,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>INF,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>'0.8banana','h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>null,'h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>-0.2,'h'=>0.3)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>0.2,'h'=>1.1)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>0.2,'h'=>array(0.3))),
);
foreach($wrong as $bad){
    $db=$GLOBALS['wpdb'];$before=$db->writes;
    $r=$method->invoke(null,$bad,3);
    $check(is_wp_error($r)&&$r->code==='pldr_room_region_shape',
        'malformed page-region shape silently coerced: '.json_encode($bad,JSON_PARTIAL_OUTPUT_ON_ERROR));
    $check($db->writes===$before,
        'malformed page-region wrote persistent room context');
    $created=PLDR_Future_Rooms::create(42,3,$bad);
    $check(is_wp_error($created)&&$created->code==='pldr_room_region_shape',
        'malformed native room create did not fail before File17 provider');
    $check($db->writes===$before,
        'malformed region created a room in native create handler');
}
$bounds=array(
    array('region'=>array('x'=>0.9,'y'=>0.1,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>0.1,'y'=>0.9,'w'=>0.3,'h'=>0.2)),
);
foreach($bounds as $bad){
    $x=$method->invoke(null,$bad,2);
    $check(is_wp_error($x)&&$x->code==='pldr_room_region_bounds',
        'outside-page region was clamped instead of rejected');
}
$empty=array(
    array('region'=>array('x'=>0,'y'=>0,'w'=>0,'h'=>0.2)),
    array('region'=>array('x'=>0,'y'=>0,'w'=>0.3,'h'=>0))
);
foreach($empty as $bad){
    $x=$method->invoke(null,$bad,3);
    $check(is_wp_error($x)&&$x->code==='pldr_room_region',
        'zero-area region accepted');
}
$allowed=array(
    array('region'=>array('x'=>0,'y'=>0,'w'=>0.5,'h'=>0.5)),
    array('region'=>array('x'=>0.25,'y'=>0.4,'w'=>0.2,'h'=>0.3)),
    array('region'=>array('x'=>'0.125','y'=>'0.2','w'=>'0.4','h'=>'0.5')),
    array('region'=>array('x'=>0.6,'y'=>0.2,'w'=>0.4,'h'=>0.8)),
    array('type'=>'FragmentSelector','value'=>'page=3','region'=>array('x'=>0.3,'y'=>0.3,'w'=>0.2,'h'=>0.2))
);
foreach($allowed as $good){
    $x=$method->invoke(null,$good,3);
    $check(is_array($x)&&isset($x['region']),
        'valid File17 page-region was denied');
    $original=$good['region'];
    foreach(array('x','y','w','h') as $axis){
        $check(abs($x['region'][$axis]-(float)$original[$axis])<0.00000001,
            'room changed a valid normalized region '.$axis);
    }
}
$src=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-rooms.php');
$check(str_contains($src,"if(\$page<1||\$page>(int)\$edition['pages'])"),
    'actual create handler lacks exact page guard');
$check(!str_contains($src,"\$page=max(1,min((int)\$edition['pages'],\$page))"),
    'legacy page clamp still present');
$check(!str_contains($src,"'x'=>max(0,min(1,(float)"),
    'legacy region coercion clamp still present');
echo "PASS R47: {$checks} real File17 reading-room page/region integrity assertions\n";
