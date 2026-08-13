<?php

defined('ABSPATH') || exit;

/** Final R20 cross-cutting guards for repair replay safety and admin preauthorization. */
final class PLDR_R20_Guards {
    private static bool $hooked=false;

    public static function hooks():void {
        if(self::$hooked)return;self::$hooked=true;
        add_filter('rest_pre_dispatch',array(__CLASS__,'repair_idempotency_guard'),6,3);
        add_action('admin_post_pldr_approve_document',array(__CLASS__,'approve_admin_guard'),0);
        add_action('admin_post_pldr_rights_decision',array(__CLASS__,'rights_admin_guard'),0);
    }

    public static function approve_admin_guard():void {
        $document_id=absint($_POST['document_id']??0);
        if($document_id<1||(!PLDR_Core::authorize('rights',$document_id)&&!PLDR_Core::authorize('manage',$document_id)))wp_die('Denied',array('response'=>403));
        check_admin_referer('pldr_approve_document_'.$document_id);
    }

    public static function rights_admin_guard():void {
        $case_id=absint($_POST['case_id']??0);
        if($case_id<1||(!PLDR_Core::authorize('rights')&&!PLDR_Core::authorize('manage')))wp_die('Denied',array('response'=>403));
        check_admin_referer('pldr_rights_decision_'.$case_id);
    }

    public static function repair_idempotency_guard($result,$server,WP_REST_Request $request) {
        if(null!==$result||'/pldr/v1/repair'!==(string)$request->get_route()||'POST'!==$request->get_method())return $result;
        $operation=sanitize_key((string)$request['operation']);
        if(!in_array($operation,array('integrity-sample','rotate-keys'),true))return null;
        if(!PLDR_Core::authorize('repair'))return PLDR_Core::machine_error('pldr_repair_forbidden','Repair authority is required.',403);

        $key=substr(sanitize_text_field((string)$request->get_header('Idempotency-Key')),0,200);
        if(''===$key)return PLDR_Core::machine_error('pldr_idempotency_required','This mutation requires an Idempotency-Key.',428);
        $actor=get_current_user_id();
        $request_hash=PLDR_Core::request_fingerprint($request);
        if(''===$request_hash)return PLDR_Core::machine_error('pldr_idempotency_fingerprint','The repair request could not be fingerprinted safely; it was not executed.',503);
        $claim=PLDR_Core::idempotency_begin('repair',$key,$actor,$request_hash);
        if('hit'===($claim['state']??''))return new WP_REST_Response($claim['body'],$claim['status']);
        if('conflict'===($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_conflict','This Idempotency-Key was already used for a different repair payload.',409);
        if('pending'===($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_in_progress','A repair with this Idempotency-Key is already in progress.',409,array('retry_after'=>2));
        if('reserved'!==($claim['state']??''))return PLDR_Core::machine_error('pldr_idempotency_unavailable','Idempotency protection could not be reserved; the repair was not executed.',503);

        $rate=PLDR_Core::consume_mutation_rate('core-repair',$actor,600);
        if(is_wp_error($rate)){
            if(!PLDR_Core::idempotency_abort('repair',$key,$actor))PLDR_Core::audit('mutation',0,'idempotency_abort_after_rate_failure',array('route'=>'repair'),$actor);
            return $rate;
        }
        try {
            $value='rotate-keys'===$operation?PLDR_Integrity_Policy::rotate_keys(10):PLDR_Integrity_Policy::integrity_sample(10);
        } catch(Throwable $e) {
            PLDR_Core::idempotency_abort('repair',$key,$actor);
            PLDR_Core::audit('mutation',0,'repair_mutation_exception',array('operation'=>$operation),$actor);
            return PLDR_Core::machine_error('pldr_mutation_exception','The repair could not be completed safely. Retry is allowed after reconciliation.',500,array('retry_safe'=>true));
        }
        if(is_array($value)&&isset($value['error'])&&is_wp_error($value['error']))$value=$value['error'];
        $response=is_wp_error($value)?rest_convert_error_to_response($value):rest_ensure_response($value);
        $status=$response instanceof WP_REST_Response?$response->get_status():200;
        $body=$response instanceof WP_REST_Response?$response->get_data():$value;
        if(!PLDR_Core::idempotency_complete('repair',$key,$actor,$body,$status,$request_hash))return PLDR_Core::machine_error('pldr_idempotency_persist','The repair completed but its idempotency result could not be finalized; reconcile before retrying with a new key.',503,array('original_status'=>$status));
        return is_wp_error($value)?$value:$response;
    }
}
