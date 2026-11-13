<?php
/** Source-only File26 integration: public catalog is proposed, with no runtime registration. */
declare(strict_types=1);
define('ABSPATH',__DIR__);
define('PLDR_DB_VERSION','1.1.0');
define('ARRAY_A','ARRAY_A');
class WP_Error { public string $code; public function __construct($code,$message=''){ $this->code=(string)$code; } }
class PLDR_Core {
    public static function table($part){return 'wp_pldr_'.$part;}
    public static function current_edition($id){return $GLOBALS['edition'];}
    public static function policy($id){return $GLOBALS['policy'];}
    public static function public_document_dto($doc,$edition,$policy){
        return array('id'=>$doc['public_id'],'status'=>'published','access_mode'=>'public',
            'slug'=>'original-book','title'=>$doc['title'],'language'=>'en-US',
            'edition'=>array('author'=>'Verified Author'));
    }
    public static function document_by_public_id($id){return $GLOBALS['doc'];}
    public static function route_url($type,$args){return 'https://example.org/library/document/'.$args['id'];}
}
class PLDR_Access { public static function can_access_edition($id,$operation='read',$u=-1){return $GLOBALS['public_read'];} }
class PLDR_Rights_Policy {
    public static function check($id){return $GLOBALS['rights_on']?true:new WP_Error('rights_denied');}
}
function get_option($name,$default=null){return $GLOBALS['options'][$name]??$default;}
function wp_http_validate_url($u){return str_starts_with($u,'https://');}
function sanitize_text_field($text){return trim(strip_tags((string)$text));}
function is_wp_error($x){return $x instanceof WP_Error;}
function must($ok,$label){$GLOBALS['checks']++;if(!$ok){fwrite(STDERR,"FAIL {$label}\n");exit(1);}}
$wpdb=new class {
    public string $last_error='';
    public function esc_like($s){return $s;}
    public function prepare($sql,...$args){return vsprintf($sql,$args);}
    public function get_var($sql){
        if(!preg_match('/LIKE (wp_pldr_[a-z_]+)/',$sql,$m))return false;
        return $m[1];
    }
    public function get_results($sql,$format){
        return $GLOBALS['rows'];
    }
};
$uuid='aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa';
$GLOBALS['checks']=0;
$GLOBALS['options']=array('pldr_db_version'=>'1.1.0');
$GLOBALS['public_read']=true;$GLOBALS['rights_on']=true;
$GLOBALS['doc']=array('id'=>17,'status'=>'published','public_id'=>$uuid,'title'=>'Approved historical text','version'=>6);
$GLOBALS['rows']=array($GLOBALS['doc']);
$GLOBALS['edition']=array('id'=>31,'status'=>'published','document_status'=>'published');
$GLOBALS['policy']=array('audience'=>'public');
require dirname(__DIR__).'/pdf-library-foundation-12/includes/class-pldr-file26-connector.php';
function add_filter($name,$handler,$priority=10){$GLOBALS['registrations']++;}
function add_action($name,$handler,$priority=10){$GLOBALS['registrations']++;}
function sabri_file26_register_connector($manifest){$GLOBALS['registrations']++;}
$GLOBALS['registrations']=0;
must(PLDR_File26_Connector::register()===false,'Proposed connector must refuse explicit registration');
must($GLOBALS['registrations']===0,'No File26 hook or direct registry side effects');
must(PLDR_File26_Connector::append(array('existing'))===array('existing'),'Pending manifest not advertised');
$registry=new class{public function register($x){$GLOBALS['registrations']++;}};
must(PLDR_File26_Connector::ready($registry)===false&&$GLOBALS['registrations']===0,'Ready hook cannot register proposed connector');
$manifest=PLDR_File26_Connector::manifest();
// R54 expected RED: a proposed-only adapter may not claim unimplemented book packs
// or advertise healthy operational state before its rights/tombstone cutover.
must($manifest['entity_types']===array('pdf'),'File26 catalog manifest must not claim unsupported book_pack entity type');
must(PLDR_File26_Connector::health()['state']==='disabled','Proposed unregistered File26 connector must not report healthy operational state');
must($manifest['status']==='proposed'&&str_contains($manifest['deletion_semantics'],'pending'),
    'Cutover metadata cannot claim release readiness');
$batch=PLDR_File26_Connector::list_batch('',50,array());
must(is_array($batch)&&count($batch['items'])===1,'Public approved edition is a catalog candidate');
$entry=$batch['items'][0];
must($entry['visibility']==='public'&&$entry['object_id']===$uuid,'Canonical public ID');
foreach(array('ocr','reading_history','annotations','download_url','corpus','book_packs') as $secret){
    must(!array_key_exists($secret,$entry),'No protected or private fields');
}
must(PLDR_File26_Connector::can_view($entry,array())===true,'Public result revalidates against current owner');
$GLOBALS['rights_on']=false;
must(PLDR_File26_Connector::list_batch('',50,array())['items']===array(),'Rights withdrawal removes source candidate');
must(PLDR_File26_Connector::can_view($entry,array())===false,'Rights withdrawal denies stale index result');
$GLOBALS['rights_on']=true;
$GLOBALS['policy']['audience']='account';
must(PLDR_File26_Connector::list_batch('',50,array())['items']===array(),'Private audience denied');
$GLOBALS['policy']['audience']='public';$GLOBALS['public_read']=false;
must(PLDR_File26_Connector::list_batch('',50,array())['items']===array(),'Anonymous rights denied');
$GLOBALS['public_read']=true;
$GLOBALS['doc']['version']=7;
must(PLDR_File26_Connector::can_view($entry,array())===false,'Stale document version denied');
$GLOBALS['rows']=array($GLOBALS['doc']);
must(PLDR_File26_Connector::list_batch('nope',50,array()) instanceof WP_Error,'Malformed cursor denied');
must(PLDR_File26_Connector::list_batch('',50,array('object_id'=>$uuid)) instanceof WP_Error,'Unapproved partial scope denied');
must(PLDR_File26_Connector::list_batch('',50,array('topic'=>'homeopathy')) instanceof WP_Error,'Unknown partial scope cannot expand into full snapshot');
foreach(array(false,'50',50.1,array(50),0,-1) as $badLimit){
    must(PLDR_File26_Connector::list_batch('',$badLimit,array()) instanceof WP_Error,'Wrong-typed/nonpositive limit denied');
}
$malformed=$entry;$malformed['object_version']='6junk';
must(PLDR_File26_Connector::can_view($malformed,array())===false,'Invalid stored index version cannot coerce to current version');
$malformed=$entry;$malformed['object_version']=6.5;
must(PLDR_File26_Connector::can_view($malformed,array())===false,'Fractional stored index version denied');
$GLOBALS['options']['pldr_db_version']='0';
must(PLDR_File26_Connector::health()['state']==='degraded','Stale DB schema fails closed');
$bootstrap=file_get_contents(dirname(__DIR__).'/pdf-library-foundation-12/pdf-library-digital-reading.php');
must(strpos($bootstrap,'PLDR_File26_Connector::register()')===false,
    'Proposed File26 adapter must NOT be registered before rights/tombstone cutover approval');
echo "R51 File26 proposed-only catalog: {$GLOBALS['checks']} isolated checks PASS.\n";
