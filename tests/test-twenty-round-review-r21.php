<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path)use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"R21 missing review target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $haystack,string $needle,string $why):void{if(false===strpos($haystack,$needle)){fwrite(STDERR,"R21 regression: {$why}\n");exit(1);}};
$forbid=static function(string $haystack,string $needle,string $why):void{if(false!==strpos($haystack,$needle)){fwrite(STDERR,"R21 regression: {$why}\n");exit(1);}};

$main=$read('pdf-library-digital-reading.php');
$plugin=$read('includes/class-pldr-plugin.php');
$ready=$read('includes/class-pldr-r21-readiness.php');
$guards=$read('includes/class-pldr-r21-runtime-guards.php');
$outbox=$read('includes/class-pldr-r21-outbox.php');
$futureSchema=$read('includes/class-pldr-future-schema.php');
$future=$read('includes/class-pldr-future.php');
$privacy=$read('includes/class-pldr-privacy.php');
$css=$read('assets/reader.css');

// Round 1 — physical runtime/schema readiness is required, not only a version marker.
$must($main,"'class-pldr-r21-readiness.php'",'R21 readiness class is not loaded.');
$must($plugin,'if(!PLDR_R21_Readiness::core_ready())return;','domain runtime no longer fails closed on physical core-schema readiness.');
$must($ready,'inspect_core_schema','physical core-schema inspection missing.');
$must($ready,"'missing_columns'",'core readiness no longer records missing-column evidence.');
$must($ready,"'missing_indexes'",'core readiness no longer verifies required indexes.');
$must($ready,'PLDR_Schema_Corrections::is_current()','forward schema-correction revision is not part of the runtime gate.');
$must($futureSchema,'private static function verify_schema','Future-24 marker drift is no longer physically verified.');
$must($future,'!PLDR_Future_Schema::maybe_upgrade() || !PLDR_Future_Schema::is_current()','Future-24 hooks can register before schema readiness.');

// Round 3 — stale pending replay reservations are recoverable but cleanup is bounded to mutations.
$must($guards,"status_code=0 AND created_at<=%s",'stale pending idempotency reservation recovery missing.');
$must($guards,"in_array(\$method,array('GET','HEAD','OPTIONS'),true)",'idempotency maintenance again runs on read-only traffic.');
$must($guards,"get_transient('pldr_r21_idempotency_reap')",'cross-request maintenance throttle missing.');

// Round 4 — encryption writes cannot silently select an arbitrary key from a multi-key ring.
$must($guards,'ambiguous_active_key','ambiguous active encryption-key preflight missing.');
$must($guards,"'pldr_active_key_ambiguous'",'ambiguous active-key mutation is not fail-closed.');

// Round 7 — privacy export excludes replay response bodies/signed credentials and failure audits are pseudonymous.
$must($privacy,"SELECT route,key_hash,status_code,expires_at,created_at",'minimized idempotency privacy export missing.');
$forbid($privacy,"SELECT route,key_hash,response_json",'privacy export again includes idempotency response bodies.');
$must($privacy,"'subject_ref'=>substr(hash_hmac('sha256'",'privacy failure audit pseudonymization missing.');

// Round 8/13 — canonical/status/SEO navigation and UI containment.
$must($plugin,"wp_safe_redirect(\$canonical,301)",'canonical document-slug redirect missing.');
$must($plugin,"status_header(404)",'not-found/restricted virtual route status is not explicit.');
$must($plugin,"'Back to PDF Library'",'native account-reading Back control missing.');
$must($plugin,"'No private reading progress yet'",'private reading workspace empty state missing.');
$must($plugin,"0===strpos(\$page,'pldr-')",'admin reader asset scope is no longer File-12-specific.');
$must($css,'.pldr-shell button,.pldr-reader-shell button','reader button styling is not confined to File 12 surfaces.');
if(preg_match('/(^|})button\s*,/m',$css)){fwrite(STDERR,"R21 regression: global unscoped button styling returned.\n");exit(1);}

// Round 12 — logout purge survives a deliberately fail-closed domain/schema runtime.
$must($main,"add_action('wp_logout', array('PLDR_Future', 'mark_vault_purge'))",'offline-vault logout purge is no longer registered independently of domain readiness.');
$must($main,"add_action('wp_enqueue_scripts', array('PLDR_Future', 'vault_purge_asset'), 2)",'offline-vault purge asset is not globally available after logout.');

// Round 14 — legacy migration is serialized and cannot regress newer native progress.
$must($plugin,"add_action('pldr_legacy_migration',array('PLDR_R21_Runtime_Guards','legacy_migration_guarded'))",'legacy cron bypasses the R21 serialized migration guard.');
$must($guards,"private const LEGACY_LOCK='pldr_r21_legacy_runtime_lock'",'legacy migration runtime lease missing.');
$must($guards,'capture_current_progress','legacy migration native-progress snapshot missing.');
$must($guards,'ON DUPLICATE KEY UPDATE last_page=IF(updated_at<=VALUES(updated_at)','newer native reading progress can again be overwritten by legacy reconciliation.');

// Round 15 — event schemas are explicit and consumer failures never persist arbitrary exception text.
$must($main,"'class-pldr-r21-outbox.php'",'R21 governed outbox dispatcher is not loaded.');
$must($plugin,"add_action('pldr_dispatch_outbox',array('PLDR_R21_Outbox','dispatch'))",'scheduled outbox bypasses governed R21 dispatcher.');
$must($outbox,"'privacy'=>'private-user'",'private event privacy classification missing.');
$must($outbox,"'consumer_requirement'=>'idempotent-by-event-id'",'at-least-once consumer idempotency contract missing.');
$must($outbox,"'consumer-dispatch-failed'",'bounded/safe consumer failure code missing.');
$forbid($outbox,'sanitize_text_field($e->getMessage())','raw consumer exception text is again persisted to the outbox.');

// Round 16 — one exact integrity path plus canonical guarded operational repairs.
$forbid($plugin,"PLDR_Health::integrity_sample(3)",'legacy and exact integrity samplers are both scheduled.');
$must($guards,"admin_post_pldr_safe_repair",'guarded admin repair interception missing.');
$must($guards,"PLDR_R21_Outbox::dispatch()",'admin outbox repair bypasses governed dispatcher.');
$must($guards,"delete_transient('pldr_r21_core_schema_ready')",'manual schema repair can trust a stale readiness transient.');

// Round 17 — fingerprint schedule uses an exact argument tuple and reader assets are route/shortcode scoped.
$must($guards,"\$args=array(\$edition_id,0)",'fingerprint scheduling does not use one canonical argument tuple.');
$must($guards,"wp_next_scheduled('pldr_future_fingerprint_edition',\$args)",'fingerprint duplicate-event guard mismatches scheduled arguments.');
$must($plugin,"has_shortcode((string)\$post->post_content,'pldr_library')",'shortcode compatibility was lost while scoping reader assets.');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-13-R21.md';
if(!is_file($doc)){fwrite(STDERR,"R21 review record missing.\n");exit(1);} $record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R21 review record is missing Round {$i}.");
$must($record,'First-ten defect rounds: **1, 3, 4, 7, 8**','R21 first-ten defect checkpoint missing.');
$must($record,'Through Round 19 defect rounds: **1, 3, 4, 7, 8, 12, 13, 14, 15, 16, 17, 19**','R21 Round-19 checkpoint defect summary missing.');

echo "PLDR R21 fresh twenty-round corrective-review contract: PASS\n";
