<?php
/**
 * R34: Run ACTUAL Core DTO, Access::can_access_edition and issue_token
 * methods against a faithful fake WordPress DB authorization fixture.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
defined('HOUR_IN_SECONDS') || define('HOUR_IN_SECONDS',3600);
final class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($x):bool{return $x instanceof WP_Error;}
function sanitize_key($x):string{return trim(strtolower((string)$x));}
function sanitize_text_field($x):string{return trim(strip_tags((string)$x));}
function get_current_user_id():int{return 55;}
function get_option($k,$default=false){return $default;}
function user_can($id,$cap):bool{return false;}
function absint($x):int{return abs((int)$x);}
function apply_filters($tag,$value,...$args){return $value;}
function current_time($format,$gmt=false):string{return gmdate('Y-m-d H:i:s');}
function wp_json_encode($v):string{return json_encode($v,JSON_UNESCAPED_SLASHES);}
function wp_salt($x='auth'):string{return 'test-r34-authentication-salt';}
function get_transient($k){return 0;}
function set_transient($k,$value,$ttl):bool{return true;}
function home_url($path=''):string{return 'https://example.test'.$path;}
function sanitize_file_name($name):string{return basename((string)$name);}
final class PLDR_R34_DB {
    public string $prefix='wp_';
    public string $last_error='';
    public int $insert_id=1;
    public array $stored_tokens=array();
    public array $audit=array();
    public static array $policy=array(
        'audience'=>'public','download_allowed'=>'0','print_allowed'=>'0','offline_allowed'=>'0',
        'embargo_until'=>null,'entitlement_key'=>''
    );
    public function prepare($sql,...$args):string{return $sql;}
    public function get_row($sql,$format=null):?array {
        if(str_contains($sql,'pldr_editions'))return array(
          'id'=>11,'document_id'=>7,'object_id'=>3,'status'=>'published','document_status'=>'published',
          'rights_expires_at'=>null
        );
        if(str_contains($sql,'pldr_access_policies'))return self::$policy;
        if(str_contains($sql,'pldr_objects'))return array(
          'id'=>3,'object_status'=>'available','byte_size'=>200,'sha256'=>str_repeat('a',64),
          'original_name'=>'book.pdf','mime_type'=>'application/pdf'
        );
        return null;
    }
    public function get_var($sql){return 1;}
    public function insert($table,$data,$format=null) {
        if(str_contains($table,'pldr_access_tokens'))$this->stored_tokens[]=$data;
        if(str_contains($table,'pldr_audit'))$this->audit[]=$data;
        return 1;
    }
    public function query($sql){return 1;}
}
$GLOBALS['wpdb']=new PLDR_R34_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
$checks=0;
$assert=static function(bool $condition,string $message)use(&$checks):void {
    if(!$condition)throw new RuntimeException("R34 rights regression: ".$message);
    $checks++;
};
$doc=array('id'=>7,'public_id'=>'public-7','title'=>'Test PDF','slug'=>'book','document_type'=>'book',
    'category'=>'research','language'=>'en','subjects_json'=>'[]','collections_json'=>'[]','status'=>'published','access_mode'=>'public');
$edition=array('id'=>11,'edition_label'=>'1','isbn'=>'000','publication_year'=>2026,'pages'=>3,
    'author_name'=>'Researcher','translator'=>'','publisher'=>'Publisher','source_name'=>'Library',
    'license_code'=>'CC BY','sha256'=>'abc','version'=>1);
foreach(array(
    '0'=>false,'false'=>false,'off'=>false,'no'=>false,'garbage'=>false,
    '1'=>true,'true'=>true,'on'=>true,'yes'=>true
) as $raw=>$expected){
    foreach(array('download'=>'download_allowed','print'=>'print_allowed','offline'=>'offline_allowed') as $operation=>$column){
        PLDR_R34_DB::$policy=array_replace(PLDR_R34_DB::$policy,array('download_allowed'=>'0','print_allowed'=>'0','offline_allowed'=>'0'));
        PLDR_R34_DB::$policy[$column]=$raw;
        $allowed=PLDR_Access::can_access_edition(11,$operation,-1);
        $assert($allowed===$expected,$operation.' persisted string '.$raw.' evaluated incorrectly');
        $dto=PLDR_Core::public_document_dto($doc,$edition,PLDR_R34_DB::$policy);
        $assert($dto['permissions'][$operation]===$expected,$operation.' public DTO disagrees for '.$raw);
    }
}
foreach(array('unknown','reader-private','','bulk-export','Delete') as $invalid) {
    $assert(!PLDR_Access::can_access_edition(11,$invalid,-1),'unknown operation was authorized: '.$invalid);
}
PLDR_R34_DB::$policy=array_replace(PLDR_R34_DB::$policy,array('download_allowed'=>'0','print_allowed'=>'0','offline_allowed'=>'0'));
$assert(PLDR_Access::can_access_edition(11,'read',-1),'legitimate anonymous reader denied');
$anonymous=PLDR_Access::issue_token(11,3,'read',-1,600);
$assert(is_array($anonymous)&&isset($anonymous['token']),'explicit anonymous grant not issued');
$entry=end($GLOBALS['wpdb']->stored_tokens);
$assert((int)$entry['user_id']===0,'anonymous token should store public audience user_id zero');
$hash=hash('sha256','public|11|read');
$assert(hash_equals($hash,(string)$entry['audience_hash']),'anonymous issue audience differs from delivery recalculation');
$user=PLDR_Access::issue_token(11,3,'read',55,600);
$assert(is_array($user)&&isset($user['token']),'legitimate authenticated reader denied');
$entry=end($GLOBALS['wpdb']->stored_tokens);
$assert((int)$entry['user_id']===55,'authenticated grant identity was lost');
$assert(hash_equals(hash('sha256','user:55|11|read'),(string)$entry['audience_hash']),'authenticated audience hash mismatch');
echo "PASS R34: {$checks} actual persisted-rights / public DTO / operation / anonymous-token assertions\n";
