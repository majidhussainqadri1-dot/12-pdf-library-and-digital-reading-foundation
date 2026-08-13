<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing R3 review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$must = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, "Third ten-round regression: {$why}\n"); exit(1); }
};
$mustNot = static function (string $haystack, string $needle, string $why): void {
    if (strpos($haystack, $needle) !== false) { fwrite(STDERR, "Third ten-round regression: {$why}\n"); exit(1); }
};

$access = $read('includes/class-pldr-access.php');
$rights = $read('includes/class-pldr-rights.php');
$outbox = $read('includes/class-pldr-r21-outbox.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$annotations = $read('includes/class-pldr-future-annotations.php');
$reader = $read('includes/class-pldr-reader.php');

// Round 1 — delivery quota must be atomically consumed before body streaming.
$must($access, 'SET used_count=used_count+1', 'Atomic delivery usage increment missing.');
$must($access, 'revoked_at IS NULL AND expires_at>%s AND used_count<max_uses', 'Delivery consumption is not guarded by current grant state.');
$must($access, '1 !== $consumed', 'Delivery does not require a successful atomic grant claim.');
$must($access, 'Document object has an invalid byte size.', 'Zero-byte delivery guard missing.');

// Round 2 — rights authority must be scoped to the affected document.
$must($rights, "authorize('rights',\$document_id,\$reviewer_id)", 'Rights decision authority is not document-scoped.');
$must($rights, "authorize('rights',\$document_id,\$actor_id)", 'Privileged appeal authority is not document-scoped.');

// Round 3 — case decision and document transition must be one fail-visible transaction.
$must($rights, 'transition_document_status_row', 'Transactional document-state helper missing.');
$must($rights, 'pldr_case_commit', 'Rights decision commit failure is not fail-visible.');
$must($rights, 'Document state changed concurrently; the rights decision was not committed.', 'Document status CAS failure contract missing.');

// Round 4 — access-policy row and document access_mode must commit together.
$must($access, 'pldr_policy_document_conflict', 'Access-policy document CAS failure missing.');
$must($access, 'Access-policy update could not be committed atomically.', 'Access-policy commit failure is not fail-visible.');
$must($access, 'version=version+1', 'Document version CAS update missing from access-policy mutation.');

// Round 5 — outbox workers must lease rows before dispatch.
$must($outbox, '$lease_until=gmdate', 'Outbox dispatch lease missing.');
$must($outbox, "'processing',\$lease_until", 'Outbox processing claim missing.');
$must($outbox, 'if(1!==$claimed)continue;', 'Outbox dispatch does not require a successful claim.');

// Round 6 — only the current published edition may expire document rights.
$must($rights, 'MAX(id) current_id', 'Current-edition rights-expiry selector missing.');
$must($rights, 'WHERE status=%s GROUP BY document_id', 'Rights expiry is not constrained to published editions.');

// Round 7 — fingerprinting must use the bounded OCR sample.
$must($fingerprint, 'ocr_pages($edition_id,0,12,0)', 'Fingerprint OCR retrieval is not bounded to twelve pages.');
$must($fingerprint, 'ocr_pages_sampled', 'Fingerprint sample-size disclosure missing.');

// Round 8 — portable annotation source binding is mandatory, not optional.
$must($annotations, 'source_required', 'Annotation import does not disclose mandatory source binding.');
$must($annotations, "''===\$source||untrailingslashit", 'Missing annotation source is not rejected.');

// Round 9 — logical pagination still happens after entitlement filtering; R18 adds signed cursor/keyset continuation.
$must($reader, 'cursor_skip_remaining', 'Logical access-filtered pagination offset/cursor compatibility missing.');
$must($reader, 'access_filtered_pagination', 'Access-filtered pagination disclosure missing.');
$must($reader, 'scan_truncated', 'Bounded search scan does not disclose truncation.');
$must($reader, 'cursor_supported', 'Signed cursor pagination capability missing.');
$mustNot($reader, '$params[] = $per_page * 3;', 'Legacy pre-entitlement raw pagination returned.');

// Round 10 — persistence must succeed before appeal/book-pack events are emitted.
$must($rights, 'pldr_appeal_store', 'Rights appeal persistence failure is not fail-visible.');
$must($rights, 'no appeal event was emitted', 'Appeal persistence/event ordering contract missing.');
$must($rights, 'pldr_pack_store', 'Book Content Pack persistence failure is not fail-visible.');
$must($rights, 'no registration event was emitted', 'Book-pack persistence/event ordering contract missing.');

$doc = dirname(__DIR__) . '/docs/TEN-ROUND-REVIEW-2026-08-11-R3.md';
if (!is_file($doc)) { fwrite(STDERR, "Third ten-round review record missing.\n"); exit(1); }
$record = (string) file_get_contents($doc);
for ($i = 1; $i <= 10; $i++) $must($record, "## Round {$i}", "R3 review record is missing Round {$i}.");
$must($record, 'Rounds 1, 2, 3, 4, 5, 6, 7, 8, 9 and 10', 'R3 defect-round summary missing.');

echo "PLDR third fresh ten-round corrective review contract: PASS\n";
