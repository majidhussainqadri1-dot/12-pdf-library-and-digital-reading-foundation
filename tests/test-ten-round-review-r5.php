<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R5 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Fifth ten-round regression: {$why}\n"); exit(1); }
};

$data = $read('includes/class-pldr-future-data.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$derived = $read('includes/class-pldr-future-derived-text.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$handoff = $read('includes/class-pldr-future-handoff.php');
$insights = $read('includes/class-pldr-future-insights.php');
$schema = $read('includes/class-pldr-future-schema.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$authority = $read('includes/class-pldr-future-authority.php');
$preservation = $read('includes/class-pldr-future-preservation.php');

// Round 1 — bounded bulk OCR and reflow.
$must($data, 'BULK_OCR_LIMIT = 1000', 'Bulk OCR ceiling is missing.');
$must($data, 'REFLOW_WINDOW_LIMIT = 100', 'Reflow window ceiling is missing.');
$must($data, "'truncated'=>\$truncated", 'Reflow truncation disclosure is missing.');
$must($data, 'provider_input_truncated', 'Provider-backed reflow truncation disclosure is missing.');

// Round 2 — aggregate accessibility scoring.
$must($a11y, 'COUNT(*) page_count,AVG(quality_score) avg_quality', 'Accessibility scoring still depends on hydrating OCR text.');
$must($a11y, 'ocr_pages_assessed', 'Accessibility result does not disclose OCR page count.');

// Round 3 — page-scoped derived-text verification and bounded provider output.
$must($derived, 'ocr_pages($edition_id,$page,1,0)', 'Derived-text source verification is not page scoped.');
$must($derived, 'pldr_derive_provider', 'Derived-text provider failure path is missing.');
$must($derived, "self::limit(wp_strip_all_tags((string)\$result['text']),10000)", 'Derived provider output bound is missing.');

// Round 4 — page-scoped reading-room anchor verification.
$must($rooms, 'ocr_pages($edition_id,$page,1,0)', 'Reading-room anchor verification is not page scoped.');
$must($rooms, 'pldr_room_anchor_source', 'Reading-room source binding guard is missing.');

// Round 5 — bounded OCR Quality Lab report.
$must($ocr, 'CORRECTION_LIMIT = 500', 'OCR correction report limit is missing.');
$must($ocr, 'HEATMAP_LIMIT = 2000', 'OCR heatmap report limit is missing.');
$must($ocr, 'heatmap_meta', 'OCR heatmap truncation metadata is missing.');
$must($ocr, 'corrections_meta', 'OCR correction truncation metadata is missing.');

// Round 6 — truthful/CAS-safe Smart Shelf mutations.
$must($shelves, 'INSERT IGNORE INTO', 'Duplicate shelf add still rewrites existing membership.');
$must($shelves, 'AND version=%d', 'Shelf deletion version-CAS guard is missing.');
$must($shelves, 'pldr_shelf_commit', 'Shelf COMMIT failure is not fail-visible.');
$must($shelves, 'pldr_shelf_item_missing', 'Missing shelf item can still report successful removal.');

// Round 7 — IIIF full-PDF rendering honors download rights.
$must($iiif, "if(!empty(\$policy['download_allowed']))", 'IIIF rendering is not conditioned on download policy.');
$must($iiif, "'download',get_current_user_id(),900", 'IIIF full-PDF rendering does not use a download-bound grant.');
$must($iiif, 'file12DownloadRenderingAllowed', 'IIIF download-rendering disclosure is missing.');

// Round 8 — handoff race handling and normalized DTO.
$must($handoff, 'Reading-session handoff was created concurrently', 'Initial handoff insert race is not reported as a conflict.');
$must($handoff, 'private static function dto', 'Handoff public response normalization is missing.');
$must($handoff, "'anchor'=>is_array(\$anchor)?\$anchor:array()", 'Handoff anchor is not integrity-checked and decoded into the public DTO.');

// Round 9 — bounded reading-event ingestion.
$must($insights, 'MAX_EVENTS_PER_HOUR = 1200', 'Reading-event ingestion ceiling is missing.');
$must($insights, 'pldr_reading_event_hourly_limit', 'Reading-event limit is not governable.');
$must($insights, 'pldr_insight_rate_limit', 'Reading-event 429 path is missing.');

// Round 10 — clean verification scope retained.
$must($schema, 'option_value=%s WHERE option_name=%s AND option_value=%s', 'Future migration stale-lock CAS guard is missing.');
$must($corpus, 'pldr_ai_corpus_allowed', 'AI corpus deny-by-default allowlist boundary is missing.');
$must($corpus, 'next_cursor', 'AI corpus signed pagination is missing.');
$must($corpus, 'cursor_supported', 'AI corpus cursor capability disclosure is missing.');
$must($authority, 'pldr_authority_provider', 'Authority-provider degraded path is missing.');
$must($preservation, 'pldr_preservation_quarantine_store', 'Preservation quarantine persistence guard is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R5.md';
if (!is_file($doc)) { fwrite(STDERR, "Fifth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R5 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9**', 'R5 defect-round summary is missing.');
$must($record, 'Clean round: **10**', 'R5 clean-round summary is missing.');

echo "PLDR fifth fresh ten-round corrective review contract: PASS\n";
