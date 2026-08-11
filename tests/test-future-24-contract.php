<?php
$root = dirname(__DIR__) . '/pdf-library-foundation-12';
$future = file_get_contents($root . '/includes/class-pldr-future.php');
$rest = file_get_contents($root . '/includes/class-pldr-future-rest.php');
$entry = file_get_contents($root . '/pdf-library-digital-reading.php');
$css = file_get_contents($root . '/assets/future-reader.css');
$js = '';
foreach (array('future-reader.js','future-reader-scholar.js','future-reader-personal.js','future-reader-vault.js') as $name) {
    $path = $root . '/assets/' . $name;
    if (!is_file($path)) { fwrite(STDERR, "Missing Future-24 asset: {$name}\n"); exit(1); }
    $js .= "\n" . file_get_contents($path);
}

for ($i = 1; $i <= 24; $i++) {
    $id = sprintf('F12-FUT-%03d', $i);
    if (strpos($future, $id) === false) { fwrite(STDERR, "Missing approved Future-24 ID: {$id}\n"); exit(1); }
}

$requiredFiles = array(
    'class-pldr-future-schema.php','class-pldr-future-data.php','class-pldr-future-derived-text.php',
    'class-pldr-future-anchors.php','class-pldr-future-citations.php','class-pldr-future-authority.php',
    'class-pldr-future-ocr-lab.php','class-pldr-future-annotations.php','class-pldr-future-iiif.php',
    'class-pldr-future-search.php','class-pldr-future-preferences.php','class-pldr-future-shelves.php',
    'class-pldr-future-insights.php','class-pldr-future-handoff.php','class-pldr-future-a11y.php',
    'class-pldr-future-rooms.php','class-pldr-future-context.php','class-pldr-future-corpus.php',
    'class-pldr-future-preservation.php','class-pldr-future-fingerprint.php','class-pldr-future-rest.php',
);
foreach ($requiredFiles as $file) {
    if (!is_file($root . '/includes/' . $file)) { fwrite(STDERR, "Missing Future-24 implementation file: {$file}\n"); exit(1); }
}

$must = array(
    'Version: 1.1.0-rc.1','PLDR_DB_VERSION', "'1.1.0'", 'PLDR_CONTRACT_VERSION',
    'Advanced Reflow Reading Mode','Read Aloud / Text-to-Speech Reader','Smart Table of Contents & Outline Recovery',
    'Edition Comparison Laboratory','Precise Scholarly Anchors','Citation Export Center',
    'Global Bibliographic Authority Enrichment','OCR Quality Laboratory','Portable Annotation Standard',
    'IIIF Digital Library Interoperability','Inside-Book Search Heatmap','Encrypted Offline Reading Vault',
    'Ultra-Low-Bandwidth Reader','Multiple Reading Layouts','Personal Smart Shelves',
    'Private Reading Insights','Cross-Device Reading Session Handoff','Accessibility Quality Inspector',
    'Scholarly Reading Rooms','Knowledge Context Sidebar','AI-Ready Corpus Manifest',
    'Translation & Transliteration Overlay','Digital Preservation Laboratory','Visual Duplicate & Scan-Fingerprint Detection',
);
$combined = $entry . "\n" . $future . "\n" . $rest . "\n" . $js . "\n" . $css;
foreach ($must as $needle) {
    if (strpos($combined, $needle) === false) { fwrite(STDERR, "Missing Future-24 contract marker: {$needle}\n"); exit(1); }
}

$boundaryMarkers = array(
    'canonical_overwrite' => 'Bibliographic enrichment must not silently overwrite canonical truth.',
    'automatic_merge' => 'Scan fingerprint feature must not auto-merge editions.',
    'pldr_ai_corpus_allowed' => 'AI corpus must be deny-by-default/allowlist governed.',
    'pending-provider' => 'Scholarly room context must preserve provider boundary.',
    'non-extractable WebCrypto key' => 'Offline vault must use a non-extractable client key.',
    'derived, not authorial' => 'Derived translation/transliteration must not impersonate source text.',
);
$allPhp = '';
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/includes')) as $f) {
    if ($f->isFile() && strtolower($f->getExtension()) === 'php') $allPhp .= "\n" . file_get_contents($f->getPathname());
}
foreach ($boundaryMarkers as $needle => $why) {
    if (stripos($allPhp . "\n" . $js, $needle) === false) { fwrite(STDERR, "Missing boundary: {$why}\n"); exit(1); }
}

$forbidden = array(
    "require_once __DIR__ . '/class-pldr-future-reading.php'",
    "require_once __DIR__ . '/class-pldr-future-standards.php'",
    "require_once __DIR__ . '/class-pldr-future-personal.php'",
    "require_once __DIR__ . '/class-pldr-future-preservation-lab.php'",
    'automatic_merge\'=>true',
);
foreach ($forbidden as $needle) {
    if (strpos($combined . "\n" . $allPhp, $needle) !== false) { fwrite(STDERR, "Forbidden Future-24 regression marker: {$needle}\n"); exit(1); }
}

if (strpos($future, "future-reader-scholar.js") === false || strpos($future, "future-reader-personal.js") === false || strpos($future, "future-reader-vault.js") === false) {
    fwrite(STDERR, "Future-24 secondary clients are not enqueued by the canonical loader.\n"); exit(1);
}
if (!is_file($root . '/assets/future-reader.css')) { fwrite(STDERR, "Future-24 stylesheet missing.\n"); exit(1); }
if (strpos($css, 'spread-rtl') === false || strpos($css, 'prefers-reduced-motion') === false) { fwrite(STDERR, "Future-24 RTL/reduced-motion styles missing.\n"); exit(1); }

echo "PLDR Future Digital Reading Intelligence 24 contract: PASS\n";
