<?php

defined('ABSPATH') || exit;

$pldr_future_files = array(
    'class-pldr-future-schema.php',
    'class-pldr-future-data.php',
    'class-pldr-future-derived-text.php',
    'class-pldr-future-anchors.php',
    'class-pldr-future-citations.php',
    'class-pldr-future-authority.php',
    'class-pldr-future-ocr-lab.php',
    'class-pldr-future-annotations.php',
    'class-pldr-future-iiif.php',
    'class-pldr-future-search.php',
    'class-pldr-future-preferences.php',
    'class-pldr-future-shelves.php',
    'class-pldr-future-insights.php',
    'class-pldr-future-handoff.php',
    'class-pldr-future-a11y.php',
    'class-pldr-future-rooms.php',
    'class-pldr-future-context.php',
    'class-pldr-future-corpus.php',
    'class-pldr-future-preservation.php',
    'class-pldr-future-fingerprint.php',
);
foreach ($pldr_future_files as $pldr_future_file) {
    $pldr_future_path = __DIR__ . '/' . $pldr_future_file;
    if (!is_readable($pldr_future_path)) {
        add_action('admin_notices', static function () use ($pldr_future_file): void {
            if (!current_user_can('manage_pdf_library')) return;
            echo '<div class="notice notice-error"><p><strong>File 12 Future-24 loader error:</strong> ' . esc_html($pldr_future_file) . ' is missing from the deployed package.</p></div>';
        });
        continue;
    }
    require_once $pldr_future_path;
}

final class PLDR_Future {
    public const VERSION = '1.1.0';
    public const FEATURES = array(
        'F12-FUT-001' => 'Advanced Reflow Reading Mode',
        'F12-FUT-002' => 'Read Aloud / Text-to-Speech Reader',
        'F12-FUT-003' => 'Smart Table of Contents & Outline Recovery',
        'F12-FUT-004' => 'Edition Comparison Laboratory',
        'F12-FUT-005' => 'Precise Scholarly Anchors',
        'F12-FUT-006' => 'Citation Export Center',
        'F12-FUT-007' => 'Global Bibliographic Authority Enrichment',
        'F12-FUT-008' => 'OCR Quality Laboratory',
        'F12-FUT-009' => 'Portable Annotation Standard',
        'F12-FUT-010' => 'IIIF Digital Library Interoperability',
        'F12-FUT-011' => 'Inside-Book Search Heatmap',
        'F12-FUT-012' => 'Encrypted Offline Reading Vault',
        'F12-FUT-013' => 'Ultra-Low-Bandwidth Reader',
        'F12-FUT-014' => 'Multiple Reading Layouts',
        'F12-FUT-015' => 'Personal Smart Shelves',
        'F12-FUT-016' => 'Private Reading Insights — Non-Gamified',
        'F12-FUT-017' => 'Cross-Device Reading Session Handoff',
        'F12-FUT-018' => 'Accessibility Quality Inspector',
        'F12-FUT-019' => 'Scholarly Reading Rooms',
        'F12-FUT-020' => 'Knowledge Context Sidebar',
        'F12-FUT-021' => 'AI-Ready Corpus Manifest',
        'F12-FUT-022' => 'Translation & Transliteration Overlay',
        'F12-FUT-023' => 'Digital Preservation Laboratory',
        'F12-FUT-024' => 'Visual Duplicate & Scan-Fingerprint Detection',
    );

    private static bool $hooked = false;

