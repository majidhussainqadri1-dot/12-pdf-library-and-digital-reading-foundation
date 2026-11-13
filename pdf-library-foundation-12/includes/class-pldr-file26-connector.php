<?php
/**
 * File12 rights-aware public PDF catalog source for File26 — PROPOSED ONLY.
 *
 * Search must never mint download grants, expose OCR/corpus, annotations,
 * protected book packs, user reading history, or infer rights from metadata.
 * Rights+policy versioned tombstone reconciliation remains a launch blocker.
 */
defined('ABSPATH') || exit;
final class PLDR_File26_Connector {
    const SLUG='file12-public-pdfs';
    /** Cannot release a proposed connector without a versioned tombstone/event bridge. */
    private static function cutover_approved():bool {
        $m=self::manifest();
        return ($m['status']??'')==='approved'
            && ($m['deletion_semantics']??'')==='versioned_rights_tombstones_verified'
            && ($m['event_contract']??'')==='PDFDocumentAccessChanged.v1';
    }
    public static function register(){
        if(!self::cutover_approved())return false;
        add_filter('sabri_file26_connector_manifests',array(__CLASS__,'append'),20);
        add_action('sabri_file26_connectors_ready',array(__CLASS__,'ready'),20);
        if(function_exists('sabri_file26_register_connector')){sabri_file26_register_connector(self::manifest());}
        return true;
    }
    public static function manifest(){
        return array(
            'slug'=>self::SLUG,'owner_file'=>'File 12','contract_version'=>'1.0',
            'entity_types'=>array('pdf','book_pack'),
            'privacy_classes'=>array('public'),'visibility_fields'=>array('public'),
            'deletion_semantics'=>'rights_and_policy_versioned_tombstone_gate_pending',
            'status'=>'proposed',
            'event_contract'=>'PDFDocumentAccessChanged.v1.pending-file26-cutover',
            'index_schema'=>'sabri.file26.document.v1.1',
            'list_batch'=>array(__CLASS__,'list_batch'),
            'can_view'=>array(__CLASS__,'can_view'),
            'fetch_object'=>array(__CLASS__,'fetch_object'),
            'health'=>array(__CLASS__,'health')
        );
    }
    public static function append($manifests){
        $manifests=is_array($manifests)?$manifests:array();
        if(!self::cutover_approved())return $manifests;
        foreach($manifests as $item){if(is_array($item)&&($item['slug']??'')===self::SLUG){return $manifests;}}
        $manifests[]=self::manifest();return $manifests;
    }
    public static function ready($registry){
        if(!self::cutover_approved())return false;
        if(is_object($registry)&&method_exists($registry,'register')){$registry->register(self::manifest());}
    }
    private static function source_ready(){
        if(!class_exists('PLDR_Core')||!class_exists('PLDR_Access')||!class_exists('PLDR_Rights_Policy')||
            !defined('PLDR_DB_VERSION')||version_compare((string)get_option('pldr_db_version','0'),PLDR_DB_VERSION,'<')){return false;}
        global $wpdb;
        foreach(array('documents','editions','access_policies','objects','rights_cases') as $part){
            $table=PLDR_Core::table($part);$wpdb->last_error='';
            if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table||$wpdb->last_error){return false;}
        }
        return true;
    }
    private static function project($doc,$known_version=0){
        if(!self::source_ready()||!is_array($doc)||($doc['status']??'')!=='published'||!isset($doc['id'],$doc['public_id'],$doc['version'])){return null;}
        $doc_id=(int)$doc['id'];$version=(int)$doc['version'];
        if($doc_id<1||$version<1||($known_version>0&&$known_version!==$version)){return null;}
        $edition=PLDR_Core::current_edition($doc_id);
        $policy=PLDR_Core::policy($doc_id);
        if(!is_array($edition)||($edition['status']??'')!=='published'||!is_array($policy)||
            ($policy['audience']??'')!=='public'){return null;}
        $rights=PLDR_Rights_Policy::check($doc_id);
        if(is_wp_error($rights)||false===$rights){return null;}
        // Explicit -1 asks the canonical File12 access controller for public/
        // anonymous permission even if the executing WP-CLI user is an admin.
        if(!PLDR_Access::can_access_edition((int)$edition['id'],'read',-1)){return null;}
        $dto=PLDR_Core::public_document_dto($doc,$edition,$policy);
        if(!is_array($dto)||($dto['status']??'')!=='published'||($dto['access_mode']??'')!=='public'){return null;}
        $url=PLDR_Core::route_url('document',array('id'=>(string)$dto['id'],'slug'=>(string)$dto['slug']));
        if(!$url||!wp_http_validate_url($url)){return null;}
        $title=sanitize_text_field((string)($dto['title']??''));
        if($title===''){return null;}
        $author=sanitize_text_field((string)($dto['edition']['author']??''));
        return array(
            'connector_slug'=>self::SLUG,'domain'=>'pdf_library',
            'object_id'=>(string)$dto['id'],'object_version'=>$version,'source_event_sequence'=>$version,
            'entity_type'=>'pdf','locale'=>substr(sanitize_text_field((string)($dto['language']??'en-US')),0,20),
            'state'=>'published','visibility'=>'public','title'=>$title,
            'excerpt'=>$author,'search_text'=>$title.' '.$author,
            'canonical_url'=>$url,'safety_class'=>'rights_reviewed_public_catalog'
        );
    }
    public static function list_batch($cursor='',$limit=50,$scope=array()){
        global $wpdb;
        if(!self::source_ready()){return new WP_Error('file12_search_schema_unverified','Library database and rights policy must be verified.');}
        if(!is_array($scope)||$scope!==array()){return new WP_Error('file12_search_partial_scope','Only an explicitly empty full-catalog scope is supported.');}
        if(!is_int($limit)||$limit<1){return new WP_Error('file12_search_limit_invalid','Catalog batch limit must be a positive integer.');}
        if($cursor!==''&&(!is_string($cursor)||!preg_match('/^[0-9]{1,18}$/D',$cursor))){return new WP_Error('file12_search_cursor_invalid','Invalid catalog cursor.');}
        $table=PLDR_Core::table('documents');$limit=min(50,$limit);$current=(int)$cursor;
        $wpdb->last_error='';
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE id>%d ORDER BY id ASC LIMIT %d",$current,$limit+1),ARRAY_A);
        if(!is_array($rows)||$wpdb->last_error){return new WP_Error('file12_search_catalog_unreadable','PDF catalog could not be scanned.');}
        $more=count($rows)>$limit;if($more){array_pop($rows);}
        $items=array();
        foreach($rows as $row){$doc=self::project($row,(int)$row['version']);if($doc){$items[]=$doc;}$current=max($current,(int)$row['id']);}
        return array('items'=>$items,'done'=>!$more,'next_cursor'=>$more?(string)$current:'');
    }
    public static function fetch_object($type,$public_id=''){
        if($type!=='pdf'||!is_string($public_id)||!preg_match('/^[a-f0-9-]{36}$/iD',$public_id)||!self::source_ready()){return null;}
        $doc=PLDR_Core::document_by_public_id($public_id);
        return $doc?self::project($doc):null;
    }
    public static function can_view($document,$audience=array()){
        if(!is_array($document)||($document['connector_slug']??'')!==self::SLUG||
            ($document['visibility']??'')!=='public'){return false;}
        $version=$document['object_version']??null;
        if(!is_int($version)||$version<1)return false;
        $fresh=self::fetch_object((string)($document['entity_type']??''),(string)($document['object_id']??''));
        return is_array($fresh)&&is_int($fresh['object_version'])&&$fresh['object_version']===$version;
    }
    public static function health(){
        return self::source_ready()
            ? array('state'=>'healthy','owner_file'=>'File 12','rights_checked'=>true,'status'=>'proposed','policy_event_gate_approved'=>false)
            : array('state'=>'degraded','owner_file'=>'File 12','reason'=>'library_schema_or_rights_policy_unverified');
    }
}
