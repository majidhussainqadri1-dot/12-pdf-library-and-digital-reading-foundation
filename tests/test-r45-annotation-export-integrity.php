<?php
/**
 * R45: actual native PLDR_Future_Annotations::export uses the SAME canonical
 * PLDR_Reading::parse_stored_tags validator as the direct and REST private
 * reader. This is an isolated WordPress/DB fixture, NOT live or staging.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($x):bool{return $x instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function add_query_arg($key,$val,$uri):string{return (string)$uri.'?'.rawurlencode((string)$key).'='.rawurlencode((string)$val);}
final class PLDR_Core {
    public static function table(string $name):string{return 'wp_pldr_'.$name;}
    public static function route_url(string $route,array $data=array()):string{return 'https://example.org/library/read/'.($data['id']??'');}
    public static function machine_error(string $code,string $message,int $status=400,array $more=array()):WP_Error{return new WP_Error($code,$message,array_merge(array('status'=>$status),$more));}
    public static function audit(string $kind,int $id,string $event,array $data=array(),int $uid=0):bool{
        $GLOBALS['r45_audits'][]=array($kind,$id,$event,$data,$uid);return true;
    }
}
final class PLDR_Future_Data {
    public static int $called=0;
    public static function require_edition(int $edition_id,string $operation='read') {
        self::$called++;
        return $edition_id===42?array('id'=>42,'public_id'=>'r45-document','pages'=>100,'document_id'=>5):new WP_Error('edition_unknown');
    }
}
final class R45_DB {
    public string $last_error='';
    public int $reads=0;
    public array $rows=array();
    public function prepare(string $sql,...$args):string{return $sql;}
    public function get_results(string $sql,$format=null):array{$this->reads++;return $this->rows;}
}
$GLOBALS['wpdb']=new R45_DB();
$GLOBALS['r45_audits']=array();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-annotations.php';
$checks=0;
$check=static function(bool $condition,string $label)use(&$checks):void{
    if(!$condition)throw new RuntimeException('R45 private annotation export: '.$label);
    $checks++;
};
$good=array('id'=>10,'item_type'=>'note','page_number'=>7,
    'anchor_text'=>'chapter seven','note_text'=>'Private annotation',
    'tags_json'=>'["reference","reading"]','version'=>2,
    'created_at'=>'2026-10-11 00:00:00','updated_at'=>'2026-10-11 00:00:00');
$valid=array('[]','["reference"]','["reference","reading"]');
foreach($valid as $json){
    $GLOBALS['wpdb']->rows=array(array_replace($good,array('tags_json'=>$json)));
    $got=PLDR_Future_Annotations::export(42,0,2);
    $check(!isset($got['error']) && ($got['returned']??0)===1,'canonical valid private tags denied: '.$json);
    $check(($got['private']??false)===true && ($got['items'][0]['type']??null)==='Annotation','valid portable annotation lost private identity');
    $check(!array_key_exists('tags_json',$got['items'][0]),'private raw tags JSON exposed');
    $check(($got['edition_id']??0)===42,'cross-edition metadata altered');
}
$bad=array('{}','{"0":"reference"}','{"tag":"private"}',
    '[["nested"]]','[true]','[42]','["ok",false]','"string"','null','42',
    'broken-json',str_repeat('Z',16001),
    json_encode(array_fill(0,31,'too-many')));
foreach($bad as $json){
    $check(null===PLDR_Reading::parse_stored_tags($json),'native parser unexpectedly accepts corrupt JSON: '.substr($json,0,40));
    $GLOBALS['wpdb']->rows=array(array_replace($good,array('tags_json'=>$json)));
    $result=PLDR_Future_Annotations::export(42,0,2);
    $check(is_array($result) && is_wp_error($result['error']??null)
       && $result['error']->code==='pldr_annotation_export_corrupt',
       'portable export accepted corrupt historic JSON: '.substr($json,0,40));
    $check(!isset($result['items']) && !isset($result['returned']),
       'portable export returned partial private annotation data after corruption');
    $check(($result['error']->data['item_id']??0)===10,
       'corruption status did not identify the record for reconciliation');
}
$GLOBALS['wpdb']->rows=array($good,array_replace($good,array('id'=>11,'tags_json'=>'{}')));
$first=PLDR_Future_Annotations::export(42,0,5);
$check(is_wp_error($first['error']??null),'corrupt second row did not fail entire export batch');
$check(!isset($first['items']),'valid first row leaked in partial failed export');
$last=end($GLOBALS['r45_audits']);
$check(($last[0]??null)==='reading_item' && ($last[1]??0)===11
    && ($last[2]??null)==='annotation_export_tags_corrupt',
    'canonical audit evidence not recorded for malformed private row');
echo "PASS R45: {$checks} actual portable private annotation/tag consistency assertions\n";
