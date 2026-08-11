<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R9 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Ninth ten-round regression: {$why}\n"); exit(1); }
};

$data = $read('includes/class-pldr-future-data.php');
$context = $read('includes/class-pldr-future-context.php');
$preservation = $read('includes/class-pldr-future-preservation.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$derived = $read('includes/class-pldr-future-derived-text.php');

// Round 1.
$must($data, 'pldr_reflow_provenance', 'Reflow anonymous-provider rejection missing.');
$must($data, 'reflow_provider_failed', 'Reflow provider exception containment/audit missing.');
$must($data, 'provider_input_truncated', 'Reflow provider truncation disclosure missing.');

// Round 2.
$must($data, 'outline_provider_failed', 'Outline provider exception containment missing.');
$must($data, "'provider_failure'=>$external_failure", 'Outline degraded fallback disclosure missing.');
if (strpos($data, "external['provider']??'adapter'") !== false) { fwrite(STDERR, "Ninth ten-round regression: fabricated outline provider fallback returned.\n"); exit(1); }

// Round 3.
$must($context, 'EXPECTED_OWNERS', 'Knowledge Context governed-owner allowlist missing.');
$must($context, 'provenance_rejected', 'Knowledge Context provenance rejection accounting missing.');
$must($context, "true!==($item['canonical']??false)", 'Knowledge Context canonical assertion guard missing.');

// Round 4.
$must($preservation, 'provider_requested_quarantine', 'Provider quarantine request disclosure missing.');
$must($preservation, "external_health='needs-review'", 'Provider quarantine is not downgraded to review-only state.');

// Round 5.
$must($a11y, 'pldr_a11y_verify_conflict', 'Accessibility verification CAS conflict missing.');
$must($a11y, 'AND verified_by=0 AND score=%f AND status=%s AND findings_json=%s AND provider=%s AND updated_at=%s', 'Accessibility verification is not snapshot-bound.');

// Round 6.
$must($fingerprint, 'function can_compute', 'Fingerprint compute authority helper missing.');
$must($fingerprint, 'pldr_fingerprint_compute_forbidden', 'Fingerprint heavy-compute authority failure missing.');
$must($fingerprint, '&&self::can_compute($edition)', 'Candidate lookup can still trigger unrestricted compute.');

// Round 7.
$must($context, 'MAX_PROVIDER_CALLS_PER_HOUR', 'Knowledge Context provider rate ceiling missing.');
$must($context, 'GET_LOCK', 'Knowledge Context serialized rate accounting missing.');
$must($context, 'pldr_context_rate_limit', 'Knowledge Context HTTP 429 path missing.');

// Round 8.
$must($iiif, 'PREVIEW_GRANT_LIMIT', 'IIIF preview-grant amplification ceiling missing.');
$must($iiif, 'file12PreviewGrantsDeferred', 'IIIF deferred-grant disclosure missing.');
$must($iiif, 'file12CanvasIdentityPreserved', 'IIIF canvas identity preservation regressed.');

// Round 9.
$must($annotations, 'serialized_duplicate_suppression', 'Annotation serialized duplicate suppression marker missing.');
$must($annotations, 'pldr_anno_', 'Annotation identity advisory lock missing.');
$must($annotations, 'RELEASE_LOCK', 'Annotation advisory lock release missing.');

// Round 10 preserved boundaries.
$must($corpus, 'pldr_ai_corpus_consumer_allowed', 'File 16 AI corpus consumer authorization regressed.');
$must($derived, 'selection_belongs', 'Derived-text page/source verification regressed.');
$must($derived, 'provider_rate_limited', 'Derived-text provider rate limiting regressed.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R9.md';
if (!is_file($doc)) { fwrite(STDERR, "Ninth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "Review record is missing round {$i}.");
$must($record, 'Rounds 1, 2, 3, 4, 5, 6, 7, 8, and 9', 'R9 defect distribution missing.');
$must($record, 'Round 10 was clean', 'R9 clean-round statement missing.');

echo "PLDR ninth fresh ten-round corrective review contract: PASS\n";
