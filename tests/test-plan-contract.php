<?php
$root = dirname(__DIR__);
$plugin = $root . '/pdf-library-foundation-12';
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($plugin));
$source = '';
foreach ($files as $file) {
    if (!$file->isFile()) continue;
    $ext = strtolower(pathinfo($file->getPathname(), PATHINFO_EXTENSION));
    if (in_array($ext, array('php','js','css','txt'), true)) $source .= "\n" . file_get_contents($file->getPathname());
}
$must = array(
    'PLDR_VERSION', 'pdf-library-digital-reading', 'PLDR_Core::DOCUMENT_TYPES',
    'aes-256-gcm', 'PLD3', 'Accept-Ranges: bytes', 'audience_hash', 'operation',
    'pldr_malware_scan', 'pldr_ocr_extract_text', 'PDFDocumentPublished.v1', 'dead-letter',
    'PDFBookPackRegistered.v1', 'table_of_contents', 'update_manifest',
    'requestFullscreen', 'data-action="zoom-in"', 'data-action="print"', 'data-action="highlight"',
    'downloads/session', 'Range:', 'data-download-pause', 'data-download-resume',
    'PDFRightsCaseAppealed.v1', 'approve_document', 'rescan_document',
    'spf_register_contract', 'suas_register_slot', 'spui_register_component_provider',
    'pldr_user_entitled', 'ReadingProgressUpdated.v1', 'Idempotency-Key',
    'Urdu/Arabic/RTL', 'direction:rtl'
);
foreach ($must as $needle) {
    if (strpos($source, $needle) === false) { fwrite(STDERR, "Missing plan contract marker: {$needle}\n"); exit(1); }
}
$forbidden = array(
    'This release accepts American English public content only.',
    'file_get_contents($file[\'tmp_name\'])',
    "wp_salt('auth').'|spl-pdf'"
);
foreach ($forbidden as $needle) {
    if (strpos($source, $needle) !== false) { fwrite(STDERR, "Forbidden legacy pattern remains: {$needle}\n"); exit(1); }
}
$core = file_get_contents($plugin . '/includes/class-pldr-core.php');
if (substr_count($core, "=>") < 32) { fwrite(STDERR, "Governed taxonomy coverage appears incomplete.\n"); exit(1); }
if (!is_dir($plugin) || !is_file($plugin . '/pdf-library-digital-reading.php')) { fwrite(STDERR, "Canonical package layout missing.\n"); exit(1); }
echo "PLDR new-plan static contract: PASS\n";
