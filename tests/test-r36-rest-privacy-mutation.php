<?php
/**
 * R36 real PLDR_REST::reading_items and mutation route guards.
 * Exercises actual PHP methods, not only grep/static signatures.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($value):bool{return $value instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function absint($v):int{return abs((int)$v);}
function sanitize_text_field($v):string{return trim(strip_tags((string)$v));}
function sanitize_textarea_field($v):string{return sanitize_text_field($v);}
function sanitize_key($v):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$v));}
function rest_ensure_response($x){return $x;}
function wp_json_encode($x){return json_encode($x);}
class WP_REST_Request implements ArrayAccess {
    public function __construct(private array $data=array()){}
    public function offsetExists(mixed $key):bool{return array_key_exists($key,$this->data);}
    public function offsetGet(mixed $key):mixed{return $this->data[$key]??null;}
    public function offsetSet(mixed $key,mixed $value):void{$this->data[$key]=$value;}
    public function offsetUnset(mixed $key):void{unset($this->data[$key]);}
    public function get_json_params():array{return $this->data;}
    public function get_params():array{return $this->data;}
    public function get_header($key):string{return '';}
}
final class PLDR_Core {
    public static function table(string $key):string{return 'wp_pldr_'.$key;}
    public static function machine_error(string $code,string $message,int $status=400,array $info=array()):WP_Error{
        return new WP_Error($code,$message,$info);
    }
    public static function audit(string $kind,int $id,string $what,array $data=array(),int $uid=0):bool{
        $GLOBALS['r36_audits']++;return true;
    }
}
final class PLDR_Access {
    public static function can_access_edition(int $edition_id,string $op='read',int $user_id=0):bool {
        return $edition_id===42 && $op==='read' && $user_id===77;
    }
}
final class PLDR_R36_DB {
    public string $last_error='';
    public int $reads=0;
    public int $writes=0;
    public array $rows=array();
    public function prepare(string $query,...$values):string{return $query;}
    public function get_results(string $sql,$mode=null):array {$this->reads++;return $this->rows;}
    public function get_row(string $sql,$mode=null):?array {$this->reads++;return null;}
    public function get_var(string $sql){$this->reads++;return null;}
    public function insert(string $table,array $row,$args=null):int {$this->writes++;return 1;}
    public function query(string $sql):int {$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new PLDR_R36_DB();
$GLOBALS['r36_audits']=0;
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rest.php';
$checks=0;
$assert=static function(bool $condition,string $info)use(&$checks):void{
    if(!$condition)throw new RuntimeException('R36 REST regression: '.$info);
    $checks++;
};
$base=array('id'=>5,'item_type'=>'note','page_number'=>2,'anchor_text'=>'paragraph 2',
    'note_text'=>'strictly private','tags_json'=>'["clinical","citation"]','version'=>1,
    'created_at'=>'2026-10-11 01:00:00','updated_at'=>'2026-10-11 01:00:00');
$request=new WP_REST_Request(array('edition_id'=>42,'offset'=>0,'limit'=>5,'cursor'=>''));
foreach(array(
    '{"tag":"associative"}',
    '[["nested"]]',
    '["clinical",7]',
    json_encode(array_fill(0,31,'too-many')),
    'bad-json',
    '"scalar-string"',
    str_repeat('z',16001)
) as $bad){
    $GLOBALS['wpdb']->rows=array(array_replace($base,array('tags_json'=>$bad)));
    $reads=$GLOBALS['wpdb']->reads;
    $res=PLDR_REST::reading_items($request);
    $assert(is_wp_error($res)&&$res->code==='pldr_items_corrupt','REST leaked malformed historic tags: '.substr($bad,0,26));
    $assert($GLOBALS['wpdb']->reads===$reads+1,'REST did not exercise actual paged DB retrieval');
}
$GLOBALS['wpdb']->rows=array($base);
$good=PLDR_REST::reading_items($request);
$assert(is_array($good)&&count($good['items']??array())===1,'valid private REST item rejected');
$assert($good['items'][0]['tags']===array('clinical','citation'),'valid tags DTO changed');
$assert(!array_key_exists('tags_json',$good['items'][0]),'private raw JSON leaked');

// Malformed mutation payloads must be denied BEFORE any edition/case query,
// rights evidence coercion, idempotency claim or private progress write.
$negative=array(
    array('save_progress',array('edition_id'=>42,'page'=>array(1),'expected_updated_at'=>'')),
    array('save_progress',array('edition_id'=>42,'page'=>'2.5','expected_updated_at'=>'')),
    array('save_progress',array('edition_id'=>42,'page'=>true,'expected_updated_at'=>'')),
    array('save_progress',array('edition_id'=>array(42),'page'=>2)),
    array('save_progress',array('edition_id'=>42,'page'=>2,'expected_updated_at'=>array())),
    array('add_reading_item',array('edition_id'=>array(42),'type'=>'bookmark')),
    array('delete_reading_item',array('id'=>array(1),'expected_version'=>1)),
    array('delete_reading_item',array('id'=>1,'expected_version'=>array(1))),
    array('rights_case',array('document_id'=>array('uuid'),'reason'=>'copyright')),
    array('rights_case',array('document_id'=>'uuid','reason'=>array('copyright'))),
    array('rights_case',array('document_id'=>'uuid','reason'=>'copyright','evidence'=>'nested-strings-are-not-evidence')),
    array('rights_case',array('document_id'=>'uuid','reason'=>'copyright','evidence'=>(object)array('a'=>'b'))),
    array('rights_decision',array('id'=>5,'decision'=>array('remove'),'note'=>'reasoned','expected_version'=>1)),
    array('rights_decision',array('id'=>5,'decision'=>'remove','note'=>array('reasoned'),'expected_version'=>1)),
    array('rights_decision',array('id'=>5,'decision'=>'remove','note'=>'reasoned','expected_version'=>array(1))),
    array('rights_appeal',array('id'=>array(5),'reason'=>'appeal','expected_version'=>1)),
    array('rights_appeal',array('id'=>5,'reason'=>array('appeal'),'expected_version'=>1)),
    array('rights_appeal',array('id'=>5,'reason'=>'appeal','expected_version'=>true)),
    array('rights_appeal',array('id'=>5,'reason'=>'appeal','evidence'=>'string','expected_version'=>1))
);
foreach($negative as [$fn,$data]){
    $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes;
    $v=PLDR_REST::$fn(new WP_REST_Request($data));
    $assert(is_wp_error($v)&&$v->code==='pldr_rest_input_shape',$fn.' failed to deny bad JSON shape');
    $assert($before===$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes,$fn.' attempted DB read/write with malformed JSON');
}
$assert($GLOBALS['r36_audits']>=7,'legacy private-tag corruption audit not emitted');
echo "PASS R36: {$checks} real private REST page, strict rights/progress mutation and privacy assertions\n";
