<?php
/**
 * R32: actual PDF validator / physical upload-byte integrity fixture,
 * plus source-flow guards for eager temp clean-up and cover preflight.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('MB_IN_BYTES') || define('MB_IN_BYTES',1048576);
defined('UPLOAD_ERR_OK') || define('UPLOAD_ERR_OK',0);
final class WP_Error {
    public function __construct(public string $code, public string $message='') {}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function wp_max_upload_size():int{return 8*MB_IN_BYTES;}
function apply_filters(string $tag,$value,...$arguments){return $value;}
final class PLDR_Core {
    public static function machine_error(string $code,string $message,int $status=400,array $args=array()):WP_Error {
        return new WP_Error($code,$message);
    }
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php';
$assert=static function(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('R32 PDF integrity: '.$message);
};
$method=new ReflectionMethod('PLDR_Ingest','validate_pdf');
$temp=tempnam(sys_get_temp_dir(),'r32-pdf-');
$assert(is_string($temp)&&$temp!=='','Could not create test PDF');
try {
    // Actual PDF recognisable by fileinfo and PDF signature/EOF validation.
    $bytes="%PDF-1.4\n1 0 obj\n<< /Type /Catalog >>\nendobj\ntrailer\n<< /Root 1 0 R >>\n%%EOF\n";
    file_put_contents($temp,$bytes);
    $actual=filesize($temp);
    $case=array('name'=>'source.pdf','tmp_name'=>$temp,'error'=>UPLOAD_ERR_OK,'size'=>$actual);
    $check=static fn(array $pdf)=>$method->invoke(null,$pdf);
    $assert(true===$check($case),'Valid physical bytes and declared count must pass');
    $short=$case;$short['size']=$actual-1;
    $v=$check($short);
    $assert(is_wp_error($v)&&$v->code==='pldr_pdf_size_mismatch','One-byte under-reported source must fail');
    $large=$case;$large['size']=$actual+2048;
    $v=$check($large);
    $assert(is_wp_error($v)&&$v->code==='pldr_pdf_size_mismatch','Over-reported source must fail');
    $append=$case;
    file_put_contents($temp,$bytes."EVIL_APPENDED_BYTES");
    $v=$check($append);
    $assert(is_wp_error($v)&&$v->code==='pldr_pdf_size_mismatch','Changed source file must fail before further processing');
    $v=$check(array('name'=>'source.pdf','tmp_name'=>$temp,'error'=>UPLOAD_ERR_OK,'size'=>filesize($temp)));
    $assert(is_wp_error($v)&&$v->code==='pldr_pdf_trailing_payload','Accurate size still rejects appended polyglot');
} finally {
    @unlink($temp);
}
$source=(string)file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php');
$methodBegin=strpos($source,'public static function ingest(');
$methodEnd=strpos($source,'private static function validate_pdf(',$methodBegin);
$body=substr($source,$methodBegin,$methodEnd-$methodBegin);
$start=strpos($body,'$encrypted=PLDR_Crypto::encrypt_file(');
$fail=strpos($body,'if (!$encrypted)');
$preCommit=strpos($body,"$"."wpdb->query('START TRANSACTION')");
$cover=strpos($body,"$"."cover_supplied=");
$docRead=strpos($body,'$existing_doc = null;');
$assert($start!==false && $fail!==false && $start<$fail && $fail<$preCommit,
  'Encryption failure must be checked before canonical DB transaction');
$falseSegment=substr($body,$fail,strpos($body,'if (!PLDR_Storage::atomic_commit(',$fail)-$fail);
$assert(str_contains($falseSegment,'PLDR_Storage::delete($temp);'),
  'False-returned encryption must eagerly remove private temporary file');
$exceptionSegment=substr($body,$start,$fail-$start);
$assert(str_contains($exceptionSegment,'catch (Throwable $e)')&&str_contains($exceptionSegment,'PLDR_Storage::delete($temp);'),
  'Exception during encryption must eagerly remove private temporary file');
$assert($cover!==false&&$docRead!==false&&$cover<$docRead,
  'Supplied-cover HTTP preflight must precede canonical document lookup');
echo "PASS R32: 5 real PDF physical-size/polyglot cases; source-order cover/crypto cleanup invariants\n";
