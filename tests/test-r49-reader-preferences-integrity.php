<?php
/**
 * R49 actual PLDR_Future_Preferences::save/get validation.
 * Private user-scoped isolated WordPress DB fixture, not deployed proof.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function sanitize_text_field($x):string{return trim(strip_tags((string)$x));}
function wp_json_encode($x,...$flags):string|false{return json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
final class PLDR_Core {
    public static function machine_error(string $code,string $message,int $status=400,array $data=array()):WP_Error{
        return new WP_Error($code,$message,array_merge(array('status'=>$status),$data));
    }
    public static function table(string $x):string{return 'wp_pldr_'.$x;}
    public static function now():string{return '2026-10-12 00:00:00';}
    public static function audit(string $kind,int $id,string $event,array $data=array(),int $uid=0):bool{
        $GLOBALS['r49_audit'][]=array($kind,$id,$event,$data,$uid);return true;
    }
}
final class R49_DB {
    public string $last_error='';
    public int $reads=0;
    public int $writes=0;
    public array $rows=array();
    public array $prepared=array();
    public function prepare(string $sql,...$args):string{$this->prepared=$args;return $sql;}
    public function get_row(string $sql,$type=null):?array{$this->reads++;return $this->rows[77]['reader']??null;}
    public function insert(string $table,array $data,$format=null):int{
        $this->writes++;
        $this->rows[(int)$data['user_id']][$data['preference_key']]=array(
           'preference_json'=>$data['preference_json'],'version'=>$data['version'],
           'updated_at'=>$data['updated_at']);
        return 1;
    }
    public function query(string $sql):int{
        $this->writes++;
        if(str_contains($sql,'UPDATE ')){
            [$encoded,$version,$now,$uid,$key,$expected]=$this->prepared;
            if(!isset($this->rows[$uid][$key])||(int)$this->rows[$uid][$key]['version']!==$expected)return 0;
            $this->rows[$uid][$key]=array('preference_json'=>$encoded,'version'=>$version,'updated_at'=>$now);
            return 1;
        }
        return 1;
    }
}
$GLOBALS['wpdb']=new R49_DB();
$GLOBALS['r49_audit']=array();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-preferences.php';
$n=0;
$check=static function(bool $ok,string $why) use(&$n):void{
    if(!$ok)throw new RuntimeException('R49 reader preferences: '.$why);
    $n++;
};
$invalid=array(
    array('layout'=>array('single')),
    array('layout'=>'not-a-layout'),
    array('contrast'=>false),
    array('contrast'=>'unreviewed'),
    array('text_size'=>'120'),
    array('text_size'=>true),
    array('text_size'=>120.3),
    array('line_height'=>array(160)),
    array('line_height'=>INF),
    array('column_width'=>new stdClass()),
    array('column_width'=>NAN),
    array('data_saver'=>'false'),
    array('data_saver'=>'0'),
    array('data_saver'=>0),
    array('data_saver'=>array()),
    array('tts_rate'=>'1.25'),
    array('tts_rate'=>false),
    array('tts_rate'=>NAN),
    array('tts_rate'=>INF),
    array('tts_voice'=>array('voice')),
    array('tts_voice'=>false),
    array('language'=>new stdClass()),
    array('language'=>null),
    array('unknown_key'=>'should not surface'),
    array(0=>'positional list'),
);
foreach($invalid as $item){
    $db=$GLOBALS['wpdb'];$beforeRead=$db->reads;$beforeWrite=$db->writes;
    $result=PLDR_Future_Preferences::save('reader',$item,0);
    $check(is_wp_error($result)&&$result->code==='pldr_future_pref_shape',
        'new typed preferences silently coerced '.json_encode($item,JSON_PARTIAL_OUTPUT_ON_ERROR));
    $check($db->reads===$beforeRead,'malformed new preference fetched private DB data');
    $check($db->writes===$beforeWrite,'malformed new preference performed write');
}
$corrupt=array(
    '{"text_size":"120"}',
    '{"data_saver":"false"}',
    '{"data_saver":2}',
    '{"layout":["single"]}',
    '{"layout":"UNKNOWN"}',
    '{"tts_voice":["voices"]}',
    '{"tts_rate":"1.25"}',
    '{"tts_rate":1e1000}',
    '{"contrast":false}',
    '{"column_width":true}',
    '{"line_height":{"nested":160}}',
    '{"language":null}',
    '{"unknown_private":"secret"}',
    '["single","spread-rtl"]',
    '[{"layout":"single"}]',
    'null',
    'false',
    '42',
    '"reader"',
    'broken-json',
    '{"layout":"single","text_size":999}',
);
foreach($corrupt as $j){
    $db=$GLOBALS['wpdb'];
    $db->rows[77]['reader']=array('preference_json'=>$j,'version'=>6,'updated_at'=>'2026-10-12 00:00:00');
    $beforeWrite=$db->writes;
    $result=PLDR_Future_Preferences::get('reader');
    $check(is_wp_error($result)&&$result->code==='pldr_future_pref_corrupt',
        'malformed historic private preferences projected: '.$j);
    $check(($result->data['version']??0)===6,
        'historic corruption lost optimistic version identity');
    $check($db->writes===$beforeWrite,
        'historic preference read silently migrated/destroyed source');
}
$valid=array(
    array(),
    array('layout'=>'spread-rtl','data_saver'=>false,'text_size'=>110),
    array('tts_rate'=>1.25,'tts_voice'=>'English voice','language'=>'en-US'),
    array('line_height'=>160,'column_width'=>70,'contrast'=>'dark'),
    array('layout'=>'continuous','data_saver'=>true,'tts_rate'=>1),
);
foreach($valid as $value){
    $db=$GLOBALS['wpdb'];
    unset($db->rows[77]);
    $created=PLDR_Future_Preferences::save('reader',$value,0);
    $check(is_array($created)&&($created['version']??0)===1,
        'valid new preference creation failed');
    $loaded=PLDR_Future_Preferences::get('reader');
    $check(is_array($loaded)&&($loaded['version']??0)===1,
        'valid preference read/version failed');
    // JSON's numeric model does not preserve PHP float(1.0) vs int(1).
    // The settings contract permits either finite numeric representation;
    // strict boolean/string/collection typing is separately tested above.
    $check(($loaded['value']??null)==$created['value'],
        'valid persisted private preferences diverged from canonical saved values');
    $new=PLDR_Future_Preferences::save('reader',$value,1);
    $check(is_array($new)&&($new['version']??0)===2,
        'valid optimistic version update failed');
    $loaded=PLDR_Future_Preferences::get('reader');
    $check(is_array($loaded)&&($loaded['version']??0)===2,
        'valid updated private preferences did not persist');
}
$empty=array('[]','{}');
foreach($empty as $json){
    $GLOBALS['wpdb']->rows[77]['reader']=array('preference_json'=>$json,'version'=>4,'updated_at'=>'2026-10-12 00:00:00');
    $result=PLDR_Future_Preferences::get('reader');
    $check(is_array($result)&&($result['value']??null)===array(),
        'lawful empty legacy preference rejected: '.$json);
}
$audits=$GLOBALS['r49_audit'];
$check(count($audits)>=count($corrupt),'corrupt historical preferences not audited');
echo "PASS R49: {$n} actual Reader Preferences GET/SAVE and private JSON integrity assertions\n";
