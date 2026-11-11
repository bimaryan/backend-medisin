<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;

class SecurityService
{
    /**
     * The encryption key used for payload encryption/decryption (32 bytes for AES-256-CBC).
     */
    protected string $key;

    public function __construct()
    {
        $configuredKey = config('app.security_payload_key', env('SECURITY_PAYLOAD_KEY'));

        if (! empty($configuredKey)) {
            $this->key = substr(hash('sha256', $configuredKey, true), 0, 32);
        } else {
            // Fallback derived from APP_KEY
            $appKey = config('app.key', '');
            if (str_starts_with($appKey, 'base64:')) {
                $raw = base64_decode(substr($appKey, 7), true);
                $this->key = $raw !== false && strlen($raw) === 32 ? $raw : substr(hash('sha256', $appKey, true), 0, 32);
            } else {
                $this->key = substr(hash('sha256', $appKey ?: 'MedisinSecretKey2026PayloadAES!', true), 0, 32);
            }
        }
    }

    /**
     * Get the active 32-byte binary encryption key.
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Encrypt a plain string into AES-256-CBC (Base64 encoded with prepended IV).
     */
    public function encrypt(string $value): string
    {
        $iv = openssl_random_pseudo_bytes(16);
        $ciphertext = openssl_encrypt($value, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);

        return base64_encode($iv.$ciphertext);
    }

    /**
     * Decrypt an encrypted payload string.
     * Supports:
     * 1. AES-256-CBC (IV + Ciphertext in Base64)
     * 2. CryptoJS OpenSSL-compatible format ("Salted__" / Base64 starting with U2FsdGVkX1)
     * 3. Laravel Crypt::encryptString payload
     * 4. Base64 encoded plaintext
     * 5. Plaintext fallback (passes through if not encrypted)
     */
    public function decrypt(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return $value;
        }

        // 1. Try Laravel standard Crypt::decryptString
        try {
            return Crypt::decryptString($value);
        } catch (\Throwable) {
            // Not Laravel Crypt format, continue to next
        }

        // 2. Try CryptoJS OpenSSL-compatible format (U2FsdGVkX1...)
        if (str_starts_with($value, 'U2FsdGVkX1')) {
            $cryptoJsDecrypted = $this->decryptCryptoJS($value, $this->key);
            if ($cryptoJsDecrypted !== null) {
                return $cryptoJsDecrypted;
            }
        }

        // 3. Try AES-256-CBC with prepended 16-byte IV (Base64)
        $decoded = base64_decode($value, true);
        if ($decoded !== false && strlen($decoded) > 16) {
            $iv = substr($decoded, 0, 16);
            $ciphertext = substr($decoded, 16);
            $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $this->key, OPENSSL_RAW_DATA, $iv);

            if ($decrypted !== false && $decrypted !== '' && mb_check_encoding($decrypted, 'UTF-8')) {
                return $decrypted;
            }
        }

        // 4. Fallback: return value as-is (e.g. if plain text was provided)
        return $value;
    }

    /**
     * Decrypt CryptoJS / OpenSSL formatted AES ciphertext ("Salted__" + 8-byte salt + ciphertext).
     */
    protected function decryptCryptoJS(string $data, string $key): ?string
    {
        $raw = base64_decode($data, true);
        if ($raw === false || strlen($raw) < 16) {
            return null;
        }

        if (substr($raw, 0, 8) !== 'Salted__') {
            return null;
        }

        $salt = substr($raw, 8, 8);
        $ciphertext = substr($raw, 16);

        // Derive key and IV using OpenSSL EVP_BytesToKey equivalent
        $rounds = 3;
        $data00 = $key.$salt;
        $md5Hash = [];
        $md5Hash[0] = md5($data00, true);
        $result = $md5Hash[0];

        for ($i = 1; $i < $rounds; $i++) {
            $md5Hash[$i] = md5($md5Hash[$i - 1].$data00, true);
            $result .= $md5Hash[$i];
        }

        $derivedKey = substr($result, 0, 32);
        $iv = substr($result, 32, 16);

        $decrypted = openssl_decrypt($ciphertext, 'AES-256-CBC', $derivedKey, OPENSSL_RAW_DATA, $iv);

        return ($decrypted !== false && mb_check_encoding($decrypted, 'UTF-8')) ? $decrypted : null;
    }
}
