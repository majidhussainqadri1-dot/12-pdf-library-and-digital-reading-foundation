<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R10 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Tenth ten-round regression: {$why}\n"); exit(1); }
};

$core = $read('includes/class-pldr-core.php');
$access = $read('includes/class-pldr-access.php');
$rest = $read('includes/class-pldr-rest.php');
$privacy = $read('includes/class-pldr-privacy.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$annotations = $read('includes/class-pldr-future-annotations.php');

// Round 1 — authorization provider exceptions fail closed.
$must($core, 'authorization_provider_failed', 'Authorization-provider failure containment missing.');
$must($core, 'doctor_verification_provider_failed', 'Doctor-verification provider failure containment missing.');

// Round 2 — entitlement provider exceptions fail closed.
$must($access, 'entitlement_provider_failed', 'Entitlement-provider failure containment missing.');

// Round 3 — access policy requires optimistic precondition.
$must($access, 'pldr_policy_precondition', 'Access-policy expected-version precondition missing.');
$must($access, 'expected_version<1', 'Access-policy missing-version guard missing.');

// Round 4 — core reader manifest preview grants are capped separately from thumbnails.
$must($rest, 'READER_PREVIEW_GRANT_LIMIT = 50', 'Reader-manifest preview-grant ceiling missing.');
$must($rest, 'preview_grants_deferred', 'Reader-manifest deferred-grant disclosure missing.');

// Round 5 — private reading items are bounded and use stable signed continuation.
$must($rest, 'min(200,absint', 'Private reading-item response ceiling missing.');
$must($rest, 'has_more', 'Private reading-item pagination continuation missing.');
$must($rest, 'next_cursor', 'Private reading-item signed continuation metadata missing.');
$must($rest, "'cursor_supported'=>true", 'Private reading-item cursor capability disclosure missing.');

// Round 6 — secure access-token issuance is rate limited and serialized.
$must($access, 'MAX_TOKEN_ISSUES_PER_HOUR = 600', 'Secure access-token issuance ceiling missing.');
$must($access, 'GET_LOCK', 'Secure access-token serialized rate accounting missing.');
$must($access, 'pldr_access_rate_limit', 'Secure access-token HTTP 429 path missing.');

// Round 7 — delivery integrity failures quarantine/revoke instead of log-only behavior.
$must($access, 'quarantine_delivery_failure', 'Delivery integrity quarantine path missing.');
$must($access, 'delivery_integrity_quarantined', 'Delivery integrity quarantine audit missing.');

// Round 8 — legal-hold provider failure retains data and requires reconciliation.
$must($privacy, 'privacy_legal_hold_provider_failed', 'Privacy legal-hold provider containment missing.');
$must($privacy, 'legal-hold status could not be verified', 'Privacy fail-closed retention message missing.');

// Round 9 — AI corpus policy provider failures deny corpus access explicitly.
$must($corpus, 'ai_corpus_policy_provider_failed', 'AI corpus policy-provider audit missing.');
$must($corpus, 'pldr_corpus_policy_provider', 'AI corpus fail-closed provider error missing.');

// Round 10 — annotation source-validator failure rejects safely rather than fatals.
$must($annotations, 'annotation_source_provider_failed', 'Annotation source-provider containment missing.');
$must($annotations, 'catch(Throwable $e)', 'Annotation source-provider Throwable guard missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R10.md';
if (!is_file($doc)) { fwrite(STDERR, "Tenth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R10 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9, 10**', 'R10 defect-round summary missing.');
$must($record, 'Clean rounds: **none**', 'R10 clean-round summary missing.');

echo "PLDR tenth fresh ten-round corrective review contract: PASS\n";
