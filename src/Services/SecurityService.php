<?php
/**
 * SHINEGUARD SECURITY SERVICE
 * Responsibility: AES-256 encryption, Hashing, Blind Indexing, and Secret Management
 *
 * Zero-Trust Architecture:
 *   - encrypt()           → AES-256-CBC for reversible PII (full_name, email, log details)
 *   - generateBlindIndex() → HMAC-SHA256 for fast searchable indexes on encrypted fields
 *   - generateLogSignature() → HMAC-SHA256 chain-of-trust for forensic audit logs
 *
 * KEY RULE: If the secret keys are lost, the database is permanently unrecoverable.
 * Store backups of the key in a secure physical location.
 */

namespace ShineGuard\Services;

class SecurityService {

    // AES-256 encryption key (must be consistent across Local & AWS)
    // In production, load from environment: $_ENV['SG_ENCRYPT_KEY']
    private static $secret_key = 'SHINEGUARD_INFRA_KEY_2026_v3_AES';
    private static $cipher = "AES-256-CBC";

    // Separate key for Blind Index (HMAC) to prevent key reuse attacks
    private static $blind_index_key = 'SHINEGUARD_BLIND_IDX_KEY_2026_v3';

    /**
     * Encrypt a string using AES-256-CBC.
     * Produces a Base64-encoded ciphertext safe for VARCHAR storage.
     */
    public static function encrypt($data) {
        if (empty($data)) return $data;
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        $iv = openssl_random_pseudo_bytes($iv_length);
        $encrypted = openssl_encrypt($data, self::$cipher, self::$secret_key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt an AES-256-CBC encrypted string.
     * Returns the original plaintext.
     */
    public static function decrypt($data) {
        if (empty($data)) return $data;
        $raw = base64_decode($data);
        if ($raw === false) return $data; // Not encrypted; return as-is (safe fallback)
        $iv_length = openssl_cipher_iv_length(self::$cipher);
        if (strlen($raw) <= $iv_length) return $data;
        $iv = substr($raw, 0, $iv_length);
        $encrypted = substr($raw, $iv_length);
        $decrypted = openssl_decrypt($encrypted, self::$cipher, self::$secret_key, 0, $iv);
        return ($decrypted !== false) ? $decrypted : $data;
    }

    /**
     * Generate a Blind Index for fast, secure searching of encrypted fields.
     *
     * How it works:
     *   Store this hash alongside the encrypted value.
     *   To search, hash the search term and compare hashes (never decrypt).
     *   A hacker who sees the hash cannot reverse it to find the original value.
     *
     * @param  string $value The plaintext value to index (e.g., "arvin@example.com")
     * @return string        A 32-char hex string safe for indexed VARCHAR columns
     */
    public static function generateBlindIndex($value) {
        if (empty($value)) return null;
        $normalized = strtolower(trim($value));
        return substr(hash_hmac('sha256', $normalized, self::$blind_index_key), 0, 32);
    }

    /**
     * Generate a cryptographic chain-of-trust signature for a log entry.
     * Each log entry is signed with a hash of the previous entry's hash,
     * making it mathematically provable if any log is altered or deleted.
     */
    public static function generateLogSignature($prev_hash, $user_id, $action, $details, $ip) {
        $payload = $prev_hash . $user_id . $action . $details . $ip;
        return hash_hmac('sha256', $payload, self::$secret_key);
    }
}

