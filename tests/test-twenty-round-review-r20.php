<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path)use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"R20 missing review target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $haystack,string $needle,string $why):void{if(false===strpos($haystack,$needle)){fwrite(STDERR,"R20 regression: {$why}\n");exit(1);}};
$forbid=static function(string $haystack,string $needle,string $why):void{if(false!==strpos($haystack,$needle)){fwrite(STDERR,"R20 regression: {$why}\n");exit(1);}};

$main=$read('pdf-library-digital-reading.php');
$response=$read('includes/class-pldr-response-policy.php');
$schema=$read('includes/class-pldr-schema-corrections.php');
$rights=$read('includes/class-pldr-rights-policy.php');
$storage=$read('includes/class-pldr-storage.php');
$access=$read('includes/class-pldr-access.php');
$context=$read('includes/class-pldr-future-context.php');
$ocr=$read('includes/class-pldr-future-ocr-lab.php');
$ocrSearch=$read('includes/class-pldr-ocr-search-overlay.php');
$anchors=$read('includes/class-pldr-future-anchors.php');
$annotations=$read('includes/class-pldr-future-annotations.php');
$handoff=$read('includes/class-pldr-future-handoff.php');
$objectIntegrity=$read('includes/class-pldr-object-integrity.php');
$integrityPolicy=$read('includes/class-pldr-integrity-policy.php');
$preservation=$read('includes/class-pldr-future-preservation.php');
$privacyExt=$read('includes/class-pldr-privacy-extension.php');
$ops=$read('includes/class-pldr-operations-policy.php');
$guards=$read('includes/class-pldr-r20-guards.php');
$vault=$read('assets/future-reader-vault.js');

// Round 2 — explicit File 12 response/cache privacy and approved-only public OCR projection.
$must($response,'private, no-store, max-age=0','private/conditional REST responses lost no-store policy.');
$must($response,'status=%s ORDER BY page_number ASC,id ASC LIMIT %d','public OCR projection is not explicitly approved-only and bounded.');
$must($response,"public_projection'=>'approved-corrections-only'",'public OCR projection provenance marker missing.');

// Round 4/17 — verified schema postconditions and transactional engine.
$must($schema,'MODIFY last_error text NULL','outbox last_error compatibility correction missing.');
$must($schema,'ENGINE=InnoDB','transactional storage-engine correction missing.');
$must($schema,"'2026-08-13-r20-17'",'R20 verified schema correction revision missing.');
$must($schema,'schema_correction_engine_unverified','engine postcondition verification missing.');

// Round 5 — rights expiry blocks publication/restoration.
$must($rights,'pldr_publication_guard_rights_expired','expired publication rights are not fail-closed.');
$must($rights,"'clean'!==\(string\)" ,'publication eligibility marker unavailable.');
$must($rights,"['scan_status']",'publication eligibility lost clean-object scan-status gate.');

// Round 6 — private temp cleanup is guaranteed at request shutdown.
$must($storage,'register_shutdown_function','private temporary-file shutdown cleanup missing.');
$must($storage,'cleanup_temporary_paths','tracked temporary-file cleanup missing.');

// Round 8 — delivery quarantine is bound to the exact sampled encrypted object state.
$must($access,'storage_scope=%s AND key_id=%s AND sha256=%s AND encrypted_sha256=%s','delivery-integrity quarantine exact-state CAS missing.');
$must($access,'delivery_integrity_reconciliation_failed','delivery quarantine reconciliation audit missing.');

// Round 9/20 — review records export without creating a second erasure owner.
$must($privacyExt,'wp_privacy_personal_data_exporters','durable review-record export coverage missing.');
$forbid($privacyExt,'wp_privacy_personal_data_erasers','duplicate File 12 privacy-erasure owner returned.');

// Round 11 — companion context is canonical and same-origin.
$must($context,'private static function same_origin','same-origin companion-provider validation missing.');
$must($context,'hash_equals($home_host,$host)','knowledge-context host binding missing.');

