<?php
/** File 12 → File 06: exact PDF edition/page and quotation rights-safety. */
declare(strict_types=1);define('ABSPATH',__DIR__);
class WP_Error{public function __construct($c,$m='',$data=array()){}}
class PLDR_Core {
	public static function edition($id){return $GLOBALS['edition'];}
	public static function document($id){return $GLOBALS['doc'];}
	public static function route_url($type,$args){return 'https://example.org/pdf/'.$args['id'];}
	public static function normalize_search($txt){return trim(mb_strtolower((string)$txt,'UTF-8'));}
	public static function machine_error($code,$msg,$status=400){return new WP_Error($code,$msg,array('status'=>$status));}
	public static function authorize($cap,$docid=0,$uid=0){return true;}
}
class PLDR_Access {
	public static function can_access_edition($id,$operation='read',$user=-1){return $GLOBALS['public_read'];}
}
class PLDR_Rights_Policy {public static function check($id){return $GLOBALS['rights_on']?true:new WP_Error('rights-denied');}}
class PLDR_Future_Data {
	public static function ocr_pages($id,$page,$limit,$offset){return array(array(
		'page_number'=>$page,'text_content'=>'This is an authentic source quotation drawn from the approved digitized original edition.',
	));}
}
function absint($v){return abs((int)$v);}
function get_option($key,$default=null){return $GLOBALS['options'][$key]??$default;}
function wp_http_validate_url($v){return str_starts_with($v,'https://');}
function sanitize_text_field($s){return trim((string)$s);}
function is_wp_error($x){return $x instanceof WP_Error;}
function must($truth,$name){$GLOBALS['checks']++;if(!$truth){fwrite(STDERR,"FAILED {$name}\n");exit(1);}}
$wpdb=new class { public $last_error=''; };
$uuid='11111111-1111-4111-8111-111111111111';
$GLOBALS['checks']=0;$GLOBALS['rights_on']=true;$GLOBALS['public_read']=true;
$GLOBALS['doc']=array('id'=>11,'status'=>'published','public_id'=>'pdf_published_0001','title'=>'Original Sources','version'=>6);
$GLOBALS['edition']=array('id'=>27,'document_id'=>11,'status'=>'published','document_status'=>'published',
	'edition_label'=>'1st','version'=>4,'sha256'=>str_repeat('a',64),'rights_basis'=>'public-domain',
	'rights_expires_at'=>null,'pages'=>400);
$dto=array('id'=>$uuid,'version'=>3);
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-file06-sources.php';
$lk=new ReflectionMethod('PLDR_File06_Sources','link_key');$lk->setAccessible(true);$key=$lk->invoke(null,$uuid);
$GLOBALS['options']=array($key=>array(array('approved'=>true,'concept_public_id'=>$uuid,
	'concept_version'=>3,'document_id'=>11,'edition_id'=>27,'edition_version'=>4,
	'edition_sha256'=>str_repeat('a',64),'rights_evidence_ref'=>'rights:document-2026','page'=>84,'reviewer_id'=>8)));
$links=PLDR_File06_Sources::collect(array(),$dto);
must(count($links)===1,'Reviewed edition and page returns real File12 reader link');
must(PLDR_File06_Sources::attest(false,$links[0],$dto)===true,'Revalidate exact source version and page');
$GLOBALS['rights_on']=false;
must(PLDR_File06_Sources::collect(array(),$dto)===array(),'Withdrawn publication rights stop links');
$GLOBALS['rights_on']=true;$GLOBALS['public_read']=false;
must(PLDR_File06_Sources::collect(array(),$dto)===array(),'Private edition never leaked to anonymous File06');
$GLOBALS['public_read']=true;$GLOBALS['edition']['version']=5;
must(PLDR_File06_Sources::collect(array(),$dto)===array(),'Changed edition invalidates source link');
$GLOBALS['edition']['version']=4;
$exact='This is an authentic source quotation drawn from the approved digitized original edition.';
$claim=array('concept_public_id'=>$uuid,'concept_version'=>3,'edition_id'=>27,'edition_version'=>4,
	'page'=>84,'exact'=>$exact,'rights_evidence_ref'=>'rights:document-2026',
	'edition_sha256'=>str_repeat('a',64));
$q=new ReflectionMethod('PLDR_File06_Sources','quote_key');$q->setAccessible(true);
$quote_key=$q->invoke(null,$claim);
$GLOBALS['options'][$quote_key]=array('approved'=>true,'exact_sha256'=>hash('sha256',$exact),
	'edition_sha256'=>str_repeat('a',64),'rights_evidence_ref'=>'rights:document-2026','reviewer_id'=>8);
$verified=PLDR_File06_Sources::quote_assertion(null,$claim);
must(is_array($verified)&&$verified['page']===84&&$verified['source_title']==='Original Sources','Exact lawful OCR quotation verified');
$wrong=$claim;$wrong['exact']='This quotation is invented and absent from the original.';
must(null===PLDR_File06_Sources::quote_assertion(null,$wrong),'Invented text rejected even with a citation');
$wrong=$claim;$wrong['page']=3;
must(null===PLDR_File06_Sources::quote_assertion(null,$wrong),'Unapproved source page rejected');
$GLOBALS['edition']['sha256']=str_repeat('b',64);
must(null===PLDR_File06_Sources::quote_assertion(null,$claim),'New source bytes invalidate old quote');
echo "File12→File06 source and quotation: {$GLOBALS['checks']} isolated checks PASS.\n";
