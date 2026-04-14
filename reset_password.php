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
    <link rel="stylesheet" href="assets/css/login.css?v=1.1">
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
                            <button type="button" class="eye-toggle" onclick="togglePassword('password')">👁️</button>
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
                            <button type="button" class="eye-toggle" onclick="togglePassword('confirm_password')">👁️</button>
                        </div>
                    </div>
                    
                    <div id="pwMatchStatus" style="font-size: 13px; font-weight: 700; margin-bottom: 20px; min-height: 20px; transition: all 0.3s ease;"></div>

                    <div id="pwError" style="color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 15px; display: none; background: #fff1f2; padding: 10px; border-radius: 8px; border: 1px solid #fecdd3;">
                        ⚠️ Passwords do not match.
                    </div>

                    <button type="submit" id="submitBtn" class="login-button" style="margin-top: 10px;">
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

    <!-- UI Enhancement Styles -->
    <style>
        .input-wrapper { position: relative; }
        .eye-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            opacity: 0.5;
            transition: opacity 0.2s;
            padding: 5px;
            z-index: 10;
        }
        .eye-toggle:hover { opacity: 1; }
        
        .input-wrapper input { 
            padding-left: 45px !important;
            padding-right: 45px !important; 
        }
        
        .input-icon {
            left: 15px !important;
            right: auto !important;
        }
        
        /* Dynamic Validation States */
        input.match-success { border-color: #10b981 !important; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1) !important; }
        input.match-error { border-color: #ef4444 !important; box-shadow: 0 0 0 4px rgba(239, 68, 68, 0.1) !important; }
    </style>
    
    <script>
        function togglePassword(id) {
            const el = document.getElementById(id);
            const btn = el.nextElementSibling.nextElementSibling;
            if (el.type === 'password') {
                el.type = 'text';
                btn.textContent = '🙈';
            } else {
                el.type = 'password';
                btn.textContent = '👁️';
            }
        }

        const pwInput = document.getElementById('password');
        const confInput = document.getElementById('confirm_password');
        const statusDiv = document.getElementById('pwMatchStatus');
        const submitBtn = document.getElementById('submitBtn');

        function checkMatch() {
            const pw = pwInput.value;
            const conf = confInput.value;

            if (!pw || !conf) {
                statusDiv.textContent = '';
                pwInput.classList.remove('match-success', 'match-error');
                confInput.classList.remove('match-success', 'match-error');
                return;
            }

            if (pw === conf) {
                statusDiv.innerHTML = '<span style="color: #10b981;">✅ Passwords Match</span>';
                pwInput.classList.add('match-success');
                pwInput.classList.remove('match-error');
                confInput.classList.add('match-success');
                confInput.classList.remove('match-error');
            } else {
                statusDiv.innerHTML = '<span style="color: #ef4444;">❌ Passwords do not match</span>';
                pwInput.classList.add('match-error');
                pwInput.classList.remove('match-success');
                confInput.classList.add('match-error');
                confInput.classList.remove('match-success');
            }
        }

        pwInput.addEventListener('input', checkMatch);
        confInput.addEventListener('input', checkMatch);

        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                const pw = pwInput.value;
                const conf = confInput.value;
                const err = document.getElementById('pwError');

                if (pw !== conf) {
                    e.preventDefault();
                    err.textContent = '⚠️ Passwords do not match.';
                    err.style.display = 'block';
                    return;
                }
                
                if (pw.length < 8) {
                    e.preventDefault();
                    err.textContent = '⚠️ Password must be at least 8 characters.';
                    err.style.display = 'block';
                    return;
                }

                submitBtn.classList.add('loading');
                submitBtn.textContent = 'Updating...';
            });
        }
    </script>
</body>
</html>
