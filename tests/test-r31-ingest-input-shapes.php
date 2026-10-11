<?php
/**
 * R31: execute the ACTUAL PLDR_Ingest::ingest() against malformed request
 * fixtures. Invalid metadata/files must return a stable machine error
 * BEFORE performing DB/file operations; valid requests still reach the
 * normal verified-HTTP-upload gate.
 */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
final class WP_Error {
    public function __construct(public string $code,public string $message='',public array $data=array()){}
    public function get_error_code():string{return $this->code;}
}
function is_wp_error($v):bool{return $v instanceof WP_Error;}
function get_current_user_id():int{return 19;}
final class PLDR_Core {
    public const DOCUMENT_TYPES=array('book'=>'Book');
    public const CATEGORIES=array('research'=>'Research');
    public static function authorize(string $action,int $doc=0,int $user=0):bool{return $action==='publish'&&$user===19;}
    public static function machine_error(string $code,string $message,int $status=400,array $details=array()):WP_Error{return new WP_Error($code,$message,$details);}
}
final class PLDR_R31_DB {
    public string $last_error='';
    public function __call(string $n,array $args){throw new RuntimeException('Unexpected DB operation during ingest input gate: '.$n);}
}
$GLOBALS['wpdb']=new PLDR_R31_DB();
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-ingest.php';
$good=array(
    'title'=>'Test Book','document_type'=>'book','category'=>'research','language'=>'en',
    'author_name'=>'Author','source_name'=>'Publisher','rights_basis'=>'owned','access_mode'=>'public'
);
$cases=array();
foreach(array('title','document_type','category','language','author_name','source_name','rights_basis','access_mode') as $key){
    $input=$good;$input[$key]=array('not'=>'scalar');
    $cases[]=array($key.' list injection',$input,array(),'pldr_ingest_metadata_shape');
}
foreach(array('document_public_id','expected_document_version','slug','translator','publisher','isbn','year','publication_year','pages','edition_label','license_code','territory','takedown_contact','rights_evidence_ref','entitlement_key','rights_expires_at','embargo_until','supersedes_edition_id','patient_case_consent_ref','patient_case_anonymization_ref') as $key){
    $input=$good;$input[$key]=array('nested');
    $cases[]=array($key.' nested injection',$input,array(),'pldr_ingest_metadata_shape');
}
foreach(array('download_allowed','print_allowed','offline_allowed','patient_case_consent','confirm_distinct_scan') as $key){
    $input=$good;$input[$key]=array('true');
    $cases[]=array($key.' invalid flag',$input,array(),'pldr_ingest_metadata_shape');
}
foreach(array('subjects','collections') as $key){
    foreach(array(array(array('nested'=>1)),'[{"nested":"bad"}]','{"bad":"object"}','["unterminated"') as $value){
        // Malformed JSON beginning with '[' is rejected rather than warning
        // or storing a synthetic "Array" label.
        $input=$good;$input[$key]=$value;
        $cases[]=array($key.' nested/malformed list',$input,array(),'pldr_ingest_metadata_shape');
    }
}
foreach(array(
    array('pdf'=>array('tmp_name'=>array('tmp'))),
    array('pdf'=>array('tmp_name'=>(object)array('path'=>'x'))),
    array('pdf'=>array('name'=>array('doc.pdf'))),
    array('pdf'=>array('tmp_name'=>'/tmp/nope','size'=>array(10))),
    array('cover'=>array('tmp_name'=>array('tmp'))),
    array('cover'=>array('error'=>array(0)))
) as $files)$cases[]=array('malformed upload file structure',$good,$files,'pldr_ingest_upload_shape');
$checks=0;
foreach($cases as [$label,$input,$files,$code]){
    $res=PLDR_Ingest::ingest($input,$files,19);
    if(!is_wp_error($res)||$res->code!==$code)throw new RuntimeException($label.' expected '.$code.', got '.(is_wp_error($res)?$res->code:get_debug_type($res)));
    $checks++;
}
// Positive validation control: permitted scalar metadata + legal lists reach
// the next existing security gate; they do not get blanket-denied.
$good['subjects']=array('research','paper');
$good['collections']='["Classical Books","Research Shelf"]';
$res=PLDR_Ingest::ingest($good,array(),19);
if(!is_wp_error($res)||$res->code!=='pldr_pdf_missing')throw new RuntimeException('Valid metadata did not reach the required verified PDF upload gate.');
$checks++;
echo "PASS R31: {$checks} real ingest entrypoint test cases; nested metadata/uploads deny without DB or file side effects.\n";
