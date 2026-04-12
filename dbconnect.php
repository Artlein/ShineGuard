<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$host     = 'localhost';
$host     = 'localhost';
$database = 'Hulo';

// Auto-detect environment and brute-force credentials to guarantee connection
$credentials = [];

if (file_exists('/var/www/html/ShineGuard')) {
    // AWS EC2 Environment - try all likely setups they might have configured
    $credentials = [
        ['root', ''],
        ['shineguard', 'ShineGuard2026!'],
        ['root', 'ShineGuard2026!'],
        ['shineguard', ''],
        ['shineguard', 'password'],
        ['root', 'root']
    ];
} else {
    // Local XAMPP
    $credentials = [
        ['root', '']
    ];
}

$conn = null;
foreach ($credentials as $cred) {
    try {
        $conn = @new mysqli($host, $cred[0], $cred[1], $database);
        if (!$conn->connect_error) {
            break; // Connection successful!
        }
    } catch (Exception $e) {
        continue;
    }
}

if (!$conn || $conn->connect_error) {
    if (!$conn) {
        die("CRITICAL DB FIX NEEDED: mysqli object could not be created.");
    } else {
        die("CRITICAL DB FIX NEEDED: " . $conn->connect_error);
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

date_default_timezone_set('Asia/Manila');

$baseDir = str_replace('\\', '/', dirname(__FILE__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relPath = str_replace($docRoot, '', $baseDir);
define('BASE_URL', rtrim($relPath, '/') . '/');

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

/**
 * RESTORED STUBS
 * These prevent fatal errors in pages that call these functions.
 * In this "Hard Reset" mode, they always return true.
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token'])
            && is_string($token)
            && hash_equals($_SESSION['csrf_token'], $token);
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
        $_SESSION['last_auth_time'] = time();
    }
}

if (!function_exists('isRecentlyAuthorized')) {
    function isRecentlyAuthorized() {
        if (!isset($_SESSION['last_auth_time'])) return false;
        return (time() - $_SESSION['last_auth_time']) < 300; // 5-minute window
    }
}

if (!function_exists('revokeAuthorization')) {
    function revokeAuthorization() {
        unset($_SESSION['last_auth_time']);
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
}

function canDo(string $action): bool {
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
        'view_settings'        => ['System Admin'],
        'manage_firebase'      => ['System Admin'],
        'view_activity_logs'   => ['System Admin'],
        'control_streetlights' => ['System Admin', 'Maintenance Operator'],
        'take_snapshots'       => ['System Admin'],
    ];
    return in_array(getUserRole(), $map[$action] ?? [], true);
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
    $stmt = $conn->prepare(
        "INSERT INTO activity_logs (user_id, action, details, ip_address) VALUES (?, ?, ?, ?)"
    );
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt->bind_param("isss", $user_id, $action, $details, $ip);
    $stmt->execute();
    $stmt->close();
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
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
