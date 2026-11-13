<?php
/** R62 red-first: Book Content Pack normalized identity must be validated before DB access. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
class WP_Error {
    private string $code;
    public function __construct($code,$message='',$data=array()){$this->code=(string)$code;}
    public function get_error_code():string{return $this->code;}
}
function get_current_user_id():int{return 7;}
function sanitize_key($s):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$s));}
function sanitize_text_field($s):string{return trim(strip_tags((string)$s));}
function sanitize_textarea_field($s):string{return trim(strip_tags((string)$s));}
function absint($s):int{return abs((int)$s);}
function wp_json_encode($v,$flags=0){return json_encode($v,$flags);}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
class PLDR_Core {
    public static function authorize($name,$id=0,$uid=0):bool{return $uid===7;}
    public static function table($name):string{return 'wp_pldr_'.$name;}
    public static function founder($id):bool{return false;}
    public static function machine_error($name,$msg,$status=400,$context=array()){return new WP_Error($name,$msg);}
    public static function sanitize_json_list($v):array{return is_array($v)?$v:array();}
    public static function now():string{return '2026-11-13 06:00:00';}
    public static function emit($event,$type,$id,$payload){return true;}
    public static function audit($type,$id,$action,$data=array(),$actor=0):void{}
}
$GLOBALS['db_calls']=0;
$GLOBALS['permit_db']=false;
$wpdb=new class {
    public string $last_error='';
    public int $insert_id=0;
    public function __call(string $method,array $args) {
        $GLOBALS['db_calls']++;
        if(!($GLOBALS['permit_db']??false))throw new RuntimeException('R62: invalid manifest attempted a DB operation: '.$method);
        if($method==='prepare')return 'prepared-valid-query';
        if($method==='get_row')return null;
        if($method==='query')return 1;
        if($method==='insert'){$this->insert_id=71;return 1;}
        throw new RuntimeException('R62 unknown mock DB method: '.$method);
    }
};
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rights.php';
$valid=array(
    'pack_key'=>'classical-books','version'=>'1.0.0','title'=>'Classical Sources',
    'author'=>'A Verified Author','language'=>'en-US','rights'=>'public-domain',
    'checksum'=>str_repeat('a',64),'update_manifest'=>'1.0.0'
);
$cases=array(
    'slug erased by normalization'=>array('pack_key'=>'!!!'),
    'overlength slug incompatible with schema'=>array('pack_key'=>str_repeat('a',121)),
    'normalized empty version'=>array('version'=>'    '),
    'overlength version incompatible with schema'=>array('version'=>str_repeat('v',41)),
    'noncanonical version'=>array('version'=>'v1 2 3'),
    'normalized empty title'=>array('title'=>'    '),
    'normalized empty author'=>array('author'=>'    '),
    'normalized empty language'=>array('language'=>'    '),
    'wrong typed checksum'=>array('checksum'=>array('not-a-sha')),
    'wrong typed rights'=>array('rights'=>array('public-domain')),
);
$checks=0;
foreach($cases as $label=>$mutation){
    $manifest=array_merge($valid,$mutation);
    $result=PLDR_Book_Packs::register($manifest,7);
    $checks++;
    if(!($result instanceof WP_Error)){
        fwrite(STDERR,"R62 FAIL: rejected identity was accepted: $label\n");exit(1);
    }
    if($GLOBALS['db_calls']!==0){
        fwrite(STDERR,"R62 FAIL: invalid identity reached DB: $label\n");exit(1);
    }
}
$source=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rights.php');
$checks++;
if(!str_contains($source,'private static function persist_authorized')){
    fwrite(STDERR,"R62 FAIL: controlled private Book Pack writer missing\n");exit(1);
}
// After invalid-input checks, verify the same class still accepts a real valid
// manager's canonical pack and reaches its transactional/event persistence path.
$GLOBALS['permit_db']=true;
$positive=PLDR_Book_Packs::register($valid,7);
$checks++;
if(!is_array($positive)||($positive['id']??0)!==71
    ||($positive['pack_key']??'')!=='classical-books'
    ||($positive['immutable_version']??false)!==true
    ||$GLOBALS['db_calls']<4){
    fwrite(STDERR,"R62 FAIL: valid canonical pack did not register transactionally\n");exit(1);
}
echo "R62 Book Pack canonical identity: $checks negative and positive regression checks PASS.\n";
