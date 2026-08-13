<?php

defined('ABSPATH') || exit;

/**
 * Publication/restoration eligibility policy shared by REST and wp-admin entrypoints.
 * This is a read-only guard; PLDR_Rights remains the only rights-state writer.
 */
final class PLDR_Rights_Policy {
    private static bool $hooked=false;

    public static function hooks():void {
        if(self::$hooked)return;
        self::$hooked=true;
        add_filter('rest_pre_dispatch',array(__CLASS__,'rest_guard'),5,3);
        add_action('admin_post_pldr_approve_document',array(__CLASS__,'admin_approve_guard'),1);
        add_action('admin_post_pldr_rights_decision',array(__CLASS__,'admin_decision_guard'),1);
    }

    public static function rest_guard($result,$server,WP_REST_Request $request) {
        if(null!==$result)return $result;
        $route=(string)$request->get_route();
        if(preg_match('#^/pldr/v1/documents/(?P<id>[a-f0-9\-]{36})/approve$#',$route,$m)){
            global $wpdb;$wpdb->last_error='';
            $doc=PLDR_Core::document_by_public_id((string)$m['id']);
            if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_publication_guard_document_read','Publication eligibility could not be verified reliably.',503,array('degraded'=>true));
            if(!$doc)return PLDR_Core::machine_error('pldr_document_missing','Document not found.',404);
            $check=self::check((int)$doc['id']);
            return is_wp_error($check)?$check:null;
        }
        if(preg_match('#^/pldr/v1/rights/cases/(?P<id>\d+)/decision$#',$route,$m)&&'restore'===sanitize_key((string)$request['decision'])){
            $document_id=self::case_document((int)$m['id']);
            if(is_wp_error($document_id))return $document_id;
            $check=self::check($document_id);
            return is_wp_error($check)?$check:null;
        }
        return null;
    }

    public static function admin_approve_guard():void {
        $document_id=absint($_POST['document_id']??0);
        $check=self::check($document_id);
        if(is_wp_error($check))wp_die(esc_html($check->get_error_message()),array('response'=>(int)($check->get_error_data()['status']??409)));
    }

    public static function admin_decision_guard():void {
        if('restore'!==sanitize_key((string)($_POST['decision']??'')))return;
        $document_id=self::case_document(absint($_POST['case_id']??0));
        if(is_wp_error($document_id))wp_die(esc_html($document_id->get_error_message()),array('response'=>(int)($document_id->get_error_data()['status']??409)));
        $check=self::check($document_id);
        if(is_wp_error($check))wp_die(esc_html($check->get_error_message()),array('response'=>(int)($check->get_error_data()['status']??409)));
    }

    private static function case_document(int $case_id) {
        global $wpdb;$wpdb->last_error='';
        $document_id=(int)$wpdb->get_var($wpdb->prepare('SELECT document_id FROM '.PLDR_Core::table('rights_cases').' WHERE id=%d',$case_id));
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_publication_guard_case_read','Rights-case publication eligibility could not be verified reliably.',503,array('degraded'=>true));
        if($document_id<1)return PLDR_Core::machine_error('pldr_case_missing','Rights case not found.',404);
        return $document_id;
    }

    public static function check(int $document_id) {
        global $wpdb;
        if($document_id<1)return PLDR_Core::machine_error('pldr_publication_guard_document','A valid document is required for publication eligibility.',400);
        $wpdb->last_error='';$edition=PLDR_Core::latest_edition($document_id);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_publication_guard_edition_read','Publication rights evidence could not be read reliably.',503,array('degraded'=>true));
        if(!$edition)return PLDR_Core::machine_error('pldr_publication_guard_edition','A document edition is required before publication or restoration.',409);
        foreach(array('author_name','source_name','rights_basis','sha256') as $field){
            if(''===trim((string)($edition[$field]??'')))return PLDR_Core::machine_error('pldr_publication_guard_metadata','Publication/restoration requires complete rights and source evidence.',409,array('field'=>$field));
        }
        $basis=sanitize_key((string)$edition['rights_basis']);
        if(!in_array($basis,array('founder-owned','owned','permission','public-domain','open-license','licensed'),true))return PLDR_Core::machine_error('pldr_publication_guard_rights_basis','The current edition has no approved publication-rights basis.',409);
        if(!empty($edition['rights_expires_at'])){
            $expires=strtotime((string)$edition['rights_expires_at']);
            if(false===$expires)return PLDR_Core::machine_error('pldr_publication_guard_rights_expiry','The current edition rights-expiry value is invalid; publication is blocked until reconciled.',409,array('degraded'=>true));
            if($expires<=time())return PLDR_Core::machine_error('pldr_publication_guard_rights_expired','The current edition publication rights have expired; renew the rights evidence before publishing or restoring access.',409,array('rights_expired'=>true));
        }
        $wpdb->last_error='';$object=PLDR_Core::object((int)$edition['object_id']);
        if(''!==(string)$wpdb->last_error)return PLDR_Core::machine_error('pldr_publication_guard_object_read','Publication object state could not be read reliably.',503,array('degraded'=>true));
        if(!$object||'available'!==(string)$object['object_status']||'clean'!==(string)$object['scan_status'])return PLDR_Core::machine_error('pldr_publication_guard_object','Publication/restoration requires a clean available encrypted object.',409);
        return true;
    }
}
