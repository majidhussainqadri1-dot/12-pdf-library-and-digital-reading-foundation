<?php

defined('ABSPATH') || exit;

/** Exact stored-object integrity verification: ciphertext bytes + authenticated plaintext. */
final class PLDR_Object_Integrity {
    public static function verify(array $object,string $path,string &$error='',array &$evidence=array()):bool {
        $evidence=array('encrypted_sha256_verified'=>false,'plaintext_sha256_verified'=>false);
        if(!is_file($path)){$error='Encrypted object file is unavailable.';return false;}
        $expected_cipher=strtolower(trim((string)($object['encrypted_sha256']??'')));
        if(''===$expected_cipher){$error='Encrypted object checksum metadata is missing.';return false;}
        $actual_cipher=hash_file('sha256',$path);
        if(!is_string($actual_cipher)||''===$actual_cipher){$error='Encrypted object checksum could not be computed.';return false;}
        if(!hash_equals($expected_cipher,strtolower($actual_cipher))){$error='Encrypted object checksum mismatch.';return false;}
        $evidence['encrypted_sha256_verified']=true;
        $expected_plain=(string)($object['sha256']??'');
        if(''===trim($expected_plain)){$error='Plaintext object checksum metadata is missing.';return false;}
        if(!PLDR_Crypto::verify_file($path,$expected_plain,$error))return false;
        $evidence['plaintext_sha256_verified']=true;
        return true;
    }
}
