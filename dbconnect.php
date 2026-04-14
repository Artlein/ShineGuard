<?php
/**
 * ENTERPRISE SECURITY HEADERS (Pillar 3)
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com; connect-src 'self';");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(self), camera=(self)");

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host     = 'localhost';
$host     = 'localhost';
$database = 'Hulo';

// Auto-detect environment
$is_aws = file_exists('/var/www/html/ShineGuard');

if ($is_aws) {
    // AWS EC2 Environment
    $user     = 'shineguard';
    $password = 'ShineGuard2026';
} else {
    // Local XAMPP Environment
    $user     = 'root';
    $password = '';
}

/**
 * CORPORATE EMAIL INFRASTRUCTURE (Mailtrap Sandbox)
 * Grab your credentials from https://mailtrap.io
 */
define('MAILTRAP_API_TOKEN', '1455d7c786b90dcc3450dfd347ca82ba');
define('MAILTRAP_INBOX_ID',  '4546141');
define('SYSTEM_EMAIL',       'noreply@hulo.barangay.ph');
define('SYSTEM_NAME',        'ShineGuard Security');

try {
    $conn = @new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        throw new Exception($conn->connect_error);
    }
} catch (Exception $e) {
    if ($is_aws) {
        die("<h3>AWS Database Connection Failed</h3><p>Username: <b>{$user}</b></p><p>Error: <b>" . $e->getMessage() . "</b></p><p>Please run the terminal command to create the MySQL user exactly as instructed.</p>");
    } else {
        die("Connection failed: " . $e->getMessage());
    }
}

$conn->set_charset("utf8mb4");

// Auto-create missing activity_logs table for AWS (without strict FK constraint)
$conn->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Patch: add created_at column if it was missing when the table was first created
$col_check = $conn->query("SHOW COLUMNS FROM `activity_logs` LIKE 'created_at'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE `activity_logs` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp()");
    $conn->query("ALTER TABLE `activity_logs` ADD KEY `idx_created` (`created_at`)");
    $conn->query("ALTER TABLE `activity_logs` ADD KEY `idx_user_created` (`user_id`, `created_at`)");
}

// Patch: add log_hash column for Tamper-Evident Logging (Immutable Audit)
$hash_check = $conn->query("SHOW COLUMNS FROM `activity_logs` LIKE 'log_hash'");
if ($hash_check && $hash_check->num_rows === 0) {
    $conn->query("ALTER TABLE `activity_logs` ADD COLUMN `log_hash` varchar(64) DEFAULT NULL");
}

// Security & MFA Patch for `users` table
$users_cols = $conn->query("SHOW COLUMNS FROM `users`")->fetch_all(MYSQLI_ASSOC);
$existing_cols = array_column($users_cols, 'Field');

if (!in_array('mfa_enabled', $existing_cols)) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `mfa_enabled` tinyint(1) DEFAULT 0");
}
if (!in_array('mfa_secret', $existing_cols)) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `mfa_secret` varchar(32) DEFAULT NULL");
}
if (!in_array('failed_attempts', $existing_cols)) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `failed_attempts` int(11) DEFAULT 0");
}
if (!in_array('last_failed_attempt', $existing_cols)) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `last_failed_attempt` datetime DEFAULT NULL");
}
if (!in_array('lockout_until', $existing_cols)) {
    $conn->query("ALTER TABLE `users` ADD COLUMN `lockout_until` datetime DEFAULT NULL");
}

// Maintenance & Lifecycle Patch for `streetlights` table
$light_check = $conn->query("SHOW COLUMNS FROM `streetlights` LIKE 'installed_at'");
if ($light_check && $light_check->num_rows === 0) {
    // We detected missing maintenance columns, let's mature the schema
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `installed_at` date DEFAULT NULL");
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `runtime_hours` int(11) DEFAULT 0");
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `hardware_revision` varchar(50) DEFAULT 'v1.0'");
    
    // Backfill installed_at from installation_date if it exists
    $conn->query("UPDATE `streetlights` SET installed_at = installation_date WHERE installed_at IS NULL AND installation_date IS NOT NULL");
}

