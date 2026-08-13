<?php

defined('ABSPATH') || exit;

final class PLDR_Crypto {
    private const MAGIC = 'PLD3';
    private const LEGACY_MAGIC = 'SPL2';
    private const VERSION = 1;
    private const CHUNK_SIZE = 1048576;

    public static function is_ready(?string &$message = null): bool {
        if (!extension_loaded('openssl') || !in_array('aes-256-gcm', openssl_get_cipher_methods(), true)) {
            $message = 'OpenSSL AES-256-GCM is unavailable.';
            return false;
        }
        $keys = self::keys();
        if (!$keys) {
            $message = 'Define PLDR_PDF_MASTER_KEYS with at least one backed-up 32-byte key.';
            return false;
        }
        $active = self::active_key_id();
        if (!$active || !isset($keys[$active])) {
            $message = count($keys)>1
                ? 'Multiple File 12 master keys are configured; define PLDR_PDF_ACTIVE_KEY_ID explicitly before encryption/key rotation.'
                : 'PLDR_PDF_ACTIVE_KEY_ID is not present in the configured File 12 key ring.';
            return false;
        }
        $message = '';
        return true;
    }

    public static function active_key_id(): string {
        $keys = self::keys();
        $configured = defined('PLDR_PDF_ACTIVE_KEY_ID') ? sanitize_key((string) PLDR_PDF_ACTIVE_KEY_ID) : '';
        if ($configured && isset($keys[$configured])) return $configured;
        if (defined('SPL_PDF_ACTIVE_KEY_ID')) {
            $legacy = sanitize_key((string) SPL_PDF_ACTIVE_KEY_ID);
            if ($legacy && isset($keys[$legacy])) return $legacy;
        }
        if (1 === count($keys)) {
            reset($keys);
            return (string) key($keys);
        }
        return '';
    }

    public static function encrypt_file(string $input, string $output, array &$metadata = array(), string &$error = ''): bool {
        if (!self::is_ready($error) || !is_readable($input)) {
            $error = $error ?: 'Source object is not readable.';
            return false;
        }
        $size = filesize($input);
        if (false === $size || $size < 1) {
            $error = 'Source object size is invalid.';
            return false;
        }
        $key_id = self::active_key_id();
        $keys = self::keys();
        $key = $keys[$key_id];
        $in = fopen($input, 'rb');
        $out = fopen($output, 'wb');
        if (is_resource($out)) @chmod($output, 0600);
        if (!$in || !$out) {
            if (is_resource($in)) fclose($in);
            if (is_resource($out)) fclose($out);
            $error = 'Private object streams could not be opened.';
            return false;
        }

        $ok = true;
        try {
            $header = self::MAGIC . chr(self::VERSION) . chr(strlen($key_id)) . $key_id . pack('N', self::CHUNK_SIZE) . self::pack_u64((int) $size);
            if (!self::write_all($out, $header)) throw new RuntimeException('Encrypted object header write failed.');
            $header_hash = hash('sha256', $header, true);
            $index = 0;
            while (!feof($in)) {
                $plain = fread($in, self::CHUNK_SIZE);
                if (false === $plain) throw new RuntimeException('Source object read failed.');
                if ('' === $plain) break;
                $nonce = random_bytes(12);
                $tag = '';
                $aad = $header_hash . pack('N', $index);
                $cipher = openssl_encrypt($plain, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $nonce, $tag, $aad, 16);
                if (false === $cipher || 16 !== strlen($tag)) throw new RuntimeException('Authenticated object encryption failed.');
                if (!self::write_all($out, pack('N', strlen($cipher)) . $nonce . $tag . $cipher)) throw new RuntimeException('Encrypted object chunk write failed.');
                $index++;
            }
            if (!fflush($out)) throw new RuntimeException('Encrypted object flush failed.');
            $metadata = array('format'=>self::MAGIC,'key_id'=>$key_id,'chunk_size'=>self::CHUNK_SIZE,'original_size'=>(int)$size,'encrypted_sha256'=>hash_file('sha256',$output) ?: '');
        } catch (Throwable $e) {
            $ok = false;
            $error = $e->getMessage();
        }
        fclose($in); fclose($out);
        if (!$ok) @unlink($output);
        return $ok;
    }

