<?php
/**
 * R37 executable persisted-rights, actual REST reader-manifest and native
 * private-bookmark deletion assertions. WordPress stubs isolate DB behavior.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()) {}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function sanitize_key($v):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$v));}
function sanitize_text_field($v):string{return trim(strip_tags((string)$v));}
function absint($v):int{return abs((int)$v);}
function get_current_user_id():int{return 9;}
function rest_ensure_response($x){return $x;}
function current_time($format,$utc=false):string{return '2026-10-11 00:00:00';}
function wp_json_encode($v,...$args){return json_encode($v);}
function wp_generate_uuid4():string{return '01234567-89ab-4cde-89ab-0123456789ab';}
function get_option($name,$default=false){return $default;}
function user_can($uid,$cap):bool{return false;}
function apply_filters($name,$default,...$args){return $default;}
class WP_REST_Request implements ArrayAccess {
    public function __construct(private array $params=array()){}
    public function offsetExists(mixed $offset):bool{return array_key_exists($offset,$this->params);}
    public function offsetGet(mixed $offset):mixed{return $this->params[$offset]??null;}
    public function offsetSet(mixed $offset,mixed $value):void{$this->params[$offset]=$value;}
    public function offsetUnset(mixed $offset):void{unset($this->params[$offset]);}
}
final class PLDR_R37_DB {
    public string $prefix='wp_';
    public string $last_error='';
    public int|false $deletion_result=0;
    public array $delete_calls=array();
    public array $policy=array('audience'=>'public','entitlement_key'=>'','download_allowed'=>'0','print_allowed'=>'0','offline_allowed'=>'0','embargo_until'=>null);
    public function prepare($sql,...$args):string{return $sql;}
    public function get_row($sql,$format=null):?array{
        if(str_contains($sql,'pldr_editions'))return array(
           'id'=>42,'document_id'=>7,'object_id'=>3,'title'=>'Allowed PDF',
           'pages'=>5,'status'=>'published','document_status'=>'published',
           'version'=>2,'author_name'=>'Author','language'=>'en','rights_expires_at'=>null
        );
        if(str_contains($sql,'pldr_objects'))return array(
           'id'=>3,'object_status'=>'available','byte_size'=>900,
           'original_name'=>'private.pdf','mime_type'=>'application/pdf','sha256'=>str_repeat('a',64)
        );
        if(str_contains($sql,'pldr_access_policies'))return $this->policy;
        return null;
    }
    public function get_var($sql){return 0;}
    public function get_results($sql,$fmt=null):array{return array();}
    public function delete($table,array $where,array $format=array()) {
        $this->delete_calls[]=array('table'=>$table,'where'=>$where);
        return $this->deletion_result;
    }
}
$GLOBALS['wpdb']=new PLDR_R37_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
class PLDR_Access {
    public static function can_access_edition(int $edition_id,string $op='read',int $user_id=0):bool{
        return $edition_id===42 && in_array($op,array('read','preview'),true) && ($user_id===9||$user_id===0);
    }
    public static function issue_token(int $edition,int $object,string $operation,int $user=0,int $ttl=600):array{
        return array('url'=>'https://example.test/library/delivery/test/',
            'token'=>'safe-r37-test-token','expires_at'=>'2026-10-11 08:00:00');
    }
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rest.php';
$checked=0;
$assert=static function(bool $good,string $why)use(&$checked):void {
    if(!$good)throw new RuntimeException('R37 regression failed: '.$why);
    $checked++;
};
$db=$GLOBALS['wpdb'];
$manifest_request=new WP_REST_Request(array('edition'=>42));
$policy_cases=array(
  '0'=>false,'false'=>false,'FALSE'=>false,'off'=>false,'no'=>false,
  'unknown'=>false,'1'=>true,'true'=>true,'TRUE'=>true,'on'=>true,'yes'=>true
);
foreach($policy_cases as $raw=>$expected){
    foreach(array('download'=>'download_allowed','print'=>'print_allowed','offline'=>'offline_allowed') as $operation=>$field){
        $db->policy=array_replace($db->policy,array('download_allowed'=>'0','print_allowed'=>'0','offline_allowed'=>'0'));
        $db->policy[$field]=$raw;
        $result=PLDR_REST::reader_manifest($manifest_request);
        $assert(is_array($result),'actual REST reader manifest rejected legitimate read');
        $assert(($result['permissions'][$operation]??null)===$expected,
          'REST reader manifest incorrectly projected '.$operation.' from '.var_export($raw,true));
        foreach(array('download','print','offline') as $other){
            if($other!==$operation)$assert(($result['permissions'][$other]??null)===false,'unrelated permission unexpectedly enabled');
        }
    }
}
foreach(array('download'=>'download_allowed','print'=>'print_allowed','offline'=>'offline_allowed') as $op=>$field){
    $db->policy[$field]=null;
    $result=PLDR_REST::reader_manifest($manifest_request);
    $assert(($result['permissions'][$op]??null)===false,'null policy granted '.$op);
    $db->policy[$field]=array('malformed');
    $result=PLDR_REST::reader_manifest($manifest_request);
    $assert(($result['permissions'][$op]??null)===false,'nested policy granted '.$op);
    $db->policy[$field]='0';
}
// Native private erasure must be truthful; direct helper is not the
// separate optimistic-lock REST deletion endpoint.
foreach(array(0=>false,1=>true) as $affected=>$expected){
    $db->deletion_result=$affected;
    $assert(PLDR_Reading::delete_item(11,9)===$expected,'native delete '.$affected.' rows returned wrong success');
}
$db->deletion_result=false;
$assert(PLDR_Reading::delete_item(11,9)===false,'DB delete error returned success');
$before=count($db->delete_calls);
foreach(array(array(0,9),array(-1,9),array(11,-1),array(11,-200)) as [$item,$uid]){
    $assert(PLDR_Reading::delete_item($item,$uid)===false,'invalid item/owner delete succeeded');
    $assert(count($db->delete_calls)===$before,'invalid item/owner delete touched DB');
}
$last=end($db->delete_calls);
$assert(($last['where']['id']??null)===11&&($last['where']['user_id']??null)===9,'delete not scoped to item and user');
// For JS reader boot configuration, assert the exact source invariant.
// This is a SOURCE check, not a live browser render assertion.
$reader_source=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php');
foreach(array('canDownload'=>'download_allowed','canPrint'=>'print_allowed','canOffline'=>'offline_allowed') as $config=>$flag){
    $expected="'".$config."'=>1===PLDR_Core::policy_flag(\$policy['".$flag."']??null)";
    $assert(str_contains($reader_source,$expected),'UI runtime config not bound to canonical binary '.$config);
}
echo "PASS R37: {$checked} real reader-manifest and private deletion assertions plus three source UI invariants\n";
