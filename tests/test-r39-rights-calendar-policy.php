<?php
/** R39 exercises actual PLDR_Access and PLDR_Core with calendar/legacy DB fixture. */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $metadata=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function absint($v):int{return abs((int)$v);}
function get_option($name,$default=false){return $name==='spf_founder_user_id'?77:$default;}
function user_can($uid,$cap):bool{return false;}
function apply_filters($filter,$default,...$args){return $default;}
function sanitize_text_field($s):string{return trim(strip_tags((string)$s));}
function sanitize_key($s):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$s));}
function current_time($format,$utc=false):string{return '2026-10-11 06:00:00';}
function wp_generate_uuid4():string{return '44a7c050-4681-465e-9bf4-0d9828a280a9';}
function wp_json_encode($v,...$args){return json_encode($v);}
class R39_DB {
    public string $prefix='wp_';
    public string $last_error='';
    public int $reads=0;
    public int $queries=0;
    public array $writes=array();
    public array $policy=array();
    public array $edition=array();
    public function __construct(){
        $this->policy=array('document_id'=>5,'version'=>2,'audience'=>'public','entitlement_key'=>'',
          'download_allowed'=>1,'print_allowed'=>1,'offline_allowed'=>1,'embargo_until'=>null);
        $this->edition=array('id'=>42,'status'=>'published','document_id'=>5,'document_status'=>'published',
          'rights_expires_at'=>null);
    }
    public function prepare(string $sql,...$params):string{return $sql;}
    public function get_row(string $sql,$mode=null):?array{
        $this->reads++;
        if(str_contains($sql,'pldr_editions'))return $this->edition;
        if(str_contains($sql,'pldr_access_policies'))return $this->policy;
        if(str_contains($sql,'pldr_documents'))return array(
           'id'=>5,'public_id'=>'calendar-r39','version'=>3,'title'=>'PDF','status'=>'published');
        return null;
    }
    public function insert(string $table,array $row,$format=null):int{$this->writes[$table][]=$row;return 1;}
    public function query(string $sql):int{$this->queries++;return 1;}
}
$GLOBALS['wpdb']=new R39_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
$checks=0;
$check=static function(bool $ok,string $description)use(&$checks):void{
    if(!$ok)throw new RuntimeException('R39 actual rights: '.$description);
    $checks++;
};
$bad=array(
  '2026-02-31','2026-04-31','2026-02-29','2026-00-01',
  '2026-03-31 24:00:00','2026-03-31 12:60:00','2026-03-31T10:30:61Z',
  '2026-12-01T12:00:00+15:00','2026-11-01T12:00:00+14:30',
  '2026-10-11T10:00:00+05:99','2026-10-11T10:00:00-16:00'
);
foreach($bad as $value){
    $db=$GLOBALS['wpdb'];
    $before=$db->reads+$db->queries;
    $answer=PLDR_Access::update_policy(5,array('embargo_until'=>$value),77,2);
    $check(is_wp_error($answer)&&$answer->code==='pldr_policy_embargo',
        'invalid calendar date or timezone accepted: '.$value);
    $check($before<$db->reads+$db->queries,
        'actual policy read/expected version was bypassed unexpectedly'); // guard executes after authorization/read
    $check(!array_key_exists('wp_pldr_access_policies',$db->writes),
        'invalid calendar date written: '.$value);
}
$valid=array(
  array('2028-02-29','2028-02-29 00:00:00'),
  array('2026-02-28','2026-02-28 00:00:00'),
  array('2026-10-11T14:30:00+05:00','2026-10-11 09:30:00'),
  array('2026-10-11T12:30:00Z','2026-10-11 12:30:00'),
  array('2026-10-11 14:30:00','2026-10-11 14:30:00')
);
// WordPress PHP default timezone must not change date-only/naive embargo UTC normalization.
date_default_timezone_set('Asia/Karachi');
foreach($valid as [$input,$expected]){
    $answer=PLDR_Access::update_policy(5,array('embargo_until'=>$input),77,2);
    $check(is_array($answer),'valid real date rejected: '.$input);
    $rows=$GLOBALS['wpdb']->writes['wp_pldr_access_policies']??array();
    $row=end($rows);
    $check(($row['embargo_until']??'')===$expected,
       'explicit/implicit timezone normalized incorrectly: '.$input);
}
// The same calendar law must govern legacy persisted rights at READ time.
// A wrongly parsed February 31 should NEVER grant access to a non-curator.
foreach($bad as $value){
    $db=$GLOBALS['wpdb'];$db->policy['embargo_until']=$value;$db->edition['rights_expires_at']=null;
    $check(PLDR_Access::can_access_edition(42,'read',13)===false,
       'invalid legacy embargo allowed an ordinary reader: '.$value);
    $db->policy['embargo_until']=null;$db->edition['rights_expires_at']=$value;
    $check(PLDR_Access::can_access_edition(42,'read',13)===false,
       'invalid legacy rights-expiry allowed an ordinary reader: '.$value);
}
$GLOBALS['wpdb']->edition['rights_expires_at']=null;
$GLOBALS['wpdb']->policy['embargo_until']=null;
$check(PLDR_Access::can_access_edition(42,'read',13)===true,
    'valid public document was blocked after strict date hardening');
$GLOBALS['wpdb']->policy['download_allowed']='true';
$GLOBALS['wpdb']->policy['print_allowed']='on';
$GLOBALS['wpdb']->policy['offline_allowed']='yes';
$check(PLDR_Access::can_access_edition(42,'download',13)===true,
    'legacy positive download flag rejected before policy update');
$answer=PLDR_Access::update_policy(5,array('audience'=>'account'),77,2);
$check(is_array($answer),'valid audience-only rights update failed');
$rows=$GLOBALS['wpdb']->writes['wp_pldr_access_policies']??array();$row=end($rows);
$check($row['download_allowed']===1 && $row['print_allowed']===1 && $row['offline_allowed']===1,
    'audience-only update silently removed historical true/on/yes permission flags');
// Malformed legacy flags must NEVER become true during an unrelated policy update.
$GLOBALS['wpdb']->policy['download_allowed']='nonsense';
$GLOBALS['wpdb']->policy['print_allowed']='off';
$GLOBALS['wpdb']->policy['offline_allowed']='false';
$answer=PLDR_Access::update_policy(5,array('audience'=>'account'),77,2);
$rows=$GLOBALS['wpdb']->writes['wp_pldr_access_policies']??array();$row=end($rows);
$check(is_array($answer)&&$row['download_allowed']===0&&$row['print_allowed']===0&&$row['offline_allowed']===0,
   'invalid/legacy false flags escalated on audience-only policy update');
echo "PASS R39: {$checks} actual rights policy/calendar, stored-rights expiry, timezone and no-privilege-drift assertions\n";
