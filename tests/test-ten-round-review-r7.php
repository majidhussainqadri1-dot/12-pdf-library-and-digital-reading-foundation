<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R7 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Seventh ten-round regression: {$why}\n"); exit(1); }
};

$anchors = $read('includes/class-pldr-future-anchors.php');
$preservation = $read('includes/class-pldr-future-preservation.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$prefs = $read('includes/class-pldr-future-preferences.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$citations = $read('includes/class-pldr-future-citations.php');

// Round 1 — precise anchor source binding.
$must($anchors, 'pldr_anchor_source', 'Text-quote anchors are not source verified.');
$must($anchors, 'ocr_pages($edition_id,$page,1,0)', 'Anchor verification is not page scoped.');
$must($anchors, "'source_verified'=>'TextQuoteSelector'===\$type", 'Anchor source-verification disclosure is missing.');

// Round 2 — preservation provider containment and bounds.
$must($preservation, 'PROVIDER_FINDINGS_LIMIT = 50', 'Preservation provider findings are not bounded.');
$must($preservation, 'catch (Throwable $e)', 'Preservation provider exceptions are not contained.');
$must($preservation, 'preservation_provider_failed', 'Preservation provider failures are not audited.');
$must($preservation, "'provider_findings_truncated'=>", 'Preservation provider truncation disclosure is missing.');

// Round 3 — accessibility provider containment/provenance.
$must($a11y, 'PROVIDER_FINDINGS_LIMIT = 50', 'Accessibility provider findings are not bounded.');
$must($a11y, 'catch (Throwable $e)', 'Accessibility provider exceptions are not contained.');
$must($a11y, 'External accessibility findings were ignored because provider provenance was missing.', 'Anonymous accessibility provider output is not rejected.');
$must($a11y, "'provider_failure'=>", 'Accessibility provider failure disclosure is missing.');

// Round 4 — OCR correction abuse and final-decision state.
$must($ocr, 'pldr_ocr_correction_hourly_limit', 'OCR correction submission rate ceiling is missing.');
$must($ocr, 'pldr_ocr_correction_rate', 'OCR correction 429 path is missing.');
$must($ocr, "'pending'!==\$row['status']", 'Already-decided OCR corrections are not locked.');
$must($ocr, "array('id'=>\$correction_id,'version'=>(int)\$row['version'],'status'=>'pending')", 'OCR review status/version CAS is missing.');

// Round 5 — optimistic synchronized preferences.
$must($prefs, 'pldr_future_pref_precondition', 'Existing synchronized preferences do not require expected_version.');
$must($prefs, 'expected_version is required', 'Preference precondition message is missing.');
$must($prefs, 'pldr_future_pref_encode', 'Preference JSON encoding failure is not fail visible.');
$must($prefs, "if(0===\$current_version && \$expected>0)", 'Missing-row preference version conflict guard is missing.');

// Round 6 — atomic/retryable fingerprints.
$must($fingerprint, "START TRANSACTION", 'Fingerprint persistence is not transactional.');
$must($fingerprint, 'pldr_fingerprint_commit', 'Fingerprint COMMIT failure is not fail visible.');
$must($fingerprint, '$needs_visual=', 'Missing visual fingerprints are not detected for retry.');
$must($fingerprint, "'atomic_persistence'=>true", 'Fingerprint atomic-persistence disclosure is missing.');

// Round 7 — IIIF page identity independent of thumbnails.
$must($iiif, '$canvas_count=min($edition_pages,$canvas_limit)', 'IIIF canvas count is not page-identity based.');
$must($iiif, "for(\$page=1;\$page<=\$canvas_count;\$page++)", 'IIIF canvases are not generated per edition page.');
$must($iiif, "'file12PreviewMissing'=>\$preview_missing", 'IIIF missing-preview disclosure is missing.');
$must($iiif, "'file12CanvasIdentityPreserved'=>true", 'IIIF page-identity preservation marker is missing.');

// Round 8 — imported annotation selector integrity.
$must($annotations, 'selector_source_verified', 'Imported annotation selector verification disclosure is missing.');
$must($annotations, 'quote_belongs(', 'Imported text-quote source verification is missing.');
$must($annotations, "!in_array(\$motivation,array('bookmarking','commenting','highlighting'),true)", 'Unsupported annotation motivations are not rejected.');
$must($annotations, 'pldr_annotation_import_source_allowed', 'Governed annotation source adapter boundary is missing.');

// Round 9 — File 16 consumer boundary.
$must($corpus, 'pldr_ai_corpus_consumer_allowed', 'AI corpus consumer authorization is missing.');
$must($corpus, 'pldr_corpus_consumer_forbidden', 'AI corpus consumer deny path is missing.');
$must($corpus, "'consumer_authorized'=>true", 'AI corpus consumer authorization disclosure is missing.');

// Round 10 — structured citation sanitation.
$must($citations, 'private static function single_line', 'Structured citation single-line normalization is missing.');
$must($citations, 'private static function bibtex_escape', 'BibTeX structural escaping is missing.');
$must($citations, "preg_replace('/[\\x00-\\x1F\\x7F]+/u'", 'Citation control-character removal is missing.');
$must($citations, '$url = esc_url_raw($url);', 'Citation export URL sanitation is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R7.md';
if (!is_file($doc)) { fwrite(STDERR, "Seventh ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R7 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R7 defect-round summary is missing.');
$must($record, 'Clean rounds: **none**', 'R7 clean-round summary is missing.');

echo "PLDR seventh fresh ten-round corrective review contract: PASS\n";
