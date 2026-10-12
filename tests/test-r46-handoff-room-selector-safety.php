<?php
/**
 * R46 actual native File12 Future Handoff and Reading Room method regressions.
 * Isolated WordPress/DB simulation only, not installed Hostinger evidence.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
final class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function absint($x):int{return abs((int)$x);}
function sanitize_text_field($s):string{return trim(strip_tags((string)$s));}
function wp_strip_all_tags($s):string{return strip_tags((string)$s);}
function wp_json_encode($s,...$flags):string|false{return json_encode($s,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
final class PLDR_Core {
    public static function machine_error(string $code,string $message,int $status=400,array $data=array()):WP_Error{
        return new WP_Error($code,$message,array_merge(array('status'=>$status),$data));
    }
    public static function table(string $name):string{return 'wp_pldr_'.$name;}
    public static function document(int $id):?array{return $id===5?array('id'=>5,'category'=>'books'):null;}
    public static function now():string{return '2026-10-12 00:00:00';}
    public static function audit(string $kind,int $id,string $message,array $data=array(),int $uid=0):bool{return true;}
}
final class PLDR_Future_Data {
    public static int $reads=0;
    public static function require_edition(int $id,string $operation='read') {
        self::$reads++;
        return $id===42?array('id'=>42,'document_id'=>5,'public_id'=>'f12-r46-book','pages'=>12):
            new WP_Error('pldr_edition_missing');
    }
}
final class R46_DB {
    public string $last_error='';
    public int $reads=0;
    public int $writes=0;
    public int $queries=0;
    public array $inserted=array();
    public function prepare(string $sql,...$params):string{return $sql;}
    public function get_row(string $sql,$format=null):?array{$this->reads++;return null;}
    public function insert(string $table,array $data,$format=null):int{
        $this->writes++;$this->inserted[]=array($table,$data);return 1;
    }
    public function query(string $sql):int{$this->queries++;return 1;}
}
$GLOBALS['wpdb']=new R46_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-handoff.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-rooms.php';
$tests=0;
$assert=static function(bool $ok,string $why)use(&$tests):void{
    if(!$ok)throw new RuntimeException('R46 Future-24 input/selector: '.$why);
    $tests++;
};
$badPages=array(-1,'-2',0,'0',2.5,'2.5',true,false,null,array(2),(object)array('page'=>2),'99999999999999999999999999','02');
foreach($badPages as $bad){
    $wpdb=$GLOBALS['wpdb'];$before=$wpdb->reads+$wpdb->writes+$wpdb->queries;
    $beforeEdition=PLDR_Future_Data::$reads;
    $result=PLDR_Future_Handoff::save(42,array('page'=>$bad),0);
    $assert(is_wp_error($result)&&$result->code==='pldr_handoff_page_shape',
        'invalid page value accepted: '.get_debug_type($bad));
    $assert($before===$wpdb->reads+$wpdb->writes+$wpdb->queries,
        'invalid handoff page initiated DB work');
    $assert($beforeEdition===PLDR_Future_Data::$reads,
        'invalid handoff page initiated edition provider work');
}
foreach(array('layout','zoom','device') as $field){
    foreach(array(10,true,array('malformed'),new stdClass()) as $bad){
        $before=$GLOBALS['wpdb']->writes;
        $result=PLDR_Future_Handoff::save(42,array('page'=>1,$field=>$bad),0);
        $assert(is_wp_error($result)&&$result->code==='pldr_handoff_text_shape',
            'coercive handoff text field accepted '.$field);
        $assert($before===$GLOBALS['wpdb']->writes,'invalid text caused handoff write');
    }
}
foreach(array('not-array',10,true,new stdClass()) as $bad){
    $r=PLDR_Future_Handoff::save(42,array('page'=>1,'anchor'=>$bad),0);
    $assert(is_wp_error($r)&&$r->code==='pldr_handoff_anchor_shape',
        'malformed nested anchor did not fail');
}
$upper=PLDR_Future_Handoff::save(42,array('page'=>13),0);
$assert(is_wp_error($upper)&&$upper->code==='pldr_handoff_page','page above actual edition silently clamped');
$anchor=new ReflectionMethod(PLDR_Future_Handoff::class,'anchor');
$shapeBad=array(
    array('region'=>array('x'=>'bad','y'=>0,'w'=>0.2,'h'=>0.2)),
    array('region'=>array('x'=>true,'y'=>0,'w'=>0.2,'h'=>0.2)),
    array('region'=>array('x'=>-0.2,'y'=>0,'w'=>0.2,'h'=>0.2)),
    array('region'=>array('x'=>1.2,'y'=>0,'w'=>0.2,'h'=>0.2)),
    array('region'=>array('x'=>array(0.3),'y'=>0,'w'=>0.2,'h'=>0.2)),
    array('region'=>array('x'=>0.1,'y'=>0,'w'=>'forty','h'=>0.2)),
    array('region'=>array('x'=>0.1,'y'=>0,'w'=>null,'h'=>0.2)),
    array('region'=>array('x'=>0.1,'y'=>0,'w'=>0.2,'h'=>INF)),
    array('region'=>'not-a-region'),
    array('type'=>array('TextQuoteSelector')),
    array('selection'=>array('nested')),
    array('exact'=>true)
);
foreach($shapeBad as $bad){
    $before=$GLOBALS['wpdb']->writes;
    $r=$anchor->invoke(null,$bad,3);
    $assert(is_wp_error($r),'invalid nested handoff anchor was coerced');
    $assert($before===$GLOBALS['wpdb']->writes,'invalid selector altered persistent state');
}
foreach(array(
    array('region'=>array('x'=>0,'y'=>0,'w'=>0.5,'h'=>0.3)),
    array('region'=>array('x'=>'0.25','y'=>'0.4','w'=>'0.5','h'=>'0.4')),
    array('type'=>'TextQuoteSelector','exact'=>'real words'),
    array('type'=>'FragmentSelector','value'=>'page=3')
) as $good){
    $r=$anchor->invoke(null,$good,3);
    $assert(is_array($r)&&!is_wp_error($r),'valid handoff selector rejected');
}
$before=$GLOBALS['wpdb']->writes;
$r=PLDR_Future_Handoff::save(42,array('page'=>3,'layout'=>'spread-rtl','zoom'=>'page-width',
    'anchor'=>array('region'=>array('x'=>0.1,'y'=>0.2,'w'=>0.4,'h'=>0.5))),0);
$assert(is_array($r)&&($r['page']??null)===3,'valid cross-device RTL handoff did not round-trip');
$assert($GLOBALS['wpdb']->writes===$before+1,'valid reading handoff did not write exactly once');
$assert(abs(($r['anchor']['region']['x']??0)-0.1)<0.0000001,'valid region location was altered');
foreach(array(
    array('type'=>'SvgSelector','value'=>'<svg><circle/></svg>'),
    array('type'=>'SvgSelector','region'=>array('x'=>0,'y'=>0,'w'=>0.5,'h'=>0.5)),
) as $svg){
    $before=$GLOBALS['wpdb']->writes;
    $r=PLDR_Future_Rooms::create(42,3,$svg);
    $assert(is_wp_error($r)&&$r->code==='pldr_room_svg_unsupported',
        'unreviewed SVG selector accepted by File17 room boundary');
    $assert($before===$GLOBALS['wpdb']->writes,'unreviewed SVG selector created persistent room');
}
foreach(array(array('type'=>array('TextQuoteSelector')),array('type'=>'BadSelector')) as $type){
    $before=$GLOBALS['wpdb']->writes;
    $r=PLDR_Future_Rooms::create(42,3,$type);
    $assert(is_wp_error($r)&&in_array($r->code,array('pldr_room_selector_shape','pldr_room_selector'),true),
        'invalid room selector type accepted');
    $assert($before===$GLOBALS['wpdb']->writes,'invalid room selector wrote DB');
}
foreach(array(array('type'=>'CssSelector','value'=>array('bad')),array('selection'=>true)) as $type){
    $r=PLDR_Future_Rooms::create(42,3,$type);
    $assert(is_wp_error($r)&&$r->code==='pldr_room_selector_shape',
        'invalid room selector text silently coerced');
}
$roomAnchor=new ReflectionMethod(PLDR_Future_Rooms::class,'anchor');
foreach(array(
    array('type'=>'CssSelector','value'=>'#p3'),
    array('type'=>'TextQuoteSelector','exact'=>'a real quote'),
    array('type'=>'FragmentSelector','value'=>'page=3'),
) as $good){
    $r=$roomAnchor->invoke(null,$good,3);
    $assert(is_array($r),'approved File17 selector was mistakenly blocked');
}
echo "PASS R46: {$tests} real Future-24 Handoff/Room native handler assertions\n";
