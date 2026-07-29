<?php
defined('ABSPATH') || exit;

final class SPL_Plugin {
    public function run() {
        add_action('init', array('SPL_Activator', 'register'));
        add_action('admin_init', array('SPL_Activator', 'maybe_upgrade'));
        (new SPL_Frontend())->hooks();
        (new SPL_Interactions())->hooks();
        (new SPL_Admin())->hooks();
        (new SPL_Privacy())->hooks();
        (new SPL_SEO())->hooks();
        add_action('wp_enqueue_scripts', array($this, 'assets'));
        add_action('template_redirect', array($this, 'headers'));
    }

    public function assets() {
        global $post;
        $map = (array) get_option('spl_page_map', array());
        if (!is_singular(SPL_Helpers::TYPE) && !($post instanceof WP_Post && in_array($post->ID, array_map('absint', $map), true))) {
            return;
        }
        wp_enqueue_style('spl', SPL_URL . 'assets/library.css', array(), SPL_VERSION);
        wp_enqueue_script('spl', SPL_URL . 'assets/library.js', array(), SPL_VERSION, true);
        wp_localize_script('spl', 'splData', array(
            'url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('spl_action'),
        ));
    }

    public function headers() {
        $map = (array) get_option('spl_page_map', array());
        foreach (array('upload', 'saved', 'reading') as $key) {
            if (!empty($map[$key]) && is_page($map[$key])) {
                nocache_headers();
                header('X-Robots-Tag: noindex, noarchive', true);
                return;
            }
        }
    }
}
