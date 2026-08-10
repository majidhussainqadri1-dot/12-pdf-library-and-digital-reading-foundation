<?php
require __DIR__ . '/bootstrap.php';
$key = random_bytes(32);
define('PLDR_PDF_MASTER_KEYS', array('test-v1' => 'base64:' . base64_encode($key)));
define('PLDR_PDF_ACTIVE_KEY_ID', 'test-v1');
require dirname(__DIR__) . '/pdf-library-foundation-12/includes/class-pldr-crypto.php';

$plain = tempnam(sys_get_temp_dir(), 'pldr-plain-');
$enc = tempnam(sys_get_temp_dir(), 'pldr-enc-');
$data = "%PDF-1.7\n" . random_bytes(2 * 1024 * 1024 + 517) . "\n%%EOF\n";
file_put_contents($plain, $data);
$meta = array(); $error = '';
assert(PLDR_Crypto::encrypt_file($plain, $enc, $meta, $error), $error);
assert($meta['format'] === 'PLD3');
assert(PLDR_Crypto::verify_file($enc, hash('sha256', $data), $error), $error);
assert(PLDR_Crypto::plaintext_sha256($enc, $error) === hash('sha256', $data));

$start = 12345; $end = 1024 * 1024 + 777;
$range = '';
assert(PLDR_Crypto::stream_range($enc, $start, $end, static function ($chunk) use (&$range) { $range .= $chunk; }, $meta, $error), $error);
assert($range === substr($data, $start, $end - $start + 1));

// Tamper with authenticated ciphertext, never the header.
$fh = fopen($enc, 'r+b');
fseek($fh, 128, SEEK_SET);
$b = fread($fh, 1);
fseek($fh, 128, SEEK_SET);
fwrite($fh, chr(ord($b) ^ 0x01));
fclose($fh);
$error = '';
assert(!PLDR_Crypto::verify_file($enc, '', $error));
assert($error !== '');
@unlink($plain); @unlink($enc);
echo "PLDR crypto/range/tamper: PASS\n";
