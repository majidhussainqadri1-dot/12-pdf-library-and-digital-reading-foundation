<?php
$root = dirname(__DIR__);
$checks = array(
    'pdf-library-foundation-12/includes/class-pldr-rest.php' => array('pldr_mutation_exception','pldr_idempotency_required','pldr_document_read','pldr_reader_edition_read','pldr_download_edition_read'),
    'pdf-library-foundation-12/assets/reader.js' => array('progress-${cfg.editionId}-${page}-${Date.now()}','bookmark-${cfg.editionId}-${page}-${Date.now()}','delete-${item.id}-${Date.now()}'),
    'pdf-library-foundation-12/includes/class-pldr-future-rest.php' => array('pldr_future_idempotency_required','PLDR_Future_Shelves::list','PLDR_Future_Fingerprint::candidates'),
    'pdf-library-foundation-12/includes/class-pldr-core.php' => array('pldr_outbox_encode','pldr_outbox_store','audit-store-failed','public static function audit'),
    'pdf-library-foundation-12/includes/class-pldr-rights.php' => array('pldr_case_event_reconcile','pldr_case_decision_event_reconcile','pldr_appeal_event_reconcile','pldr_publish_event_reconcile','pldr_pack_event_reconcile','pldr_rights_event_reconcile'),
    'pdf-library-foundation-12/includes/class-pldr-access.php' => array('pldr_policy_event_reconcile'),
    'pdf-library-foundation-12/includes/class-pldr-reader.php' => array('pldr_progress_event_reconcile'),
    'pdf-library-foundation-12/includes/class-pldr-ingest.php' => array('pldr_derivative_schedule_reconcile','pldr_cover_reconcile','pldr_cover_dimensions','RESOURCETYPE_MEMORY','ocr_provider_provenance_missing','partial-truncated','pldr_pdf_trailing_payload','pldr_scanner_provenance','pldr_rescan_quarantine_reconcile'),
    'pdf-library-foundation-12/includes/class-pldr-future-data.php' => array('pldr_future_edition_read','pldr_future_access_read'),
    'pdf-library-foundation-12/includes/class-pldr-future-iiif.php' => array('pldr_iiif_document_read','pldr_iiif_edition_read'),
    'pdf-library-foundation-12/includes/class-pldr-schema.php' => array('migration_lock_read_failed','migration_lock_takeover_failed','migration_lock_release_failed'),
    'pdf-library-foundation-12/includes/class-pldr-future-schema.php' => array('future_migration_lock_takeover_failed','future_migration_lock_release_failed'),
    'pdf-library-foundation-12/includes/class-pldr-future-rooms.php' => array('pldr_room_event_reconcile'),
    'pdf-library-foundation-12/includes/class-pldr-future-corpus.php' => array('pldr_corpus_document_read'),
);
$missing = array();
foreach ($checks as $path => $markers) {
    $full = $root . '/' . $path;
    $source = is_file($full) ? file_get_contents($full) : false;
    if (!is_string($source)) { $missing[] = $path . ':<file>'; continue; }
    foreach ($markers as $marker) if (false === strpos($source, $marker)) $missing[] = $path . ':' . $marker;
}
if ($missing) {
    fwrite(STDERR, "R16 regression markers missing:\n - " . implode("\n - ", $missing) . "\n");
    exit(1);
}
echo "R16 twenty-round corrective-review regression contract: PASS\n";
