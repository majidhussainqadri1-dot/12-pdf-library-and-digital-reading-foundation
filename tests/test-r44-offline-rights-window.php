<?php
/**
 * R44: real File12 strict calendar parser + actual Future-24 reauthorization
 * handler and grant-TTL helper. Isolated WordPress fixture, NOT staging proof.
 */
declare(strict_types=1);
require __DIR__.'/test-r39-rights-calendar-policy.php';
class WP_REST_Request implements ArrayAccess {
    public function __construct(private array $input=array()){}
    public function offsetExists(mixed $key):bool{return array_key_exists($key,$this->input);}
    public function offsetGet(mixed $key):mixed{return $this->input[$key]??null;}
    public function offsetSet(mixed $key,mixed $value):void{$this->input[$key]=$value;}
    public function offsetUnset(mixed $key):void{unset($this->input[$key]);}
    public function get_json_params():array{return $this->input;}
    public function get_params():array{return $this->input;}
    public function get_header($key):string{return '';}
}
function rest_ensure_response($value){return $value;}
final class PLDR_Future_Data {
    public static array $fixture=array();
    public static int $calls=0;
    public static function require_edition(int $edition_id,string $operation='read') {
        self::$calls++;
        if($edition_id!==42||$operation!=='offline')return new WP_Error('fixture_unknown');
        return self::$fixture;
    }
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-rest.php';
$checks=0;
$check=static function(bool $condition,string $msg)use(&$checks):void{
    if(!$condition)throw new RuntimeException('R44 offline rights: '.$msg);
    $checks++;
};
$rights=new ReflectionMethod(PLDR_Future_REST::class,'offline_rights_expiry');
$ttl=new ReflectionMethod(PLDR_Future_REST::class,'offline_delivery_token_ttl');
date_default_timezone_set('Asia/Karachi');
$bad=array('2026-02-31','2026-04-31','2026-02-29',
    '2026-10-11 24:00:00','2026-11-01T20:61:00Z',
    '2026-10-11T18:30:00+15:00','next Thursday','0',true,array('year'=>2026));
foreach($bad as $v) {
    PLDR_Future_Data::$fixture=array('id'=>42,'object_id'=>9,'rights_expires_at'=>$v);
    $direct=$rights->invoke(null,PLDR_Future_Data::$fixture);
    $check(is_wp_error($direct)&&$direct->code==='pldr_offline_rights_state',
        'actual canonical calendar accepted invalid '.get_debug_type($v));
    $result=PLDR_Future_REST::offline_authorization(new WP_REST_Request(array('edition'=>42)));
    $check(is_wp_error($result)&&$result->code==='pldr_offline_rights_state',
        'offline reconnect authorized malformed historical rights expiry');
}
$expired=gmdate('Y-m-d H:i:s',time()-3600);
PLDR_Future_Data::$fixture=array('id'=>42,'object_id'=>9,'rights_expires_at'=>$expired);
$answer=PLDR_Future_REST::offline_authorization(new WP_REST_Request(array('edition'=>42)));
$check(is_wp_error($answer)&&$answer->code==='pldr_offline_rights_expired',
    'expired offline rights survived reauthorization');
$check(is_wp_error($rights->invoke(null,PLDR_Future_Data::$fixture)),
    'expired offline grant rights parser allowed expired edition');
$future=gmdate('Y-m-d H:i:s',time()+7200);
PLDR_Future_Data::$fixture=array('id'=>42,'object_id'=>9,'rights_expires_at'=>$future);
$answer=PLDR_Future_REST::offline_authorization(new WP_REST_Request(array('edition'=>42)));
$check(is_array($answer)&&($answer['authorized']??false)===true,
    'valid UTC future calendar rights rejected');
$check(($answer['rights_valid_until']??null)===gmdate('c',(int)strtotime($future.' UTC')),
    'expiry projection differs from canonical UTC date under Asia/Karachi timezone');
PLDR_Future_Data::$fixture=array('id'=>42,'object_id'=>9,'rights_expires_at'=>null);
$answer=PLDR_Future_REST::offline_authorization(new WP_REST_Request(array('edition'=>42)));
$check(is_array($answer)&&($answer['rights_valid_until']??false)===null,
    'lawfully unbounded rights incorrectly given an expiry');
foreach(array(-1,0,10,60,90,119,120) as $seconds){
    $answer=$ttl->invoke(null,time()+$seconds);
    $check(is_wp_error($answer)&&$answer->code==='pldr_offline_rights_window',
        'unsafe minimum-60s grant issued with '.$seconds.' remaining seconds');
}
foreach(array(180,360,901,3600,86400) as $seconds){
    $expires=time()+$seconds;
    $issued=$ttl->invoke(null,$expires);
    $check(is_int($issued)&&$issued>=60&&$issued<=900,
        'safe future token TTL outside File12 signed delivery range');
    $check($issued+60<=$expires-time(),
        'token expiry could exceed rights expiry despite 60s safety margin');
}
$source=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-rest.php');
$check(str_contains($source,'$rights=self::offline_rights_expiry($edition);'),
    'both Future offline handlers must use canonical stored rights parser');
$check(str_contains($source,'$grant_ttl=self::offline_delivery_token_ttl($valid);'),
    'actual offline grant bypasses safe token TTL preflight');
$check(!str_contains($source,"strtotime((string)\$edition['rights_expires_at'])"),
    'unsafe lenient rights timestamp still in Future offline handler');
echo "PASS R44: {$checks} strict historical rights/reauthorization/token-lifetime assertions\n";
