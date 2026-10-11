<?php
/**
 * R32 real-implementation rights/consent flag safety fixture.
 * Executes PLDR_Core::policy_flag, PLDR_Ingest::ingest and
 * PLDR_Access::update_policy against an isolated WordPress DB stub.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public $data=array()) {}
    public function get_error_code(): string { return $this->code; }
}
function is_wp_error($value):bool {return $value instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function get_option($name,$default=false){return $name==='spf_founder_user_id'?77:$default;}
function absint($v):int{return abs((int)$v);}
function apply_filters($name,$default,...$args){return $default;}
function user_can($uid,$cap):bool{return false;}
function sanitize_text_field($v):string{return trim(strip_tags((string)$v));}
function wp_json_encode($v,...$args){return json_encode($v);}
function wp_generate_uuid4():string{return 'c1f9a73b-2244-4aa9-9e10-150e477253ff';}
function current_time($format,$gmt=false):string{return '2026-10-11 00:00:00';}
final class PLDR_R32_DB {
    public string $prefix='wp_';
    public string $last_error='';
    public array $writes=array();
    public int $reads=0;
    public int $queries=0;
    public function prepare(string $sql,...$args):string{return $sql;}
    public function get_row(string $sql,$output=ARRAY_A):?array {
        $this->reads++;
        if(str_contains($sql,'pldr_documents'))return array('id'=>5,'public_id'=>'r32-doc','version'=>3,'title'=>'PDF','status'=>'published');
        if(str_contains($sql,'pldr_access_policies'))return array('document_id'=>5,'version'=>2,'audience'=>'public','entitlement_key'=>'','download_allowed'=>0,'print_allowed'=>0,'offline_allowed'=>0,'embargo_until'=>null);
        return null;
    }
    public function insert(string $table,array $data,$formats=null):int { $this->writes[$table][]=$data;return 1; }
    public function query(string $sql):int {$this->queries++;return 1;}
}
$GLOBALS['wpdb']=new PLDR_R32_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php';

$tests=0;
$check=static function(bool $okay,string $why)use(&$tests):void {
    if(!$okay)throw new RuntimeException('R32 flag parser regression: '.$why);
    $tests++;
};
foreach(array(true,1,'1','true','TRUE','yes','on',' On ') as $v){
    $check(PLDR_Core::policy_flag($v)===1,'true value '.var_export($v,true));
}
foreach(array(false,0,'0','false','FALSE','no','off',' Off ','') as $v){
    $check(PLDR_Core::policy_flag($v)===0,'false value '.var_export($v,true));
}
foreach(array('enabled','disabled','truthy','2',2,-1,0.5,null,array('true'),(object)array('value'=>'on')) as $v){
    $check(PLDR_Core::policy_flag($v)===null,'unexpected value '.get_debug_type($v));
}
$good=array('title'=>'Research PDF','document_type'=>'book','category'=>'research','language'=>'en','author_name'=>'Author','source_name'=>'Publisher','rights_basis'=>'owned','access_mode'=>'public');
foreach(array('download_allowed','print_allowed','offline_allowed','patient_case_consent','confirm_distinct_scan') as $name){
    foreach(array('enabled',2,'2') as $v){
        $data=$good;$data[$name]=$v;
        $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->queries;
        $result=PLDR_Ingest::ingest($data,array(),77);
        $check(is_wp_error($result)&&$result->code==='pldr_ingest_policy_flag','ingest should reject invalid '.$name);
        $check($before===$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->queries,'invalid ingest touched DB for '.$name);
    }
}
foreach(array('download_allowed','print_allowed','offline_allowed') as $name){
    foreach(array('enabled',2,'2',array('on')) as $v){
        $before=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->queries;
        $result=PLDR_Access::update_policy(5,array($name=>$v),77,2);
        $check(is_wp_error($result)&&$result->code==='pldr_policy_flag','policy update should reject invalid '.$name);
        $check($before===$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->queries,'invalid policy update touched DB for '.$name);
    }
}
// Real policy update persists the canonical 0 for previously dangerous text
// "false" and "off", but still accepts truly enabled access flags.
$ok=PLDR_Access::update_policy(5,array('download_allowed'=>'false','print_allowed'=>'off','offline_allowed'=>'no'),77,2);
$check(is_array($ok),'legitimate disabled flags rejected by policy update');
$rows=$GLOBALS['wpdb']->writes['wp_pldr_access_policies']??array();
$last=end($rows);
$check(is_array($last)&&$last['download_allowed']===0&&$last['print_allowed']===0&&$last['offline_allowed']===0,'text false/off/no persisted as enabled');
$ok=PLDR_Access::update_policy(5,array('download_allowed'=>'true','print_allowed'=>'on','offline_allowed'=>'yes'),77,2);
$check(is_array($ok),'legitimate enabled flags rejected by policy update');
$rows=$GLOBALS['wpdb']->writes['wp_pldr_access_policies']??array();$last=end($rows);
$check(is_array($last)&&$last['download_allowed']===1&&$last['print_allowed']===1&&$last['offline_allowed']===1,'explicit true/on/yes not persisted as enabled');
// Patient-case consent text "false" must not stand in for actual consent.
$data=$good;$data['category']='patient-cases';$data['patient_case_consent']='false';
$result=PLDR_Ingest::ingest($data,array(),77);
$check(is_wp_error($result)&&$result->code==='pldr_patient_case_consent','false consent text passed the consent gate');
// Ordinary metadata with a disabled flat policy flag still reaches PDF upload
// gate instead of failing before the pre-existing verified upload path.
$data=$good;$data['offline_allowed']='false';
$result=PLDR_Ingest::ingest($data,array(),77);
$check(is_wp_error($result)&&$result->code==='pldr_pdf_missing','valid disabled policy flag should reach verified upload gate');
echo "PASS R32: {$tests} real Core/Ingest/Access policy flag assertions, including false-string privilege denial and DB write checks\n";
