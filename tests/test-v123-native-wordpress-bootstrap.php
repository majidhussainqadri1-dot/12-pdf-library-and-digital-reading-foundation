<?php
/**
 * File12 V12.3 real WordPress+MySQL disposable bootstrap test.
 * Called by WP-CLI eval-file ONLY against synthetic GitHub Actions install.
 * NO patient records, PDF binaries, encryption keys or genuine credentials.
 * Cannot prove Hostinger Staging/Live readiness.
 */
declare(strict_types=1);
if(!defined('ABSPATH') || getenv('FILE12_CI_EPHEMERAL_DB')!=='yes'
   || getenv('CI')!=='true'
   || home_url('/')!=='http://file12-ci.invalid/'){
    fwrite(STDERR,"File12 native smoke refused: disposable CI environment guard not met.\n");
    exit(86);
}
function f12_native_assert(bool $condition,string $label):void{
  if(!$condition){fwrite(STDERR,"FILE12 DISPOSABLE FAIL: ".$label."\n");exit(1);}
  echo "PASS: ".$label."\n";
}
require_once ABSPATH.'wp-admin/includes/plugin.php';
f12_native_assert(is_plugin_active('pdf-library-foundation-12/pdf-library-digital-reading.php'),
  'candidate plugin is active in disposable WordPress');
f12_native_assert(defined('PLDR_VERSION') && PLDR_VERSION==='1.1.0-rc.43',
  'exact candidate code version loaded');
f12_native_assert(defined('PLDR_DB_VERSION') && PLDR_DB_VERSION==='1.1.0',
  'source schema version declaration loaded');
f12_native_assert(class_exists('PLDR_Core') && class_exists('PLDR_Access') &&
  class_exists('PLDR_Rights_Policy') && class_exists('PLDR_File26_Connector'),
  'required actual owner classes loaded');
global $wpdb;
$schemas=['documents','editions','objects','access_policies','reading_state',
  'reading_items','rights_cases','derivatives','ocr_text','access_tokens',
  'outbox','audit','book_packs'];
foreach($schemas as $suffix){
  $table=PLDR_Core::table($suffix);
  f12_native_assert(is_string($table) && str_starts_with($table,$wpdb->prefix),
    'canonical table naming '.$suffix);
  $wpdb->last_error='';
  $match=$wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)));
  f12_native_assert($wpdb->last_error==='' && $match===$table,
    'physical schema table exists '.$suffix);
}
f12_native_assert((string)get_option('pldr_db_version','')==='1.1.0',
  'actual synthetic DB migration marker agrees with candidate');
f12_native_assert(PLDR_R21_Readiness::core_ready(false),
  'actual physical core schema readiness without repair on second read');
$packCount=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.PLDR_Core::table('book_packs'));
$docCount=(int)$wpdb->get_var('SELECT COUNT(*) FROM '.PLDR_Core::table('documents'));
f12_native_assert($packCount===0 && $docCount===0,
  'synthetic fresh install has no original book or patient data');
$m=PLDR_File26_Connector::manifest();
f12_native_assert(is_array($m)&&($m['status']??'')==='proposed'&&
  ($m['entity_types']??null)===['pdf'],
  'unapproved File26 PDF-only connector truthfully dormant');
f12_native_assert(PLDR_File26_Connector::register()===false,
  'File26 registration remains blocked without owner-approved cutover');
wp_set_current_user(0);
$unauth=PLDR_Book_Packs::register([],0);
f12_native_assert(is_wp_error($unauth) &&
  $unauth->get_error_code()==='pldr_pack_forbidden',
  'anonymous Book Pack registration denied by native WordPress session');
f12_native_assert(PLDR_Access::can_access_edition(999999,'read',-1)===false,
  'nonexistent PDF edition never granted public read');
f12_native_assert((int)$wpdb->get_var('SELECT COUNT(*) FROM '.PLDR_Core::table('book_packs'))===0,
  'denied Book Pack authorization writes no DB row');
echo "FILE12 NATIVE WORDPRESS/MYSQL DISPOSABLE SMOKE PASS; Hostinger and CF04/File26 integration remain unverified.\n";