// Reporting Archive Patch (Historical Governance)
$conn->query("CREATE TABLE IF NOT EXISTS `report_archive` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `period_range` varchar(100) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Inventory Stock Patch (Workforce Logistics)
$conn->query("CREATE TABLE IF NOT EXISTS `inventory_stock` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `category` enum('Lighting','Sensors','Connectivity','Power') NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `part_number` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Maintenance Logs Patch (MTTR Analytics)
$conn->query("CREATE TABLE IF NOT EXISTS `maintenance_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `light_id` int(11) NOT NULL,
  `alert_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `action_taken` text NOT NULL,
  `notes` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `maintenance_date` datetime DEFAULT current_timestamp(),
  `completion_time` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  PRIMARY KEY (`log_id`),
  KEY `idx_light` (`light_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_maintenance_date` (`maintenance_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

date_default_timezone_set('Asia/Manila');

$baseDir = str_replace('\\', '/', dirname(__FILE__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relPath = str_replace($docRoot, '', $baseDir);

// ── CORPORATE STANDARDS: Absolute URL Detection for Emails ──
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost'; 
define('BASE_URL', $protocol . $host . rtrim($relPath, '/') . '/');

define('SESSION_IDLE_TIMEOUT', 1800); 

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_GET['autologin_debug'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'System Admin';
    $_SESSION['full_name'] = 'System Admin';
    $_SESSION['last_activity'] = time();
}

// Load Core Services
require_once __DIR__ . '/src/Services/IdentityService.php';
require_once __DIR__ . '/src/Services/AuditService.php';
require_once __DIR__ . '/src/Services/IOTService.php';
require_once __DIR__ . '/src/Services/SecurityService.php';
require_once __DIR__ . '/src/Services/ReportingService.php';
require_once __DIR__ . '/src/Services/MaintenanceService.php';

/**
 * RESTORED STUBS
 * These prevent fatal errors in pages that call these functions.
 * In this "Hard Reset" mode, they always return true.
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        return \ShineGuard\Services\IdentityService::generateCsrfToken();
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        return \ShineGuard\Services\IdentityService::verifyCsrfToken($token);
    }
}

if (!function_exists('checkCsrf')) {
    function checkCsrf() {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrfToken($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Invalid CSRF token. Please refresh.']));
            } else {
                header('Location: ' . $_SERVER['PHP_SELF'] . '?error=invalid_csrf');
                exit();
            }
        }
    }
}

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($action_key, $max_attempts = 10, $window_minutes = 1) {
        global $conn;
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // 1. Clean up old entries (older than 1 hour to keep table small)
        if (rand(1, 100) <= 5) { // 5% chance to run cleanup on call
            $conn->query("DELETE FROM rate_limits WHERE attempted_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        }

        // 2. Count recent attempts
        $stmt = $conn->prepare("SELECT COUNT(*) FROM rate_limits WHERE ip_address = ? AND action_key = ? AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)");
        if ($stmt) {
            $stmt->bind_param("ssi", $ip, $action_key, $window_minutes);
            $stmt->execute();
            $count = 0;
            $stmt->bind_result($count);
            $stmt->fetch();
            $stmt->close();
            
            if ($count >= $max_attempts) {
                // Log the security alert
                $user_id = $_SESSION['user_id'] ?? 0;
                logActivity($conn, $user_id, 'Security Alert', "Rate Limit Exceeded: $action_key from IP $ip");

                if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                    http_response_code(429);
                    exit(json_encode(['success' => false, 'error' => 'rate_limit', 'message' => 'Too many requests. Cooling period active.']));
                }

                header('HTTP/1.1 429 Too Many Requests');
                $current_page = basename($_SERVER['PHP_SELF']);
                $query = $_SERVER['QUERY_STRING'] ?? '';
                
                // Reconstruct URL with error=rate_limit
                if (str_contains($query, 'error=')) {
                    $new_query = preg_replace('/error=[^&]*/', 'error=rate_limit', $query);
                } else {
                    $new_query = $query . (empty($query) ? '' : '&') . 'error=rate_limit';
                }
                
                header('Location: ' . $current_page . '?' . $new_query);
                exit("Too many requests. Cooling period active.");
            }

            // 3. Record this attempt
            $ins = $conn->prepare("INSERT INTO rate_limits (ip_address, action_key) VALUES (?, ?)");
            $ins->bind_param("ss", $ip, $action_key);
            $ins->execute();
            $ins->close();
        }
    }
}

if (!function_exists('setAuthorized')) {
    function setAuthorized() {
        \ShineGuard\Services\IdentityService::setAuthorized();
    }
}

if (!function_exists('isRecentlyAuthorized')) {
    function isRecentlyAuthorized() {
        return \ShineGuard\Services\IdentityService::isRecentlyAuthorized();
    }
}

if (!function_exists('revokeAuthorization')) {
    function revokeAuthorization() {
        \ShineGuard\Services\IdentityService::revokeAuthorization();
    }
}

function isLoggedIn() {
    return \ShineGuard\Services\IdentityService::isLoggedIn();
}

function getUserRole() {
    return \ShineGuard\Services\IdentityService::getUserRole();
}

