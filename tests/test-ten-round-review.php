<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$read = static function (string $path) use ($root): string {
    $full = $root . '/' . $path;
    if (!is_file($full)) { fwrite(STDERR, "Missing review target: {$path}\n"); exit(1); }
    return (string) file_get_contents($full);
};
$contains = static function (string $haystack, string $needle, string $message): void {
    if (strpos($haystack, $needle) === false) { fwrite(STDERR, $message . "\n"); exit(1); }
};
$forbids = static function (string $haystack, string $needle, string $message): void {
    if (strpos($haystack, $needle) !== false) { fwrite(STDERR, $message . "\n"); exit(1); }
};

$handoff = $read('includes/class-pldr-future-handoff.php');
$reader = $read('includes/class-pldr-reader.php');
$ocr = $read('includes/class-pldr-future-ocr-lab.php');
$data = $read('includes/class-pldr-future-data.php');
$rest = $read('includes/class-pldr-future-rest.php');
$schema = $read('includes/class-pldr-future-schema.php');
$a11y = $read('includes/class-pldr-future-a11y.php');
$future = $read('includes/class-pldr-future.php');
$preservation = $read('includes/class-pldr-future-preservation.php');
$shelves = $read('includes/class-pldr-future-shelves.php');
$prefs = $read('includes/class-pldr-future-preferences.php');
$derived = $read('includes/class-pldr-future-derived-text.php');
$context = $read('includes/class-pldr-future-context.php');
$rooms = $read('includes/class-pldr-future-rooms.php');
$authority = $read('includes/class-pldr-future-authority.php');
$insights = $read('includes/class-pldr-future-insights.php');
$fingerprint = $read('includes/class-pldr-future-fingerprint.php');
$vault = $read('assets/future-reader-vault.js');
$futureJs = $read('assets/future-reader.js');

// Pass 1: every private reading-state read revalidates current edition access.
$contains($handoff, 'PLDR_Future_Data::require_edition($edition_id)', 'Pass 1 regression: handoff read lost edition-access revalidation.');
$contains($reader, 'PLDR_Access::can_access_edition($edition_id, \'read\', $user_id)', 'Pass 1 regression: private reader state/items lost access revalidation.');
$contains($reader, '$allowed=PLDR_Access::can_access_edition((int)$row[\'edition_id\'],\'read\',$uid);', 'Pass 1 regression: reading dashboard can disclose revoked-edition history.');
$contains($reader, 'if($allowed)$visible[]=$row;', 'Pass 1 regression: reading dashboard access-filter append guard is missing.');
$contains($fingerprint, 'self::can_inspect($other)', 'Pass 1 regression: fingerprint candidate disclosure is no longer filtered by current access.');

// Pass 2: approved OCR corrections remain a derived overlay; base OCR is immutable.
$contains($data, "PLDR_Core::table('ocr_corrections')", 'Pass 2 regression: approved OCR correction overlay is missing.');
$contains($ocr, "'derived_correction_layer'=>true", 'Pass 2 regression: OCR review no longer declares the derived correction layer.');
$forbids($ocr, "PLDR_Core::table('ocr_text'),array('text_content'", 'Pass 2 regression: OCR review writes destructively into the base OCR layer.');

// Pass 3: durable Future-24 mutations participate in server-side idempotency.
$contains($rest, 'private static function idempotent', 'Pass 3 regression: Future-24 idempotency helper is missing.');
foreach (array('anchor','ocr-correction','annotations-import','preferences-save','shelf-create','insight-event','handoff-save','reading-room','fingerprint') as $route) {
    $needle = 'self::idempotent($r,\'' . $route . '\'';
    $contains($rest, $needle, "Pass 3 regression: {$route} is not protected by Future-24 idempotency.");
}

