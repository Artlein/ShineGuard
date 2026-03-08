<?php

$host     = 'localhost';
$user     = 'root';
$password = '';
$database = 'Hulo';

$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

date_default_timezone_set('Asia/Manila');

header('X-Frame-Options: DENY');

header('X-Content-Type-Options: nosniff');

header('Referrer-Policy: strict-origin-when-cross-origin');

header(
    "Content-Security-Policy: " .
    "default-src 'self'; " .
    "script-src 'self' 'unsafe-inline' https://www.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://maps.googleapis.com https://unpkg.com https://*.firebasedatabase.app https://*.firebaseapp.com; " .
    "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdnjs.cloudflare.com https://unpkg.com; " .
    "font-src 'self' https://fonts.gstatic.com https://unpkg.com; " .
    "img-src 'self' data: blob: https://*.googleapis.com https://*.gstatic.com https://*.tile.openstreetmap.org https://*.openstreetmap.org https://*.ngrok-free.dev https://*.ngrok-free.app; " .
    "connect-src 'self' https://*.firebaseio.com wss://*.firebaseio.com https://*.firebasedatabase.app wss://*.firebasedatabase.app https://*.firebaseapp.com https://maps.googleapis.com https://*.tile.openstreetmap.org; " .
    "frame-src 'none'; " .
    "object-src 'none';"
);

define('SESSION_IDLE_TIMEOUT', 1800); 

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => false,   
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['username']);
}

function getUserRole() {
    return $_SESSION['role'] ?? 'guest';
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

    if ($require_role !== null && getUserRole() !== $require_role) {
        http_response_code(403);
        die('Access denied: insufficient permissions.');
    }
}

function requireLoginApi() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'error' => 'Unauthorized. Please log in.']);
        exit();
    }
    checkSessionTimeout();
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

define('MAX_LOGIN_ATTEMPTS',   5);   
define('LOGIN_LOCKOUT_MINUTES', 15); 

function isIpLockedOut($conn, $ip) {
    $window = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $stmt = $conn->prepare(
        "SELECT COUNT(*) AS cnt FROM login_attempts
         WHERE ip_address = ? AND attempted_at >= ?"
    );
    $stmt->bind_param("ss", $ip, $window);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int)$row['cnt'] >= MAX_LOGIN_ATTEMPTS;
}

function recordFailedAttempt($conn, $ip, $username = '') {
    $stmt = $conn->prepare(
        "INSERT INTO login_attempts (ip_address, username) VALUES (?, ?)"
    );
    $stmt->bind_param("ss", $ip, $username);
    $stmt->execute();
    $stmt->close();
}

function clearLoginAttempts($conn, $ip) {
    $stmt = $conn->prepare(
        "DELETE FROM login_attempts WHERE ip_address = ?"
    );
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $stmt->close();
}

function getLockoutSecondsRemaining($conn, $ip) {
    $window = date('Y-m-d H:i:s', time() - LOGIN_LOCKOUT_MINUTES * 60);
    $stmt = $conn->prepare(
        "SELECT MIN(attempted_at) AS oldest FROM login_attempts
         WHERE ip_address = ? AND attempted_at >= ?
         HAVING COUNT(*) >= ?"
    );
    $max = MAX_LOGIN_ATTEMPTS;
    $stmt->bind_param("ssi", $ip, $window, $max);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if (!$row || !$row['oldest']) return 0;
    $unlock_time = strtotime($row['oldest']) + LOGIN_LOCKOUT_MINUTES * 60;
    return max(0, $unlock_time - time());
}

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($submitted_token, $redirect_on_fail = null) {
    $valid = isset($_SESSION['csrf_token'])
          && hash_equals($_SESSION['csrf_token'], (string)$submitted_token);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if (!$valid) {
        if ($redirect_on_fail) {
            header('Location: ' . $redirect_on_fail);
            exit();
        }
        http_response_code(403);
        die('Invalid CSRF token.');
    }
}
?>
