<?php
/**
 * V12.3 / WP-05 / CF04 boundary: real AES-256-GCM chunk tests with only a
 * transient synthetic CI-generated key and synthetic random bytes.
 * This does NOT test production key escrow, Hostinger private storage,
 * malware scanner, legal licence or an actual published PDF.
 */
if(!defined('ABSPATH') || getenv('CI')!=='true' ||
 getenv('FILE12_CI_EPHEMERAL_DB')!=='yes' ||
 home_url('/')!=='http://file12-ci.invalid/'){
 fwrite(STDERR,"File12 native crypto fixture refused outside disposable CI.\n");exit(86);
}
function f12_crypto_assert($condition,string $message):void {
 if(!$condition){fwrite(STDERR,"FILE12 NATIVE CRYPTO FAIL: ".$message."\n");exit(1);}
 echo "PASS: ".$message."\n";
}
f12_crypto_assert(extension_loaded('openssl'),'OpenSSL available for true native AES-GCM test');
$key_id='ci-ephemeral';
f12_crypto_assert(!defined('PLDR_PDF_MASTER_KEYS'),
 'synthetic key test never overwrites an installed key ring');
$key=random_bytes(32); // Process-local ephemeral key, never logged or committed.
define('PLDR_PDF_MASTER_KEYS',array($key_id=>'base64:'.base64_encode($key)));
define('PLDR_PDF_ACTIVE_KEY_ID',$key_id);
unset($key);
$error='';
f12_crypto_assert(PLDR_Crypto::is_ready($error),'actual transient test key ring is usable');
$input=tempnam(sys_get_temp_dir(),'f12-data-');
$cipher=tempnam(sys_get_temp_dir(),'f12-ciph-');
$corrupt=tempnam(sys_get_temp_dir(),'f12-bad-');
$output=tempnam(sys_get_temp_dir(),'f12-out-');
$paths=array($input,$cipher,$corrupt,$output);
register_shutdown_function(static function()use($paths):void{
 foreach($paths as $path) if(is_file($path))@unlink($path);
});
$size=2*1048576+413; // Three GCM chunks, including a short final chunk.
$plain=random_bytes($size); // Not a licensed book and not patient content.
f12_crypto_assert(file_put_contents($input,$plain,LOCK_EX)===$size,
 'synthetic plaintext stored only in ephemeral runner');
$sha=hash('sha256',$plain);
$meta=[];$error='';
f12_crypto_assert(PLDR_Crypto::encrypt_file($input,$cipher,$meta,$error)===true,
 'chunked AES-256-GCM source encrypt successful');
f12_crypto_assert(($meta['format']??'')==='PLD3' &&
 ($meta['key_id']??'')===$key_id &&
 ($meta['original_size']??0)===$size &&
 ($meta['chunk_size']??0)===1048576,
 'ciphertext header declares expected authenticated key, size and chunk');
$error='';
f12_crypto_assert(PLDR_Crypto::verify_file($cipher,$sha,$error)===true,
 'native encrypted object decrypt/hash round-trip verified');
$extract=[];$error='';
f12_crypto_assert(PLDR_Crypto::decrypt_to_file($cipher,$output,$error)===true &&
 hash_file('sha256',$output)===$sha,
 'private object decrypt-to-file exact bytes verified');
foreach([[0,0],[100,1000],[1048576-50,1048576+50],
 [1048576-5,1048576+2000],[0,$size-1],[$size-200,$size-1]] as $range){
 $result='';$metadata=[];$error='';
 $ok=PLDR_Crypto::stream_range($cipher,$range[0],$range[1],
     static function(string $chunk)use(&$result):void{$result.=$chunk;},
     $metadata,$error);
 f12_crypto_assert($ok===true &&
  hash_equals(substr($plain,$range[0],$range[1]-$range[0]+1),$result),
  'authenticated exact-byte range '.(string)$range[0].'-'.(string)$range[1]);
}
$received='';$error='';
f12_crypto_assert(PLDR_Crypto::stream_range($cipher,$size,null,
   static function(string $chunk)use(&$received):void{$received.=$chunk;},
   $meta,$error)===false && $received==='',
 'out-of-bounds range cannot return plaintext');
f12_crypto_assert(copy($cipher,$corrupt),'ciphertext copied for isolated tamper test');
$headerLength=4+1+1+strlen($key_id)+4+8;
$tagOffset=$headerLength+4+12; // First chunk tag begins after size + nonce.
$fh=fopen($corrupt,'c+b');
f12_crypto_assert(is_resource($fh)&&fseek($fh,$tagOffset)===0,
 'tamper fixture locates authenticated chunk tag');
$original=fread($fh,1);
f12_crypto_assert(is_string($original)&&strlen($original)===1,
 'tamper fixture reads one authenticated tag byte');
fseek($fh,$tagOffset);
f12_crypto_assert(fwrite($fh,chr(ord($original)^1))===1,
 'tamper fixture modifies exactly one AES-GCM tag byte');
fclose($fh);
$error='';$received='';
$ok=PLDR_Crypto::stream_range($corrupt,0,100,
  static function(string $chunk)use(&$received):void{$received.=$chunk;},
  $meta,$error);
f12_crypto_assert($ok===false && $received==='' &&
 str_contains($error,'authentication'),
 'tampered first ciphertext chunk is rejected before any plaintext is returned');
$error='';
f12_crypto_assert(PLDR_Crypto::verify_file($corrupt,$sha,$error)===false,
 'corrupt ciphertext cannot pass verified object digest');
$error='';
f12_crypto_assert(PLDR_Crypto::decrypt_to_file($corrupt,$output,$error)===false &&
 !is_file($output),
 'failed decryption deletes incomplete plaintext temporary output');
echo "FILE12 NATIVE DISPOSABLE CRYPTO RANGE/TAMPER QA PASS; no live key, scanner, CF04 or original PDF claim.\n";
