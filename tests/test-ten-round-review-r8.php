<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R8 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Eighth ten-round regression: {$why}\n"); exit(1); }
};

$anchors = $read('includes/class-pldr-future-anchors.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$insights = $read('includes/class-pldr-future-insights.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$derived = $read('includes/class-pldr-future-derived-text.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$privacy = $read('includes/class-pldr-privacy.php');
$citations = $read('includes/class-pldr-future-citations.php');
$rooms = $read('includes/class-pldr-future-rooms.php');

// Round 1 — precise non-text selector fidelity.
$must($anchors, 'pldr_anchor_selector_value', 'Non-text scholarly selectors are not value validated.');
$must($anchors, 'pldr_anchor_fragment_page', 'Fragment selector page binding is missing.');
$must($anchors, 'selector_value_preserved', 'Anchor response does not disclose selector-value preservation.');

// Round 2 — bounded Smart Shelves and no N+1 counts.
$must($shelves, 'private const CUSTOM_SHELF_LIMIT = 100', 'Custom shelf ceiling is missing.');
$must($shelves, 'COUNT(i.id) item_count', 'Shelf item counts are still N+1 queries.');
$must($shelves, 'pldr_shelf_limit', 'Custom shelf limit is not fail-visible.');

// Round 3 — truthful bounded reading-insight reports.
$must($insights, 'private const REPORT_GROUP_LIMIT = 1000', 'Reading-insight group scan bound is missing.');
$must($insights, 'group_scan_truncated', 'Reading-insight group truncation is not disclosed.');
$must($insights, 'aggregate_truncated', 'Reading-insight aggregate incompleteness is not disclosed.');

// Round 4 — public accessibility reads are read-only; provider/persistence refresh is governed.
$must($a11y, 'if($refresh&&!$can_refresh)', 'Accessibility refresh authority gate is missing.');
$must($a11y, 'if($refresh){', 'Accessibility provider invocation is not confined to governed refresh.');
$must($a11y, 'if(!$refresh)return $report;', 'Read-only accessibility cache-miss path can still persist/provider-refresh.');
$must($a11y, "'persisted'=>false", 'Transient read-only accessibility result is not marked non-persistent.');
$must($a11y, "'persisted'=>true", 'Persisted accessibility DTO state is not explicit.');

// Round 5 — bounded provider invocation for derived text.
$must($derived, 'private const MAX_PROVIDER_CALLS_PER_HOUR = 120', 'Derived-text provider rate ceiling is missing.');
$must($derived, 'SELECT GET_LOCK(%s,1)', 'Derived-text rate accounting is not serialized.');
$must($derived, 'pldr_derive_rate_limit', 'Derived-text provider rate-limit failure is missing.');

// Round 6 — fingerprint provenance/version continuity.
$must($fingerprint, 'store_fingerprint', 'Fingerprint persistence is not version-aware.');
$must($fingerprint, 'pldr_fingerprint_conflict', 'Fingerprint CAS conflict handling is missing.');
$must($fingerprint, 'fingerprint_versions', 'Fingerprint version evidence is not returned.');

// Round 7 — stable portable annotation identity and dedupe.
$must($annotations, 'stable_annotation_ids', 'Portable annotation export IDs are not declared stable.');
$must($annotations, 'duplicates_skipped', 'Repeated annotation imports are not deduplicated.');
$must($annotations, 'stable_identity_dedupe', 'Annotation stable-identity dedupe evidence is missing.');
$must($annotations, "'refinedBy'", 'Portable selector refinement is not preserved.');

// Round 8 — delivery-grant privacy lifecycle.
$must($privacy, 'pldr-delivery-grants', 'User-bound delivery grant metadata is absent from privacy export.');
$must($privacy, "array('access_tokens','user_id','id')", 'User-bound delivery grants are absent from bounded privacy erasure.');
$must($privacy, 'user-bound delivery grants', 'Privacy erasure result does not disclose delivery-grant cleanup.');

// Round 9 — page-locator fidelity in structured citations.
$must($citations, "'-p'.\$page", 'Page-specific citation keys are not locator-bound.');
$must($citations, 'pages={', 'BibTeX page locator is missing.');
$must($citations, 'SP  - {$page}', 'RIS page locator is missing.');
$must($citations, 'locator_bound', 'Citation locator binding is not disclosed.');

// Round 10 — reading-room selector fidelity.
$must($rooms, 'pldr_room_selector_value', 'Reading-room non-text selector value validation is missing.');
$must($rooms, 'pldr_room_fragment_page', 'Reading-room fragment page binding is missing.');
$must($rooms, 'selector_value_preserved', 'Reading-room selector-value preservation is not disclosed.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R8.md';
if (!is_file($doc)) { fwrite(STDERR, "Eighth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R8 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R8 defect-round summary is missing.');
$must($record, 'Clean rounds: **none**', 'R8 clean-round summary is missing.');

echo "PLDR eighth fresh ten-round corrective review contract: PASS\n";
