<?php
/**
 * R43 executable negative-input tests of eighteen genuine Future-24
 * mutation handlers. Reuses the tested R42 WordPress/DB fixture and route
 * registration without claiming a real installation or external providers.
 */
declare(strict_types=1);
require __DIR__.'/test-r42-future-rest-query-shapes.php';
$checks=0;
$assertR43=static function(bool $passed,string $message)use(&$checks):void {
    if(!$passed)throw new RuntimeException('R43 malformed Future mutation: '.$message);
    $checks++;
};
$cases=array(
    array('anchor',array('edition_id'=>42,'page'=>1,'selector'=>array(),'note'=>'ok'),array('edition_id','page'),array('note'),array('selector'),array()),
    array('authority',array('type'=>'isbn','value'=>'978000','force'=>false),array(),array('type','value'),array(),array('force')),
    array('ocr_correction',array('edition'=>42,'page'=>1,'original'=>'old','corrected'=>'new'),array('page'),array('original','corrected'),array(),array()),
    array('ocr_review',array('id'=>6,'expected_version'=>1,'decision'=>'approve','note'=>'ok'),array('expected_version'),array('decision','note'),array(),array()),
    array('annotations_import',array('edition'=>42,'items'=>array()),array(),array(),array('items'),array()),
    array('offline_grant',array('edition_id'=>42),array('edition_id'),array(),array(),array()),
    array('preferences_save',array('key'=>'reader','expected_version'=>1,'value'=>array('layout'=>'single')),array('expected_version'),array('key'),array('value'),array()),
    array('shelf_create',array('name'=>'reading'),array(),array('name'),array(),array()),
    array('shelf_add',array('id'=>6,'edition_id'=>42,'expected_version'=>1),array('edition_id','expected_version'),array(),array(),array()),
    array('shelf_rename',array('id'=>6,'name'=>'favorites','expected_version'=>1),array('expected_version'),array('name'),array(),array()),
    array('shelf_delete',array('id'=>6,'expected_version'=>1),array('expected_version'),array(),array(),array()),
    array('shelf_remove_item',array('id'=>6,'edition_id'=>42,'expected_version'=>1),array('edition_id','expected_version'),array(),array(),array()),
    array('insight_event',array('edition_id'=>42,'page'=>1,'duration_seconds'=>20,'type'=>'read','context'=>array()),array('edition_id','page','duration_seconds'),array('type'),array('context'),array()),
    array('handoff_save',array('edition'=>42,'expected_version'=>1,'context'=>array('page'=>1)),array('expected_version'),array(),array('context'),array()),
    array('a11y_verify',array('edition'=>42,'note'=>'verified'),array(),array('note'),array(),array()),
    array('reading_room',array('edition_id'=>42,'page'=>1,'anchor'=>array()),array('edition_id','page'),array(),array('anchor'),array()),
    array('derive_text',array('edition_id'=>42,'page'=>1,'text'=>'text','mode'=>'translate','target'=>'ur'),array('edition_id','page'),array('text','mode','target'),array(),array()),
    array('preservation_refresh',array('edition'=>42,'verify'=>false),array(),array(),array(),array('verify')),
);
$badNumbers=array(-5,'-5',1.5,'1.5',true,false,array(9),(object)array('id'=>9),'999999999999999999999999999999999');
$badTexts=array(array('nested'),(object)array('nested'=>true),true,false,17,2.5);
$badArrays=array('not-array',false,true,1,(object)array('x'=>1));
$badFlags=array('yes','false',2,-1,array(1),(object)array('x'=>1));
foreach($cases as [$name,$base,$numbers,$texts,$arrays,$flags]){
    foreach(array(
        array('numbers',$numbers,$badNumbers),
        array('texts',$texts,$badTexts),
        array('arrays',$arrays,$badArrays),
        array('flags',$flags,$badFlags)
    ) as [$kind,$fields,$invalid]){
        foreach($fields as $field){
            foreach($invalid as $bad){
                $input=$base;$input[$field]=$bad;
                $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes;
                $request=new WP_REST_Request($input);
                $reflection=new ReflectionMethod(PLDR_Future_REST::class,'validate_mutation_shape');
                $checked=$reflection->invoke(null,$request,$texts,$numbers,$arrays,$flags);
                $assertR43(is_wp_error($checked)&&$checked->code==='pldr_future_mutation_input_shape',
                    'common validator accepted '.$name.'/'.$field.' '.get_debug_type($bad));
                $result=PLDR_Future_REST::$name($request);
                $assertR43(is_wp_error($result) && $result->code==='pldr_future_mutation_input_shape',
                    $name.'/'.$field.' accepted invalid '.$kind.' '.get_debug_type($bad));
                $assertR43($before===$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes,
                    $name.'/'.$field.' performed DB work before input validation');
            }
        }
    }
}
$reflect=new ReflectionMethod(PLDR_Future_REST::class,'validate_mutation_shape');
$valid=array(
    array(array('edition_id'=>42,'page'=>'2','note'=>'text','selector'=>array('type'=>'TextQuoteSelector')),
        array('note'),array('edition_id','page'),array('selector'),array()),
    array(array('verify'=>true,'force'=>0),array(),array(),array(),array('verify','force')),
    array(array('verify'=>false,'force'=>'1'),array(),array(),array(),array('verify','force')),
    array(array('expected_version'=>0,'context'=>array()),array(),array('expected_version'),array('context'),array()),
    array(array(),array('note'),array('page'),array('selector'),array('verify')),
);
foreach($valid as [$payload,$text,$numbers,$arrays,$flags]){
    $actual=$reflect->invoke(null,new WP_REST_Request($payload),$text,$numbers,$arrays,$flags);
    $assertR43(true===$actual,'valid Future mutation form unexpectedly denied');
}
echo "PASS R43: {$checks} real Future-24 mutation-handler input-shape assertions\n";