// Round 12 — corrections and search use the approved-correction overlay.
$must($ocr,'PLDR_Future_Data::ocr_pages($edition_id,$page,1,0)','OCR correction submission is not bound to current derived OCR.');
$must($ocr,"PLDR_Future_Data::ocr_pages((int)\$row['edition_id'],(int)\$row['page_number'],1,0)",'OCR approval does not revalidate current derived OCR.');
$must($ocrSearch,'PLDR_Future_Data::ocr_pages($edition_id,0,$take,0,$last_scanned)','OCR search continuation is not after-page bound on corrected OCR.');
$must($ocrSearch,"'approved_correction_overlay'=>true",'corrected OCR search provenance marker missing.');

// Round 13/14 — selector fidelity is preserved or unsupported SVG fails closed.
$must($anchors,'pldr_anchor_svg_unsupported','lossy SVG precise-anchor support returned.');
$must($annotations,"in_array(\$type,array('TextQuoteSelector','CssSelector'),true)",'portable annotation import selector allowlist changed unexpectedly.');
$must($handoff,'pldr_handoff_svg_unsupported','lossy SVG handoff support returned.');
$must($handoff,"foreach(array('exact'=>500,'prefix'=>120,'suffix'=>120,'value'=>300)",'cross-device selector fields are no longer preserved.');
$must($handoff,"\$out['region']=\$region",'cross-device selector region preservation missing.');

// Round 16 — exact object integrity includes encrypted bytes and authenticated plaintext.
$must($objectIntegrity,'encrypted_sha256_verified','ciphertext checksum evidence missing.');
$must($objectIntegrity,'PLDR_Crypto::verify_file','authenticated plaintext verification missing.');
$must($preservation,'PLDR_Object_Integrity::verify','preservation path no longer uses exact object integrity.');
$must($integrityPolicy,"'ciphertext_and_plaintext_verified'=>true",'health/key-rotation exact-integrity disclosure missing.');

// Round 18 — IndexedDB writes resolve only after transaction completion and corrupt vaults purge.
$must($vault,"tx.oncomplete=()=>{if(mode!=='readonly'",'offline-vault write promise can resolve before transaction commit.');
$must($vault,'The damaged/incomplete local copy was purged.','corrupt offline-vault purge path missing.');

// Round 19 — schema-correction readiness is observable.
$must($ops,'PLDR_Schema_Corrections::is_current()','schema correction health gate missing.');
$must($ops,"'status'=>\$current?'ok':'blocked'",'schema correction health status is not fail-closed.');

// Round 20 — repair interception preserves idempotency and admin preflight is preauthorized.
$must($guards,"idempotency_begin('repair'",'exact-integrity REST repair bypasses canonical idempotency.');
$must($guards,"add_action('admin_post_pldr_approve_document'",'admin publication preauthorization guard missing.');
$must($guards,"check_admin_referer('pldr_approve_document_'",'admin publication preflight nonce guard missing.');
$must($guards,"check_admin_referer('pldr_rights_decision_'",'admin rights-decision preflight nonce guard missing.');

foreach(array('class-pldr-r20-guards.php','class-pldr-response-policy.php','class-pldr-object-integrity.php','class-pldr-integrity-policy.php','class-pldr-schema-corrections.php','class-pldr-operations-policy.php','class-pldr-privacy-extension.php') as $loader)$must($main,"'{$loader}'","main loader missing {$loader}.");
$must($main,'PLDR_R20_Guards::hooks();','R20 final cross-cutting guard hooks are not active.');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-13-R20.md';
if(!is_file($doc)){fwrite(STDERR,"R20 review record missing.\n");exit(1);} $record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R20 review record is missing Round {$i}.");
$must($record,'First-ten defect rounds: 2, 4, 5, 6, 7, 8, 9.','R20 first-ten defect checkpoint missing.');
$must($record,'Defect rounds: **2, 4, 5, 6, 7, 8, 9, 11, 12, 13, 14, 16, 17, 18, 19, 20**','R20 final defect-round summary missing.');
$must($record,'Clean rounds: **1, 3, 10, 15**','R20 clean-round summary missing.');

echo "PLDR twentieth fresh twenty-round corrective review contract: PASS\n";
