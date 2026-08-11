<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R13 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "R13 twenty-round regression: {$why}\n"); exit(1); }
};

$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$preservation = $read('includes/class-pldr-future-preservation.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$citations = $read('includes/class-pldr-future-citations.php');
$authority = $read('includes/class-pldr-future-authority.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$search = $read('includes/class-pldr-future-search.php');
$context = $read('includes/class-pldr-future-context.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$schema = $read('includes/class-pldr-future-schema.php');
$preferences = $read('includes/class-pldr-future-preferences.php');
$handoff = $read('includes/class-pldr-future-handoff.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$derived = $read('includes/class-pldr-future-derived-text.php');

// Round 1 — exact internal cron authorization for scheduled fingerprints.
$must($fingerprint, "doing_action('pldr_future_fingerprint_edition')", 'Fingerprint scheduled worker is not bound to its exact cron action.');

// Round 2 — scan-fingerprint evidence remains review-only.
$must($fingerprint, 'pldr_fingerprint_review_forbidden', 'Fingerprint candidates are not explicitly review-authority protected.');
$must($fingerprint, "PLDR_Core::authorize('repair',$document_id)", 'Fingerprint review/repair authority marker is missing.');

// Round 3 — preservation system bypass is exact-action scoped.
$must($preservation, "doing_action('pldr_future_preservation_scan')", 'Preservation system authority is not exact-action scoped.');

// Round 4 — reading-room regions stay inside page bounds.
$must($rooms, "($region['x']+$region['w'])>1", 'Reading-room horizontal region bound is missing.');
$must($rooms, 'pldr_room_region_bounds', 'Reading-room region bound error is missing.');

// Round 5 — annotation provider degradation is explicit.
$must($annotations, 'pldr_annotation_source_provider', 'Annotation source-provider failure is not explicit.');
$must($annotations, "'source_provider_failures'=>", 'Annotation import does not disclose source-provider failures.');

// Round 6 — portable annotation IDs do not depend on mutable auth salt.
$must($annotations, "'pldr-annotation-v1|'", 'Versioned stable annotation identity seed is missing.');
if (strpos($annotations, "annotation_id(int \$uid,int \$edition_id,int \$item_id):string {return 'urn:pldr:annotation:'.hash_hmac") !== false) {
    fwrite(STDERR, "R13 twenty-round regression: Annotation IDs still depend on mutable auth-salt HMAC.\n"); exit(1);
}

// Round 7 — governed plain citation alias.
$must($citations, "array('apa','mla','sabri','plain')", 'Plain citation export alias is missing.');

// Round 8 — non-recursive BibTeX escaping.
$must($citations, 'return strtr($text,array(', 'BibTeX escaping is not non-recursive.');

// Round 9 — authority cache read/repair fail closed.
$must($authority, 'pldr_authority_cache_read', 'Authority-cache DB read failure is not fail-visible.');
$must($authority, 'pldr_authority_cache_repair', 'Corrupt authority-cache repair failure is not fail-visible.');

// Round 10 — preservation evidence reads/encoding fail visibly.
$must($preservation, 'pldr_preservation_derivative_read', 'Preservation derivative DB read failure is not explicit.');
$must($preservation, 'pldr_preservation_record_read', 'Preservation record DB read failure is not explicit.');
$must($preservation, 'pldr_preservation_derivative_encode', 'Preservation derivative JSON encoding is not checked.');
$must($preservation, 'preservation_schedule_read_failed', 'Scheduled preservation selection failure is not audited.');

// Round 11 — IIIF derivative DB failure cannot masquerade as missing previews.
$must($iiif, 'pldr_iiif_derivative_read', 'IIIF derivative DB failure is not fail-visible.');
$must($iiif, 'file12DownloadGrantFailed', 'IIIF download-grant failure disclosure is missing.');

// Round 12 — heatmap does not return partial success after OCR DB failure.
$must($search, 'pldr_heatmap_source_read', 'Search heatmap OCR DB failure is not explicit.');
$must($search, "\$wpdb->last_error=''", 'Search heatmap does not reset/check DB error state per OCR batch.');

// Round 13 — knowledge-context source/provider degradation is explicit.
$must($context, 'pldr_context_source_read', 'Knowledge-context OCR DB failure is not explicit.');
$must($context, 'pldr_context_selection_provider', 'Knowledge-context source-validation provider failure is not explicit.');

// Round 14 — accessibility scoring requires reliable DB evidence.
$must($a11y, 'pldr_a11y_ocr_read', 'Accessibility OCR DB failure is not explicit.');
$must($a11y, 'pldr_a11y_derivative_read', 'Accessibility derivative DB failure is not explicit.');
$must($a11y, 'pldr_a11y_verify_read', 'Accessibility verification re-read failure is not explicit.');

// Round 15 — every declared Future schema index is verified.
foreach (array('shelves'=>array('PRIMARY','shelf_key','user_id'),'shelf_items'=>array('PRIMARY','shelf_edition','edition_id'),'reading_events'=>array('PRIMARY','event_id','user_created','edition_id'),'session_handoffs'=>array('PRIMARY','updated_at'),'ocr_corrections'=>array('PRIMARY','edition_page','status'),'authority_cache'=>array('PRIMARY','authority_key','expires_at'),'a11y_audits'=>array('PRIMARY','status'),'room_contexts'=>array('PRIMARY','room_key','edition_id','created_by'),'preservation_records'=>array('PRIMARY','object_id','format_health'),'scan_fingerprints'=>array('PRIMARY','fingerprint_value','metadata_hash')) as $table=>$indexes) {
    foreach ($indexes as $index) $must($schema, "'{$index}'", "Schema verifier is missing required index {$table}.{$index}.");
}

// Round 16 — preference DB read failure is distinct from absent version 0.
$must($preferences, 'pldr_future_pref_read', 'Reading-preference DB read failure is not explicit.');

// Round 17 — handoff DB read failure is distinct from no session.
$must($handoff, 'pldr_handoff_read', 'Cross-device handoff DB read failure is not explicit.');

// Round 18 — shelf DB reads fail closed throughout private collection workflows.
$must($shelves, 'pldr_shelf_default_read', 'Default-shelf DB read failure is not explicit.');
$must($shelves, 'pldr_shelf_limit_read', 'Shelf capacity DB read failure is not explicit.');
$must($shelves, 'pldr_shelf_item_read', 'Shelf-membership DB read failure is not explicit.');

// Round 19 — OCR Lab DB evidence/rate/review reads fail closed.
$must($ocr, 'pldr_ocr_report_read', 'OCR report DB failure is not explicit.');
$must($ocr, 'pldr_ocr_correction_rate_read', 'OCR correction rate-state DB failure is not explicit.');
$must($ocr, 'pldr_ocr_review_source_read', 'OCR review source DB failure is not explicit.');

// Round 20 — derived-text source DB failure blocks provider fallback.
$must($derived, 'pldr_derive_source_read', 'Derived-text OCR DB failure is not explicit.');
$must($derived, "no external provider validation or processing was attempted", 'Derived-text DB failure does not explicitly block provider fallback.');

$doc = dirname(__DIR__) . '/docs/TWENTY-ROUND-REVIEW-2026-08-12-R13.md';
if (!is_file($doc)) { fwrite(STDERR, "R13 twenty-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=20; $i++) $must($record, "## Round {$i}", "Review record is missing Round {$i}.");
$must($record, 'Defect rounds: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20.', 'Defect-round summary is missing.');
$must($record, 'Clean rounds: none.', 'Clean-round summary is missing.');

echo "PLDR thirteenth fresh twenty-round corrective review contract: PASS\n";
