<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R11 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Eleventh ten-round regression: {$why}\n"); exit(1); }
};

$schema = $read('includes/class-pldr-schema.php');
$anchors = $read('includes/class-pldr-future-anchors.php');
$derived = $read('includes/class-pldr-future-derived-text.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$insights = $read('includes/class-pldr-future-insights.php');
$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$rest = $read('includes/class-pldr-future-rest.php');
$heatmap = $read('includes/class-pldr-future-search.php');

// Round 1 — migration lock owner/CAS safety.
$must($schema, 'MIGRATION_LOCK_OPTION', 'Core migration-lock identity is missing.');
$must($schema, 'option_value=%s WHERE option_name=%s AND option_value=%s', 'Stale migration-lock compare-and-set takeover is missing.');
$must($schema, 'release_migration_lock', 'Owner-bound migration-lock release is missing.');

// Round 2 — schema shape truth before version advancement.
$must($schema, 'missing_columns', 'Core schema column verification is missing.');
$must($schema, 'missing_indexes', 'Core schema index verification is missing.');
$must($schema, 'SHOW COLUMNS FROM', 'Core schema SHOW COLUMNS verification is missing.');
$must($schema, 'SHOW INDEX FROM', 'Core schema SHOW INDEX verification is missing.');

// Round 3 — precise-anchor provider failure containment.
$must($anchors, 'precise_anchor_source_provider_failed', 'Precise-anchor source-provider failure audit is missing.');
$must($anchors, 'pldr_anchor_source_provider', 'Precise-anchor explicit degraded error is missing.');

// Round 4 — derived-text surrounding policy/source/rate failures fail closed.
$must($derived, 'derived_text_patient_policy_provider_failed', 'Derived-text patient-case policy containment is missing.');
$must($derived, 'pldr_derive_selection_provider', 'Derived-text source-validator failure path is missing.');
$must($derived, 'pldr_derive_rate_policy', 'Derived-text rate-policy failure path is missing.');

// Round 5 — reading-room policy/anchor/compensation hardening.
$must($rooms, 'reading_room_patient_policy_provider_failed', 'Reading-room patient-case policy containment is missing.');
$must($rooms, 'reading_room_anchor_provider_failed', 'Reading-room anchor-validator containment is missing.');
$must($rooms, 'provider_compensation_failed', 'Reading-room provider compensation failure disclosure is missing.');

// Round 6 — reading-insight serialized rate accounting.
$must($insights, 'pldr_insight_rate_lock', 'Reading-insight rate lock is missing.');
$must($insights, 'reading_insight_rate_policy_provider_failed', 'Reading-insight rate-policy failure containment is missing.');
$must($insights, 'rate_accounting_serialized', 'Reading-insight serialized-accounting disclosure is missing.');

// Round 7 — OCR correction serialized rate accounting.
$must($ocr, 'pldr_ocr_correction_rate_lock', 'OCR correction rate lock is missing.');
$must($ocr, 'ocr_correction_rate_policy_provider_failed', 'OCR correction rate-policy failure containment is missing.');
$must($ocr, 'rate_accounting_serialized', 'OCR correction serialized-accounting disclosure is missing.');

// Round 8 — IIIF limit-policy failure before token issuance.
$must($iiif, 'iiif_limit_policy_provider_failed', 'IIIF limit-policy failure audit is missing.');
$must($iiif, 'pldr_iiif_limit_policy', 'IIIF explicit degraded limit-policy error is missing.');
$must($iiif, 'no preview grants were issued', 'IIIF no-side-effect failure disclosure is missing.');

// Round 9 — offline policy checked before issuing an offline grant.
$must($rest, 'offline_vault_ttl_policy_provider_failed', 'Offline-vault TTL policy containment is missing.');
$must($rest, 'pldr_offline_ttl_policy', 'Offline-vault explicit degraded policy error is missing.');
$must($rest, 'policy_checked_before_grant', 'Offline pre-grant policy-order evidence is missing.');
$ttl_pos = strpos($rest, "apply_filters('pldr_offline_vault_ttl'");
$grant_marker = <<<'MARKER'
PLDR_Access::issue_token((int)$edition['id'],(int)$edition['object_id'],'offline'
MARKER;
$grant_pos = strpos($rest, $grant_marker);
if (false === $ttl_pos || false === $grant_pos || $ttl_pos >= $grant_pos) { fwrite(STDERR, "Eleventh ten-round regression: Offline token is issued before TTL/right policy is resolved.\n"); exit(1); }

// Round 10 — heatmap policy containment and abuse budget.
$must($heatmap, 'MAX_REQUESTS_PER_HOUR = 180', 'Heatmap hourly request ceiling is missing.');
$must($heatmap, 'heatmap_limit_policy_provider_failed', 'Heatmap limit-policy failure containment is missing.');
$must($heatmap, 'pldr_heatmap_rate_limit', 'Heatmap HTTP 429 rate-limit path is missing.');
$must($heatmap, 'pldr_heatmap_rate_store', 'Heatmap fail-closed rate-state persistence path is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R11.md';
if (!is_file($doc)) { fwrite(STDERR, "Eleventh ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R11 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R11 defect-round summary missing.');
$must($record, 'Clean rounds: **none**', 'R11 clean-round summary missing.');

echo "PLDR eleventh fresh ten-round corrective review contract: PASS\n";
