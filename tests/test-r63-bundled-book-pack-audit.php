<?php
/** R63 RED-first bundled Book Pack import: malformed, rejected and symlink files must not disappear silently. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
define('ARRAY_A','ARRAY_A');
$root=sys_get_temp_dir().'/pldr-r63-'.bin2hex(random_bytes(7));
mkdir($root.'/book-packs',0700,true);
define('PLDR_DIR',$root.'/');
class WP_Error {
    private string $code;
    public function __construct($code,$message='',$details=array()){$this->code=(string)$code;}
    public function get_error_code():string{return $this->code;}
}
function get_current_user_id():int{return 0;}
function sanitize_key($x):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$x));}
function sanitize_text_field($x):string{return trim(strip_tags((string)$x));}
function sanitize_textarea_field($x):string{return trim(strip_tags((string)$x));}
function absint($x):int{return abs((int)$x);}
function wp_json_encode($x,$flags=0){return json_encode($x,$flags);}
function is_wp_error($x):bool{return $x instanceof WP_Error;}
class PLDR_Core {
    public static function table($name):string{return 'wp_pldr_'.$name;}
    public static function machine_error($code,$msg,$status=400,$details=array()){return new WP_Error($code,$msg);}
    public static function sanitize_json_list($x):array{return is_array($x)?$x:array();}
    public static function audit($scope,$id,$event,$detail=array(),$actor=0):void{
        $GLOBALS['audit'][]=array('scope'=>$scope,'event'=>$event,'detail'=>$detail);
    }
}
$GLOBALS['audit']=array();$GLOBALS['db_calls']=0;
$wpdb=new class {
    public string $last_error='';
    public function __call($name,$args){$GLOBALS['db_calls']++;throw new RuntimeException('R63 unapproved pack reached DB '.$name);}
};
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rights.php';
$invalid=$root.'/book-packs/broken.json';
file_put_contents($invalid,'{broken-private-source');
$refused=$root.'/book-packs/refused.json';
$pack=array('pack_key'=>'!!!','version'=>'1.0.0','title'=>'Title','author'=>'Author','language'=>'en-US',
   'rights'=>'public-domain','checksum'=>str_repeat('b',64),'update_manifest'=>'1.0.0');
file_put_contents($refused,json_encode($pack));
$external=$root.'/outside-original.json';
$private_marker='SENSITIVE_NEVER_LOG';
$pack['provenance']=$private_marker;
file_put_contents($external,json_encode($pack));
$symlink=$root.'/book-packs/linked.json';
if(!symlink($external,$symlink)){fwrite(STDERR,"R63 setup failure: symlink unavailable\n");exit(1);}
PLDR_Book_Packs::scan_bundled_manifests();
$events=array_column($GLOBALS['audit'],'event');
$checks=0;
foreach(array('bundled_manifest_invalid_json','bundled_manifest_registration_rejected','bundled_manifest_symlink_rejected') as $expect){
    $checks++;
    if(!in_array($expect,$events,true)){fwrite(STDERR,"R63 FAIL: no safe audit for $expect\n");exit(1);}
}
$checks++;
if($GLOBALS['db_calls']!==0){fwrite(STDERR,"R63 FAIL: rejected/linked manifest reached DB\n");exit(1);}
$checks++;
if(str_contains(json_encode($GLOBALS['audit']),$private_marker)||str_contains(json_encode($GLOBALS['audit']),'{broken-private-source')){
    fwrite(STDERR,"R63 FAIL: private source contents appeared in audit\n");exit(1);
}
foreach(array($invalid,$refused,$symlink,$external) as $file)unlink($file);
rmdir($root.'/book-packs');rmdir($root);
echo "R63 Book Pack source-bundle safety and audit: $checks red-first cases PASS.\n";
