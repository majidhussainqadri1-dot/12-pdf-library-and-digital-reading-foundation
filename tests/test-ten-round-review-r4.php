<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R4 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Fourth ten-round regression: {$why}\n"); exit(1); }
};

$privacy = $read('includes/class-pldr-privacy.php');
$rest = $read('includes/class-pldr-rest.php');
$reader = $read('includes/class-pldr-reader.php');
$access = $read('includes/class-pldr-access.php');
$context = $read('includes/class-pldr-future-context.php');
$rights = $read('includes/class-pldr-rights.php');
$vault = $read('assets/future-reader-vault.js');
$schema = $read('includes/class-pldr-future-schema.php');
$corpus = $read('includes/class-pldr-future-corpus.php');
$iiif = $read('includes/class-pldr-future-iiif.php');
$authority = $read('includes/class-pldr-future-authority.php');

// Round 1 — bounded, Future-aware privacy export/erase.
$must($privacy, 'private const BATCH = 50', 'Bounded privacy batch size is missing.');
$must($privacy, "'future_prefs'", 'Future preferences are missing from privacy coverage.');
$must($privacy, "'session_handoffs'", 'Session handoffs are missing from privacy coverage.');
$must($privacy, "'room_contexts'", 'Reading-room contexts are missing from privacy coverage.');
$must($privacy, "'done'=>0 === \$remaining", 'Privacy erasure does not report remaining work truthfully.');

// Round 2 — truthful/idempotent private item deletion and citation page validation.
$must($rest, "'reading-item-delete'", 'Reading-item DELETE is not idempotency protected.');
$must($rest, 'pldr_item_missing', 'Reading-item ownership/existence guard is missing.');
$must($rest, 'pldr_citation_page', 'Citation page upper-bound validation is missing.');

// Round 3 — bounded thumbnail token issuance.
$must($rest, 'READER_THUMB_LIMIT = 300', 'REST reader thumbnail grant cap is missing.');
$must($rest, 'thumbnails_meta', 'Reader-manifest truncation disclosure is missing.');
$must($reader, 'THUMBNAIL_PREVIEW_LIMIT = 300', 'Server-rendered reader thumbnail grant cap is missing.');
$must($reader, "ORDER BY page_number ASC LIMIT %d", 'Thumbnail derivative query is not bounded.');

// Round 4 — fail-closed access-policy validation.
$must($access, 'pldr_policy_entitlement', 'Entitlement-backed audience key validation is missing.');
$must($access, 'pldr_policy_embargo', 'Explicit embargo validation is missing.');
$must($access, 'parse_embargo', 'Embargo parser guard is missing.');
$must($access, "if ('' === \$entitlement_key) return false", 'Missing entitlement keys do not fail closed.');

// Round 5 — page-scoped context verification.
$must($context, 'ocr_pages($edition_id,$page,1,0)', 'Knowledge-context source verification is not page bounded.');

// Round 6 — bounded rights evidence.
$must($rights, 'EVIDENCE_JSON_MAX = 32768', 'Rights evidence total payload bound is missing.');
$must($rights, 'sanitize_evidence', 'Common rights evidence sanitizer is missing.');
$must($rights, 'pldr_case_evidence_size', 'Oversized rights evidence is not rejected.');

// Round 7 — approval transaction correctness.
$must($rights, 'pldr_edition_supersede_failed', 'Supersede failure is not fail-visible during approval.');
$must($rights, 'pldr_edition_publish_conflict', 'Edition publication CAS guard is missing.');
$must($rights, 'pldr_approve_commit', 'Approval COMMIT failure is not fail-visible.');

// Round 8 — sensitive report restriction atomicity.
$must($rights, 'pldr_sensitive_restriction_failed', 'Sensitive report restriction failure is not fail-visible.');
$must($rights, 'sensitive_restriction_committed', 'Sensitive report response/audit does not expose committed restriction state.');
$must($rights, "START TRANSACTION", 'Rights workflow transaction boundary is missing.');

// Round 9 — offline vault cleanup/retry correctness.
$must($vault, 'clearAllOnce', 'Offline vault purge does not distinguish success from blocked/error.');
$must($vault, 'pldrOfflinePurgePending', 'Offline vault purge-pending retry marker is missing.');
$must($vault, 'await clearEdition(false);F.say', 'Failed offline capture does not clear partial edition data.');

// Round 10 — clean verification scope retained.
$must($schema, 'option_value=%s WHERE option_name=%s AND option_value=%s', 'Future migration stale-lock CAS guard is missing.');
$must($corpus, 'pldr_ai_corpus_allowed', 'AI corpus deny-by-default allowlist boundary is missing.');
$must($corpus, 'next_offset', 'AI corpus bounded pagination is missing.');
$must($iiif, 'file12CanvasTruncated', 'IIIF bounded-canvas disclosure is missing.');
$must($authority, 'pldr_authority_provider', 'Authority-provider degraded-path guard is missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R4.md';
if (!is_file($doc)) { fwrite(STDERR, "Fourth ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i=1; $i<=10; $i++) $must($record, "## Round {$i}", "R4 review record is missing Round {$i}.");
$must($record, 'Defect rounds: **1, 2, 3, 4, 5, 6, 7, 8, 9**', 'R4 defect-round summary is missing.');
$must($record, 'Clean round: **10**', 'R4 clean-round summary is missing.');

echo "PLDR fourth fresh ten-round corrective review contract: PASS\n";
