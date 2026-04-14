<?php
require_once 'dbconnect.php';
require_once 'src/Services/TOTPService.php';

use ShineGuard\Services\TOTPService;

session_start();

if (!isset($_SESSION['mfa_pending_user_id'])) {
    header('Location: login.php');
    exit();
}

// ── SECURITY FEATURE: MFA BRUTE-FORCE LOCKOUT ──
$ip = $_SERVER['REMOTE_ADDR'];
if (isIpLockedOut($conn, $ip)) {
    $secs_left = getLockoutSecondsRemaining($conn, $ip);
    $mins = ceil($secs_left / 60);
    logActivity($conn, $_SESSION['mfa_pending_user_id'], 'Security Alert', "IP $ip blocked from MFA gate due to excessive failed attempts.");
    header("Location: login.php?error=locked&mins=$mins");
    exit();
}

// Also check if the specific user account is currently locked out
$user_check_stmt = $conn->prepare("SELECT lockout_until, failed_attempts FROM users WHERE user_id = ?");
$user_check_stmt->bind_param("i", $_SESSION['mfa_pending_user_id']);
$user_check_stmt->execute();
$user_check = $user_check_stmt->get_result()->fetch_assoc();
$user_check_stmt->close();

if ($user_check['lockout_until'] !== null && strtotime($user_check['lockout_until']) > time()) {
    $secs_left = strtotime($user_check['lockout_until']) - time();
    $mins = ceil($secs_left / 60);
    header("Location: login.php?error=account_locked&mins=$mins");
    exit();
}

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['totp_code'] ?? '';
    $secret = $_SESSION['mfa_pending_secret'] ?? '';

    // Verify code
    // Remove spaces if entered
    $code = preg_replace('/\s+/', '', $code);
    
    if (TOTPService::verifyCode($secret, $code)) {
        // SUCCESS: Move pending credentials to real session
        session_regenerate_id(true);

        $user_id = $_SESSION['mfa_pending_user_id'];
        $_SESSION['user_id']       = $user_id;
        $_SESSION['username']      = $_SESSION['mfa_pending_username'];
        $_SESSION['full_name']     = $_SESSION['mfa_pending_full_name'];
        $_SESSION['role']          = $_SESSION['mfa_pending_role'];
        $_SESSION['login_time']    = time();
        $_SESSION['last_activity'] = time();

        $remember = isset($_SESSION['mfa_pending_remember']);

        // Clean up pending session data
        unset($_SESSION['mfa_pending_user_id'], $_SESSION['mfa_pending_username']);
        unset($_SESSION['mfa_pending_full_name'], $_SESSION['mfa_pending_role']);
        unset($_SESSION['mfa_pending_secret'], $_SESSION['mfa_pending_remember']);

        // Database updates
        $ip = $_SERVER['REMOTE_ADDR'];
        clearLoginAttempts($conn, $ip);

        $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0, last_failed_attempt = NULL, lockout_until = NULL WHERE user_id = ?");
        $reset_stmt->bind_param("i", $user_id);
        $reset_stmt->execute();
        $reset_stmt->close();

        $upd = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
        $upd->bind_param("i", $user_id);
        $upd->execute();
        $upd->close();

        logActivity($conn, $user_id, 'MFA Login', 'User passed MFA and logged in successfully');

        if ($remember) {
            $raw_token  = bin2hex(random_bytes(32));
            $token_hash = hash('sha256', $raw_token);
            $expires    = date('Y-m-d H:i:s', time() + 86400 * 30); 

            $del = $conn->prepare("DELETE FROM remember_tokens WHERE user_id = ?");
            $del->bind_param("i", $user_id);
            $del->execute();
            $del->close();

            $ins = $conn->prepare("INSERT INTO remember_tokens (user_id, token_hash, expires_at) VALUES (?, ?, ?)");
            $ins->bind_param("iss", $user_id, $token_hash, $expires);
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

        header('Location: dashboard.php?login=success');
        exit();

    } else {
        // MFA FAILURE: Increment attempts and handle lockout
        $user_id = $_SESSION['mfa_pending_user_id'];
        recordFailedAttempt($conn, $ip, $_SESSION['mfa_pending_username']);
        
        $new_failures = ($user_check['failed_attempts'] ?? 0) + 1;
        if ($new_failures >= 5) {
            $lock_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW(), lockout_until = DATE_ADD(NOW(), INTERVAL 15 MINUTE) WHERE user_id = ?");
            $lock_stmt->bind_param("ii", $new_failures, $user_id);
            $lock_stmt->execute();
            $lock_stmt->close();
            
            logActivity($conn, $user_id, 'Security Alert', "Account locked automatically after 5 failed MFA attempts from IP: $ip");
            
            // Wipe the pending session so they must start a full login again after 15 mins
            session_unset();
            session_destroy();
            
            header("Location: login.php?error=account_locked&mins=15");
            exit();
        } else {
            $fail_stmt = $conn->prepare("UPDATE users SET failed_attempts = ?, last_failed_attempt = NOW() WHERE user_id = ?");
            $fail_stmt->bind_param("ii", $new_failures, $user_id);
            $fail_stmt->execute();
            $fail_stmt->close();
            
            logActivity($conn, $user_id, 'Security Alert', "Failed MFA verification attempt from IP: $ip (Attempt $new_failures/5)");
            $error = "Invalid or expired authenticator code. (Attempt $new_failures/5)";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MFA Verification - ShineGuard</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .mfa-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 32px;
            padding: 48px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            color: white;
            position: relative;
            overflow: hidden;
            margin: auto;
        }

        .mfa-card::before {
            content: '';
            position: absolute;
            top: -50%; left: -50%; width: 200%; height: 200%;
            background: conic-gradient(from 0deg, transparent, rgba(59, 130, 246, 0.1), transparent);
            animation: rotate 6s linear infinite;
            z-index: -1;
        }

        .icon-shield {
            width: 80px; height: 80px;
            background: rgba(59, 130, 246, 0.1);
            border-radius: 20px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
            border: 2px solid rgba(59, 130, 246, 0.3);
            font-size: 40px;
            box-shadow: 0 0 30px rgba(59, 130, 246, 0.2);
        }

        h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.02em; color: white;}
        p { color: rgba(255, 255, 255, 0.6); margin-bottom: 32px; line-height: 1.6; font-size: 15px; }

        .totp-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.1);
            padding: 18px;
            border-radius: 16px;
            color: white;
            font-size: 24px;
            font-weight: 800;
            outline: none;
            transition: all 0.3s;
            text-align: center;
            letter-spacing: 0.5em;
            margin-bottom: 30px;
        }
        
        .totp-input:focus { border-color: #3b82f6; background: rgba(25, 25, 25, 0.2); box-shadow: 0 0 20px rgba(59, 130, 246, 0.1); }

        .btn-verify {
            width: 100%;
            background: #3b82f6;
            color: white;
            border: none;
            padding: 16px;
            border-radius: 16px;
            font-weight: 800;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 10px 20px rgba(59, 130, 246, 0.2);
        }
        .btn-verify:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 12px 24px rgba(59, 130, 246, 0.3); }

        .btn-cancel { color: rgba(255, 255, 255, 0.4); text-decoration: none; font-size: 14px; font-weight: 600; display: inline-block; margin-top: 25px; transition: color 0.2s;}
        .btn-cancel:hover { color: white; }

        .error-msg {
            color: #fca5a5; background: rgba(239, 68, 68, 0.1);
            padding: 12px; border-radius: 12px; margin-bottom: 20px;
            font-size: 14px; font-weight: 600;
            animation: shake 0.4s ease-in-out;
        }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="orb" style="background: radial-gradient(circle, rgba(59,130,246,0.8) 0%, rgba(30,58,138,0) 70%);"></div>
    
    <div style="min-height: 100vh; display: flex; align-items: center; justify-content: center; width: 100%; padding: 20px;">
        <div class="mfa-card">
            <div class="icon-shield">📱</div>
            <h2>Two-Factor Verification</h2>
            <p>Open your Google Authenticator app and enter the 6-digit code for your ShineGuard account.</p>
            
            <?php if ($error): ?>
                <div class="error-msg">⚠️ <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="POST" action="mfa_verify.php">
                <input type="text" name="totp_code" class="totp-input" placeholder="000000" maxlength="6" autocomplete="one-time-code" required autofocus>
                <button type="submit" class="btn-verify">Verify Identity</button>
            </form>

            <a href="login.php" class="btn-cancel">← Return to Login</a>
        </div>
    </div>
</body>
</html>
