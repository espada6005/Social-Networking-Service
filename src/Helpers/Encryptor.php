<<?php
namespace Helpers;

class Encryptor {
    private const CIPHER = "aes-256-cbc";

    public static function encrypt(string $plainText): string {
        $key = self::getSecretKey();
        $iv = self::generateIv();

        $encrypted = openssl_encrypt($plainText, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv . $encrypted);
    }

    public static function decrypt(string $cipherText): string {
        $key = self::getSecretKey();
        $decoded = base64_decode($cipherText, true);

        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        $iv = substr($decoded, 0, $ivLength);
        $encrypted = substr($decoded, $ivLength);

        $decrypted = openssl_decrypt($encrypted, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv);

        return $decrypted;
    }

    private static function getSecretKey(): string {
        return hash('sha256', Settings::env("ENCRYPTION_KEY"), true);
    }

    private static function generateIv(): string {
        $ivLength = openssl_cipher_iv_length(self::CIPHER);
        return openssl_random_pseudo_bytes($ivLength);
    }

}