    public static function inspect(string $path, array &$metadata = array(), string &$error = ''): bool {
        $reader = self::open_reader($path, $error); if (!$reader) return false;
        $metadata = $reader['metadata']; fclose($reader['stream']); return true;
    }

    public static function verify_file(string $path, string $expected_sha256 = '', string &$error = ''): bool {
        $ctx = hash_init('sha256'); $meta = array();
        $ok = self::stream_range($path, 0, null, static function (string $chunk) use ($ctx): void { hash_update($ctx, $chunk); }, $meta, $error);
        if (!$ok) return false;
        $hash = hash_final($ctx);
        if ($expected_sha256 && !hash_equals(strtolower($expected_sha256), strtolower($hash))) { $error = 'Decrypted object checksum mismatch.'; return false; }
        return true;
    }

    public static function plaintext_sha256(string $path, string &$error = '') {
        $ctx = hash_init('sha256'); $meta = array();
        $ok = self::stream_range($path, 0, null, static function (string $chunk) use ($ctx): void { hash_update($ctx, $chunk); }, $meta, $error);
        return $ok ? hash_final($ctx) : false;
    }

    public static function decrypt_to_file(string $encrypted, string $output, string &$error = ''): bool {
        $out = fopen($output, 'wb');
        if (is_resource($out)) @chmod($output, 0600);
        if (!$out) { $error = 'Temporary decrypt target could not be opened.'; return false; }
        $meta = array();
        $ok = self::stream_range($encrypted, 0, null, static function (string $chunk) use ($out): void { if (!self::write_all($out, $chunk)) throw new RuntimeException('Temporary decrypted object could not be written.'); }, $meta, $error);
        fclose($out); if (!$ok) @unlink($output); return $ok;
    }

    public static function stream_range(string $path, int $start, ?int $end, callable $writer, array &$metadata = array(), string &$error = ''): bool {
        $reader = self::open_reader($path, $error); if (!$reader) return false;
        $stream = $reader['stream']; $metadata = $reader['metadata']; $size=(int)$metadata['original_size']; $chunk_size=(int)$metadata['chunk_size'];
        if ($start < 0 || $start >= $size) { fclose($stream); $error='Requested byte range starts outside the object.'; return false; }
        $end = null === $end ? $size - 1 : min($end, $size - 1);
        if ($end < $start) { fclose($stream); $error='Requested byte range is invalid.'; return false; }
        $first=intdiv($start,$chunk_size); $last=intdiv($end,$chunk_size); $record_size=32+$chunk_size; $offset=(int)$reader['header_length']+($first*$record_size);
        if (0 !== fseek($stream,$offset,SEEK_SET)) { fclose($stream); $error='Encrypted object range seek failed.'; return false; }
        try {
            for ($index=$first;$index<=$last;$index++) {
                $length_raw=self::read_exact($stream,4); if(false===$length_raw) throw new RuntimeException('Encrypted object chunk header is truncated.');
                $unpacked=unpack('Nvalue',$length_raw); $length=(int)($unpacked['value']??0); if($length<1||$length>$chunk_size) throw new RuntimeException('Encrypted object chunk length is invalid.');
                $nonce=self::read_exact($stream,12); $tag=self::read_exact($stream,16); $cipher=self::read_exact($stream,$length);
                if(false===$nonce||false===$tag||false===$cipher) throw new RuntimeException('Encrypted object chunk is truncated.');
                $aad=$reader['header_hash'].pack('N',$index); $plain=openssl_decrypt($cipher,'aes-256-gcm',$reader['key'],OPENSSL_RAW_DATA,$nonce,$tag,$aad);
                if(false===$plain) throw new RuntimeException('Encrypted object authentication failed.');
                $chunk_start=$index*$chunk_size; $slice_start=max(0,$start-$chunk_start); $slice_end=min(strlen($plain)-1,$end-$chunk_start);
                if($slice_end>=$slice_start) $writer(substr($plain,$slice_start,$slice_end-$slice_start+1));
            }
        } catch(Throwable $e) { fclose($stream); $error=$e->getMessage(); return false; }
        fclose($stream); return true;
    }

