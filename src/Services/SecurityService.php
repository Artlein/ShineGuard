<?php
/**
 * SHINEGUARD SECURITY SERVICE
 * Responsibility: AES-256 encryption, Hashing, and Secret Management
 */

namespace ShineGuard\Services;

class SecurityService {
    
    // In a production environment, this key would be stored in the ENV variables
    private static $secret_key = 'SHINEGUARD_INFRA_KEY_2026';
    private static $cipher = "AES-256-CBC";

    /**
     * Encrypt a string using AES-256-CBC
     */
    public static function encrypt($data) {
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        
        $encrypted = openssl_encrypt($data, self::$cipher, self::$secret_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt an AES-256-CBC encrypted string
     */
    public static function decrypt($data) {
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        
        return openssl_decrypt($encrypted, self::$cipher, self::$secret_key, 0, $iv);
    }

    /**
     * Generate a cryptographic signature for a log entry
     */
    public static function generateLogSignature($prev_hash, $user_id, $action, $details, $ip) {
        $payload = $prev_hash . $user_id . $action . $details . $ip;
        return hash_hmac('sha256', $payload, self::$secret_key);
    }
}
