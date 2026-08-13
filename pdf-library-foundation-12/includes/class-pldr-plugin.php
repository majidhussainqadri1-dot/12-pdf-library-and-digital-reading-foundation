<?php

defined('ABSPATH') || exit;

final class PLDR_Plugin {
    private static ?self $instance=null;
    private bool $ran=false;
    public static function instance():self { if(!self::$instance)self::$instance=new self();return self::$instance; }
    public function run():void {
        if($this->ran)return;
        $this->ran=true;
        PLDR_Admin::hooks();
        PLDR_Privacy::hooks();
        add_action('admin_enqueue_scripts',array($this,'register_assets'));
        if(!PLDR_R21_Readiness::core_ready())return;
        add_filter('cron_schedules',array($this,'cron_schedules'));
        add_action('init',array($this,'init'));
        add_filter('query_vars',array($this,'query_vars'));
        add_action('template_redirect',array($this,'template_redirect'),1);
        add_action('rest_api_init',array('PLDR_REST','register'));
        add_action('wp_enqueue_scripts',array($this,'register_assets'));
        add_action('pldr_dispatch_outbox',array('PLDR_Integrations','dispatch_outbox'));
        add_action('pldr_cleanup_tokens',array('PLDR_Access','cleanup_tokens'));
        add_action('pldr_integrity_sample',static fn()=>PLDR_Health::integrity_sample(3));
        add_action('pldr_rights_expiry',array('PLDR_Rights','expire_rights'));
        add_action('pldr_generate_derivatives',array('PLDR_Ingest','generate_derivatives'),10,2);
        add_action('pldr_legacy_migration',array('PLDR_Schema','migrate_legacy_batch'));
        PLDR_Schema::schedule();
        PLDR_Future::hooks();
    }
    public function cron_schedules(array $schedules):array { if(!isset($schedules['pldr_five_minutes']))$schedules['pldr_five_minutes']=array('interval'=>300,'display'=>'Every five minutes (File 12)');return $schedules; }
    public function init():void { load_plugin_textdomain('pdf-library-digital-reading',false,dirname(plugin_basename(PLDR_FILE)).'/languages');$this->rewrites();add_shortcode('pldr_library',static fn()=>PLDR_Reader::library_html());add_shortcode('pldr_reading_workspace',static fn()=>PLDR_Reader::reading_dashboard_html());PLDR_Integrations::register_contracts();if('1'===get_option('pldr_rewrite_flush_needed')){flush_rewrite_rules(false);delete_option('pldr_rewrite_flush_needed');} }
    private function rewrites():void { add_rewrite_rule('^library/?$','index.php?pldr_route=library','top');add_rewrite_rule('^library/document/([a-f0-9\-]{36})/([^/]+)/?$','index.php?pldr_route=document&pldr_id=$matches[1]','top');add_rewrite_rule('^library/document/([a-f0-9\-]{36})/?$','index.php?pldr_route=document&pldr_id=$matches[1]','top');add_rewrite_rule('^library/read/([a-f0-9\-]{36})/?$','index.php?pldr_route=read&pldr_id=$matches[1]','top');add_rewrite_rule('^library/delivery/([A-Za-z0-9_-]{40,60})/?$','index.php?pldr_route=delivery&pldr_token=$matches[1]','top');add_rewrite_rule('^account/reading/?$','index.php?pldr_route=reading','top'); }
    public function query_vars(array $vars):array { $vars[]='pldr_route';$vars[]='pldr_id';$vars[]='pldr_token';return $vars; }
    public function template_redirect():void { $route=(string)get_query_var('pldr_route');if(!$route)return;if('delivery'===$route){PLDR_Access::deliver((string)get_query_var('pldr_token'));return;}if('reading'===$route&&!is_user_logged_in()){auth_redirect();return;}$content='';if('library'===$route)$content=PLDR_Reader::library_html();elseif('document'===$route)$content=PLDR_Reader::document_html((string)get_query_var('pldr_id'));elseif('read'===$route)$content=PLDR_Reader::reader_html((string)get_query_var('pldr_id'));elseif('reading'===$route)$content=PLDR_Reader::reading_dashboard_html();else return;$this->render_virtual($route,$content); }
    private function render_virtual(string $route,string $content):void { try{$handled=(bool)apply_filters('pldr_shell_rendered',false,$route,$content);}catch(Throwable $e){$handled=false;PLDR_Core::audit('system',0,'shell_adapter_failed',array('route'=>sanitize_key($route),'provider_failure'=>true));}if($handled)exit;status_header(200);nocache_headers();if(in_array($route,array('read','reading'),true)){header('X-Robots-Tag: noindex, nofollow, noarchive',true);}get_header();echo $content;get_footer();exit; }
    public function register_assets():void { wp_register_style('pldr-reader',PLDR_URL.'assets/reader.css',array(),PLDR_VERSION);wp_register_script('pldr-reader',PLDR_URL.'assets/reader.js',array(),PLDR_VERSION,true);wp_enqueue_style('pldr-reader'); }
}
