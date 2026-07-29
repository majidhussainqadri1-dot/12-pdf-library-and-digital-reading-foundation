<?php
/**
 * Chunked authenticated encryption for private PDF storage.
 */
defined('ABSPATH') || exit;

final class SPL_Crypto {
    const MAGIC = "SPL2";
    const VERSION = 1;
    const DEFAULT_CHUNK_SIZE = 1048576; // 1 MiB.

    public static function active_key_id() {
        $keys = self::keys();
        if (!$keys) {
            return '';
        }

        $configured = defined('SPL_PDF_ACTIVE_KEY_ID') ? sanitize_key((string) SPL_PDF_ACTIVE_KEY_ID) : '';
        if ($configured && isset($keys[$configured])) {
            return $configured;
        }

        reset($keys);
        return (string) key($keys);
    }

    public static function is_ready(&$message = '') {
        if (!extension_loaded('openssl') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $message = 'The OpenSSL AES-256-GCM cipher is unavailable.';
            return false;
        }

        $keys = self::keys();
        if (!$keys) {
            $message = 'Define SPL_PDF_MASTER_KEYS or SPL_PDF_MASTER_KEY with a securely backed-up 32-byte key before uploading documents.';
            return false;
        }

        $active = self::active_key_id();
        if (!$active || !isset($keys[$active])) {
            $message = 'The active PDF encryption key ID is not present in the configured key ring.';
            return false;
        }

        $message = '';
        return true;
    }

    public static function encrypt_file($input_path, $output_path, &$metadata = array(), &$error = '') {
        if (!self::is_ready($error)) {
            return false;
        }
        if (!is_readable($input_path)) {
            $error = 'The uploaded PDF cannot be read.';
            return false;
        }

        $keys = self::keys();
        $key_id = self::active_key_id();
        $key = $keys[$key_id];
        $chunk_size = self::DEFAULT_CHUNK_SIZE;
        $size = filesize($input_path);
        if (false === $size) {
            $error = 'The uploaded PDF size could not be determined.';
            return false;
        }

        $in = fopen($input_path, 'rb');
        if (!$in) {
            $error = 'The uploaded PDF could not be opened.';
            return false;
        }

        $out = fopen($output_path, 'wb');
        if (!$out) {
            fclose($in);
            $error = 'The encrypted output file could not be created.';
            return false;
        }

        $ok = true;
        try {
            $header = self::MAGIC
                . chr(self::VERSION)
                . chr(strlen($key_id))
                . $key_id
                . pack('N', $chunk_size)
                . self::pack_uint64($size);

            if (!self::write_all($out, $header)) {
                throw new RuntimeException('The encrypted file header could not be written.');
            }

            $header_hash = hash('sha256', $header, true);
            $chunk_index = 0;
            while (!feof($in)) {
                $plain = fread($in, $chunk_size);
                if (false === $plain) {
                    throw new RuntimeException('The uploaded PDF could not be read completely.');
                }
                if ('' === $plain) {
                    break;
                }

                $nonce = random_bytes(12);
                $tag = '';
                $aad = $header_hash . pack('N', $chunk_index);
                $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
                if (false === $cipher || 16 !== strlen($tag)) {
                    throw new RuntimeException('A PDF encryption chunk failed.');
                }

                $record = pack('N', strlen($cipher)) . $nonce . $tag . $cipher;
                if (!self::write_all($out, $record)) {
                    throw new RuntimeException('The encrypted PDF could not be written completely.');
                }
                $chunk_index++;
            }

            if (!fflush($out)) {
                throw new RuntimeException('The encrypted PDF could not be flushed to storage.');
            }

            $metadata = array(
                'format' => 'SPL2',
                'key_id' => $key_id,
                'chunk_size' => $chunk_size,
                'original_size' => (int) $size,
            );
        } catch (Throwable $exception) {
            $ok = false;
            $error = $exception->getMessage();
        }

        fclose($in);
        fclose($out);

        if (!$ok) {
            @unlink($output_path);
        }

        return $ok;
    }

    public static function inspect($path, &$error = '') {
        $handle = self::open_reader($path, $error);
        if (!$handle) {
            return false;
        }
        $metadata = $handle['metadata'];
        fclose($handle['stream']);
        return $metadata;
    }

    public static function stream_file($path, $writer, &$metadata = array(), &$error = '') {
        $reader = self::open_reader($path, $error);
        if (!$reader) {
            return false;
        }

        $stream = $reader['stream'];
        $metadata = $reader['metadata'];
        $key = $reader['key'];
        $header_hash = $reader['header_hash'];
        $written = 0;
        $chunk_index = 0;
        $ok = true;

        try {
            while (!feof($stream)) {
                $length_raw = fread($stream, 4);
                if ('' === $length_raw) {
                    break;
                }
                if (false === $length_raw || 4 !== strlen($length_raw)) {
                    throw new RuntimeException('The encrypted PDF has a truncated chunk header.');
                }

                $length = unpack('Nlength', $length_raw);
                $length = isset($length['length']) ? (int) $length['length'] : 0;
                if ($length < 1 || $length > $metadata['chunk_size'] + 32) {
                    throw new RuntimeException('The encrypted PDF contains an invalid chunk length.');
                }

                $nonce = self::read_exact($stream, 12);
                $tag = self::read_exact($stream, 16);
                $cipher = self::read_exact($stream, $length);
                if (false === $nonce || false === $tag || false === $cipher) {
                    throw new RuntimeException('The encrypted PDF has a truncated chunk record.');
                }

                $aad = $header_hash . pack('N', $chunk_index);
                $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad);
                if (false === $plain) {
                    throw new RuntimeException('The encrypted PDF failed authentication and was not streamed.');
                }

                $written += strlen($plain);
                call_user_func($writer, $plain);
                $chunk_index++;
            }

            if ($written !== (int) $metadata['original_size']) {
                throw new RuntimeException('The decrypted PDF length does not match its authenticated header.');
            }
        } catch (Throwable $exception) {
            $ok = false;
            $error = $exception->getMessage();
        }