    private static function open_reader(string $path, string &$error = '') {
        if (!is_readable($path)) { $error='Encrypted private object is unavailable.'; return false; }
        $stream=fopen($path,'rb'); if(!$stream){$error='Encrypted private object could not be opened.';return false;}
        $magic=self::read_exact($stream,4); if(!in_array($magic,array(self::MAGIC,self::LEGACY_MAGIC),true)){fclose($stream);$error='Unsupported encrypted object format.';return false;}
        $version_raw=self::read_exact($stream,1);$key_len_raw=self::read_exact($stream,1);if(false===$version_raw||false===$key_len_raw){fclose($stream);$error='Encrypted object header is incomplete.';return false;}
        $version=ord($version_raw);$key_len=ord($key_len_raw);if(self::VERSION!==$version||$key_len<1||$key_len>64){fclose($stream);$error='Encrypted object header is invalid.';return false;}
        $key_id=self::read_exact($stream,$key_len);$chunk_raw=self::read_exact($stream,4);$size_raw=self::read_exact($stream,8);if(false===$key_id||false===$chunk_raw||false===$size_raw){fclose($stream);$error='Encrypted object header is incomplete.';return false;}
        $chunk=unpack('Nvalue',$chunk_raw);$chunk_size=(int)($chunk['value']??0);$size=self::unpack_u64($size_raw);if($chunk_size<65536||$chunk_size>8*1024*1024||$size<1){fclose($stream);$error='Encrypted object limits are invalid.';return false;}
        $keys=self::keys();if(!isset($keys[$key_id])){fclose($stream);$error='Required encryption key is not configured; restore the backed-up key ring.';return false;}
        $header=$magic.$version_raw.$key_len_raw.$key_id.$chunk_raw.$size_raw;
        return array('stream'=>$stream,'key'=>$keys[$key_id],'header_hash'=>hash('sha256',$header,true),'header_length'=>strlen($header),'metadata'=>array('format'=>$magic,'key_id'=>$key_id,'chunk_size'=>$chunk_size,'original_size'=>$size));
    }

    private static function keys(): array {
        $sources=array(); if(defined('SPL_PDF_MASTER_KEYS'))$sources[]=SPL_PDF_MASTER_KEYS; if(defined('SPL_PDF_MASTER_KEY'))$sources[]=array('legacy'=>SPL_PDF_MASTER_KEY); if(defined('PLDR_PDF_MASTER_KEYS'))$sources[]=PLDR_PDF_MASTER_KEYS;
        $keys=array(); foreach($sources as $raw){if(is_string($raw)){$decoded=json_decode($raw,true);$raw=is_array($decoded)?$decoded:array();}if(!is_array($raw))continue;foreach($raw as $id=>$value){$id=sanitize_key((string)$id);$key=self::normalize_key($value);if($id&&false!==$key)$keys[$id]=$key;}} return $keys;
    }

    private static function normalize_key($value) {
        if (!is_string($value)) return false;
        if (str_starts_with($value,'base64:')) { $decoded=base64_decode(substr($value,7),true); return is_string($decoded)&&32===strlen($decoded)?$decoded:false; }
        if (str_starts_with($value,'hex:')) { $hex=substr($value,4);$decoded=ctype_xdigit($hex)?hex2bin($hex):false;return is_string($decoded)&&32===strlen($decoded)?$decoded:false; }
        return 32===strlen($value)?$value:false;
    }
    private static function read_exact($stream,int $length){$buffer='';while(strlen($buffer)<$length&&!feof($stream)){$part=fread($stream,$length-strlen($buffer));if(false===$part)return false;$buffer.=$part;}return strlen($buffer)===$length?$buffer:false;}
    private static function write_all($stream,string $data):bool{$total=strlen($data);$written=0;while($written<$total){$n=fwrite($stream,substr($data,$written));if(false===$n||0===$n)return false;$written+=$n;}return true;}
    private static function pack_u64(int $value):string{$high=intdiv($value,4294967296);$low=$value-($high*4294967296);return pack('N2',$high,$low);}
    private static function unpack_u64(string $raw):int{$parts=unpack('Nhigh/Nlow',$raw);return $parts?(int)(($parts['high']*4294967296)+$parts['low']):0;}
}