    public static function hooks(): void {
        if (self::$hooked) return;
        self::$hooked = true;

        $required_classes = array(
            'PLDR_Future_Schema','PLDR_Future_Data','PLDR_Future_Derived_Text','PLDR_Future_Anchors',
            'PLDR_Future_Citations','PLDR_Future_Authority','PLDR_Future_OCR_Lab','PLDR_Future_Annotations',
            'PLDR_Future_IIIF','PLDR_Future_Search','PLDR_Future_Preferences','PLDR_Future_Shelves',
            'PLDR_Future_Insights','PLDR_Future_Handoff','PLDR_Future_A11y','PLDR_Future_Rooms',
            'PLDR_Future_Context','PLDR_Future_Corpus','PLDR_Future_Preservation','PLDR_Future_Fingerprint',
        );
        $missing = array_values(array_filter($required_classes, static fn(string $class): bool => !class_exists($class)));
        if ($missing) {
            update_option('pldr_future_loader_error', array('missing_classes'=>$missing,'at'=>PLDR_Core::now()), false);
            return;
        }
        delete_option('pldr_future_loader_error');

        PLDR_Future_Schema::maybe_upgrade();
        add_action('rest_api_init', array('PLDR_Future_REST', 'register'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'assets'), 30);
        add_filter('pldr_interaction_controls_html', array(__CLASS__, 'reader_controls'), 20, 3);
        add_action('pldr_future_preservation_scan', array('PLDR_Future_Preservation', 'scheduled_scan'));
        add_action('pldr_future_cleanup', array(__CLASS__, 'cleanup'));
        add_action('wp_logout', array(__CLASS__, 'mark_vault_purge'));
        add_action('wp_enqueue_scripts', array(__CLASS__, 'vault_purge_asset'), 2);
        if (!wp_next_scheduled('pldr_future_preservation_scan')) {
            wp_schedule_event(time() + 300, 'daily', 'pldr_future_preservation_scan');
        }
        if (!wp_next_scheduled('pldr_future_cleanup')) {
            wp_schedule_event(time() + 600, 'daily', 'pldr_future_cleanup');
        }
        add_action('pldr_future_fingerprint_edition', array(__CLASS__, 'fingerprint_job'), 10, 2);
        add_action('pldr_generate_derivatives', array(__CLASS__, 'after_derivatives'), 40, 2);
        add_action('admin_notices', array(__CLASS__, 'schema_notice'));
        do_action('sabri_register_module_extension', array(
            'file' => 12,
            'version' => self::VERSION,
            'features' => array_keys(self::FEATURES),
            'ownership' => 'File 12 native reading/document enhancements only; companion domain data remains native.',
        ));
    }

    public static function after_derivatives(int $edition_id, int $cursor = 0): void {
        if (0 !== $cursor) return;
        if (!wp_next_scheduled('pldr_future_fingerprint_edition', array($edition_id))) {
            wp_schedule_single_event(time() + 90, 'pldr_future_fingerprint_edition', array($edition_id,0));
        }
    }


    public static function fingerprint_job(int $edition_id,int $attempt=0): void {
        $attempt=max(0,min(3,$attempt));
        $result=PLDR_Future_Fingerprint::compute_and_store($edition_id);
        $error=is_array($result)&&isset($result['error'])&&is_wp_error($result['error'])?$result['error']:null;
        if(!$error)return;
        PLDR_Core::audit('edition',$edition_id,'fingerprint_background_failed',array('attempt'=>$attempt,'error_code'=>$error->get_error_code()));
        if($attempt>=3)return;
        $delay=min(3600,60*(2**$attempt));
        wp_schedule_single_event(time()+$delay,'pldr_future_fingerprint_edition',array($edition_id,$attempt+1));
    }

    public static function cleanup(): void {
        global $wpdb;
        try{$retention=(int)apply_filters('pldr_private_reading_event_retention_days',365);}
        catch(Throwable $e){PLDR_Core::audit('system',0,'future_retention_policy_provider_failed',array('provider_failure'=>true));return;}
        $retention=max(30,min(730,$retention));
        $queries=array(
            'reading_events'=>$wpdb->prepare('DELETE FROM '.PLDR_Core::table('reading_events').' WHERE created_at<%s ORDER BY id ASC LIMIT 1000',gmdate('Y-m-d H:i:s',time()-$retention*DAY_IN_SECONDS)),
            'authority_cache'=>$wpdb->prepare('DELETE FROM '.PLDR_Core::table('authority_cache').' WHERE expires_at<%s ORDER BY id ASC LIMIT 1000',gmdate('Y-m-d H:i:s',time()-7*DAY_IN_SECONDS)),
            'room_contexts'=>$wpdb->prepare('DELETE FROM '.PLDR_Core::table('room_contexts').' WHERE status=%s AND created_at<%s ORDER BY id ASC LIMIT 1000','pending-provider',gmdate('Y-m-d H:i:s',time()-30*DAY_IN_SECONDS)),
        );
        $continuation=false;
        foreach($queries as $scope=>$sql){$affected=$wpdb->query($sql);if(false===$affected)PLDR_Core::audit('system',0,'future_cleanup_failed',array('scope'=>$scope,'db_error'=>substr((string)$wpdb->last_error,0,500)));elseif(1000===$affected)$continuation=true;}
        if($continuation)wp_schedule_single_event(time()+60,'pldr_future_cleanup');
    }

