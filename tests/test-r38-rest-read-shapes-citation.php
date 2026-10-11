<?php
/**
 * R38 actual PLDR_REST::reading_items / ocr_search / citation and
 * PLDR_Reader::citation regression on malformed GET filters and stable edition.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $meta=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 9;}
function sanitize_key($s):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$s));}
function absint($v):int{return abs((int)$v);}
function wp_json_encode($x){return json_encode($x);}
function rest_ensure_response($v){return $v;}
function home_url($path=''):string{return 'https://example.test'.$path;}
function apply_filters($tag,$value,...$args){return $value;}
class WP_REST_Request implements ArrayAccess {
    public function __construct(private array $params=array()){}
    public function offsetExists(mixed $key):bool{return array_key_exists($key,$this->params);}
    public function offsetGet(mixed $key):mixed{return $this->params[$key]??null;}
    public function offsetSet(mixed $key,mixed $v):void{$this->params[$key]=$v;}
    public function offsetUnset(mixed $key):void{unset($this->params[$key]);}
}
final class PLDR_Core {
    public static function table(string $type):string{return 'wp_pldr_'.$type;}
    public static function machine_error(string $code,string $msg,int $status=400,array $data=array()):WP_Error{
        return new WP_Error($code,$msg,$data);
    }
    public static function edition(int $id):?array{
        if(!in_array($id,array(42,43),true))return null;
        return array(
           'id'=>$id,'public_id'=>'test-document-family','title'=>'Edition Citation Test',
           'author_name'=>'Author','edition_label'=>'First','publication_year'=>2024,
           'publisher'=>'Publisher','pages'=>5,'document_id'=>7,'version'=>2
        );
    }
    public static function normalize_search(string $query):string{return strtolower(trim($query));}
    public static function audit(string $type,int $id,string $event,array $data=array(),int $actor=0):bool{return true;}
}
final class PLDR_Access {
    public static int $checks=0;
    public static function can_access_edition(int $edition,string $op='read',int $user=0):bool{
        self::$checks++;
        return in_array($edition,array(42,43),true) && $op==='read' && $user===9;
    }
}
final class R38_DB {
    public string $last_error='';
    public int $reads=0;
    public function prepare(string $sql,...$args):string{return $sql;}
    public function get_results(string $sql,$mode=null):array{$this->reads++;return array();}
    public function get_row(string $sql,$mode=null):?array{$this->reads++;return null;}
    public function get_var(string $sql){$this->reads++;return 0;}
    public function esc_like(string $term):string{return $term;}
}
$GLOBALS['wpdb']=new R38_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rest.php';
$checks=0;
$check=static function(bool $ok,string $label)use(&$checks):void{
    if(!$ok)throw new RuntimeException('R38 actual GET/citation regression: '.$label);
    $checks++;
};
$negative=array(
    array('reading_items','edition_id',array(42)),
    array('reading_items','edition_id',true),
    array('reading_items','edition_id',-42),
    array('reading_items','edition_id','42.0'),
    array('reading_items','limit',array(3)),
    array('reading_items','limit',false),
    array('reading_items','limit','3.5'),
    array('reading_items','limit','-4'),
    array('reading_items','offset',array(1)),
    array('reading_items','offset',true),
    array('reading_items','offset',-1),
    array('reading_items','offset','1.7'),
    array('reading_items','cursor',array('payload')),
    array('reading_items','cursor',false),
    array('reading_items','cursor',(object)array('payload'=>'x')),
    array('ocr_search','edition',array(42)),
    array('ocr_search','edition',-42),
    array('ocr_search','q',array('secret')),
    array('ocr_search','q',(object)array('q'=>'search')),
    array('ocr_search','q',true),
    array('ocr_search','cursor',array('abc')),
    array('ocr_search','cursor',false),
    array('ocr_search','limit',array(2)),
    array('ocr_search','limit',-4),
    array('ocr_search','limit','4.5'),
    array('citation','edition',array(42)),
    array('citation','edition',true),
    array('citation','edition',-42),
    array('citation','page',array(2)),
    array('citation','page',false),
    array('citation','page',-3),
    array('citation','page','3.1'),
    array('citation','style',array('apa')),
    array('citation','style',(object)array('name'=>'mla')),
    array('citation','style',true)
);
$defaults=array(
    'reading_items'=>array('edition_id'=>42,'limit'=>10,'offset'=>0,'cursor'=>''),
    'ocr_search'=>array('edition'=>42,'q'=>'clinical','limit'=>10,'cursor'=>''),
    'citation'=>array('edition'=>42,'page'=>2,'style'=>'sabri')
);
foreach($negative as [$method,$field,$value]){
    $args=$defaults[$method];$args[$field]=$value;
    $beforeReads=$GLOBALS['wpdb']->reads;$beforeAuth=PLDR_Access::$checks;
    $response=PLDR_REST::$method(new WP_REST_Request($args));
    $check(is_wp_error($response) && $response->code==='pldr_query_input_shape',
       $method.' accepted invalid '. $field.' / '.get_debug_type($value));
    $check($GLOBALS['wpdb']->reads===$beforeReads,
       $method.' accessed database before rejecting malformed '.$field);
    $check(PLDR_Access::$checks===$beforeAuth,
       $method.' reached edition authorization before rejecting malformed '.$field);
}
foreach(array('chicago','bibtex','ris','csl-json','not-a-style') as $bad_style){
    $beforeReads=$GLOBALS['wpdb']->reads;
    $params=$defaults['citation'];$params['style']=$bad_style;
    $result=PLDR_REST::citation(new WP_REST_Request($params));
    $check(is_wp_error($result)&&$result->code==='pldr_citation_style',
       'unsupported basic style was silently relabeled: '.$bad_style);
    $check($GLOBALS['wpdb']->reads===$beforeReads,'unsupported style queried document DB');
}
foreach(array(42,43) as $edition_id){
    foreach(array('sabri','apa','mla') as $style){
        foreach(array(0,1,2,5) as $page){
            $result=PLDR_REST::citation(new WP_REST_Request(array(
               'edition'=>$edition_id,'style'=>$style,'page'=>$page
            )));
            $check(is_array($result),'valid basic citation failed');
            $check(($result['style']??'')===$style,'citation style output mislabeled');
            $check(($result['page']??-1)===$page,'citation page changed');
            $check(str_contains((string)$result['citation'],'edition ID: '.$edition_id),
               'citation omitted immutable edition identity '.$edition_id);
            $check(str_contains((string)$result['citation'],'test-document-family'),
               'citation lost stable document family');
            $check($page===0 || str_contains((string)$result['citation'],(string)$page),
               'page-specific citation omitted page');
        }
    }
}
$read=PLDR_REST::reading_items(new WP_REST_Request($defaults['reading_items']));
$check(is_array($read)&&($read['items']??null)===array(),'valid empty private reading page failed');
$check(($read['cursor_supported']??false)===true,'signed cursor availability incorrectly reported');
$ocr=PLDR_REST::ocr_search(new WP_REST_Request($defaults['ocr_search']));
$check(is_array($ocr)&&($ocr['items']??null)===array(),'valid empty OCR search was denied');
$check(($ocr['cursor_supported']??false)===true,'valid OCR cursor contract changed');
$short=PLDR_REST::ocr_search(new WP_REST_Request(array('edition'=>42,'q'=>'a','limit'=>10)));
$check(is_wp_error($short)&&$short->code==='pldr_ocr_query_short',
    'valid-shape short OCR query did not reach existing domain validator');
echo "PASS R38: {$checks} actual GET query-shape, stable edition/page citation and accepted request assertions\n";