        fclose($stream);
        return $ok;
    }

    private static function open_reader($path, &$error = '') {
        if (!is_readable($path)) {
            $error = 'The encrypted PDF file is unavailable.';
            return false;
        }

        $stream = fopen($path, 'rb');
        if (!$stream) {
            $error = 'The encrypted PDF file could not be opened.';
            return false;
        }

        $magic = self::read_exact($stream, 4);
        if (self::MAGIC !== $magic) {
            fclose($stream);
            $error = 'This document uses an unsupported legacy encryption format and requires controlled migration.';
            return false;
        }

        $version_raw = self::read_exact($stream, 1);
        $key_length_raw = self::read_exact($stream, 1);
        if (false === $version_raw || false === $key_length_raw) {
            fclose($stream);
            $error = 'The encrypted PDF header is incomplete.';
            return false;
        }

        $version = ord($version_raw);
        $key_length = ord($key_length_raw);
        if (self::VERSION !== $version || $key_length < 1 || $key_length > 64) {
            fclose($stream);
            $error = 'The encrypted PDF header version or key ID is invalid.';
            return false;
        }

        $key_id = self::read_exact($stream, $key_length);
        $chunk_raw = self::read_exact($stream, 4);
        $size_raw = self::read_exact($stream, 8);
        if (false === $key_id || false === $chunk_raw || false === $size_raw) {
            fclose($stream);
            $error = 'The encrypted PDF header is incomplete.';
            return false;
        }

        $chunk = unpack('Nvalue', $chunk_raw);
        $chunk_size = isset($chunk['value']) ? (int) $chunk['value'] : 0;
        $original_size = self::unpack_uint64($size_raw);
        if ($chunk_size < 65536 || $chunk_size > 8 * 1024 * 1024 || $original_size < 1) {
            fclose($stream);
            $error = 'The encrypted PDF header contains invalid limits.';
            return false;
        }

        $header = self::MAGIC . $version_raw . $key_length_raw . $key_id . $chunk_raw . $size_raw;

        $keys = self::keys();
        if (!isset($keys[$key_id])) {
            fclose($stream);
            $error = 'The encryption key required for this PDF is not configured. Restore the backed-up key ring before continuing.';
            return false;
        }

        return array(
            'stream' => $stream,
            'key' => $keys[$key_id],
            'header_hash' => hash('sha256', $header, true),
            'metadata' => array(
                'format' => 'SPL2',
                'key_id' => $key_id,
                'chunk_size' => $chunk_size,
                'original_size' => $original_size,
            ),
        );
    }

    private static function keys() {
        $raw = array();

        if (defined('SPL_PDF_MASTER_KEYS')) {
            $configured = SPL_PDF_MASTER_KEYS;
            if (is_string($configured)) {
                $decoded = json_decode($configured, true);
                if (is_array($decoded)) {
                    $configured = $decoded;
                }
            }
            if (is_array($configured)) {
                $raw = $configured;
            }
        } elseif (defined('SPL_PDF_MASTER_KEY')) {
            $raw = array('default' => SPL_PDF_MASTER_KEY);
        }

        $keys = array();
        foreach ($raw as $id => $value) {
            $id = sanitize_key((string) $id);
            $key = self::normalize_key($value);
            if ($id && false !== $key) {
                $keys[$id] = $key;
            }
        }

        return $keys;
    }

    private static function normalize_key($value) {
        if (!is_string($value)) {
            return false;
        }

        if (0 === strpos($value, 'base64:')) {
            $decoded = base64_decode(substr($value, 7), true);
            return is_string($decoded) && 32 === strlen($decoded) ? $decoded : false;
        }

        if (0 === strpos($value, 'hex:')) {
            $hex = substr($value, 4);
            $decoded = ctype_xdigit($hex) ? hex2bin($hex) : false;
            return is_string($decoded) && 32 === strlen($decoded) ? $decoded : false;
        }

        return 32 === strlen($value) ? $value : false;
    }

    private static function read_exact($stream, $length) {
        $buffer = '';
        while (strlen($buffer) < $length && !feof($stream)) {
            $piece = fread($stream, $length - strlen($buffer));
            if (false === $piece) {
                return false;
            }
            $buffer .= $piece;
        }
        return strlen($buffer) === $length ? $buffer : false;
    }

    private static function write_all($stream, $data) {
        $length = strlen($data);
        $written = 0;
        while ($written < $length) {
            $result = fwrite($stream, substr($data, $written));
            if (false === $result || 0 === $result) {
                return false;
            }
            $written += $result;
        }
        return true;
    }

    private static function pack_uint64($value) {
        $high = (int) floor($value / 4294967296);
        $low = (int) ($value - ($high * 4294967296));
        return pack('N2', $high, $low);
    }

    private static function unpack_uint64($raw) {
        $parts = unpack('Nhigh/Nlow', $raw);
        if (!$parts) {
            return 0;
        }
        return (int) (($parts['high'] * 4294967296) + $parts['low']);
    }
}