    public static function mark_vault_purge(): void {
        if (headers_sent()) return;
        setcookie('pldr_vault_purge', '1', array('expires'=>time()+DAY_IN_SECONDS,'path'=>COOKIEPATH ?: '/','domain'=>COOKIE_DOMAIN ?: '','secure'=>is_ssl(),'httponly'=>false,'samesite'=>'Lax'));
    }

    public static function vault_purge_asset(): void {
        if (empty($_COOKIE['pldr_vault_purge'])) return;
        wp_register_script('pldr-vault-purge', '', array(), PLDR_VERSION, true);
        wp_enqueue_script('pldr-vault-purge');
        $cookie = 'pldr_vault_purge=; Max-Age=0; Path=' . (COOKIEPATH ?: '/') . '; SameSite=Lax' . (is_ssl() ? '; Secure' : '') . (COOKIE_DOMAIN ? '; Domain=' . COOKIE_DOMAIN : '');
        $script = "(function(){var clear=function(){document.cookie=" . wp_json_encode($cookie) . ";};if(!window.indexedDB){clear();return;}var r=indexedDB.deleteDatabase('pldr-offline-v1');r.onsuccess=clear;r.onerror=function(){};r.onblocked=function(){};})();";
        wp_add_inline_script('pldr-vault-purge', $script);
    }

    public static function schema_notice(): void {
        if (!current_user_can('manage_pdf_library')) return;
        $loader = get_option('pldr_future_loader_error');
        if ($loader) {
            echo '<div class="notice notice-error"><p><strong>File 12 Future-24 package is incomplete.</strong> ' . esc_html(wp_json_encode($loader)) . '</p></div>';
        }
        $error = get_option('pldr_future_schema_error');
        if (!$error) return;
        echo '<div class="notice notice-error"><p><strong>File 12 Future-24 schema is not ready.</strong> ' . esc_html(wp_json_encode($error)) . '</p></div>';
    }

    public static function deactivate(): void {
        wp_clear_scheduled_hook('pldr_future_preservation_scan');
        wp_clear_scheduled_hook('pldr_future_fingerprint_edition');
        wp_clear_scheduled_hook('pldr_future_cleanup');
    }

    public static function assets(): void {
        if ((string) get_query_var('pldr_route') !== 'read') return;
        wp_register_style('pldr-future-24', PLDR_URL . 'assets/future-reader.css', array('pldr-reader'), PLDR_VERSION);
        wp_register_script('pldr-future-24', PLDR_URL . 'assets/future-reader.js', array('pldr-reader'), PLDR_VERSION, true);
        wp_register_script('pldr-future-24-scholar', PLDR_URL . 'assets/future-reader-scholar.js', array('pldr-future-24'), PLDR_VERSION, true);
        wp_register_script('pldr-future-24-personal', PLDR_URL . 'assets/future-reader-personal.js', array('pldr-future-24'), PLDR_VERSION, true);
        wp_register_script('pldr-future-24-vault', PLDR_URL . 'assets/future-reader-vault.js', array('pldr-future-24'), PLDR_VERSION, true);
        wp_enqueue_style('pldr-future-24');
        wp_enqueue_script('pldr-future-24');
        wp_enqueue_script('pldr-future-24-scholar');
        wp_enqueue_script('pldr-future-24-personal');
        wp_enqueue_script('pldr-future-24-vault');
        wp_add_inline_script('pldr-future-24', 'window.PLDR_FUTURE=' . wp_json_encode(array(
            'version' => self::VERSION,
            'rest' => esc_url_raw(rest_url('pldr/v1/future/')),
            'nonce' => wp_create_nonce('wp_rest'),
            'features' => self::FEATURES,
            'loggedIn' => is_user_logged_in(),
        )) . ';', 'before');
    }

