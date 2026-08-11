<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Second ten-round regression: {$why}\n"); exit(1); }
};

$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$prefs = $read('includes/class-pldr-future-preferences.php');
$core = $read('includes/class-pldr-core.php');
$rest = $read('includes/class-pldr-rest.php');
$frest = $read('includes/class-pldr-future-rest.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$preservation = $read('includes/class-pldr-future-preservation.php');
$data = $read('includes/class-pldr-future-data.php');
$search = $read('includes/class-pldr-future-search.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$insights = $read('includes/class-pldr-future-insights.php');

// Round 1.
$must($ocr, 'review_metadata_visible', 'OCR report privacy projection missing.');
$must($ocr, "authorize('rights',$document_id)", 'OCR review is not document-scoped.');
$must($rooms, 'pldr_room_anchor_source', 'Reading-room anchor source binding missing.');
$must($rooms, 'anchor_belongs', 'Reading-room source-validation helper missing.');

// Round 2.
$must($shelves, 'pldr_shelf_conflict', 'Shelf optimistic concurrency missing.');
$must($shelves, 'AND version=%d', 'Shelf compare-and-set predicate missing.');
$must($prefs, 'pldr_future_pref_conflict', 'Preference first-write/update conflict handling missing.');

// Round 3.
$must($core, 'function idempotency_begin', 'Atomic idempotency reservation helper missing.');
$must($core, "'status_code'=>0", 'Pending idempotency reservation state missing.');
$must($core, 'function idempotency_complete', 'Idempotency completion helper missing.');
$must($rest, 'pldr_idempotency_in_progress', 'Core REST does not block concurrent same-key mutations.');
$must($frest, 'pldr_future_idempotency_in_progress', 'Future REST does not block concurrent same-key mutations.');

// Round 4.
$must($a11y, "authorize('manage',$document_id)", 'Accessibility authority is not document-scoped.');
$must($a11y, 'self::inspect($edition_id,true)', 'Accessibility verification does not force a fresh assessment.');

// Round 5.
$must($preservation, "integrity='unavailable'", 'Preservation path-unavailable state missing.');
$must($preservation, 'pldr_preservation_quarantine_store', 'Quarantine persistence failure is not fail-visible.');
$must($preservation, "'quarantined'!==$health", 'External preservation adapter can downgrade quarantine.');

// Round 6.
$must($data, 'ocr_pages(int $edition_id,int $page=0,int $limit=0,int $offset=0)', 'Bounded OCR retrieval signature missing.');
$must($data, 'public static function reflow(int $edition_id,int $page=0)', 'Page-scoped reflow missing.');
$must($search, 'pldr_heatmap_query_long', 'Heatmap maximum query bound missing.');
$must($search, 'pldr_heatmap_page_scan_limit', 'Heatmap work budget missing.');
$must($corpus, 'next_offset', 'Corpus pagination cursor missing.');
$must($corpus, 'min(500,$limit)', 'Corpus response limit missing.');

// Round 7.
$must($annotations, 'edition_bound', 'Portable annotation edition binding missing.');
$must($annotations, 'untrailingslashit($source)', 'Annotation source comparison missing.');
$must($annotations, 'strlen($encoded)<=480', 'Portable selector size bound missing.');

// Round 8.
$must($iiif, 'cc-by-nc-sa-4.0', 'Exact CC BY-NC-SA rights mapping missing.');
$must($iiif, 'file12CanvasTruncated', 'IIIF bounded-canvas truncation disclosure missing.');
if (strpos($iiif, "str_contains($license,'cc-by')") !== false) { fwrite(STDERR, "Second ten-round regression: broad CC-BY rights misclassification returned.\n"); exit(1); }

// Round 9.
$must($insights, 'entitlement_rechecked', 'Reading insights do not report entitlement revalidation.');
$must($insights, "PLDR_Access::can_access_edition", 'Reading insights are not current-access filtered.');
$must($insights, 'updated_at>=%s', 'Completion metric is not bounded to the selected time window.');

// Round 10.
$must($frest, "PLDR_Future_Data::reflow(absint($r['edition']),absint($r['page']))", 'REST reflow still builds the full document before page selection.');
$must($frest, "PLDR_Future_Corpus::manifest(absint($r['edition']),absint($r['offset']),$limit)", 'REST corpus pagination is not wired.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R2.md';
if (!is_file($doc)) { fwrite(STDERR, "Second ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "Review record is missing round {$i}.");

echo "PLDR second fresh ten-round corrective review contract: PASS\n";
