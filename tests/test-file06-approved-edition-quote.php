<?php
/** File 12 → File 06: exact PDF edition/page and quotation rights-safety. */
declare(strict_types=1);define('ABSPATH',__DIR__);
class WP_Error{public function __construct($c,$m='',$data=array()){}}
class PLDR_Core {
	public static function edition($id){$GLOBALS['edition_reads']=($GLOBALS['edition_reads']??0)+1;return $GLOBALS['edition'];}
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
// R51: malformed request identifiers must be denied before even a source lookup.
function wp_verify_nonce($value,$action){return true;}
function get_current_user_id(){return 8;}
class R51_Request {
    private array $data;
    public function __construct(array $data){$this->data=$data;}
    public function get_header($name){return 'valid-nonce';}
    public function get_json_params(){return $this->data;}
}
$GLOBALS['edition']['sha256']=str_repeat('a',64);
$GLOBALS['edition_reads']=0;
$validSource=array('concept_public_id'=>$uuid,'concept_version'=>3,'edition_id'=>27,
    'page'=>84,'rights_evidence_ref'=>'rights:document-2026');
$validQuote=$validSource+array('original_excerpt'=>$exact);
foreach(array(-27,0,27.5,'27',true,array(27),array('id'=>27)) as $bad){
    foreach(array('edition_id','page','concept_version') as $field){
        $s=$validSource;$s[$field]=$bad;
        $before=$GLOBALS['edition_reads'];
        must(PLDR_File06_Sources::approve_source(new R51_Request($s)) instanceof WP_Error,'Malformed source ID denied');
        must($GLOBALS['edition_reads']===$before,'No source edition reads before malformed ID rejection');
        $q=$validQuote;$q[$field]=$bad;
        $before=$GLOBALS['edition_reads'];
        must(PLDR_File06_Sources::approve_quote(new R51_Request($q)) instanceof WP_Error,'Malformed quote ID denied');
        must($GLOBALS['edition_reads']===$before,'No quote edition reads before malformed ID rejection');
    }
}
foreach(array(array('concept_public_id'=>array($uuid)),array('rights_evidence_ref'=>array('rights'))) as $mutation){
    $s=array_merge($validSource,$mutation);
    $before=$GLOBALS['edition_reads'];
    must(PLDR_File06_Sources::approve_source(new R51_Request($s)) instanceof WP_Error,'Malformed source string field denied');
    must($GLOBALS['edition_reads']===$before,'Source field failure has no edition lookup');
}
$sourceRow=$GLOBALS['options'][$key][0];
$GLOBALS['options'][$key][0]['page']=-84;
must(PLDR_File06_Sources::collect(array(),$dto)===array(),'Malformed historical signed page cannot coerce to a positive page');
$GLOBALS['options'][$key][0]=$sourceRow;
$badClaim=$claim;$badClaim['edition_id']='27';
must(null===PLDR_File06_Sources::quote_assertion(null,$badClaim),'String edition ID in quote assertion denied');
$badClaim=$claim;$badClaim['page']=-84;
must(null===PLDR_File06_Sources::quote_assertion(null,$badClaim),'Negative page in quote assertion denied');

// R53 red tests: canonical File06 identity and source-rights evidence must
// remain bound to *both* the approved link and each approved exact quote.
function apply_filters($hook,$value,...$args){
    return $hook==='he_v12_file06_concept_assertion_v1'?$GLOBALS['f06_canonical']:$value;
}
function update_option($key,$row,$autoload=false){
    $GLOBALS['approval_writes']++;
    $GLOBALS['options'][$key]=$row;
    return true;
}
function rest_ensure_response($result){return $result;}
$GLOBALS['approval_writes']=0;
$GLOBALS['f06_canonical']=array('published'=>true,'owner_file'=>'file-06','public_id'=>$uuid);
$ok=PLDR_File06_Sources::approve_quote(new R51_Request($validQuote));
must(is_array($ok)&&($ok['approved']??false)===true,'Valid current File06 quote request approved');
$writes=$GLOBALS['approval_writes'];
$bad=$validQuote;$bad['rights_evidence_ref']='rights:unreviewed-evidence';
must(PLDR_File06_Sources::approve_quote(new R51_Request($bad)) instanceof WP_Error,
    'Quote with rights evidence different from independently approved mapping denied');
must($GLOBALS['approval_writes']===$writes,'Mismatched rights do not create a proof row');
$GLOBALS['f06_canonical']['owner_file']='untrusted-owner';
must(PLDR_File06_Sources::approve_quote(new R51_Request($validQuote)) instanceof WP_Error,
    'Quote must verify File06 canonical owner, not just publication boolean');
must($GLOBALS['approval_writes']===$writes,'Wrong canonical owner makes no quote write');
$GLOBALS['f06_canonical']['owner_file']='file-06';
$GLOBALS['f06_canonical']['public_id']='22222222-2222-4222-8222-222222222222';
must(PLDR_File06_Sources::approve_quote(new R51_Request($validQuote)) instanceof WP_Error,
    'Quote must verify exact File06 concept public UUID');
must($GLOBALS['approval_writes']===$writes,'Mismatched File06 UUID makes no quote write');
$GLOBALS['f06_canonical']['public_id']=$uuid;
// Historical approved quotation cannot outlive a separately changed rights-evidence mapping.
$GLOBALS['options'][$key][0]['rights_evidence_ref']='rights:new-approval-2026';
must(null===PLDR_File06_Sources::quote_assertion(null,$claim),
    'Quote proof must be reapproved when its independently approved source-rights evidence changes');
$GLOBALS['options'][$key][0]=$sourceRow;

// R55 negative: owner attestation must preserve *all* canonical, rights-sensitive
// identity fields, not just the URL/hash plus coercible edition/page numbers.
$canonicalLink=PLDR_File06_Sources::collect(array(),$dto)[0];
must(PLDR_File06_Sources::attest(false,$canonicalLink,$dto)===true,'Canonical complete File12 resource attested');
foreach(array(
    array('edition_id','27spoof'),
    array('page','84garbage'),
    array('owner_id','fabricated-document-id'),
    array('owner_version','6spoof'),
    array('source_version','3spoof'),
    array('eligible',false),
    array('rights_ok',false),
    array('owner_version',999),
    array('source_version',999),
    array('owner_id',array('fabricated')),
    array('edition_id',27.1)
) as $mutation){
    $forged=$canonicalLink;$forged[$mutation[0]]=$mutation[1];
    must(PLDR_File06_Sources::attest(false,$forged,$dto)===false,
        'File12 canonical owner attestation refuses mutated '.$mutation[0]);
}

echo "File12→File06 source and quotation: {$GLOBALS['checks']} isolated checks PASS.\n";
