<?php
namespace ShineGuard\Services;

/**
 * SHINEGUARD TOTP SERVICE
 * Handles Time-Based One-Time Passwords (Google Authenticator)
 * Pure PHP interpretation for MFA implementation without Composer dependencies.
 */
class TOTPService {
    
    private static $base32chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new 16-character Base32 secret for Google Authenticator.
     */
    public static function generateSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Generate the otpauth URI and return a Google Chart API QR Code URL.
     */
    public static function getQRCodeUrl($name, $secret, $issuer = 'ShineGuard') {
        $encodedIssuer = rawurlencode($issuer);
        $encodedName = rawurlencode($name);
        $otpauthUrl = "otpauth://totp/{$encodedIssuer}:{$encodedName}?secret={$secret}&issuer={$encodedIssuer}&algorithm=SHA1&digits=6&period=30";
        $encodedOtpauthUrl = rawurlencode($otpauthUrl);
        return "https://chart.googleapis.com/chart?chs=200x200&chld=M|0&cht=qr&chl={$encodedOtpauthUrl}";
    }

    /**
     * Verify a 6-digit code against the stored secret.
     * Allows a window of 1 interval before and after (±30 seconds) to account for clock drift.
     */
    public static function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals((string)$calculatedCode, (string)$code)) {
                return true;
            }
        }
        return false;
    }

    private static function getCode($secret, $timeSlice) {
        $secretKey = self::base32Decode($secret);
        
        // Pack time into 8 bytes
        $timeData = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // HMAC-SHA1
        $hash = hash_hmac('sha1', $timeData, $secretKey, true);
        
        // Extract 4 bytes based on offset
        $offset = ord(substr($hash, -1)) & 0x0F;
        $hashPart = substr($hash, $offset, 4);
        
        // Unpack to integer
        $value = unpack('N', $hashPart);
        $value = $value[1];
        
        // Mask out highest bit
        $value = $value & 0x7FFFFFFF;
        
        // Modulo 1 million for 6 digits
        $modulo = pow(10, 6);
        $code = str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
        
        return $code;
    }

    private static function base32Decode($base32) {
        if (empty($base32)) return '';
        
        $base32 = strtoupper($base32);
        $base32chars = self::$base32chars;
        $binaryString = '';
        
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            $pos = strpos($base32chars, $char);
            if ($pos !== false) {
                $binaryString .= sprintf('%05b', $pos);
            }
        }
        
        $decoded = '';
        $binaryLength = strlen($binaryString);
        for ($i = 0; $i < $binaryLength; $i += 8) {
            $chunk = substr($binaryString, $i, 8);
            if (strlen($chunk) == 8) {
                $decoded .= chr(bindec($chunk));
            }
        }
        
        return $decoded;
    }
}
