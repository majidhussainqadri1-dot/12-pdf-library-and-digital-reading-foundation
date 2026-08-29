<?php
$root = dirname(__DIR__);
$checks = array(
    'pdf-library-foundation-12/includes/class-pldr-reader.php' => array(
        'pldr_catalog_edition_read',
        'pldr_catalog_access_read',
        'pldr_progress_access_read',
        'pldr_progress_edition_read',
        'pldr_item_access_read',
        'pldr_item_edition_read',
        'pldr_items_access_read',
        'reading_item_tags_corrupt',
        'pldr_items_corrupt',
        '$visible=array();',
        '$interaction_dto=PLDR_Core::public_document_dto',
        'THUMBNAIL_GRANT_LIMIT = 50',
        'pldr_progress_event_atomic',
    ),
    'pdf-library-foundation-12/includes/class-pldr-core.php' => array(
        'public static function request_fingerprint',
        "'_request_hash'=>\$request_hash",
        'public static function scope_anonymous_idempotency_key',
    ),
    'pdf-library-foundation-12/includes/class-pldr-rest.php' => array(
        'pldr_idempotency_conflict',
        'pldr_document_access_read',
        'pldr_reader_access_read',
        'pldr_citation_access_read',
        'reading_item_tags_corrupt',
        'pldr_items_corrupt',
    ),
    'pdf-library-foundation-12/includes/class-pldr-future-rest.php' => array(
        "self::idempotent(\$r,'authority'",
        "self::idempotent(\$r,'offline-grant'",
        "self::idempotent(\$r,'derive-text'",
        "self::idempotent(\$r,'preservation-refresh'",
        "self::idempotent(\$r,'a11y-refresh'",
        'pldr_future_idempotency_conflict',
    ),
    'pdf-library-foundation-12/includes/class-pldr-future-preservation.php' => array(
        'public static function read',
        "'available'=>false",
    ),
    'pdf-library-foundation-12/includes/class-pldr-future-fingerprint.php' => array(
        'pldr_fingerprint_required',
        'Scan-fingerprint review is read-only',
    ),
    'pdf-library-foundation-12/includes/class-pldr-rights.php' => array(
        'queue_document_status_event',
        'pldr_case_event_atomic',
        'pldr_case_decision_event_atomic',
        'pldr_appeal_event_atomic',
        'pldr_publish_event_atomic',
        'pldr_rights_event_atomic',
        'pldr_pack_event_atomic',
    ),
    'pdf-library-foundation-12/includes/class-pldr-access.php' => array(
        'pldr_policy_event_atomic',
    ),
    'pdf-library-foundation-12/includes/class-pldr-future-rooms.php' => array(
        'pldr_room_event_atomic',
        'atomic-event-persistence-failed',
    ),
    'pdf-library-foundation-12/includes/class-pldr-ingest.php' => array(
        'Reliable ingest event could not be persisted atomically.',
        'Reliable publication event could not be persisted atomically.',
        'ocr_event_atomic_rollback',
    ),
    'pdf-library-foundation-12/includes/class-pldr-schema.php' => array(
        'legacy_completion_event_atomic_rollback',
        'Legacy migration reliable event could not be persisted atomically.',
    ),
);
$missing = array();
foreach ($checks as $path => $markers) {
    $full = $root . '/' . $path;
    $source = is_file($full) ? file_get_contents($full) : false;
    if (!is_string($source)) { $missing[] = $path . ':<file>'; continue; }
    foreach ($markers as $marker) {
        if (false === strpos($source, $marker)) $missing[] = $path . ':' . $marker;
    }
}
$reader = @file_get_contents($root . '/pdf-library-foundation-12/includes/class-pldr-reader.php');
if (!is_string($reader) || substr_count($reader, "if(''!==(string)\$wpdb->last_error)return self::state_html('error');") < 7) {
    $missing[] = 'class-pldr-reader.php:public-reader-db-failure-coverage';
}
if ($missing) {
    fwrite(STDERR, "R17 regression markers missing:\n - " . implode("\n - ", $missing) . "\n");
    exit(1);
}
echo "R17 twenty-round corrective-review regression contract: PASS\n";
