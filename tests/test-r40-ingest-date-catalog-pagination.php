<?php
/** R40: real PDF ingest date normalization and catalog numeric boundaries. */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function get_option($key,$default=false){return $key==='spf_founder_user_id'?77:$default;}
function user_can($user,$cap):bool{return false;}
function absint($v):int{return abs((int)$v);}
function apply_filters($name,$default,...$args){return $default;}
function sanitize_key($s):string{return strtolower(preg_replace('/[^a-z0-9_-]/i','',(string)$s));}
function sanitize_text_field($v):string{return trim(strip_tags((string)$v));}
function sanitize_textarea_field($v):string{return sanitize_text_field($v);}
function wp_json_encode($v,...$args){return json_encode($v);}
function current_time($f,$utc=false):string{return '2026-10-11 08:00:00';}
function wp_generate_uuid4():string{return 'f01b24ef-4a03-47b4-bc21-477400000001';}
final class R40_DB {
    public string $prefix='wp_';
    public string $last_error='';
    public int $reads=0;
    public int $writes=0;
    public function prepare(string $sql,...$params):string{return $sql;}
    public function esc_like(string $v):string{return $v;}
    public function get_results(string $sql,$format=null):array{$this->reads++;return array();}
    public function get_row(string $sql,$format=null):?array{$this->reads++;return null;}
    public function get_var(string $sql){$this->reads++;return null;}
    public function insert(string $table,array $row,$format=null):int{$this->writes++;return 1;}
    public function query(string $sql):int{$this->writes++;return 1;}
}
$GLOBALS['wpdb']=new R40_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-core.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-access.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php';
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
$checks=0;
$check=static function(bool $result,string $desc)use(&$checks){
    if(!$result)throw new RuntimeException('R40 ingest/catalog: '.$desc);
    $checks++;
};
$data=array('title'=>'Research book','document_type'=>'book','category'=>'research',
    'language'=>'en','author_name'=>'Author','source_name'=>'Publisher','rights_basis'=>'owned','access_mode'=>'public');
$bad_dates=array('2026-02-31','2026-04-31','2026-02-29','2026-13-01',
  '2028-02-30','2026-10-11T24:00:00Z','2026-10-11T19:60:00Z',
  '2026-10-11T12:30:60Z','2026-10-11T12:30:00+15:00',
  '2026-10-11T12:30:00+14:01','2026-10-11T12:30:00-19:00',
  'next Thursday','2026-10-11T12:30:00+05:99');
date_default_timezone_set('Asia/Karachi');
foreach(array('rights_expires_at','embargo_until') as $field){
    foreach($bad_dates as $date){
        $case=$data;$case[$field]=$date;
        $start=$GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes;
        $result=PLDR_Ingest::ingest($case,array(),77);
        $check(is_wp_error($result)&&$result->code==='pldr_ingest_date',
          'invalid '.$field.' was normalized: '.$date);
        $check($GLOBALS['wpdb']->reads+$GLOBALS['wpdb']->writes===$start,
          'invalid '.$field.' reached DB before validation');
    }
}
foreach(array(
  '2028-02-29',
  '2099-02-28T12:00:00Z',
  '2099-02-28T20:30:00+05:00',
  '2099-02-28 12:00:00'
) as $valid){
    foreach(array('rights_expires_at','embargo_until') as $field){
        $case=$data;$case[$field]=$valid;
        $result=PLDR_Ingest::ingest($case,array(),77);
        $check(is_wp_error($result),'valid '.$field.' skipped existing PDF upload gate: '.$valid);
        $check(in_array($result->code,array('pldr_pdf_missing','pldr_rights_expired_ingest'),true),
          'valid '.$field.' failed at calendar parser: '.$valid.' code='.$result->code);
    }
}
foreach(array('2099-02-28T20:30:00+05:00'=>'2099-02-28 15:30:00',
              '2099-02-28 12:00:00'=>'2099-02-28 12:00:00') as $date=>$expected){
    $ts=PLDR_Access::strict_rights_timestamp($date);
    $check(is_int($ts)&&gmdate('Y-m-d H:i:s',$ts)===$expected,
      'ingest/access shared parser lost UTC date correctness: '.$date);
}
$invalid_pages=array(-3,0,2.5,'-3','0','2.5','1e2','x3',
   '999999999999999999999999999999',false,true);
foreach(array('page','per_page') as $field){
    foreach($invalid_pages as $value){
        $params=array('q'=>'','page'=>1,'per_page'=>24);$params[$field]=$value;
        $before=$GLOBALS['wpdb']->reads;
        $result=PLDR_Search::search($params,77);
        $expected=is_bool($value)?'pldr_catalog_filter_invalid':'pldr_catalog_pagination_invalid';
        $check(is_array($result)&&is_wp_error($result['error']??null)
            &&$result['error']->code===$expected,
            'malformed catalog '.$field.' silently normalized: '.var_export($value,true));
        $check($GLOBALS['wpdb']->reads===$before,
            'malformed catalog '.$field.' accessed DB');
    }
}
foreach(array(1,'1',2,'24',48) as $number){
    foreach(array('page','per_page') as $field){
        $params=array('page'=>1,'per_page'=>24);$params[$field]=$number;
        $result=PLDR_Search::search($params,77);
        $check(is_array($result)&&!isset($result['error']),
            'valid catalog pagination '.$field.' rejected');
    }
}
// The post-upload pending-edition policy gate cannot be exercised with a fake
// non-HTTP upload, so verify the exact source semantics separately instead
// of falsely claiming an actual pending-edition integration test.
$source=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php');
$check(str_contains($source,"array_key_exists(\$flag,\$data)?(int)\$data[\$flag]:\$old_grant"),
    'omitted pending edition grant no longer inherits canonical current grant');
$check(str_contains($source,"array_key_exists('embargo_until',\$data)"),
    'omitted pending edition embargo no longer inherits current embargo');
echo "PASS R40: {$checks} actual ingest date/calendar, catalog numeric and 2 pending-source invariants\n";
