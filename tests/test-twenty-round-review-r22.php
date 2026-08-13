<?php
$root=dirname(__DIR__).'/pdf-library-foundation-12';
$read=static function(string $path)use($root):string{$full=$root.'/'.$path;if(!is_file($full)){fwrite(STDERR,"R22 missing target: {$path}\n");exit(1);}return (string)file_get_contents($full);};
$must=static function(string $haystack,string $needle,string $why):void{if(false===strpos($haystack,$needle)){fwrite(STDERR,"R22 regression: {$why}\n");exit(1);}};
$forbid=static function(string $haystack,string $needle,string $why):void{if(false!==strpos($haystack,$needle)){fwrite(STDERR,"R22 regression: {$why}\n");exit(1);}};

$rest=$read('includes/class-pldr-rest.php');
$ingest=$read('includes/class-pldr-ingest.php');
$crypto=$read('includes/class-pldr-crypto.php');
$integrity=$read('includes/class-pldr-integrity-policy.php');
$rights=$read('includes/class-pldr-rights.php');
$reader=$read('includes/class-pldr-reader.php');
$readerJs=$read('assets/reader.js');
$schema=$read('includes/class-pldr-schema.php');
$outbox=$read('includes/class-pldr-r21-outbox.php');
$fdata=$read('includes/class-pldr-future-data.php');
$fiiif=$read('includes/class-pldr-future-iiif.php');
$frest=$read('includes/class-pldr-future-rest.php');
$shelves=$read('includes/class-pldr-future-shelves.php');
$context=$read('includes/class-pldr-future-context.php');
$rooms=$read('includes/class-pldr-future-rooms.php');
$fcss=$read('assets/future-reader.css');
$admin=$read('includes/class-pldr-admin.php');
$access=$read('includes/class-pldr-access.php');
$a11y=$read('includes/class-pldr-future-a11y.php');
$preserve=$read('includes/class-pldr-future-preservation.php');

// R2: delivery mutations preauthorize before idempotency/token side effects.
$must($rest,'delivery_edition_or_unavailable','delivery preauthorization helper missing');
$must($rest,"self::delivery_edition_or_unavailable(\$edition_id,\$operation)",'reader-access does not preauthorize delivery');
$must($rest,"self::delivery_edition_or_unavailable(absint(\$request['edition_id']),'download')",'download-session does not preauthorize delivery');

// R3: Patient Cases need independent clearance and covers use malware scanning.
$must($ingest,"pldr_patient_case_publication_clearance",'patient-case publication clearance missing');
$must($ingest,'$cover_scan=self::scan_file','cover malware scan missing');

// R4: crypto outputs are hardened and rotation temp files are finally cleaned.
$must($crypto,'@chmod($output, 0600)','crypto output permissions not hardened');
$must($integrity,'finally','key-rotation deterministic cleanup missing');

// R6: affected publisher can appeal a rights case.
$must($rights,'$affected_publisher','affected publisher appeal path missing');

// R7: reading progress uses optimistic concurrency and JS propagates revision.
$must($reader,'expected_updated_at','reading progress revision precondition missing');
$must($reader,'FOR UPDATE','reading progress row lock missing');
$must($readerJs,'expected_updated_at','reader client does not send progress revision');
$must($readerJs,'progressRevision','reader client does not retain progress revision');

// R11: legacy publish state cannot bypass current scan/rights review.
$must($schema,"legacy-imported-pending-rescan",'legacy rescan quarantine marker missing');
$must($schema,'$legacy_publishable=false','legacy publish state can still auto-promote');

// R12: all emitted governed ingest/OCR events are registered.
$must($outbox,"'PDFDocumentIngested.v1'",'PDFDocumentIngested event contract missing');
$must($outbox,"'PDFDocumentOCRReady.v1'",'PDFDocumentOCRReady event contract missing');

// R13/R14: Future and IIIF unavailable states are generic before protected fetches.
$must($fdata,"'pldr_future_unavailable'",'Future generic unavailable boundary missing');
$must($fiiif,"'pldr_future_iiif_unavailable'",'IIIF generic unavailable boundary missing');

// R15: offline grant authorizes before idempotency.
$must($frest,"PLDR_Future_Data::require_edition(\$edition_id,'offline')",'offline-grant authorization preflight missing');

