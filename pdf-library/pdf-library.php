<?php
/**
 * Plugin Name: PDF Library and Digital Reading Foundation
 * Plugin URI: https://www.sabrihomeopathy.com/
 * Description: Encrypted PDF publishing, public reading, discovery, private progress, bookmarks, notes, reactions, reports, and moderation.
 * Version: 0.1.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Dr. Allama Majid Hussain Sabri
 * License: GPL-2.0-or-later
 * Text Domain: pdf-library
 */
defined('ABSPATH')||exit;define('SPL_VERSION','0.1.0');define('SPL_FILE',__FILE__);define('SPL_DIR',plugin_dir_path(__FILE__));define('SPL_URL',plugin_dir_url(__FILE__));
require_once SPL_DIR.'includes/class-spl-helpers.php';require_once SPL_DIR.'includes/class-spl-activator.php';require_once SPL_DIR.'includes/class-spl-frontend.php';require_once SPL_DIR.'includes/class-spl-interactions.php';require_once SPL_DIR.'includes/class-spl-admin.php';require_once SPL_DIR.'includes/class-spl-privacy.php';require_once SPL_DIR.'includes/class-spl-seo.php';require_once SPL_DIR.'includes/class-spl-plugin.php';register_activation_hook(SPL_FILE,array('SPL_Activator','activate'));register_deactivation_hook(SPL_FILE,array('SPL_Activator','deactivate'));add_action('plugins_loaded',function(){(new SPL_Plugin())->run();},70);

