<?php
/**
 * ── RESILIENCE BOOTSTRAPPER ──
 * Attempts to load the modern Composer autoloader. 
 * If missing, implements a fallback PSR-4 autoloader for internal ShineGuard classes.
 */
$autoload_path = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload_path)) {
    require_once $autoload_path;
} else {
    // FALLBACK: Register manual autoloader for the ShineGuard namespace
    spl_autoload_register(function ($class) {
        $prefix = 'ShineGuard\\';
        $base_dir = __DIR__ . '/src/';
        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0)
            return;
        $relative_class = substr($class, $len);
        $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
        if (file_exists($file))
            require $file;
    });

    // Provide a diagnostic warning in the logs (silent in production)
    if (($_SERVER['HTTP_HOST'] ?? '') === 'localhost') {
        error_log("⚠️ SHINEGUARD WARNING: vendor/autoload.php missing. Using fallback autoloader.");
    }
}

/**
 * ENTERPRISE SECURITY HEADERS (Pillar 3)
 */
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://unpkg.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https://*.tile.openstreetmap.org https://unpkg.com https://cdn-icons-png.flaticon.com; connect-src 'self';");
header("X-Frame-Options: SAMEORIGIN");
header("X-Content-Type-Options: nosniff");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(self), camera=(self)");

// ── SECURITY: Error disclosure disabled in production ──
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(0);
// Log errors server-side instead
ini_set('log_errors', 1);
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) @mkdir($log_dir, 0755, true);
ini_set('error_log', $log_dir . '/php_errors.log');

// ── CORPORATE STANDARDS: Configuration Layer ──
if (class_exists('Dotenv\Dotenv')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->safeLoad();
}

// Auto-detect environment
$is_aws = file_exists('/var/www/html/ShineGuard');

$host = $_ENV['DB_HOST'] ?? 'localhost';
$database = $_ENV['DB_NAME'] ?? 'Hulo';

if ($is_aws) {
    // AWS EC2 Environment
    $user = $_ENV['DB_USER_AWS'] ?? 'shineguard';
    $password = $_ENV['DB_PASS_AWS'] ?? 'ShineGuard2026';
} else {
    // Local XAMPP Environment
    $user = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
}

/**
 * CORPORATE EMAIL INFRASTRUCTURE (Mailtrap Sandbox)
 */
define('MAILTRAP_API_TOKEN', $_ENV['MAILTRAP_TOKEN'] ?? '1455d7c786b90dcc3450dfd347ca82ba');
define('MAILTRAP_INBOX_ID', $_ENV['MAILTRAP_INBOX'] ?? '4546141');
define('SYSTEM_EMAIL', $_ENV['SYSTEM_EMAIL'] ?? 'noreply@hulo.barangay.ph');
define('SYSTEM_NAME', $_ENV['SYSTEM_NAME'] ?? 'ShineGuard Security');


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
$conn->query("SET time_zone = '+08:00'");

// ── CORPORATE STANDARDS: System Configuration ──
date_default_timezone_set('Asia/Manila');

$baseDir = str_replace('\\', '/', dirname(__FILE__));
$docRoot = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$relPath = str_replace($docRoot, '', $baseDir);

// ── CORPORATE STANDARDS: Absolute URL Detection for Emails ──
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
define('BASE_URL', $protocol . $host . rtrim($relPath, '/') . '/');

define('SESSION_IDLE_TIMEOUT', 1800);

// ── CORPORATE STANDARDS: Identitiy & Security ──

if (session_status() === PHP_SESSION_NONE) {
    // ── SECURITY HARDENING: Session Stability (Pillar 3) ──
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    session_start();
}

// ── SECURITY: Unauthorized debug bypass removed ──
// Backdoor permanently removed. All access must go through login_process.php + MFA.

// Load Core Services
require_once __DIR__ . '/src/Services/IdentityService.php';
require_once __DIR__ . '/src/Services/AuditService.php';
require_once __DIR__ . '/src/Services/IOTService.php';
require_once __DIR__ . '/src/Services/SecurityService.php';
require_once __DIR__ . '/src/Services/ReportingService.php';
require_once __DIR__ . '/src/Services/MaintenanceService.php';
require_once __DIR__ . '/firebase_config.php';


/**
 * RESTORED STUBS
 * These prevent fatal errors in pages that call these functions.
 * In this "Hard Reset" mode, they always return true.
 */
if (!function_exists('generateCsrfToken')) {
    function generateCsrfToken()
    {
        return \ShineGuard\Services\IdentityService::generateCsrfToken();
    }
}

if (!function_exists('verifyCsrfToken')) {
    function verifyCsrfToken($token)
    {
        return \ShineGuard\Services\IdentityService::verifyCsrfToken($token);
    }
}

if (!function_exists('checkCsrf')) {
    function checkCsrf()
    {
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!verifyCsrfToken($token)) {
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                http_response_code(403);
                die(json_encode(['success' => false, 'error' => 'Invalid CSRF token. Please refresh.']));
            } else {
                // Redirect back to forgot_password.php specifically for recovery flow
                if (strpos($_SERVER['PHP_SELF'], 'forgot_password_process.php') !== false) {
                    header('Location: forgot_password.php?error=invalid_csrf');
                } else {
                    header('Location: ' . $_SERVER['PHP_SELF'] . '?error=invalid_csrf');
                }
                exit();
            }
        }
    }
}

