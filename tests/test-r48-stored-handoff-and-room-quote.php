<?php
/**
 * R48: real File12 historic FUT017 handoff DTO validation and FUT019
 * scholarly reading-room TextQuoteSelector source identity preflight.
 * Reuses R46 isolated WordPress-native handler fixture: not staging evidence.
 */
declare(strict_types=1);
require __DIR__.'/test-r46-handoff-room-selector-safety.php';
$checks=0;
$check=static function(bool $pass,string $reason)use(&$checks):void{
    if(!$pass)throw new RuntimeException('R48 stored handoff/room quote: '.$reason);
    $checks++;
};
$dto=new ReflectionMethod(PLDR_Future_Handoff::class,'dto');
$base=array('user_id'=>77,'edition_id'=>42,'page_number'=>3,'version'=>8,
    'layout_mode'=>'spread-rtl','zoom'=>'page-width','device_hint'=>'reader-a',
    'anchor_json'=>'[]','updated_at'=>'2026-10-12 00:00:00');
$badRaw=array(
    '{bad-json','null','true','42','"scalar"',
    '[{"type":"TextQuoteSelector","exact":"quote"}]',
    '["TextQuoteSelector"]','[1,2]',
    '{"type":"SvgSelector","value":"<svg/>"}',
    '{"type":"TextQuoteSelector"}',
    '{"type":"TextQuoteSelector","exact":"  "}',
    '{"type":"FragmentSelector","value":"page=4"}',
    '{"type":"CssSelector"}',
    '{"type":"unreviewed","value":"foo"}',
    '{"type":["TextQuoteSelector"],"exact":"quote"}',
    '{"selection":["nested"]}',
    '{"exact":"<b>markup</b>"}',
    '{"region":"not-a-region"}',
    '{"region":{"x":-0.1,"y":0,"w":0.5,"h":0.5}}',
    '{"region":{"x":true,"y":0,"w":0.5,"h":0.5}}',
    '{"region":{"x":2,"y":0,"w":0.5,"h":0.5}}',
    '{"region":{"x":0,"y":0,"w":0.5,"h":0}}',
    '{"region":{"x":0.9,"y":0,"w":0.5,"h":0.2}}',
    '{"region":{"x":0,"y":0,"w":0.5,"h":0.5,"unexpected":77}}',
    '{"type":"FragmentSelector","value":"page=3","malicious_private":"surplus"}',
);
foreach($badRaw as $stored){
    $db=$GLOBALS['wpdb'];$before=$db->writes+$db->queries;
    $result=$dto->invoke(null,array_replace($base,array('anchor_json'=>$stored)),12);
    $check(is_wp_error($result)&&$result->code==='pldr_handoff_corrupt',
        'corrupt persisted anchor exposed as reading handoff: '.substr($stored,0,90));
    $check(($result->data['version']??0)===8,
        'stored corrupted handoff version not retained for reconciliation');
    $check($before===$db->writes+$db->queries,
        'historic malformed handoff read changed persistent state');
}
foreach(array(0,-3,13,100,PHP_INT_MAX) as $page){
    $result=$dto->invoke(null,array_replace($base,array('page_number'=>$page)),12);
    $check(is_wp_error($result)&&$result->code==='pldr_handoff_corrupt',
        'historical out-of-edition page silently projected: '.$page);
}
$goodRaw=array(
    '',
    '[]',
    '{}',
    '{"type":"TextQuoteSelector","exact":"real quote"}',
    '{"type":"FragmentSelector","value":"page=3"}',
    '{"type":"CssSelector","value":"#p3"}',
    '{"selection":"selected passage"}',
    '{"region":{"x":0,"y":0.2,"w":0.3,"h":0.4}}',
    '{"type":"FragmentSelector","value":"page=3","region":{"x":0.1,"y":0,"w":0.6,"h":0.9}}'
);
foreach($goodRaw as $stored){
    $result=$dto->invoke(null,array_replace($base,array('anchor_json'=>$stored)),12);
    $check(is_array($result) && ($result['page']??null)===3,
        'valid historic canonical handoff rejected: '.$stored);
    $check(($result['version']??0)===8 && ($result['layout']??null)==='spread-rtl',
        'historic valid optimistic version/RTL layout lost');
    $check(is_array($result['anchor']??null),
        'valid private historical anchor not returned as structured data');
}
$room=new ReflectionMethod(PLDR_Future_Rooms::class,'anchor');
$badRoom=array(
    array('type'=>'TextQuoteSelector'),
    array('type'=>'TextQuoteSelector','exact'=>''),
    array('type'=>'TextQuoteSelector','exact'=>"\t  "),
    array('type'=>'TextQuoteSelector','selection'=>'a textual selection'),
    array('type'=>'TextQuoteSelector','exact'=>'<b></b>'),
    array('type'=>'TextQuoteSelector','exact'=>'    ','region'=>array('x'=>0.2,'y'=>0.3,'w'=>0.2,'h'=>0.3)),
);
foreach($badRoom as $anchor){
    $db=$GLOBALS['wpdb'];$before=$db->writes;
    $result=$room->invoke(null,$anchor,3);
    $check(is_wp_error($result)&&$result->code==='pldr_room_exact',
        'missing exact quote became supposedly scholarly selector');
    $created=PLDR_Future_Rooms::create(42,3,$anchor);
    $check(is_wp_error($created)&&$created->code==='pldr_room_exact',
        'native File17 create accepted TextQuoteSelector without exact quote');
    $check($db->writes===$before,'malformed TextQuoteSelector inserted room row');
}
foreach(array(
    array('type'=>'TextQuoteSelector','exact'=>'Source words'),
    array('type'=>'TextQuoteSelector','exact'=>'Source words','selection'=>'Source words'),
    array('type'=>'FragmentSelector','value'=>'page=3'),
    array('type'=>'CssSelector','value'=>'#chapter3'),
    array()
) as $anchor){
    $res=$room->invoke(null,$anchor,3);
    $check(is_array($res)&&!is_wp_error($res),
        'valid room selector/base page-only context was unexpectedly blocked');
}
$source=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-handoff.php');
$check(str_contains($source,'self::dto($row,(int)$edition[\'pages\'])'),
    'actual Handoff GET/SAVE no longer passes native edition page bound to DTO');
echo "PASS R48: {$checks} real stored handoff and File17 source-quote assertions\n";
