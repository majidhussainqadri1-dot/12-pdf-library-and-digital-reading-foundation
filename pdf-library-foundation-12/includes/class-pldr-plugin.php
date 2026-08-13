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
        add_action('pldr_legacy_migration',array('PLDR_R21_Runtime_Guards','legacy_migration_guarded'));
        PLDR_Schema::schedule();
        PLDR_Future::hooks();
    }
    public function cron_schedules(array $schedules):array { if(!isset($schedules['pldr_five_minutes']))$schedules['pldr_five_minutes']=array('interval'=>300,'display'=>'Every five minutes (File 12)');return $schedules; }
    public function init():void { load_plugin_textdomain('pdf-library-digital-reading',false,dirname(plugin_basename(PLDR_FILE)).'/languages');$this->rewrites();add_shortcode('pldr_library',static fn()=>PLDR_Reader::library_html());add_shortcode('pldr_reading_workspace',static fn()=>PLDR_Reader::reading_dashboard_html());PLDR_Integrations::register_contracts();if('1'===get_option('pldr_rewrite_flush_needed')){flush_rewrite_rules(false);delete_option('pldr_rewrite_flush_needed');} }
    private function rewrites():void { add_rewrite_rule('^library/?$','index.php?pldr_route=library','top');add_rewrite_rule('^library/document/([a-f0-9\-]{36})/([^/]+)/?$','index.php?pldr_route=document&pldr_id=$matches[1]','top');add_rewrite_rule('^library/document/([a-f0-9\-]{36})/?$','index.php?pldr_route=document&pldr_id=$matches[1]','top');add_rewrite_rule('^library/read/([a-f0-9\-]{36})/?$','index.php?pldr_route=read&pldr_id=$matches[1]','top');add_rewrite_rule('^library/delivery/([A-Za-z0-9_-]{40,60})/?$','index.php?pldr_route=delivery&pldr_token=$matches[1]','top');add_rewrite_rule('^account/reading/?$','index.php?pldr_route=reading','top'); }
    public function query_vars(array $vars):array { $vars[]='pldr_route';$vars[]='pldr_id';$vars[]='pldr_token';return $vars; }

    public function template_redirect():void {
        $route=(string)get_query_var('pldr_route');if(!$route)return;
        if('delivery'===$route){PLDR_Access::deliver((string)get_query_var('pldr_token'));return;}
        if('reading'===$route&&!is_user_logged_in()){auth_redirect();return;}
        $public_id=(string)get_query_var('pldr_id');
        if(in_array($route,array('document','read'),true)&&!$this->preflight_document_route($public_id,$route)){
            $this->render_virtual($route,PLDR_Reader::document_html($public_id));return;
        }
        $content='';
        if('library'===$route)$content=PLDR_Reader::library_html();
        elseif('document'===$route)$content=PLDR_Reader::document_html($public_id);
        elseif('read'===$route)$content=PLDR_Reader::reader_html($public_id);
        elseif('reading'===$route)$content=PLDR_Reader::reading_dashboard_html();
        else return;
        if(false!==strpos($content,'class="pldr-state"')&&http_response_code()<400)status_header(503);
        $this->render_virtual($route,$content);
    }

    private function preflight_document_route(string $public_id,string $route):bool {
        global $wpdb;$wpdb->last_error='';
        $doc=PLDR_Core::document_by_public_id($public_id);
        if(''!==(string)$wpdb->last_error){status_header(503);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        if(!$doc){status_header(404);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        $wpdb->last_error='';$edition=PLDR_Core::current_edition((int)$doc['id']);
        if(''!==(string)$wpdb->last_error){status_header(503);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        if(!$edition){status_header(404);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        $wpdb->last_error='';$allowed=PLDR_Access::can_access_edition((int)$edition['id'],'read',get_current_user_id());
        if(''!==(string)$wpdb->last_error){status_header(503);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        if(!$allowed){status_header(404);header('X-Robots-Tag: noindex, nofollow, noarchive',true);return false;}
        if('document'===$route){
            $canonical=PLDR_Core::route_url('document',array('id'=>$public_id,'slug'=>(string)$doc['slug']));
            $requested_path=(string)wp_parse_url((string)($_SERVER['REQUEST_URI']??''),PHP_URL_PATH);
            $canonical_path=(string)wp_parse_url($canonical,PHP_URL_PATH);
            if($requested_path&&$canonical_path&&untrailingslashit($requested_path)!==untrailingslashit($canonical_path)){
                wp_safe_redirect($canonical,301);exit;
            }
        }
        return true;
    }

    private function native_nav_html(string $route):string {
        if(!in_array($route,array('library','reading'),true))return '';
        $home=home_url('/');$library=PLDR_Core::route_url('library');
        $links=array();
        if('reading'===$route)$links[]='<a href="'.esc_url($library).'">'.esc_html__('Back to PDF Library','pdf-library-digital-reading').'</a>';
        $links[]='<a href="'.esc_url($home).'">'.esc_html__('Home','pdf-library-digital-reading').'</a>';
        return '<nav class="pldr-local-nav pldr-native-route-nav" aria-label="'.esc_attr__('Page navigation','pdf-library-digital-reading').'">'.implode('',$links).'</nav>';
    }

    private function render_virtual(string $route,string $content):void {
        $nav=$this->native_nav_html($route);
        if(''!==$nav&&false!==strpos($content,'<main class="pldr-shell"'))$content=preg_replace('/(<main class="pldr-shell"[^>]*>)/','$1'.$nav,$content,1)?:$content;
        if('reading'===$route&&false===strpos($content,'class="pldr-card"')&&false===strpos($content,'class="pldr-state"')){
            $content=str_replace('</main>','<div class="pldr-empty"><h2>'.esc_html__('No private reading progress yet','pdf-library-digital-reading').'</h2><p>'.esc_html__('Open an eligible document and start reading; your private progress will appear here.','pdf-library-digital-reading').'</p></div></main>',$content);
        }
        try{$handled=(bool)apply_filters('pldr_shell_rendered',false,$route,$content);}catch(Throwable $e){$handled=false;PLDR_Core::audit('system',0,'shell_adapter_failed',array('route'=>sanitize_key($route),'provider_failure'=>true));}
        if($handled)exit;
        $status=http_response_code();if(!$status||$status<300)status_header(200);
        nocache_headers();
        $filtered_library='library'===$route&&(!empty($_GET['q'])||!empty($_GET['type'])||!empty($_GET['category'])||!empty($_GET['language'])||absint($_GET['page']??1)>1||!empty($_GET['cursor']));
        if(in_array($route,array('read','reading'),true)||$filtered_library||http_response_code()>=400)header('X-Robots-Tag: noindex, nofollow, noarchive',true);
        get_header();echo $content;get_footer();exit;
    }
    public function register_assets():void { wp_register_style('pldr-reader',PLDR_URL.'assets/reader.css',array(),PLDR_VERSION);wp_register_script('pldr-reader',PLDR_URL.'assets/reader.js',array(),PLDR_VERSION,true);wp_enqueue_style('pldr-reader'); }
}