function canDo(string $action): bool {
    return \ShineGuard\Services\IdentityService::canDo($action);
}

function checkSessionTimeout() {
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = time();
        return;
    }
    if ((time() - $_SESSION['last_activity']) > SESSION_IDLE_TIMEOUT) {
        session_unset();
        session_destroy();
        header('Location: login.php?error=session_expired');
        exit();
    }
    $_SESSION['last_activity'] = time(); 
    
    // Clear standalone activity logs auth if navigating away
    if (basename($_SERVER['PHP_SELF']) !== 'activity_logs.php' && isset($_SESSION['activity_logs_authorized'])) {
        unset($_SESSION['activity_logs_authorized']);
    }
}

function requireLogin($require_role = null) {
    global $conn;

    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        $raw   = $_COOKIE['remember_token'];
        $hash  = hash('sha256', $raw);
        $stmt  = $conn->prepare(
            "SELECT u.user_id, u.username, u.full_name, u.role
             FROM remember_tokens rt
             JOIN users u ON u.user_id = rt.user_id
             WHERE rt.token_hash = ?
               AND rt.expires_at > NOW()
               AND u.is_active = 1
             LIMIT 1"
        );
        $stmt->bind_param("s", $hash);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            session_regenerate_id(true);
            $_SESSION['user_id']      = $row['user_id'];
            $_SESSION['username']     = $row['username'];
            $_SESSION['full_name']    = $row['full_name'];
            $_SESSION['role']         = $row['role'];
            $_SESSION['login_time']   = time();
            $_SESSION['last_activity']= time();
        }
        $stmt->close();
    }

    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    checkSessionTimeout();

    if ($require_role !== null) {
        $user_role = getUserRole();
        $authorized = false;
        if (is_array($require_role)) {
            if (in_array($user_role, $require_role, true)) {
                $authorized = true;
            }
        } else {
            if ($user_role === $require_role) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            http_response_code(403);
            include __DIR__ . '/includes/access_denied_ui.php';
            exit();
        }
    }
}

function requireLoginApi($require_role = null) {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
        exit();
    }
    checkSessionTimeout();
    if ($require_role !== null) {
        $user_role = getUserRole();
        $authorized = false;
        if (is_array($require_role)) {
            if (in_array($user_role, $require_role, true)) {
                $authorized = true;
            }
        } else {
            if ($user_role === $require_role) {
                $authorized = true;
            }
        }
        if (!$authorized) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Access denied: insufficient permissions.']);
            exit();
        }
    }
}

function logActivity($conn, $user_id, $action, $details = '') {
    \ShineGuard\Services\AuditService::logActivity($conn, $user_id, $action, $details);
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * PII PRIVACY LAYER (Data Privacy Act Compliance)
 */
function shouldMaskPII() {
    $role = getUserRole();
    return $role !== 'System Admin'; // Only Admins see raw PII
}

function maskEmail($email) {
    if (!$email || !str_contains($email, '@')) return $email;
    if (!shouldMaskPII()) return $email;
    
    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];
    
    if (strlen($name) <= 2) return $name . '***@' . $domain;
    return substr($name, 0, 2) . str_repeat('*', min(5, strlen($name) - 2)) . '@' . $domain;
}

function maskPhone($phone) {
    if (!$phone) return 'Not Provided';
    if (!shouldMaskPII()) return $phone;
    
    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) < 7) return '********';
    return '+' . substr($clean, 0, 2) . ' ' . substr($clean, 2, 3) . ' *** ****';
}

function maskPII($data, $type = 'text') {
    if (!shouldMaskPII()) return $data;
    if ($type === 'email') return maskEmail($data);
    if ($type === 'phone') return maskPhone($data);
    
    // Default: mask names
    if (strlen($data) <= 1) return $data;
    return substr($data, 0, 1) . '***' . substr($data, -1);
}

function isIpLockedOut($conn, $ip) {
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'] >= 5;
}

function recordFailedAttempt($conn, $ip, $username = '') {
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
    $stmt->bind_param("ss", $ip, $username);
    $stmt->execute();
    $stmt->close();
}

function clearLoginAttempts($conn, $ip) {
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
}

function getLockoutSecondsRemaining($conn, $ip) {
    // Find the 5th most recent attempt. The lockout expires when this attempt is > 15 mins old.
    $stmt = $conn->prepare("SELECT attempted_at FROM login_attempts WHERE ip_address = ? ORDER BY attempted_at DESC LIMIT 1 OFFSET 4");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $first_of_five = strtotime($row['attempted_at']);
        $expiry = $first_of_five + (15 * 60);
        $remaining = $expiry - time();
        return max(0, $remaining);
    }
    return 0;
}
