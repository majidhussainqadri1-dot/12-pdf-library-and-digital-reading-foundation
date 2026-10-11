<?php
/** R36 actual PLDR_Reading::add_item private note/tag shape regression. */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
final class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 9;}
function absint($v):int{return abs((int)$v);}
function sanitize_key($v):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$v));}
function sanitize_textarea_field($v):string{return trim(strip_tags((string)$v));}
function sanitize_text_field($v):string{return trim(strip_tags((string)$v));}
function wp_json_encode($v):string{return json_encode($v,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
final class PLDR_Core {
    public static function table(string $name):string{return 'wp_pldr_'.$name;}
    public static function machine_error(string $code,string $msg,int $status=400,array $data=array()):WP_Error {
        return new WP_Error($code,$msg,$data);
    }
    public static function edition(int $edition):?array{
        return $edition===42?array('id'=>42,'pages'=>12,'document_id'=>7,'version'=>3):null;
    }
    public static function now():string{return '2026-10-11 02:00:00';}
    public static function audit(string $type,int $id,string $action,array $data=array(),int $user_id=0):bool{return true;}
    public static function sanitize_json_list($value):array{
        if(is_string($value)){
            $decoded=json_decode($value,true);
            $value=is_array($decoded)?$decoded:explode(',',$value);
        }
        if(!is_array($value))return array();
        foreach($value as $tag)if(!is_scalar($tag)||is_bool($tag)){
            throw new RuntimeException('Malformed tags reached post-validation sanitizer');
        }
        return array_values(array_unique(array_filter(array_map('trim',array_map('strval',$value)))));
    }
}
final class PLDR_Access {
    public static function can_access_edition(int $edition,string $operation='read',int $user=0):bool{
        return $edition===42&&$user===9&&$operation==='read';
    }
}
final class PLDR_R36_DB {
    public string $last_error='';
    public int $insert_id=0;
    public int $writes=0;
    public array $last_row=array();
    public array $read_rows=array();
    public function prepare(string $sql,...$params):string{return $sql;}
    public function get_results($sql,$output=null):array{return $this->read_rows;}
    public $delete_return=0;
    public array $delete_calls=array();
    public function delete(string $table,array $where,$format=null){
        $this->delete_calls[]=$where;
        return $this->delete_return;
    }
    public function insert(string $table,array $data,$format=null):int{
        $this->writes++;$this->insert_id++;$this->last_row=$data;return 1;
    }
}
$GLOBALS['wpdb']=new PLDR_R36_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
$assertions=0;
$assert=static function(bool $ok,string $desc)use(&$assertions):void{
    if(!$ok)throw new RuntimeException('R36 private reading input: '.$desc);
    $assertions++;
};
$base=array('type'=>'note','page'=>2,'note'=>'my private reading note','anchor'=>'paragraph 5','tags'=>array('clinical','source'));
$cases=array(
    array('type nested array','type',array('note'),'pldr_item_input_shape'),
    array('type object','type',(object)array('name'=>'note'),'pldr_item_input_shape'),
    array('page nested array','page',array(2),'pldr_item_input_shape'),
    array('page object','page',(object)array('page'=>2),'pldr_item_input_shape'),
    array('page boolean','page',true,'pldr_item_input_shape'),
    array('page noninteger string','page','2.2','pldr_item_input_shape'),
    array('page alphabetic','page','page2','pldr_item_input_shape'),
    array('note nested array','note',array('private'),'pldr_item_input_shape'),
    array('note object','note',(object)array('text'=>'private'),'pldr_item_input_shape'),
    array('anchor nested array','anchor',array('anchor'),'pldr_item_input_shape'),
    array('anchor boolean','anchor',false,'pldr_item_input_shape'),
    array('tags nested array','tags',array(array('private'=>'foo')),'pldr_item_tags_shape'),
    array('tags object item','tags',array((object)array('title'=>'foo')),'pldr_item_tags_shape'),
    array('tags map','tags',array('tag'=>'safe'),'pldr_item_tags_shape'),
    array('tags nested JSON','tags','[{"text":"foo"}]','pldr_item_tags_shape'),
    array('tags object JSON','tags','{"tag":"foo"}','pldr_item_tags_shape'),
    array('tags malformed JSON','tags','["unfinished"','pldr_item_tags_shape'),
    array('tags boolean','tags',false,'pldr_item_tags_shape'),
    array('tags integer','tags',1,'pldr_item_tags_shape'),
    array('tags oversized list','tags',array_fill(0,101,'tag'),'pldr_item_tags_shape'),
    array('tags oversized string','tags',str_repeat('x',16001),'pldr_item_tags_shape'),
    array('note oversized string','note',str_repeat('x',16001),'pldr_item_input_size'),
);
foreach($cases as [$desc,$field,$value,$code]){
    $item=$base;$item[$field]=$value;
    $before=$GLOBALS['wpdb']->writes;
    $actual=PLDR_Reading::add_item(42,$item,9);
    $assert(is_wp_error($actual)&&$actual->code===$code,$desc.' expected '.$code);
    $assert($GLOBALS['wpdb']->writes===$before,$desc.' must not write malformed private state');
}
foreach(array(
    array('description'=>'proper string tags','tags'=>'clinical, learning'),
    array('description'=>'proper JSON tags','tags'=>'["clinical","learning"]'),
    array('description'=>'proper list tags','tags'=>array('clinical','learning')),
) as $good){
    $valid=$base;$valid['tags']=$good['tags'];
    $before=$GLOBALS['wpdb']->writes;
    $result=PLDR_Reading::add_item(42,$valid,9);
    $assert(is_array($result)&&$result['private']===true,$good['description'].' was rejected');
    $assert($GLOBALS['wpdb']->writes===$before+1,$good['description'].' was not saved');
    $row=$GLOBALS['wpdb']->last_row;
    $assert((int)$row['user_id']===9&&(int)$row['edition_id']===42&&
        is_array(json_decode((string)$row['tags_json'],true)),$good['description'].' persisted incorrectly');
}
$noPage=$base;$noPage['page']='0';
$res=PLDR_Reading::add_item(42,$noPage,9);
$assert(is_wp_error($res)&&$res->code==='pldr_page_range','real page bounds lost');
$notAllowed=PLDR_Reading::add_item(42,$base,13);
$assert(is_wp_error($notAllowed)&&$notAllowed->code==='pldr_item_forbidden','reader owner authorization lost');
// R36: actual PLDR_Reading::items() must reject malformed HISTORIC
// persisted JSON even after the modern writer validates new tags.
$validRow=array('id'=>1,'item_type'=>'bookmark','page_number'=>2,'anchor_text'=>'text','note_text'=>'',
    'tags_json'=>'["research","reference"]','version'=>1,'created_at'=>'2026-10-11 01:00:00','updated_at'=>'2026-10-11 01:00:00');
foreach(array('{"private":"value"}','[["nested"]]','["research",1]',json_encode(array_fill(0,31,'excess')),'not-json') as $stored){
    $db=$GLOBALS['wpdb'];$db->read_rows=array_replace($validRow,array('tags_json'=>$stored));
    $db->read_rows=array($db->read_rows);
    $items=PLDR_Reading::items(42,9);
    $assert(is_array($items)&&isset($items['error'])&&is_wp_error($items['error'])&&$items['error']->code==='pldr_items_corrupt',
       'corrupt historic private tags were exposed: '.substr($stored,0,24));
}
$GLOBALS['wpdb']->read_rows=array($validRow);
$items=PLDR_Reading::items(42,9);
$assert(is_array($items)&&count($items)===1&&$items[0]['tags']===array('research','reference'),
   'well-formed private reading item was not returned');
// Prior source reader fixtures passed; next test the separate REST path.

// R36: REST exposes the same historical private item collection through a
// separate paginated route. It must deny malformed stored tags identically.
class WP_REST_Request implements ArrayAccess {
    public function __construct(private array $data=array()) {}
    public function offsetExists(mixed $key):bool{return array_key_exists($key,$this->data);}
    public function offsetGet(mixed $key):mixed{return $this->data[$key]??null;}
    public function offsetSet(mixed $key,mixed $value):void{$this->data[$key]=$value;}
    public function offsetUnset(mixed $key):void{unset($this->data[$key]);}
}
function rest_ensure_response($value){return $value;}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rest.php';
$req=new WP_REST_Request(array('edition_id'=>42,'limit'=>10,'offset'=>0,'cursor'=>''));
foreach(array(
    '{"private":"value"}',
    '[["nested"]]',
    '["research",1]',
    json_encode(array_fill(0,31,'excess')),
    'not-json'
) as $historical){
    $GLOBALS['wpdb']->read_rows=array(array_replace($validRow,array('tags_json'=>$historical)));
    $result=PLDR_REST::reading_items($req);
    $assert(is_wp_error($result)&&$result->code==='pldr_items_corrupt',
        'REST returned malformed private tags: '.substr($historical,0,20));
}
$GLOBALS['wpdb']->read_rows=array($validRow);
$result=PLDR_REST::reading_items($req);
$assert(is_array($result)&&count($result['items']??array())===1
    &&($result['items'][0]['tags']??null)===array('research','reference'),
    'REST valid private tags/list were improperly rejected');
// Native direct-delete helper must never report a non-deleted private note as
// erased: wpdb::delete=0 is distinct from one successful deletion.
$db=$GLOBALS['wpdb'];
$db->delete_return=0;
$assert(PLDR_Reading::delete_item(11,9)===false,'delete 0 rows reported successful erasure');
$db->delete_return=false;
$assert(PLDR_Reading::delete_item(11,9)===false,'delete DB error reported successful erasure');
$db->delete_return=1;
$assert(PLDR_Reading::delete_item(11,9)===true,'single-row owned delete not acknowledged');
$last=end($db->delete_calls);
$assert(is_array($last)&&($last['id']??null)===11&&($last['user_id']??null)===9,
    'native delete did not scope mutation to item and user');
$calls=count($db->delete_calls);
$assert(PLDR_Reading::delete_item(0,9)===false&&count($db->delete_calls)===$calls,
    'zero item identifier reached database');
$assert(PLDR_Reading::delete_item(12,-1)===false&&count($db->delete_calls)===$calls,
    'anonymous or invalid user identifier reached database');
echo "PASS R36: {$assertions} real reader/REST private tag and deletion-truth assertions\n";