// Pass 4: migration locking and schema completion are both verified, not inferred from table presence.
$contains($schema, "SCHEMA_REVISION = '2026-08-11-r10'", 'Pass 4 regression: schema revision verification marker is missing.');
$contains($schema, 'SHOW COLUMNS FROM', 'Pass 4 regression: migration no longer verifies required columns.');
$contains($schema, 'SHOW INDEX FROM', 'Pass 4 regression: migration no longer verifies required indexes.');
$contains($schema, 'option_value=%s', 'Pass 4 regression: migration lock release/takeover is not token-bound.');

// Pass 5: a public accessibility read cannot erase human verification through ?refresh=1.
$contains($a11y, 'pldr_a11y_refresh_forbidden', 'Pass 5 regression: accessibility refresh is no longer authority-gated.');
$contains($a11y, 'pldr_a11y_store', 'Pass 5 regression: accessibility persistence failures can be falsely reported as success.');

// Pass 6: offline chunks are context-bound and logout purge retries after IndexedDB failure.
$contains($vault, 'additionalData', 'Pass 6 regression: offline AES-GCM chunks are not bound with AAD.');
$contains($vault, 'aadVersion:1', 'Pass 6 regression: offline AAD version metadata is missing.');
$contains($future, 'r.onsuccess=clear', 'Pass 6 regression: logout purge does not wait for IndexedDB deletion success.');
$contains($future, '$cookie = \'pldr_vault_purge=;',  'Pass 6 regression: successful client purge does not clear the retry cookie.');

// Pass 7: integrity failure is fail-closed and preservation sampling rotates by verification age.
$contains($preservation, "'object_status'=>'quarantined'", 'Pass 7 regression: preservation checksum failure no longer quarantines the object.');
$contains($preservation, "revoke_document", 'Pass 7 regression: preservation integrity failure does not revoke access grants.');
$contains($preservation, 'last_verified_at IS NULL', 'Pass 7 regression: preservation scheduler no longer rotates by verification age.');

// Pass 8: personal-state inputs are bounded and database failures are visible.
$contains($shelves, 'pldr_shelf_store', 'Pass 8 regression: custom shelf insert failure can be reported as success.');
$contains($shelves, 'default_key', 'Pass 8 regression: concurrent default shelf creation lacks a deterministic uniqueness guard.');
$contains($prefs, 'max(90,min(180', 'Pass 8 regression: text-size preference is not server-clamped.');
$contains($prefs, "self::KEYS", 'Pass 8 regression: arbitrary preference namespaces are accepted.');

// Pass 9: provider-bound selected text is size-bounded, page-bound and browser storage failure is non-fatal.
$contains($derived, 'selection_belongs', 'Pass 9 regression: derived-text provider input is not bound to source text.');
$contains($context, 'selection_belongs', 'Pass 9 regression: knowledge-context provider input is not bound to source text.');
$contains($rooms, 'Reading-room anchor is too large', 'Pass 9 regression: reading-room provider anchor is unbounded.');
$contains($futureJs, 'F.storageGet', 'Pass 9 regression: blocked/corrupt localStorage can break the advanced reader.');
$contains($futureJs, 'F.storageSet', 'Pass 9 regression: local preference storage lacks a failure-safe writer.');

// Pass 10: durable writes fail visibly and external room side effects have a local reconciliation anchor first.
$contains($authority, 'pldr_authority_cache_store', 'Pass 10 regression: authority provenance cache failure can be reported as success.');
$contains($insights, 'pldr_insight_store', 'Pass 10 regression: reading-event database failure can be reported as success.');
$contains($fingerprint, 'pldr_fingerprint_store', 'Pass 10 regression: fingerprint persistence failure can be reported as success.');
$insertPos = strpos($rooms, "'status'=>'pending-provider'");
$providerPos = strpos($rooms, "apply_filters('pldr_create_reading_room_provider'");
if ($insertPos === false || $providerPos === false || $insertPos > $providerPos) { fwrite(STDERR, "Pass 10 regression: external reading-room provider can run before a local reconciliation record exists.\n"); exit(1); }

echo "File 12 fresh ten-round corrective-review regressions: PASS\n";
