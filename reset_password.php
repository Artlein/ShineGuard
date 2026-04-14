<?php
require_once 'dbconnect.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    header('Location: login.php');
    exit();
}

$error_msg = '';
$is_valid = false;

// 1. Verify token
$token_hash = hash('sha256', $token);
$stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token_hash = ? LIMIT 1");
$stmt->bind_param("ss", $email, $token_hash);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 1) {
    $data = $res->fetch_assoc();
    if (strtotime($data['expires_at']) > time()) {
        $is_valid = true;
    } else {
        $error_msg = "⏱️ This reset link has expired. Please request a new one.";
    }
} else {
    $error_msg = "⚠️ Invalid reset link. It might have been used or is incorrect.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - Shine Guard</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .login-container { max-width: 800px; }
        @media (max-width: 768px) { .login-container { max-width: 360px; } }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="orb"></div>
    <div class="orb"></div>

    <div class="login-container" style="animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);">
        <div class="login-left">
            <div class="logo-wrapper">
                <div class="logo-circle">
                    <img src="img/ShineGuard3.png" alt="Barangay Hulo Logo">
                </div>
            </div>
            
            <div class="brand-text">
                <h1>Shine Guard</h1>
                <p>Smart Streetlight<br>Security Portal</p>
            </div>
            
            <div class="tagline">
                💡 Secure • 🛡️ Resilient • ⚡ Fast
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Set New Password</h2>
                <p>Secure your account with a strong password</p>
            </div>
            
            <?php if (!$is_valid): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fca5a5; font-size: 14px; font-weight: 600; text-align: center;">
                    <?php echo $error_msg; ?>
                    <div style="margin-top: 20px;">
                        <a href="forgot_password.php" style="background: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">Try Again</a>
                    </div>
                </div>
            <?php else: ?>
                <form id="resetForm" method="POST" action="reset_password_process.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group">
                        <label for="password">NEW PASSWORD</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Min. 8 characters" 
                                required
                            >
                            <span class="input-icon">🔒</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">CONFIRM PASSWORD</label>
                        <div class="input-wrapper">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Repeat new password" 
                                required
                            >
                            <span class="input-icon">🔒</span>
                        </div>
                    </div>
                    
                    <div id="pwError" style="color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 15px; display: none;">
                        ⚠️ Passwords do not match.
                    </div>

                    <button type="submit" class="login-button" style="margin-top: 10px;">
                        Update Password
                    </button>
                </form>
            <?php endif; ?>
            
            <div class="footer-text">
                <span class="highlight">Barangay Hulo</span> © 2025<br>
                Security & Infrastructure Center
            </div>
        </div>
    </div>
    
    <script>
        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                const pw = document.getElementById('password').value;
                const conf = document.getElementById('confirm_password').value;
                const err = document.getElementById('pwError');

                if (pw !== conf) {
                    e.preventDefault();
                    err.style.display = 'block';
                    return;
                }
                
                if (pw.length < 8) {
                    e.preventDefault();
                    err.textContent = '⚠️ Password must be at least 8 characters.';
                    err.style.display = 'block';
                    return;
                }

                const button = this.querySelector('.login-button');
                button.classList.add('loading');
                button.textContent = 'Updating...';
            });
        }
    </script>
</body>
</html>
