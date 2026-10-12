<?php
/**
 * R50: actual File12 FUT-021 AI corpus manifest and HMAC cursor methods.
 * Simulated native PHP/WordPress providers are NOT installed Hostinger proof.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($x):bool{return $x instanceof WP_Error;}
function get_current_user_id():int{return 77;}
function wp_json_encode($x,...$flags):string|false{return json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
function wp_salt($scope):string{return 'r50-test-file16-private-hmac-secret';}
function rest_url($url):string{return 'https://example.org/wp-json/'.ltrim($url,'/');}
function apply_filters(string $name,$default,...$params):bool {
    return in_array($name,array('pldr_ai_corpus_allowed','pldr_ai_corpus_consumer_allowed'),true);
}
final class PLDR_Core {
    public static function document(int $id):?array{return $id===5?array('id'=>5,'category'=>'books'):null;}
    public static function machine_error(string $code,string $message,int $status=400,array $data=array()):WP_Error {
        return new WP_Error($code,$message,array_merge(array('status'=>$status),$data));
    }
    public static function audit(string $kind,int $id,string $action,array $detail=array(),int $uid=0):bool {
        $GLOBALS['r50_audit'][]=array($kind,$id,$action,$detail,$uid);return true;
    }
}
final class PLDR_Reader {
    public static function citation(array $edition,int $page,string $format='sabri'):string {
        return 'Sabri, '.(string)$edition['public_id'].', page '.$page;
    }
}
final class R50_DB {public string $last_error='';}
$GLOBALS['wpdb']=new R50_DB();
$GLOBALS['r50_audit']=array();
final class PLDR_Future_Data {
    public static array $rows=array();
    public static int $source_reads=0;
    public static int $last_after=-1;
    public static function require_edition(int $edition_id,string $operation='read') {
        return $edition_id===42?array('id'=>42,'pages'=>12,'document_id'=>5,
            'public_id'=>'f12-r50-book','rights_basis'=>'public-domain',
            'license_code'=>'public-domain') : new WP_Error('unknown');
    }
    public static function ocr_pages(int $edition_id,int $page=0,int $limit=0,int $offset=0,int $after_page=0):array {
        self::$source_reads++;self::$last_after=$after_page;
        return self::$rows;
    }
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-future-corpus.php';
$n=0;
$check=static function(bool $condition,string $reason)use(&$n):void{
    if(!$condition)throw new RuntimeException('R50 File16 Corpus: '.$reason);
    $n++;
};
$encode=new ReflectionMethod(PLDR_Future_Corpus::class,'encode_cursor');
$decode=new ReflectionMethod(PLDR_Future_Corpus::class,'decode_cursor');
$context=hash('sha256','42|f12-r50-book|77|file16-corpus-v1');
$signed=static function($value):string {
    $json=json_encode($value);
    $payload=rtrim(strtr(base64_encode($json),'+/','-_'),'=');
    return $payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth'));
};
foreach(array(1,2,3,12,PHP_INT_MAX) as $page){
    $token=$encode->invoke(null,$page,$context);
    $result=$decode->invoke(null,$token,$context);
    $check(is_int($result)&&$result===$page,
        'real File12 generated cursor failed signed page round-trip');
}
$bad=array(
    array('p'=>-3,'c'=>$context),array('p'=>0,'c'=>$context),
    array('p'=>3.5,'c'=>$context),array('p'=>'3','c'=>$context),
    array('p'=>true,'c'=>$context),array('p'=>false,'c'=>$context),
    array('p'=>null,'c'=>$context),array('p'=>array(3),'c'=>$context),
    array('p'=>(object)array('x'=>3),'c'=>$context),
    array('p'=>3,'c'=>array($context)),array('p'=>3,'c'=>null),
    array('p'=>3,'c'=>'wrong-owner-context'),
    array('p'=>3,'c'=>$context,'extra'=>'unreviewed'),
    array('c'=>$context),array('p'=>3),
    array(3,$context),array(),
);
foreach($bad as $payload){
    $result=$decode->invoke(null,$signed($payload),$context);
    $check(is_wp_error($result)&&$result->code==='pldr_corpus_cursor',
        'signed malformed File16 cursor was coerced/accepted: '.json_encode($payload,JSON_PARTIAL_OUTPUT_ON_ERROR));
}
foreach(array('a.b.c','garbage','',str_repeat('a',501)) as $token){
    $result=$decode->invoke(null,$token,$context);
    if($token==='')$check($result===0,'empty approved initial AI corpus cursor rejected');
    else $check(is_wp_error($result)&&$result->code==='pldr_corpus_cursor',
        'unsigned malformed AI corpus cursor accepted');
}
$validRow=static function($page,string $text='Page source text'):array {
    return array('page_number'=>$page,'quality_score'=>0.98,
       'text_content'=>$text,'language'=>'ur','normalized_text'=>strtolower($text));
};
PLDR_Future_Data::$rows=array($validRow(1),$validRow('2'),$validRow(3));
$manifest=PLDR_Future_Corpus::manifest(42,'',2);
$check(!isset($manifest['error']),'valid File16 OCR manifest rejected');
$check(($manifest['has_more']??false)===true,'bounded lookahead pagination missing');
$check(count($manifest['chunks']??[])===2,'File16 emitted wrong number of chunks');
$check(($manifest['chunks'][0]['page']??0)===1 &&
    ($manifest['chunks'][1]['page']??0)===2,'File16 canonical page/source identity mismatch');
$check(($manifest['contains_text']??true)===false &&
    ($manifest['consumer_authorized']??false)===true,'AI corpus unexpectedly contains source text or lacked policy');
$next=$manifest['next_cursor']??null;
$check(is_string($next)&&$decode->invoke(null,$next,$context)===2,'File16 next cursor did not preserve last page');
PLDR_Future_Data::$rows=array($validRow(3),$validRow(4,'Author text 4'));
$page2=PLDR_Future_Corpus::manifest(42,$next,2);
$check(!isset($page2['error'])&&PLDR_Future_Data::$last_after===2,
    'next page did not advance after exactly the previous page');
$check(count($page2['chunks']??[])===2 && $page2['next_cursor']===null,'terminal corpus window malformed');
$invalidRows=array(
    array($validRow(-1)),array($validRow(0)),array($validRow(13)),
    array($validRow(99999)),array($validRow(2.5)),array($validRow('2.5')),
    array($validRow('02')),array($validRow(true)),array($validRow(null)),
    array($validRow(array(2))),array($validRow('overflow')),
    array($validRow(1),$validRow(1)),
    array($validRow(2),$validRow(1)),
    array($validRow(1),$validRow(13)),
    array($validRow(1),$validRow(2),$validRow(0)),
);
foreach($invalidRows as $badRows){
    PLDR_Future_Data::$rows=$badRows;
    $before=PLDR_Future_Data::$source_reads;
    $res=PLDR_Future_Corpus::manifest(42,'',2);
    $check(is_wp_error($res['error']??null)&&$res['error']->code==='pldr_corpus_source_integrity',
       'corrupt OCR source/page/order generated File16 chunks: '.json_encode($badRows,JSON_PARTIAL_OUTPUT_ON_ERROR));
    $check(!isset($res['chunks'])&&!isset($res['next_cursor']),
       'corrupt source returned partial AI chunks or signed cursor');
    $check(PLDR_Future_Data::$source_reads===$before+1,'unexpected File16 source read count');
}
PLDR_Future_Data::$rows=array($validRow(2));
$res=PLDR_Future_Corpus::manifest(42,$next,2);
$check(is_wp_error($res['error']??null) && $res['error']->code==='pldr_corpus_source_integrity',
    'stale/misordered OCR row behind signed cursor accepted');
$badSigned=$signed(array('p'=>true,'c'=>$context));
$before=PLDR_Future_Data::$source_reads;
$res=PLDR_Future_Corpus::manifest(42,$badSigned,2);
$check(is_wp_error($res['error']??null)&&$res['error']->code==='pldr_corpus_cursor',
    'malformed signed File16 cursor reached source provider');
$check($before===PLDR_Future_Data::$source_reads,'invalid cursor read source OCR rows');
$check(count($GLOBALS['r50_audit'])>=count($invalidRows),'source integrity audit missing');
echo "PASS R50: {$n} real File16 corpus manifest and signed cursor assertions\n";
