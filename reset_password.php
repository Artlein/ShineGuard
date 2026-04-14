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
        
        // Check for specific complexity error from backend
        if (isset($_GET['error']) && $_GET['error'] === 'weak_password') {
            $error_msg = "👮 Security Alert: The password provided does not meet corporate complexity requirements (8+ chars, Uppercase, Lowercase, Number, and Special Character).";
        }
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
            
            <?php if ($error_msg): ?>
                <div style="background: #fee2e2; color: #991b1b; padding: 20px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #fca5a5; font-size: 14px; font-weight: 600; text-align: center;">
                    <?php echo $error_msg; ?>
                    <?php if (!$is_valid): ?>
                        <div style="margin-top: 20px;">
                            <a href="forgot_password.php" style="background: #ef4444; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none;">Try Again</a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($is_valid): ?>
                <form id="resetForm" method="POST" action="reset_password_process.php">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    
                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="password">NEW PASSWORD</label>
                        <div class="input-wrapper" style="position: relative !important;">
                            <input 
                                type="password" 
                                id="password" 
                                name="password" 
                                placeholder="Enter strong password" 
                                required
                                style="padding-left: 45px !important; padding-right: 45px !important; width: 100% !important; box-sizing: border-box !important;"
                            >
                            <span class="input-icon" style="position: absolute !important; left: 15px !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 5 !important; display: flex !important; align-items: center !important;">🔒</span>
                            <button type="button" class="eye-toggle" onclick="togglePassword('password')" style="position: absolute !important; right: 10px !important; top: 50% !important; transform: translateY(-50%) !important; background: none !important; border: none !important; cursor: pointer !important; padding: 5px !important; z-index: 10 !important; font-size: 18px !important; display: flex !important; align-items: center !important;">👁️</button>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 25px;">
                        <label for="confirm_password">CONFIRM PASSWORD</label>
                        <div class="input-wrapper" style="position: relative !important;">
                            <input 
                                type="password" 
                                id="confirm_password" 
                                name="confirm_password" 
                                placeholder="Repeat new password" 
                                required
                                style="padding-left: 45px !important; padding-right: 45px !important; width: 100% !important; box-sizing: border-box !important;"
                            >
                            <span class="input-icon" style="position: absolute !important; left: 15px !important; top: 50% !important; transform: translateY(-50%) !important; z-index: 5 !important; display: flex !important; align-items: center !important;">🔒</span>
                            <button type="button" class="eye-toggle" onclick="togglePassword('confirm_password')" style="position: absolute !important; right: 10px !important; top: 50% !important; transform: translateY(-50%) !important; background: none !important; border: none !important; cursor: pointer !important; padding: 5px !important; z-index: 10 !important; font-size: 18px !important; display: flex !important; align-items: center !important;">👁️</button>
                        </div>
                    </div>

                    <!-- 🛡️ Compact Corporate Password Checklist (Relocated) -->
                    <div class="pw-requirements-panel" style="padding: 12px; margin-bottom: 20px; background: rgba(0,0,0,0.02); border-radius: 12px; border: 1px solid rgba(0,0,0,0.05);">
                        <div class="req-title" style="margin-bottom: 8px; font-size: 11px; font-weight: 800; color: #64748b;">SECURITY RULES</div>
                        <ul class="req-list" style="grid-template-columns: 1fr 1fr; display: grid; gap: 4px 12px; padding: 0; list-style: none;">
                            <li id="req-len" class="req-item invalid" style="font-size: 11px; display: flex; align-items: center; gap: 8px;"><span>❌</span> 8+ Chars</li>
                            <li id="req-upper" class="req-item invalid" style="font-size: 11px; display: flex; align-items: center; gap: 8px;"><span>❌</span> Uppercase</li>
                            <li id="req-lower" class="req-item invalid" style="font-size: 11px; display: flex; align-items: center; gap: 8px;"><span>❌</span> Lowercase</li>
                            <li id="req-num" class="req-item invalid" style="font-size: 11px; display: flex; align-items: center; gap: 8px;"><span>❌</span> Number</li>
                            <li id="req-special" class="req-item invalid" style="font-size: 11px; display: flex; align-items: center; gap: 8px;"><span>❌</span> Symbol</li>
                        </ul>
                        <div class="req-note" style="margin-top: 10px; padding-top: 8px; font-size: 10px; border-top: 1px solid rgba(0,0,0,0.05); color: #94a3b8;">
                            <strong>Note:</strong> Mandated by ShineGuard Security Protocols.
                        </div>
                    </div>
                    
                    <div id="pwMatchStatus" style="font-size: 13px; font-weight: 700; margin-bottom: 20px; min-height: 20px; transition: all 0.3s ease;"></div>

                    <div id="pwError" style="color: #ef4444; font-size: 13px; font-weight: 600; margin-bottom: 15px; display: none; background: #fff1f2; padding: 10px; border-radius: 8px; border: 1px solid #fecdd3;">
                        ⚠️ Passwords do not match.
                    </div>

                    <button type="submit" id="submitBtn" class="login-button disabled" disabled style="margin-top: 10px; opacity: 0.5; cursor: not-allowed;">
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
        .pw-requirements-panel {
            background: rgba(15, 23, 42, 0.03);
            border: 1px solid rgba(15, 23, 42, 0.08);
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }
        .req-title {
            font-size: 11px;
            font-weight: 800;
            color: #64748b;
            letter-spacing: 0.05em;
            margin-bottom: 12px;
        }
        .req-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: 1fr;
            gap: 8px;
        }
        .req-item {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s ease;
            opacity: 0.6;
        }
        .req-item span { font-size: 14px; }
        .req-item.valid { opacity: 1; color: #10b981; }
        .req-item.invalid { color: #64748b; }
        
        .req-note {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid rgba(15, 23, 42, 0.05);
            font-size: 11px;
            color: #94a3b8;
            line-height: 1.5;
        }
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

        const requirements = {
            len: { re: /.{8,}/, el: document.getElementById('req-len') },
            upper: { re: /[A-Z]/, el: document.getElementById('req-upper') },
            lower: { re: /[a-z]/, el: document.getElementById('req-lower') },
            num: { re: /[0-9]/, el: document.getElementById('req-num') },
            special: { re: /[!@#$%^&*]/, el: document.getElementById('req-special') }
        };

        function validatePassword() {
            const val = pwInput.value;
            let allValid = true;

            for (const key in requirements) {
                const req = requirements[key];
                if (req.re.test(val)) {
                    req.el.className = 'req-item valid';
                    req.el.querySelector('span').textContent = '✅';
                } else {
                    req.el.className = 'req-item invalid';
                    req.el.querySelector('span').textContent = '❌';
                    allValid = false;
                }
            }

            checkMatch(allValid);
        }

        function checkMatch(isPwValid = null) {
            const pw = pwInput.value;
            const conf = confInput.value;

            // If we didn't pass isPwValid, recalculate it
            if (isPwValid === null) {
                isPwValid = true;
                for (const key in requirements) {
                    if (!requirements[key].re.test(pw)) {
                        isPwValid = false;
                        break;
                    }
                }
            }

            let matchValid = false;
            if (!pw || !conf) {
                statusDiv.textContent = '';
                pwInput.classList.remove('match-success', 'match-error');
                confInput.classList.remove('match-success', 'match-error');
            } else if (pw === conf) {
                statusDiv.innerHTML = '<span style="color: #10b981;">✅ Passwords Match</span>';
                pwInput.classList.add('match-success');
                pwInput.classList.remove('match-error');
                confInput.classList.add('match-success');
                confInput.classList.remove('match-error');
                matchValid = true;
            } else {
                statusDiv.innerHTML = '<span style="color: #ef4444;">❌ Passwords do not match</span>';
                pwInput.classList.add('match-error');
                pwInput.classList.remove('match-success');
                confInput.classList.add('match-error');
                confInput.classList.remove('match-success');
            }

            // Lock/Unlock button
            if (isPwValid && matchValid) {
                submitBtn.disabled = false;
                submitBtn.classList.remove('disabled');
                submitBtn.style.opacity = '1';
                submitBtn.style.cursor = 'pointer';
            } else {
                submitBtn.disabled = true;
                submitBtn.classList.add('disabled');
                submitBtn.style.opacity = '0.5';
                submitBtn.style.cursor = 'not-allowed';
            }
        }

        pwInput.addEventListener('input', validatePassword);
        confInput.addEventListener('input', () => checkMatch());

        const resetForm = document.getElementById('resetForm');
        if (resetForm) {
            resetForm.addEventListener('submit', function(e) {
                submitBtn.classList.add('loading');
                submitBtn.textContent = 'Updating...';
            });
        }
    </script>
</body>
</html>
