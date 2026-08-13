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
$crypto=$read('includes/class-pldr-crypto.php');
$corrections=$read('includes/class-pldr-schema-corrections.php');
$futureSchema=$read('includes/class-pldr-future-schema.php');
$future=$read('includes/class-pldr-future.php');
$privacy=$read('includes/class-pldr-privacy.php');
$css=$read('assets/reader.css');

// Round 1/20 — physical runtime/schema readiness is required, not only a version marker.
$must($main,"'class-pldr-r21-readiness.php'",'R21 readiness class is not loaded.');
$must($plugin,'if(!PLDR_R21_Readiness::core_ready())return;','domain runtime no longer fails closed on physical core-schema readiness.');
$must($ready,'inspect_core_schema','physical core-schema inspection missing.');
$must($ready,"'missing_columns'",'core readiness no longer records missing-column evidence.');
$must($ready,"'missing_indexes'",'core readiness no longer verifies required indexes.');
$must($ready,"'storage_scope'",'complete object runtime-schema contract is no longer verified.');
$must($ready,"'audience_hash'",'access-token audience binding column is no longer part of readiness.');
$must($ready,"'payload_json'",'outbox runtime payload column is no longer part of readiness.');
$must($ready,'PLDR_Schema_Corrections::is_core_current()','core correction postconditions are not part of runtime readiness.');
$must($corrections,'public static function is_core_current()','fresh-install-safe core correction gate missing.');
$must($corrections,'private static function physical_health','stored correction marker is no longer physically verified.');
$must($corrections,'future_schema_expected','Future-table correction ordering is no longer explicit.');
$must($futureSchema,'private static function verify_schema','Future-24 marker drift is no longer physically verified.');
$must($future,'!PLDR_Future_Schema::maybe_upgrade() || !PLDR_Future_Schema::is_current()','Future-24 hooks can register before schema readiness.');

// Round 3/20 — stale pending replay recovery is mutation-only, actor-scoped and not anonymously triggerable.
$must($guards,"status_code=0 AND created_at<=%s",'stale pending idempotency reservation recovery missing.');
$must($guards,"in_array(\$method,array('GET','HEAD','OPTIONS'),true)",'idempotency maintenance again runs on read-only traffic.');
$must($guards,"if(\$actor<1)return \$result",'anonymous traffic can again trigger replay-state maintenance.');
$must($guards,"actor_id=%d AND status_code=0",'stale replay cleanup is no longer scoped to the authenticated actor.');
$must($guards,'2*HOUR_IN_SECONDS','stale replay threshold lost the R21 safety margin.');

// Round 4/20 — multi-key writes require an explicit active key in crypto itself, without a pre-permission REST config leak.
$must($crypto,'if (1 === count($keys))','single-key compatibility fallback is missing.');
$must($crypto,'Multiple File 12 master keys are configured; define PLDR_PDF_ACTIVE_KEY_ID explicitly','multi-key active selection is no longer fail-closed.');
$forbid($guards,'key_write_preflight','active-key configuration is again disclosed from a pre-permission REST interception path.');

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

// Round 14/20 — legacy migration is serialized, cannot regress newer progress, and journals recovery before mutation.
$must($plugin,"add_action('pldr_legacy_migration',array('PLDR_R21_Runtime_Guards','legacy_migration_guarded'))",'legacy cron bypasses the R21 serialized migration guard.');
$must($guards,"private const LEGACY_LOCK='pldr_r21_legacy_runtime_lock'",'legacy migration runtime lease missing.');
$must($guards,"private const LEGACY_RECOVERY_OPTION='pldr_r21_legacy_progress_recovery'",'durable legacy-progress recovery journal missing.');
$must($guards,'capture_current_progress','legacy migration native-progress snapshot missing.');
$must($guards,'persist_recovery_journal','native progress is no longer journaled before legacy mutation.');
$must($guards,"get_option(self::LEGACY_RECOVERY_OPTION",'pending legacy recovery is not replayed before the next migration batch.');
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
$must($guards,'PLDR_Schema_Corrections::invalidate_health_cache()','manual schema repair can trust stale physical correction evidence.');

// Round 17 — fingerprint schedule uses an exact argument tuple and reader assets are route/shortcode scoped.
$must($guards,"\$args=array(\$edition_id,0)",'fingerprint scheduling does not use one canonical argument tuple.');
$must($guards,"wp_next_scheduled('pldr_future_fingerprint_edition',\$args)",'fingerprint duplicate-event guard mismatches scheduled arguments.');
$must($plugin,"has_shortcode((string)\$post->post_content,'pldr_library')",'shortcode compatibility was lost while scoping reader assets.');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-13-R21.md';
if(!is_file($doc)){fwrite(STDERR,"R21 review record missing.\n");exit(1);} $record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R21 review record is missing Round {$i}.");
$must($record,'First-ten defect rounds: **1, 3, 4, 7, 8**','R21 first-ten defect checkpoint missing.');
$must($record,'Through Round 19 defect rounds: **1, 3, 4, 7, 8, 12, 13, 14, 15, 16, 17, 19**','R21 Round-19 checkpoint defect summary missing.');
$must($record,'Final defect rounds: **1, 3, 4, 7, 8, 12, 13, 14, 15, 16, 17, 19, 20**','R21 final defect-round summary missing.');
$must($record,'Final clean rounds: **2, 5, 6, 9, 10, 11, 18**','R21 final clean-round summary missing.');
$must($record,'The R21 numbered review sequence is now **20/20 complete**','R21 completion statement missing.');

echo "PLDR R21 fresh twenty-round corrective-review contract: PASS\n";
