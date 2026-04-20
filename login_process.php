<?php
require_once 'dbconnect.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: login.php');
    exit();
}

$ip       = $_SERVER['REMOTE_ADDR'];
$email    = sanitize($_POST['email'] ?? '');
$password = $_POST['password']       ?? '';
$remember = isset($_POST['remember']);

if (isIpLockedOut($conn, $ip)) {
    $secs_left = getLockoutSecondsRemaining($conn, $ip);
    $mins = ceil($secs_left / 60);
    header("Location: login.php?error=locked&mins=$mins");
    exit();
}

// ── ZERO-TRUST: Use Blind Index for fast, secure email lookup ──
// We never decrypt all emails to search — we hash the input and match the index.
$email_blind_idx = \ShineGuard\Services\SecurityService::generateBlindIndex($email);

$stmt = $conn->prepare(
    "SELECT user_id, username, email, password_hash, full_name, role, is_active, failed_attempts, lockout_until, mfa_secret, mfa_enabled
     FROM users WHERE email_blind_index = ? LIMIT 1"
);
$stmt->bind_param("s", $email_blind_idx);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    recordFailedAttempt($conn, $ip, $email);
    logActivity($conn, 0, 'Security Alert', "Failed login attempt for email: $email from IP: $ip");
    header('Location: login.php?error=1');
    exit();
}

$user = $result->fetch_assoc();
$stmt->close();

// ── ZERO-TRUST: Decrypt PII fields from AES-256 storage ──
$user['username']  = \ShineGuard\Services\SecurityService::decrypt($user['username']);
$user['full_name'] = \ShineGuard\Services\SecurityService::decrypt($user['full_name']);
$user['role']      = \ShineGuard\Services\SecurityService::decrypt($user['role']);

if ($user['lockout_until'] !== null && strtotime($user['lockout_until']) > time()) {
    $secs_left = strtotime($user['lockout_until']) - time();
    $mins = ceil($secs_left / 60);
    header("Location: login.php?error=account_locked&mins=$mins");
    exit();
}

if (!password_verify($password, $user['password_hash'])) {
    recordFailedAttempt($conn, $ip, $email);

    $new_failures = $user['failed_attempts'] + 1;
    if ($new_failures >= 5) {
        
        $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW(), lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?");
        $lock_stmt->bind_param("ii", $new_failures, $user['user_id']);
        $lock_stmt->execute();
        $lock_stmt->close();
        
        logActivity($conn, $user['user_id'], 'Security Alert', "Account locked automatically after 5 failed password attempts from IP: $ip");
        header("Location: login.php?error=account_locked&mins=15");
        exit();
    } else {
        
        $fail_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE user_id = ?");
        $fail_stmt->bind_param("ii", $new_failures, $user['user_id']);
        $fail_stmt->execute();
        $fail_stmt->close();
        
        logActivity($conn, $user['user_id'], 'Security Alert', "Failed password attempt from IP: $ip");
        header('Location: login.php?error=1');
        exit();
    }
}

if (!$user['is_active']) {
    header('Location: login.php?error=inactive');
    exit();
}

session_regenerate_id(true);

if ($user['mfa_enabled']) {
    // MFA is required. Set a temporary session and redirect to the MFA gate.
    $_SESSION['mfa_pending_user_id']  = $user['user_id'];
    $_SESSION['mfa_pending_username'] = $user['username'];
    $_SESSION['mfa_pending_full_name']= $user['full_name'];
    $_SESSION['mfa_pending_role']     = $user['role'];
    $_SESSION['mfa_pending_secret']   = $user['mfa_secret'];
    
    // Pass along the remember flag
    if ($remember) {
        $_SESSION['mfa_pending_remember'] = true;
    }
    
    $conn->close();
    header('Location: mfa_verify.php');
    exit();
}

$_SESSION['user_id']       = $user['user_id'];
$_SESSION['username']      = $user['username'];
$_SESSION['full_name']     = $user['full_name'];
$_SESSION['role']          = $user['role'];
$_SESSION['login_time']    = time();
$_SESSION['last_activity'] = time(); 

// ── ZERO TRUST: Mandatory MFA Enforcement ──
$privileged_roles = ['System Admin', 'Maintenance Operator', 'System Observer'];
if (in_array($user['role'], $privileged_roles) && !$user['mfa_enabled']) {
    // If privileged user has no MFA, redirect to setup and flag the session as "MFA Setup Pending"
    $_SESSION['mfa_setup_required'] = true;
    header('Location: settings.php?tab=security&setup=1&force=true');
    exit();
}

clearLoginAttempts($conn, $ip);

$reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL, lockout_until = NULL WHERE user_id = ?");
$reset_stmt->bind_param("i", $user['user_id']);
$reset_stmt->execute();
$reset_stmt->close();

$upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
$upd->bind_param("i", $user['user_id']);
$upd->execute();
$upd->close();

// --- Device Fingerprint Trace ---
\ShineGuard\Services\SecurityService::verifyDeviceFingerprint($conn, $user['user_id'], $ip);

logActivity($conn, $user['user_id'], 'Login', 'User logged in successfully');

if ($remember) {
    $raw_token  = bin2hex(random_bytes(32));
    $token_hash = hash('sha256', $raw_token);
    $expires    = date('Y-m-d H:i:s', time() + 86400 * 30); 

    $del = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
    $del->bind_param("i", $user['user_id']);
    $del->execute();
    $del->close();

    $ins = $conn->prepare(
        "INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)"
    );
    $ins->bind_param("iss", $user['user_id'], $token_hash, $expires);
    $ins->execute();
    $ins->close();

    setcookie('remember_token', $raw_token, [
        'expires'  => time() + 86400 * 30,
        'path'     => '/',
        'secure'   => false,   
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
}

$conn->close();
header('Location: dashboard.php?login=success');
exit();
?>
