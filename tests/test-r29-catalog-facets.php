<?php
/** R29 real PLDR_Search logic with deterministic WordPress/DB fixtures. */
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
defined('ARRAY_A') || define('ARRAY_A','ARRAY_A');
final class WP_Error {
    public function __construct(public string $code,public string $message=''){}
}
function is_wp_error($x):bool{return $x instanceof WP_Error;}
function absint($x):int{return abs((int)$x);}
function sanitize_text_field($x):string{return trim(strip_tags((string)$x));}
function wp_json_encode($x):string{return json_encode($x,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);}
function wp_salt($type=''):string{return 'r29-stable-test-secret';}
function apply_filters($tag,$value,...$args){return $value;}
final class PLDR_R29_DB {
    public string $last_error='';
    public array $docs=array(
      array('id'=>3,'public_id'=>'doc-3','created_at'=>'2026-01-03 00:00:00','title'=>'Hidden Work','document_type'=>'book','category'=>'library','language'=>'en','slug'=>'hidden'),
      array('id'=>2,'public_id'=>'doc-2','created_at'=>'2026-01-02 00:00:00','title'=>'Second Book','document_type'=>'book','category'=>'library','language'=>'en','slug'=>'second'),
      array('id'=>1,'public_id'=>'doc-1','created_at'=>'2026-01-01 00:00:00','title'=>'First Book','document_type'=>'book','category'=>'library','language'=>'en','slug'=>'first'),
    );
    public function esc_like(string $s):string{return addcslashes($s,'_%\\');}
    public function prepare(string $q,...$a):string{return $q;}
    public function get_results(string $q,$output=null):array{
        return str_contains($q,'SELECT d.*')?$this->docs:array();
    }
}
$GLOBALS['wpdb']=new PLDR_R29_DB();
final class PLDR_Core {
    public const DOCUMENT_TYPES=array('book'=>'Book');
    public const CATEGORIES=array('library'=>'Library');
    public static function normalize_search(string $s):string{return trim($s);}
    public static function table(string $n):string{return 'wp_pldr_'.$n;}
    public static function current_edition(int $doc):?array{
        $editions=array(
          1=>array('id'=>11,'author_name'=>'John Smith','source_name'=>'Oxford Publisher','edition_label'=>'Third Edition','isbn'=>'978-123','license_code'=>'CC-BY','publication_year'=>2026),
          2=>array('id'=>22,'author_name'=>'Mary Jones','source_name'=>'Sabri House','edition_label'=>'First Edition','isbn'=>'987-456','license_code'=>'Restricted','publication_year'=>2024),
          3=>array('id'=>33,'author_name'=>'John Smith','source_name'=>'Oxford Publisher','edition_label'=>'Third Edition','isbn'=>'978-123','license_code'=>'CC-BY','publication_year'=>2026)
        );
        return $editions[$doc]??null;
    }
    public static function public_document_dto(array $doc,?array $edition=null,?array $policy=null):array{
        return array('id'=>$doc['id'],'access_mode'=>$doc['id']===2?'account':'public','edition'=>$edition);
    }
    public static function machine_error(string $code,string $text,int $status=400,array $extras=array()):WP_Error{return new WP_Error($code,$text);}
    public static function audit(...$a):bool{return true;}
}
final class PLDR_Access {
    public static function can_access_edition(int $ed,string $op='read',int $uid=0):bool{return $ed!==33;}
}
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php';
$check=static function(bool $b,string $msg):void {if(!$b){fwrite(STDERR,"R29 catalog facet regression: {$msg}\n");exit(1);}};
$get_ids=static function(array $args):array{
    $x=PLDR_Search::search($args);
    if(isset($x['error']))return array('ERROR:'.$x['error']->code);
    return array_map(static fn(array $z)=>(int)$z['id'],$x['items']);
};
$cases=array(
    array(array('author'=>'John'),array(1),'author search exposes only eligible edition'),
    array(array('source'=>'Oxford'),array(1),'publication source search'),
    array(array('edition'=>'Third'),array(1),'edition label search'),
    array(array('isbn'=>'978'),array(1),'ISBN facet search'),
    array(array('license'=>'CC-BY'),array(1),'license facet search'),
    array(array('year'=>'2026'),array(1),'year exact facet search'),
    array(array('access_mode'=>'account'),array(2),'account access facet search'),
    array(array('access_mode'=>'public'),array(1),'public access facet search'),
    array(array('author'=>'Mary','source'=>'Sabri'),array(2),'combined facets'),
    array(array('author'=>'Unknown'),array(),'no matches'),
    array(array('author'=>str_repeat('x',121)),array('ERROR:pldr_catalog_filter_long'),'bounded input'),
    array(array('year'=>'abcd'),array('ERROR:pldr_catalog_filter_invalid'),'invalid year fail closed'),
    array(array('access_mode'=>'owner'),array('ERROR:pldr_catalog_filter_invalid'),'invalid access mode fail closed'),
    array(array('category'=>'bogus'),array('ERROR:pldr_catalog_filter_invalid'),'unknown category fail closed'),
    array(array('q'=>array('First')),array('ERROR:pldr_catalog_filter_invalid'),'structured search term fail closed'),
    array(array('type'=>array('book')),array('ERROR:pldr_catalog_filter_invalid'),'structured document type fail closed'),
    array(array('category'=>array('library')),array('ERROR:pldr_catalog_filter_invalid'),'structured category fail closed'),
    array(array('language'=>array('en')),array('ERROR:pldr_catalog_filter_invalid'),'structured language fail closed'),
    array(array('author'=>array('John')),array('ERROR:pldr_catalog_filter_invalid'),'structured author cannot widen catalog'),
    array(array('source'=>array('Oxford')),array('ERROR:pldr_catalog_filter_invalid'),'structured publisher cannot widen catalog'),
    array(array('edition'=>array('Third')),array('ERROR:pldr_catalog_filter_invalid'),'structured edition cannot widen catalog'),
    array(array('license'=>array('CC-BY')),array('ERROR:pldr_catalog_filter_invalid'),'structured license cannot widen catalog'),
    array(array('isbn'=>array('978')),array('ERROR:pldr_catalog_filter_invalid'),'structured ISBN cannot widen catalog'),
    array(array('year'=>array('2026')),array('ERROR:pldr_catalog_filter_invalid'),'structured year cannot widen catalog'),
    array(array('access_mode'=>array('account')),array('ERROR:pldr_catalog_filter_invalid'),'structured audience cannot widen catalog'),
    array(array('cursor'=>array('spoof')),array('ERROR:pldr_catalog_filter_invalid'),'structured signed cursor fail closed'),
    array(array('page'=>array(1)),array('ERROR:pldr_catalog_filter_invalid'),'structured pagination page fail closed'),
    array(array('per_page'=>array(24)),array('ERROR:pldr_catalog_filter_invalid'),'structured page size fail closed'),
    array(array('author'=>false),array('ERROR:pldr_catalog_filter_invalid'),'boolean author fail closed'),
    array(array('q'=>null),array('ERROR:pldr_catalog_filter_invalid'),'null search query fail closed'),
    array(array('type'=>'bogus'),array('ERROR:pldr_catalog_filter_invalid'),'unknown type fail closed'),
);
foreach($cases as [$args,$expected,$description])$check($get_ids($args)===$expected,$description.' got '.json_encode($get_ids($args)));
$page=PLDR_Search::search(array('per_page'=>1));
$check(!empty($page['next_cursor']),'signed next cursor missing');
$check($get_ids(array('per_page'=>1,'cursor'=>$page['next_cursor'],'author'=>'John'))===array('ERROR:pldr_catalog_cursor'),'cursor replayed across unrelated filters');
$next=PLDR_Search::search(array('per_page'=>1,'cursor'=>$page['next_cursor']));
$check(!isset($next['error']),'signed next page unexpectedly rejected');
$check(!in_array(3,$get_ids(array()),true),'inaccessible document leaked');
$reader=(string)file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-reader.php');
$rest=(string)file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-rest.php');
foreach(array('author','source','edition','isbn','license','year','access_mode') as $key){
    $check(str_contains($reader,"name=\"{$key}\""),"HTML filter {$key} missing");
    $check(str_contains($rest,"'{$key}'=>array("),"REST filter {$key} missing");
}
$check(str_contains($reader,'class="pldr-pagination"')&&str_contains($reader,"$"."result['next_cursor']"),'public signed-pagination links missing');
echo "PASS R29: 20+ real catalog facet/rights/cursor fixture checks and REST/HTML assertions\n";
