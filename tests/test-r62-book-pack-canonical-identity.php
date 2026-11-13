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
class PLDR_Core {
    public static function authorize($name,$id=0,$uid=0):bool{return $uid===7;}
    public static function founder($id):bool{return false;}
    public static function machine_error($name,$msg,$status=400,$context=array()){return new WP_Error($name,$msg);}
    public static function sanitize_json_list($v):array{return is_array($v)?$v:array();}
}
$GLOBALS['db_calls']=0;
$wpdb=new class {
    public string $last_error='';
    public function __call(string $method,array $args) {
        $GLOBALS['db_calls']++;
        throw new RuntimeException('R62: invalid manifest attempted a DB operation: '.$method);
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
echo "R62 Book Pack canonical identity: $checks negative regression checks PASS.\n";