// R16: shelves are bounded and actual items can be enumerated safely.
$must($shelves,'ITEM_LIMIT = 5000','shelf lifetime bound missing');
$must($shelves,'public static function items','shelf item enumeration missing');
$must($frest,"'/future/shelves/(?P<id>\\d+)/items'",'shelf items REST surface missing');

// R17: patient-case context is separately approved; room errors do not persist raw exception text.
$must($context,'pldr_knowledge_context_patient_case_allowed','Knowledge Context patient-case privacy gate missing');
$must($rooms,"'error_class'=>sanitize_key(get_class(\$e))",'Reading Room safe provider diagnostics missing');
$forbid($rooms,'sanitize_text_field($e->getMessage())','Reading Room still persists raw provider exception text');

// R18: accessibility target, reduced motion, and recovery UI.
$must($fcss,'min-width:44px;min-height:44px','Future controls remain below 44px target');
$must($readerJs,'prefers-reduced-motion: reduce','reduced-motion behavior missing from reader JS');
$must($reader,"'Support reference: %s'",'reader support reference missing');
$must($reader,"'Home'",'reader Home recovery control missing');

// R19: operational repairs and bounded cleanup use canonical guards; provider logs avoid raw exceptions.
$must($admin,'PLDR_R21_Readiness::core_ready(true)','schema repair bypasses canonical readiness guard');
$must($admin,'PLDR_R21_Outbox::dispatch()','outbox repair bypasses governed dispatcher');
$must($admin,'PLDR_R21_Runtime_Guards::legacy_migration_guarded()','legacy repair bypasses guarded migration');
$must($access,'WHERE expires_at<=%s ORDER BY expires_at ASC LIMIT','global expired idempotency cleanup missing');
$must($a11y,"'error_class'=>sanitize_key(get_class(\$e))",'a11y provider exception class minimization missing');
$must($preserve,"'error_class'=>sanitize_key(get_class(\$e))",'preservation provider exception class minimization missing');

// R20: final holistic pass also removes raw exception text from ingest/migration diagnostics.
$must($ingest,'ingest_transaction_failed','bounded ingest transaction audit missing');
$must($ingest,'cover_store_failed','bounded cover-store audit missing');
$forbid($ingest,"machine_error('pldr_ingest_transaction', ".'$e->getMessage()','ingest exposes raw exception text');
$forbid($ingest,"machine_error('pldr_cover_store',".'$e->getMessage()','cover store exposes raw exception text');
$forbid($schema,"'error' => substr(".'$e->getMessage()','legacy migration audit persists raw exception text');

// R20: protected object mutations preauthorize, denial rows are aborted, and callback exceptions remain retryable.
$must($rest,'readable_edition_before_mutation','core object mutation preauthorization missing');
$must($rest,'appeal_target_before_mutation','rights-appeal preauthorization missing');
$must($rest,'idempotency_abort_after_denial_failed','core denied idempotency cleanup missing');
$must($frest,'readable_edition_before_mutation','Future object mutation preauthorization missing');
$must($frest,'ocr_review_target_before_mutation','OCR review preauthorization missing');
$must($frest,'owned_shelf_before_mutation','private shelf preauthorization missing');
$must($frest,'review_edition_before_mutation','review/repair object preauthorization missing');
$must($frest,'future_idempotency_abort_after_exception_failed','Future callback exception does not abort replay reservation');
$must($frest,"'retry_safe'=>true",'Future callback exception is not explicitly retry-safe');
$must($frest,'future_idempotency_abort_after_denial_failed','Future denied idempotency cleanup missing');

$doc=dirname(__DIR__).'/docs/TWENTY-ROUND-REVIEW-2026-08-13-R22.md';
if(!is_file($doc)){fwrite(STDERR,"R22 review record missing.\n");exit(1);} $record=(string)file_get_contents($doc);
for($i=1;$i<=20;$i++)$must($record,"## Round {$i}","R22 review record missing Round {$i}");
$must($record,'**First-ten defect rounds:** **2, 3, 4, 6, 7**','R22 first-ten defect summary missing');
$must($record,'**Final defect rounds:** **2, 3, 4, 6, 7, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20**','R22 final defect summary missing');

echo "PLDR R22 fresh twenty-round corrective-review contract: PASS\n";
