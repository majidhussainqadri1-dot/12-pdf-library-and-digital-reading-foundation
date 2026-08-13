<?php
/**
 * Plugin Name: Sabri PDF Library and Digital Reading
 * Plugin URI: https://sabrihomeopathy.com/
 * Description: File 12 canonical PDF library, rights-aware digital reader, private reading state, signed range delivery, OCR/search contracts, book packs, preservation, Future Digital Reading Intelligence 24, and cross-file integrations.
 * Version: 1.1.0-rc.1
 * Requires at least: 7.0
 * Requires PHP: 8.1
 * Author: Dr. Allamah Majid Hussain Sabri Muhaddith Mursheed
 * License: GPL-2.0-or-later
 * Text Domain: pdf-library-digital-reading
 */

defined('ABSPATH') || exit;

define('PLDR_VERSION', '1.1.0-rc.1');
define('PLDR_DB_VERSION', '1.1.0');
define('PLDR_CONTRACT_VERSION', '1.1.0');
define('PLDR_FILE', __FILE__);
define('PLDR_DIR', plugin_dir_path(__FILE__));
define('PLDR_URL', plugin_dir_url(__FILE__));

$pldr_files = array(
    'class-pldr-core.php',
    'class-pldr-schema.php',
    'class-pldr-storage.php',
    'class-pldr-crypto.php',
    'class-pldr-object-integrity.php',
    'class-pldr-integrity-policy.php',
    'class-pldr-r20-guards.php',
    'class-pldr-r21-readiness.php',
    'class-pldr-ingest.php',
    'class-pldr-access.php',
    'class-pldr-reader.php',
    'class-pldr-rights.php',
    'class-pldr-rights-policy.php',
    'class-pldr-rest.php',
    'class-pldr-ocr-search-overlay.php',
    'class-pldr-response-policy.php',
    'class-pldr-schema-corrections.php',
    'class-pldr-operations-policy.php',
    'class-pldr-admin.php',
    'class-pldr-privacy.php',
    'class-pldr-privacy-extension.php',
    'class-pldr-future.php',
    'class-pldr-future-rest.php',
    'class-pldr-plugin.php',
);

foreach ($pldr_files as $pldr_file) {
    require_once PLDR_DIR . 'includes/' . $pldr_file;
}

register_activation_hook(PLDR_FILE, array('PLDR_Schema', 'activate'));
register_deactivation_hook(PLDR_FILE, array('PLDR_Schema', 'deactivate'));
register_deactivation_hook(PLDR_FILE, array('PLDR_Future', 'deactivate'));

add_action('plugins_loaded', static function (): void {
    PLDR_R20_Guards::hooks();
    PLDR_R21_Readiness::hooks();
    PLDR_Response_Policy::hooks();
    PLDR_OCR_Search_Overlay::hooks();
    PLDR_Integrity_Policy::hooks();
    PLDR_Schema_Corrections::hooks();
    PLDR_Rights_Policy::hooks();
    PLDR_Privacy_Extension::hooks();
    PLDR_Operations_Policy::hooks();
    PLDR_Plugin::instance()->run();
}, 60);
