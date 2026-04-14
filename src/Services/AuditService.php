<?php
/**
 * SHINEGUARD AUDIT SERVICE
 * Responsibility: Logging, Rate Limiting, and Security Auditing
 */

namespace ShineGuard\Services;

class AuditService {
    
    public static function logActivity($conn, $user_id, $action, $details = '') {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Handle anonymous/system actions (ID 0 or NULL)
        $user_id = ($user_id <= 0) ? null : $user_id;
        
        // 1. Get the hash of the PREVIOUS log entry (The "Chain")
        $prev_hash = self::getLatestLogHash($conn);
        
        // 2. Generate the signature for THIS log entry
        $signature = \ShineGuard\Services\SecurityService::generateLogSignature($prev_hash, $user_id, $action, $details, $ip);

        // 3. Insert into DB
        $stmt = $conn->prepare(
            "INSERT INTO activity_logs (user_id, action, details, log_hash, ip_address) VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issss", $user_id, $action, $details, $signature, $ip);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * Retrieves the latest log hash to continue the chain
     */
    public static function getLatestLogHash($conn) {
        $res = $conn->query("SELECT log_hash FROM activity_logs ORDER BY log_id DESC LIMIT 1");
        if ($res && $row = $res->fetch_assoc()) {
            return $row['log_hash'] ?? str_repeat('0', 64); // Initial block uses zeros
        }
        return str_repeat('0', 64);
    }

    public static function checkRateLimit($conn, $action_key, $max_attempts = 10, $window_minutes = 1) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Cleanup 
        if (rand(1, 100) <= 5) {
            $conn->query("DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }

        // Count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND action_key = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        if ($stmt) {
            $stmt->bind_param("ssi", $ip, $action_key, $window_minutes);
            $stmt->execute();
            $count = 0;
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            
            if ($count >= $max_attempts) {
                return false; // Rate limit hit
            }

            // Record
            $ins = $conn->prepare("INSERT INTO rate_limits (ip_address, action_key) VALUES (?, ?)");
            $ins->bind_param("ss", $ip, $action_key);
            $ins->execute();
            $ins->close();
        }
        return true;
    }

    public static function recordFailedAttempt($conn, $ip, $username = '') {
        $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
        $stmt->bind_param("ss", $ip, $username);
        $stmt->execute();
        $stmt->close();
    }

    public static function clearLoginAttempts($conn, $ip) {
        $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
        $stmt->bind_param("s", $ip);
        $stmt->execute();
        $stmt->close();
    }
}
