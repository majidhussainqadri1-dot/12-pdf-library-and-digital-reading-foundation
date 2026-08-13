<?php

defined('ABSPATH') || exit;

/** Release-readiness observability for R20 schema and migration corrections. */
final class PLDR_Operations_Policy {
    private static bool $hooked=false;

    public static function hooks():void {
        if(self::$hooked)return;self::$hooked=true;
        add_filter('rest_pre_dispatch',array(__CLASS__,'health_projection'),12,3);
        add_action('admin_notices',array(__CLASS__,'admin_notice'),20);
    }

    public static function health_projection($result,$server,WP_REST_Request $request) {
        if(null!==$result||'/pldr/v1/health'!==(string)$request->get_route()||'GET'!==$request->get_method())return $result;
        if(!PLDR_Core::authorize('manage'))return null;
        $report=PLDR_Health::report();
        $current=PLDR_Schema_Corrections::is_current();
        $report['checks']['schema_corrections']=array(
            'status'=>$current?'ok':'blocked',
            'required_revision'=>PLDR_Schema_Corrections::revision(),
            'applied_revision'=>(string)get_option('pldr_schema_corrections_revision',''),
            'transaction_engine'=>'InnoDB',
            'outbox_last_error_nullable'=>true,
        );
        if(!$current){
            $report['status']='blocked';
            $report['blockers'][]='File 12 verified schema-correction revision is not current; transaction-engine/outbox compatibility must be reconciled before release acceptance.';
            $report['blockers']=array_values(array_unique($report['blockers']));
        }
        return rest_ensure_response($report);
    }

    public static function admin_notice():void {
        if(!current_user_can('manage_pdf_library')||PLDR_Schema_Corrections::is_current())return;
        echo '<div class="notice notice-error"><p><strong>'.esc_html__('File 12 schema correction gate is incomplete.','pdf-library-digital-reading').'</strong> '.esc_html(sprintf(__('Required revision: %s. Run schema reconciliation and verify database health before staging or live acceptance.','pdf-library-digital-reading'),PLDR_Schema_Corrections::revision())).'</p></div>';
    }
}
