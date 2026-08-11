<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R6 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Sixth ten-round regression: {$why}\n"); exit(1); }
};

$citations = $read('includes/class-pldr-future-citations.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$search = $read('includes/class-pldr-future-search.php');
$data = $read('includes/class-pldr-future-data.php');
$handoff = $read('includes/class-pldr-future-handoff.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$authority = $read('includes/class-pldr-future-authority.php');
$derived = $read('includes/class-pldr-future-derived-text.php');
$context = $read('includes/class-pldr-future-context.php');

// Round 1 — citation page + edition identity.
$must($citations, 'pldr_citation_page', 'Future citation page upper-bound validation is missing.');
$must($citations, "'-e' . \$edition_id", 'Citation key is not edition-bound.');
$must($citations, "'PLDR-edition-id'=>\$edition_id", 'CSL projection lacks explicit edition identity.');

// Round 2 — annotation bounds, edition binding and fidelity.
$must($annotations, 'private const EXPORT_LIMIT = 1000', 'Annotation export limit is missing.');
$must($annotations, "add_query_arg('edition',\$edition_id", 'Annotation source is not edition-bound.');
$must($annotations, "'input_truncated'=>\$input_total>self::IMPORT_LIMIT", 'Annotation import truncation disclosure is missing.');
$must($annotations, "'bookmarking'===\$motivation?'bookmark'", 'Annotation motivation fidelity is missing.');

// Round 3 — truthful heatmap accounting.
$must($search, "'scan_truncated'=>\$scan_capped", 'Heatmap scan truncation is not separately disclosed.');
$must($search, "'results_truncated'=>\$result_capped", 'Heatmap result truncation is not separately disclosed.');
$must($search, '$scanned+=$count;$offset+=$count;', 'Heatmap scan accounting is not advanced per retrieved batch.');

// Round 4 — projected/bounded outline provider data.
$must($data, "'input_count'=>count(\$input)", 'Outline provider input count is not disclosed.');
$must($data, "'level'=>max(1,min(6", 'Outline level projection is not bounded.');
$must($data, "'ocr_pages_total'=>\$ocr_total", 'OCR outline fallback total/truncation evidence is missing.');

// Round 5 — truthful comparison completion.
$must($data, "'pages_compared' => \$processed", 'Edition comparison still overstates processed pages.');
$must($data, "'results_truncated'=>\$result_capped", 'Edition comparison result-cap disclosure is missing.');
$must($data, "'page_scan_truncated'=>\$page_scan_capped", 'Edition comparison page-scan truncation disclosure is missing.');

// Round 6 — mandatory optimistic-version handoff updates.
$must($handoff, 'pldr_handoff_precondition', 'Existing handoff updates do not require expected_version.');
$must($handoff, 'expected_version is required', 'Handoff optimistic-version precondition message is missing.');
$must($handoff, "if(!\$current && \$expected>0)", 'Missing-row expected_version conflict guard is missing.');

// Round 7 — reading-room provider degraded/compensation path.
$must($rooms, 'catch(Throwable $e)', 'Reading-room provider exceptions are not contained.');
$must($rooms, 'pldr_reading_room_provider_compensate', 'Reading-room external-side-effect compensation hook is missing.');
$must($rooms, "add_query_arg('edition',\$edition_id", 'Reading-room source URL is not edition-bound.');

// Round 8 — authority degraded/cache integrity.
$must($authority, 'corrupt_cache_discarded', 'Corrupt authority cache is not discarded/audited.');
$must($authority, 'provider_failure', 'Authority provider exception degraded marker is missing.');
$must($authority, 'catch(Throwable $e)', 'Authority provider exceptions are not contained.');

// Round 9 — translation/transliteration provenance and provider failure.
$must($derived, 'pldr_derive_provenance', 'Anonymous derived-text provider output is not rejected.');
$must($derived, 'provider_failure', 'Derived-text provider exception degraded marker is missing.');
$must($derived, "'provider_generated'=>true", 'Derived-text provider-generated disclosure is missing.');

// Round 10 — bounded/degraded knowledge context.
$must($context, 'private const PROVIDER_INPUT_LIMIT = 100', 'Knowledge-context provider input ceiling is missing.');
$must($context, 'catch(Throwable $e)', 'Knowledge-context provider exceptions are not contained.');
$must($context, "'provider_input_total'=>\$input_total", 'Knowledge-context provider truncation evidence is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R6.md';
if (!is_file($doc)) { fwrite(STDERR, "Sixth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R6 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R6 defect-round summary is missing.');
$must($record, 'Clean rounds: **none**', 'R6 clean-round summary is missing.');

echo "PLDR sixth fresh ten-round corrective review contract: PASS\n";
