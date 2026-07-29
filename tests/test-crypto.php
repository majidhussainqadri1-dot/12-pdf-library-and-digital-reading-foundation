<?php
// Standalone smoke test for the chunked SPL2 encryption format.
define('ABSPATH', __DIR__ . '/');
function sanitize_key($key) {
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $key));
}
define('SPL_PDF_MASTER_KEYS', array('v1' => 'base64:' . base64_encode(str_repeat('K', 32))));
define('SPL_PDF_ACTIVE_KEY_ID', 'v1');
require dirname(__DIR__) . '/pdf-library/includes/class-spl-crypto.php';

$source = tempnam(sys_get_temp_dir(), 'spl-source-');
$encrypted = tempnam(sys_get_temp_dir(), 'spl-encrypted-');
$restored = tempnam(sys_get_temp_dir(), 'spl-restored-');
$data = "%PDF-1.7\n" . random_bytes((2 * 1024 * 1024) + 333);
file_put_contents($source, $data);

$error = '';
$meta = array();
if (!SPL_Crypto::encrypt_file($source, $encrypted, $meta, $error)) {
    fwrite(STDERR, "Encryption failed: {$error}\n");
    exit(1);
}
if ('SPL2' !== $meta['format'] || 'v1' !== $meta['key_id'] || strlen($data) !== $meta['original_size']) {
    fwrite(STDERR, "Encryption metadata mismatch.\n");
    exit(1);
}

$out = fopen($restored, 'wb');
$stream_meta = array();
if (!SPL_Crypto::stream_file($encrypted, function ($chunk) use ($out) {
    fwrite($out, $chunk);
}, $stream_meta, $error)) {
    fwrite(STDERR, "Decryption failed: {$error}\n");
    exit(1);
}
fclose($out);
if (!hash_equals(hash_file('sha256', $source), hash_file('sha256', $restored))) {
    fwrite(STDERR, "Round-trip checksum mismatch.\n");
    exit(1);
}

$original_encrypted = file_get_contents($encrypted);
$key_length = ord($original_encrypted[5]);
$header_length = 4 + 1 + 1 + $key_length + 4 + 8;
$cursor = $header_length;
$records = array();
while ($cursor < strlen($original_encrypted)) {
    $length = unpack('Nvalue', substr($original_encrypted, $cursor, 4));
    $record_length = 4 + 12 + 16 + $length['value'];
    $records[] = substr($original_encrypted, $cursor, $record_length);
    $cursor += $record_length;
}
if (count($records) < 2) {
    fwrite(STDERR, "The test file did not create multiple encrypted chunks.\n");
    exit(1);
}
$reordered = substr($original_encrypted, 0, $header_length) . $records[1] . $records[0] . implode('', array_slice($records, 2));
file_put_contents($encrypted, $reordered);
$error = '';
$accepted = SPL_Crypto::stream_file($encrypted, function ($chunk) {}, $stream_meta, $error);
if ($accepted || false === strpos($error, 'authentication')) {
    fwrite(STDERR, "Chunk-order authentication failed.\n");
    exit(1);
}

file_put_contents($encrypted, $original_encrypted);
$handle = fopen($encrypted, 'r+b');
fseek($handle, -1, SEEK_END);
$byte = fread($handle, 1);
fseek($handle, -1, SEEK_END);
fwrite($handle, chr(ord($byte) ^ 1));
fclose($handle);
$error = '';
$accepted = SPL_Crypto::stream_file($encrypted, function ($chunk) {}, $stream_meta, $error);
if ($accepted || false === strpos($error, 'authentication')) {
    fwrite(STDERR, "Tamper detection failed.\n");
    exit(1);
}

@unlink($source);
@unlink($encrypted);
@unlink($restored);
echo "SPL2 crypto smoke test passed.\n";
