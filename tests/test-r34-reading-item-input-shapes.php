<?php
/** R34 actual PLDR_Reading::add_item private note/tag shape regression. */
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
final class PLDR_R34_DB {
    public string $last_error='';
    public int $insert_id=0;
    public int $writes=0;
    public array $last_row=array();
    public function insert(string $table,array $data,$format=null):int{
        $this->writes++;$this->insert_id++;$this->last_row=$data;return 1;
    }
}
$GLOBALS['wpdb']=new PLDR_R34_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
$assertions=0;
$assert=static function(bool $ok,string $desc)use(&$assertions):void{
    if(!$ok)throw new RuntimeException('R34 private reading input: '.$desc);
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
echo "PASS R34: {$assertions} executable private reading-item shape, rights and valid-tags assertions\n";
