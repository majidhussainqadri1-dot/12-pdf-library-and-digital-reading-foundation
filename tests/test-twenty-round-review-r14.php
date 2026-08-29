<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R14 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "R14 twenty-round regression: {$why}\n"); exit(1); }
};

$insights = $read('includes/class-pldr-future-insights.php');
$anchors = $read('includes/class-pldr-future-anchors.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$context = $read('includes/class-pldr-future-context.php');
$data = $read('includes/class-pldr-future-data.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$privacy = $read('includes/class-pldr-privacy.php');

// Round 1 — reading-event rate DB failure must block mutation.
$must($insights, 'pldr_insight_rate_read', 'Reading-event rate DB failure is not fail-visible.');
$must($insights, 'reading_insight_rate_read_failed', 'Reading-event rate DB failure is not audited.');

// Round 2 — aggregate DB failure must not become an empty private report.
$must($insights, 'pldr_insight_report_read', 'Reading-insight aggregate DB failure is not fail-visible.');

// Round 3 — completion DB failure must not become zero completed documents.
$must($insights, 'pldr_insight_completion_read', 'Reading-completion DB failure is not fail-visible.');

// Round 4 — canonical anchor OCR DB failure blocks provider fallback.
$must($anchors, 'pldr_anchor_source_read', 'Precise-anchor source DB failure is not explicit.');
$must($anchors, 'external validation was not attempted', 'Precise-anchor DB failure does not explicitly block external validation.');

// Round 5 — AI corpus OCR DB failure cannot fabricate an empty/partial manifest.
$must($corpus, 'pldr_corpus_source_read', 'AI corpus source DB failure is not explicit.');
$must($corpus, 'no partial manifest was returned', 'AI corpus DB failure does not reject partial manifest projection.');

// Round 6 — fingerprint OCR evidence failure blocks persistence.
$must($fingerprint, 'pldr_fingerprint_ocr_read', 'Fingerprint OCR DB failure is not explicit.');

// Round 7 — visual derivative DB failure blocks incomplete fingerprint family persistence.
$must($fingerprint, 'pldr_fingerprint_visual_read', 'Fingerprint visual-evidence DB failure is not explicit.');

// Round 8 — current fingerprint evidence read failure cannot trigger hidden compute/comparison.
$must($fingerprint, 'pldr_fingerprint_current_read', 'Current fingerprint evidence DB failure is not explicit.');
$must($fingerprint, 'pldr_fingerprint_required', 'Missing fingerprint evidence does not stop read-only candidate comparison pending explicit compute.');
$must($fingerprint, "'missing_types'=>", 'Missing fingerprint evidence does not disclose which explicit compute inputs are required.');

// Round 9 — comparison candidate-pool DB failure cannot become empty success.
$must($fingerprint, 'pldr_fingerprint_candidate_read', 'Fingerprint candidate-pool DB failure is not explicit.');

// Round 10 — truncation evidence distinguishes provider input from valid-result cap.
$must($context, 'eligible_results_seen', 'Knowledge-context eligible-result count is missing.');
$must($context, 'results_truncated', 'Knowledge-context result-cap truncation evidence is missing.');
$must($context, 'provider_input_truncated', 'Knowledge-context provider-input truncation evidence is missing.');

// Round 11 — reflow DB failure blocks external fallback.
$must($data, 'pldr_reflow_source_read', 'Reflow OCR DB failure is not explicit.');
$must($data, 'no external provider fallback was attempted', 'Reflow OCR DB failure does not explicitly block provider fallback.');

// Round 12 — reflow total-count DB failure cannot corrupt completeness metadata.
$must($data, 'pldr_reflow_source_count', 'Reflow OCR-count DB failure is not explicit.');

// Round 13 — outline OCR DB failure cannot become empty heuristic success.
$must($data, 'pldr_outline_source_read', 'Outline OCR DB failure is not explicit.');

// Round 14 — outline OCR-count DB failure cannot corrupt truncation evidence.
$must($data, 'pldr_outline_source_count', 'Outline OCR-count DB failure is not explicit.');

// Round 15 — left edition comparison source DB failure is explicit.
$must($data, 'pldr_compare_left_read', 'Left comparison OCR DB failure is not explicit.');

// Round 16 — right edition comparison source DB failure is explicit.
$must($data, 'pldr_compare_right_read', 'Right comparison OCR DB failure is not explicit.');

// Round 17 — annotation export DB failure cannot become empty AnnotationPage success.
$must($annotations, 'pldr_annotation_export_read', 'Annotation export DB failure is not explicit.');

// Round 18 — annotation quote OCR DB failure blocks provider fallback.
$must($annotations, 'pldr_annotation_source_read', 'Annotation source OCR DB failure is not explicit.');
$must($annotations, 'external source validation was not attempted', 'Annotation OCR DB failure does not explicitly block provider fallback.');

// Round 19 — duplicate-state DB failure blocks the current annotation insert.
$must($annotations, 'pldr_annotation_duplicate_read', 'Annotation duplicate-state DB failure is not explicit.');
$must($annotations, 'imported_before_failure', 'Annotation duplicate DB failure does not expose prior batch mutation count for reconciliation.');

// Round 20 — privacy shelf-item erasure is bounded and parent shelves wait for child drain.
$must($privacy, 'shelf_items_remaining', 'Privacy erasure does not track remaining shelf items before deleting parent shelves.');
$must($privacy, 'LIMIT ".self::BATCH', 'Shelf-item erasure query is not bounded by the privacy batch size.');
$must($privacy, "delete_ids('shelf_items','id'", 'Shelf-item privacy erasure is not ID-batched.');

$doc = dirname(__DIR__) . '/docs/TWENTY-ROUND-REVIEW-2026-08-12-R14.md';
if (!is_file($doc)) { fwrite(STDERR, "R14 twenty-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=20; $i++) $must($record, "## Round {$i}", "Review record is missing Round {$i}.");
$must($record, 'Defect rounds: 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20.', 'Defect-round summary is missing.');
$must($record, 'Clean rounds: none.', 'Clean-round summary is missing.');

echo "PLDR fourteenth fresh twenty-round corrective review contract: PASS\n";