if (!function_exists('checkRateLimit')) {
    function checkRateLimit($action_key, $max_attempts = 10, $window_minutes = 1)
    {
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

if (!function_exists('setRecentlyAuthorized')) {
    function setRecentlyAuthorized()
    {
        \ShineGuard\Services\IdentityService::setRecentlyAuthorized();
    }
}

if (!function_exists('isRecentlyAuthorized')) {
    function isRecentlyAuthorized()
    {
        return \ShineGuard\Services\IdentityService::isRecentlyAuthorized();
    }
}

if (!function_exists('revokeAuthorization')) {
    function revokeAuthorization()
    {
        \ShineGuard\Services\IdentityService::revokeAuthorization();
    }
}

function isLoggedIn()
{
    return \ShineGuard\Services\IdentityService::isLoggedIn();
}

function getUserRole()
{
    return \ShineGuard\Services\IdentityService::getUserRole();
}

function canDo(string $action): bool
{
    return \ShineGuard\Services\IdentityService::canDo($action);
}

function checkSessionTimeout()
{
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

function requireLogin($require_role = null)
{
    global $conn;

    if (!isLoggedIn() && isset($_COOKIE['remember_token'])) {
        $raw = $_COOKIE['remember_token'];
        $hash = hash('sha256', $raw);
        $stmt = $conn->prepare(
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
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['full_name'] = $row['full_name'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
        }
        $stmt->close();
    }

    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    checkSessionTimeout();

    // ── ZERO TRUST: Rapid Device Block Verification ──
    if (isset($_COOKIE['sg_device_fp'])) {
        $c_token = $_COOKIE['sg_device_fp'];
        $block_check = $conn->prepare("SELECT is_blocked FROM user_devices WHERE device_token = ? LIMIT 1");
        $block_check->bind_param("s", $c_token);
        $block_check->execute();
        $block_res = $block_check->get_result()->fetch_assoc();
        $block_check->close();
        if ($block_res && $block_res['is_blocked'] == 1) {
            // Instant termination
            setcookie('sg_device_fp', '', time() - 3600, '/');
            header('Location: logout.php?error=device_blocked');
            exit();
        }
    }

    // ── ZERO TRUST: Mandatory MFA Blockade ──
    if (isset($_SESSION['mfa_setup_required']) && $_SESSION['mfa_setup_required'] === true) {
        $allowed_pages = ['settings.php', 'logout.php'];
        $current_page = basename($_SERVER['PHP_SELF']);
        if (!in_array($current_page, $allowed_pages)) {
            header('Location: settings.php?tab=security&setup=1&force=true&msg=mfa_required');
            exit();
        }
    }

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

function requireLoginApi($require_role = null)
{
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
        exit();
    }
    checkSessionTimeout();

    // ── ZERO TRUST: Mandatory MFA Blockade for API ──
    if (isset($_SESSION['mfa_setup_required']) && $_SESSION['mfa_setup_required'] === true) {
        http_response_code(403);
        echo json_encode(['success' => false, 'error' => 'Security policy required: Please complete MFA setup in settings.']);
        exit();
    }
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

function logActivity($conn, $user_id, $action, $details = '')
{
    \ShineGuard\Services\AuditService::logActivity($conn, $user_id, $action, $details);
}

function sanitize($data)
{
    return htmlspecialchars(strip_tags(trim($data)));
}

/**
 * Retrieves a value from the system_config table with an optional default.
 */
function getSystemConfig($key, $default = null)
{
    global $conn;
    $stmt = $conn->prepare("SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['config_value'];
    }
    return $default;
}

/**
 * PII PRIVACY LAYER (Data Privacy Act Compliance)
 */
function shouldMaskPII()
{
    $role = getUserRole();
    return $role !== 'System Admin'; // Only Admins see raw PII
}

function maskEmail($email)
{
    if (!$email || !str_contains($email, '@'))
        return $email;
    if (!shouldMaskPII())
        return $email;

    $parts = explode('@', $email);
    $name = $parts[0];
    $domain = $parts[1];

    if (strlen($name) <= 2)
        return $name . '***@' . $domain;
    return substr($name, 0, 2) . str_repeat('*', min(5, strlen($name) - 2)) . '@' . $domain;
}

function maskPhone($phone)
{
    if (!$phone)
        return 'Not Provided';
    if (!shouldMaskPII())
        return $phone;

    $clean = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($clean) < 7)
        return '********';
    return '+' . substr($clean, 0, 2) . ' ' . substr($clean, 2, 3) . ' *** ****';
}

function maskPII($data, $type = 'text')
{
    if (!shouldMaskPII())
        return $data;
    if ($type === 'email')
        return maskEmail($data);
    if ($type === 'phone')
        return maskPhone($data);

    // Default: mask names
    if (strlen($data) <= 1)
        return $data;
    return substr($data, 0, 1) . '***' . substr($data, -1);
}

function isIpLockedOut($conn, $ip)
{
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM login_attempts WHERE ip_address = ? AND attempted_at >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) $row['cnt'] >= 5;
}

function recordFailedAttempt($conn, $ip, $username = '')
{
    $stmt = $conn->prepare("INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)");
    $stmt->bind_param("ss", $ip, $username);
    $stmt->execute();
    $stmt->close();
}

function clearLoginAttempts($conn, $ip)
{
    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
}

function getLockoutSecondsRemaining($conn, $ip)
{
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