    public static function reader_controls(string $html, int $edition_id, array $dto): string {
        if (!PLDR_Access::can_access_edition($edition_id, 'read', get_current_user_id())) return $html;
        ob_start();
        ?>
        <section class="pldr-f24" data-pldr-f24 data-edition="<?php echo absint($edition_id); ?>" aria-labelledby="pldr-f24-title">
            <div class="pldr-f24-head">
                <div><span class="pldr-kicker">Future Digital Reading Intelligence 24</span><h2 id="pldr-f24-title"><?php esc_html_e('Advanced reading tools', 'pdf-library-digital-reading'); ?></h2></div>
                <button type="button" data-f24-action="toggle-panel" aria-expanded="false"><?php esc_html_e('Open tools', 'pdf-library-digital-reading'); ?></button>
            </div>
            <div class="pldr-f24-panel" data-f24-panel hidden>
                <div class="pldr-f24-toolbar" role="toolbar" aria-label="<?php esc_attr_e('Advanced reader tools', 'pdf-library-digital-reading'); ?>">
                    <button type="button" data-f24-action="reflow">Reflow</button>
                    <button type="button" data-f24-action="read-aloud">Read aloud</button>
                    <button type="button" data-f24-action="outline">Smart outline</button>
                    <button type="button" data-f24-action="compare">Compare edition</button>
                    <button type="button" data-f24-action="anchor">Precise anchor</button>
                    <button type="button" data-f24-action="annotation-export">Annotations</button>
                    <button type="button" data-f24-action="citation-export">Citation export</button>
                    <button type="button" data-f24-action="heatmap">Search heatmap</button>
                    <button type="button" data-f24-action="ocr-lab">OCR quality</button>
                    <button type="button" data-f24-action="iiif">IIIF manifest</button>
                    <button type="button" data-f24-action="offline">Offline vault</button>
                    <button type="button" data-f24-action="shelf">Add to shelf</button>
                    <button type="button" data-f24-action="insights">Reading insights</button>
                    <button type="button" data-f24-action="room">Reading room</button>
                    <button type="button" data-f24-action="context">Knowledge context</button>
                    <button type="button" data-f24-action="translate">Translate / transliterate</button>
                    <button type="button" data-f24-action="a11y">Accessibility</button>
                </div>
                <div class="pldr-f24-preferences">
                    <label><?php esc_html_e('Layout', 'pdf-library-digital-reading'); ?>
                        <select data-f24-layout>
                            <option value="single">Single page</option>
                            <option value="continuous">Continuous</option>
                            <option value="spread-ltr">Two-page LTR</option>
                            <option value="spread-rtl">Two-page RTL</option>
                            <option value="horizontal">Horizontal swipe</option>
                            <option value="presentation">Presentation</option>
                        </select>
                    </label>
                    <label><?php esc_html_e('Text size', 'pdf-library-digital-reading'); ?><input type="range" min="90" max="180" value="110" data-f24-text-size></label>
                    <label><?php esc_html_e('Line height', 'pdf-library-digital-reading'); ?><input type="range" min="140" max="240" value="175" data-f24-line-height></label>
                    <label><?php esc_html_e('Column width', 'pdf-library-digital-reading'); ?><input type="range" min="45" max="100" value="82" data-f24-column-width></label>
                    <label><?php esc_html_e('Contrast', 'pdf-library-digital-reading'); ?><select data-f24-contrast><option value="default">Default</option><option value="high">High</option><option value="soft">Soft</option><option value="dark">Dark</option></select></label>
                    <label><input type="checkbox" data-f24-data-saver> <?php esc_html_e('Data Saver / text-first mode', 'pdf-library-digital-reading'); ?></label>
                </div>
                <div class="pldr-f24-workspace" data-f24-workspace aria-live="polite"></div>
                <div class="pldr-f24-reflow" data-f24-reflow hidden tabindex="0"></div>
                <aside class="pldr-f24-context" data-f24-context hidden></aside>
                <div class="pldr-f24-status" data-f24-status aria-live="polite"></div>
            </div>
        </section>
        <?php
        return $html . (string) ob_get_clean();
    }
}
