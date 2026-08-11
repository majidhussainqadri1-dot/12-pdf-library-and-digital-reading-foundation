<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R12 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Twelfth ten-round regression: {$why}\n"); exit(1); }
};

$authority = $read('includes/class-pldr-future-authority.php');
$future_schema = $read('includes/class-pldr-future-schema.php');
$context = $read('includes/class-pldr-future-context.php');
$plugin = $read('includes/class-pldr-plugin.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$prefs = $read('includes/class-pldr-future-preferences.php');
$handoff = $read('includes/class-pldr-future-handoff.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$anchors = $read('includes/class-pldr-future-anchors.php');

// Round 1 — authority provenance may not be fabricated.
$must($authority, 'anonymous_provider_rejected', 'Anonymous authority-provider rejection is missing.');
$must($authority, 'pldr_authority_provenance', 'Authority provenance failure path is missing.');

// Round 2 — authority provider calls are bounded per caller.
$must($authority, 'MAX_PROVIDER_CALLS_PER_HOUR = 120', 'Authority provider-call ceiling is missing.');
$must($authority, 'pldr_authority_rate_limit', 'Authority HTTP 429 rate-limit path is missing.');
$must($authority, 'rate_policy_provider_failed', 'Authority rate-policy fail-closed audit is missing.');

// Round 3 — version markers do not permanently mask Future schema drift.
$must($future_schema, 'future_24_schema_drift_detected', 'Future schema-drift detection is missing.');
$must($future_schema, '15 * MINUTE_IN_SECONDS', 'Bounded Future schema-health revalidation is missing.');

// Round 4 — Knowledge Context policy adapters fail closed.
$must($context, 'context_rate_policy_provider_failed', 'Context rate-policy provider containment is missing.');
$must($context, 'context_selection_provider_failed', 'Context selection-provider containment is missing.');

// Round 5 — Unified Shell adapter failure falls back safely.
$must($plugin, 'shell_adapter_failed', 'Shell-adapter failure containment is missing.');
$must($plugin, 'catch(Throwable $e)', 'Shell adapter Throwable guard is missing.');

// Round 6 — custom shelf capacity cannot be raced.
$must($shelves, 'pldr_shelf_create_', 'Per-user custom-shelf creation lock is missing.');
$must($shelves, 'limit_serialized', 'Serialized custom-shelf capacity evidence is missing.');

// Round 7 — corrupt synchronized preferences are not treated as empty state.
$must($prefs, 'future_preference_corrupt', 'Corrupt preference audit is missing.');
$must($prefs, 'pldr_future_pref_corrupt', 'Corrupt preference integrity error is missing.');

// Round 8 — corrupt handoff anchor state is not silently discarded.
$must($handoff, 'handoff_anchor_corrupt', 'Corrupt handoff-anchor audit is missing.');
$must($handoff, 'pldr_handoff_corrupt', 'Corrupt handoff integrity error is missing.');

// Round 9 — corrupt accessibility evidence cannot retain a trusted badge projection.
$must($a11y, 'accessibility_audit_corrupt', 'Corrupt accessibility-audit detection is missing.');
$must($a11y, 'pldr_a11y_corrupt', 'Corrupt accessibility-audit error is missing.');

// Round 10 — anchor regions stay within page coordinates.
$must($anchors, 'pldr_anchor_region_bounds', 'Scholarly anchor page-boundary validation is missing.');
$must($anchors, '($x + $w) > 100.0', 'Horizontal scholarly anchor boundary check is missing.');
$must($anchors, '($y + $h) > 100.0', 'Vertical scholarly anchor boundary check is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-12-R12.md';
if (!is_file($doc)) { fwrite(STDERR, "Twelfth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R12 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R12 defect-round summary missing.');
$must($record, 'Clean rounds: **none**', 'R12 clean-round summary missing.');

echo "PLDR twelfth fresh ten-round corrective review contract: PASS\n";
