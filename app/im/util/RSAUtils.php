<?php

namespace app\im\util;


use app\im\exception\KeyPairGenerateException;

class RSAUtils {
    public const configargs = [
        'digest_alg' => 'sha512',
        'private_key_bits' => 1024,
        'private_key_type' => OPENSSL_KEYTYPE_RSA,
    ];
    
    public const PRIVATE_KEY_TYPE = 0;
    public const PUBLIC_KEY_TYPE = 1;
    
    /**
     * 生成 RSA 密钥对.
     * @param array $configargs 配置参数
     * @return array 0 是私钥, 1 是公钥
     */
    public static function genKeyPair(array $configargs=null): array {
        $config = $configargs? 
            array_merge(self::configargs, $configargs):
            self::configargs;
        $res = openssl_pkey_new($config);
        $privKey="";
        openssl_pkey_export($res, $privKey);
        $details = openssl_pkey_get_details($res);
        $pubKey = $details["key"];
        openssl_pkey_free($res);
        if (!$privKey || !$pubKey ||  
            !is_string($privKey) || !is_string($pubKey)) {
            im_log("error", "密钥对生成失败! prikey: ", $privKey, ", pubkey: ", $pubKey, ", details: ", $details);
            throw new KeyPairGenerateException();
        }
        return [
            $privKey,
            $pubKey
        ];
    }
    
    /**
     * 通过密钥进行 RSA 加密
     * @param string $text 明文
     * @param string $key 密钥
     * @param integer $keyType 加密 key 类型.
     * @param integer $blockSize 数据块大小, 修改 private_key_bits 后要设置合适的大小.
     */
    public static function encrypt($text, $key,  $keyType = self::PRIVATE_KEY_TYPE, $blockSize = 110): string {
        $encrypted = [];
        
        $planData = str_split($text, $blockSize);
        foreach ($planData as $chunk) {
            $partialEncrypted ="";
            if (self::PUBLIC_KEY_TYPE != $keyType)
                $encryptionOk = openssl_private_encrypt($chunk, $partialEncrypted, $key, OPENSSL_PKCS1_PADDING);
            else
                $encryptionOk = openssl_public_encrypt($chunk, $partialEncrypted, $key, OPENSSL_PKCS1_PADDING);
            if (!$encryptionOk) return false;
            array_push($encrypted, $partialEncrypted);
        }
        return implode($encrypted);
    }
    
    public static function decrypt($text, $key,  $keyType = self::PUBLIC_KEY_TYPE, $blockSize = 128): string {
        $decrypted = [];
        
        $data = str_split($text, $blockSize);
        foreach ($data as $chunk) {
            $partial  ="";
            if (self::PUBLIC_KEY_TYPE != $keyType)
                $decryptionOK  = openssl_private_decrypt($chunk, $partial, $key, OPENSSL_PKCS1_PADDING);
            else
                $decryptionOK = openssl_public_decrypt($chunk, $partial, $key, OPENSSL_PKCS1_PADDING);
            if (!$decryptionOK) return false;
            array_push($decrypted, $partial);
        }
        return implode($decrypted);
    }
}