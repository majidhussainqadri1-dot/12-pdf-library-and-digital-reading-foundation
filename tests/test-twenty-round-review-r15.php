<?php
$root = dirname(__DIR__);
$checks = array(
    'pdf-library-foundation-12/includes/class-pldr-reader.php' => array('pldr_ocr_query_long','pldr_progress_read','reader_interaction_provider_failed','related_content_provider_failed'),
    'pdf-library-foundation-12/includes/class-pldr-rest.php' => array('pldr_reader_thumbnail_read','pldr_items_read','pldr_item_read'),
    'pdf-library-foundation-12/includes/class-pldr-access.php' => array('pldr_access_rate_policy','pldr_derivative_read','access_revoke_failed'),
    'pdf-library-foundation-12/includes/class-pldr-ingest.php' => array('pldr_ingest_transaction_start','pldr_pdf_size_policy','pldr_scanner_provider_failed'),
    'pdf-library-foundation-12/includes/class-pldr-rights.php' => array('pldr_case_read','pldr_document_status_transaction','pldr_pack_version_conflict','immutable_version'),
    'pdf-library-foundation-12/includes/class-pldr-privacy.php' => array('anonymize_id_batch','privacy_export_read_failed','table_check_failed'),
    'pdf-library-foundation-12/includes/class-pldr-admin.php' => array('pldr_repair_search_read','Key rotation commit failed'),
    'pdf-library-foundation-12/includes/class-pldr-future-derived-text.php' => array('pldr_derive_document_read'),
    'pdf-library-foundation-12/includes/class-pldr-future-rooms.php' => array('pldr_room_document_read','pldr_room_anchor_source_read'),
    'pdf-library-foundation-12/includes/class-pldr-future-shelves.php' => array('pldr_shelf_defaults_'),
    'pdf-library-foundation-12/includes/class-pldr-future.php' => array('future_retention_policy_provider_failed'),
    'pdf-library-foundation-12/includes/class-pldr-future-preservation.php' => array('pldr_preservation_edition_read'),
    'pdf-library-foundation-12/includes/class-pldr-future-rest.php' => array('pldr_future_mutation_failed','pldr_offline_rights_state'),
    'pdf-library-foundation-12/includes/class-pldr-future-schema.php' => array('read_errors'),
    'pdf-library-foundation-12/includes/class-pldr-schema.php' => array('read_errors','legacy_batch_read_failed','legacy_transaction_start_failed','legacy-spl-user-data-'),
    'pdf-library-foundation-12/includes/class-pldr-future-fingerprint.php' => array('pldr_fingerprint_existing_read'),
    'pdf-library-foundation-12/includes/class-pldr-core.php' => array("'phase'=>'expired-cleanup'",'current_edition'),
);
$missing = array();
foreach ($checks as $path => $markers) {
    $full = $root . '/' . $path;
    $source = is_file($full) ? file_get_contents($full) : false;
    if (!is_string($source)) { $missing[] = $path . ':<file>'; continue; }
    foreach ($markers as $marker) if (false === strpos($source, $marker)) $missing[] = $path . ':' . $marker;
}
if ($missing) {
    fwrite(STDERR, "R15 regression markers missing:\n - " . implode("\n - ", $missing) . "\n");
    exit(1);
}
echo "R15 twenty-round corrective-review regression contract: PASS\n";
