<?php
/**
 * SHINEGUARD IDENTITY SERVICE
 * Responsibility: Authentication, Authorization, CSRF, and RBAC
 */

namespace ShineGuard\Services;

class IdentityService {
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && isset($_SESSION['username']);
    }

    public static function getUserRole() {
        return $_SESSION['role'] ?? 'guest';
    }

    public static function getUserId() {
        return $_SESSION['user_id'] ?? 0;
    }

    public static function canDo(string $action): bool {
        static $map = [
            'view_reports'         => ['System Admin', 'Maintenance Operator', 'System Observer'],
            'export_reports'       => ['System Admin'],
            'manage_schedules'     => ['System Admin'],
            'manage_cctv'          => ['System Admin'],
            'view_cctv'            => ['System Admin', 'Maintenance Operator', 'System Observer'],
            'manage_streetlights'  => ['System Admin'],
            'manage_users'         => ['System Admin'],
            'create_work_orders'   => ['System Admin'],
            'update_work_orders'   => ['System Admin'],
            'acknowledge_alerts'   => ['System Admin'],
            'view_settings'        => ['System Admin', 'Maintenance Operator', 'System Observer'],
            'manage_firebase'      => ['System Admin'],
            'view_activity_logs'   => ['System Admin'],
            'control_streetlights' => ['System Admin', 'Maintenance Operator'],
            'take_snapshots'       => ['System Admin'],
        ];
        return in_array(self::getUserRole(), $map[$action] ?? [], true);
    }

    public static function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    public static function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
    }

    public static function setAuthorized() {
        $_SESSION['last_auth_time'] = time();
    }

    public static function isRecentlyAuthorized() {
        if (!isset($_SESSION['last_auth_time'])) return false;
        return (time() - $_SESSION['last_auth_time']) < 300; // 5-minute window
    }

    public static function revokeAuthorization() {
        unset($_SESSION['last_auth_time']);
    }

    public static function verifyActionMfa($conn, $code) {
        $userId = self::getUserId();
        $stmt = $conn->prepare("SELECT mfa_secret, mfa_enabled FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();

        if (!$res || !$res['mfa_enabled']) {
            return false;
        }

        $secret = $res['mfa_secret'];
        if (function_exists('clearSecret')) {
            $secret = clearSecret($secret);
        }

        require_once __DIR__ . '/TOTPService.php';
        return TOTPService::verifyCode($secret, $code);
    }
}
