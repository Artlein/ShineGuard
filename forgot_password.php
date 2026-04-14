<?php
require_once 'dbconnect.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Shine Guard</title>
    <link rel="stylesheet" href="assets/css/login.css">
    <style>
        .login-container {
            max-width: 800px;
        }
        @media (max-width: 768px) {
            .login-container {
                max-width: 360px;
            }
        }
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
                <p>Smart Streetlight<br>Recovery Center</p>
            </div>
            
            <div class="tagline">
                💡 Secure • 🛡️ Resilient • ⚡ Fast
            </div>
        </div>
        
        <div class="login-right">
            <div class="login-header">
                <h2>Reset Password</h2>
                <p>Enter your email to receive a reset link</p>
            </div>
            
            <!-- Success Message -->
            <?php if(isset($_GET['success'])): ?>
            <div style="background: #eff6ff; color: #1e40af; padding: 16px; border-radius: 12px; margin-bottom: 24px; border: 1px solid #bfdbfe; font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 10px; animation: fadeIn 0.4s; line-height: 1.5;">
                <span style="font-size: 18px;">🛡️</span>
                <span>Request Logged! For security compliance, please coordinate with your System Administrator to receive your secure, perishable recovery link.</span>
            </div>
            <?php endif; ?>

            <div class="error-message" id="errorMessage">
                <span>⚠️</span>
                <span id="errorText"></span>
            </div>
            
            <form id="forgotForm" method="POST" action="forgot_password_process.php">
                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your registered email" 
                            required
                        >
                        <span class="input-icon">✉️</span>
                    </div>
                </div>

                <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                
                <button type="submit" class="login-button" style="margin-top: 10px;">
                    Send Reset Link
                </button>
            </form>
            
            <div class="divider">OR</div>
            
            <div style="text-align: center;">
                <a href="login.php" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 700; transition: color 0.2s;" onmouseover="this.style.color='#10b981'" onmouseout="this.style.color='#64748b'">
                    Back to Sign In
                </a>
            </div>

            <div class="footer-text">
                <span class="highlight">Barangay Hulo</span> © 2025<br>
                Security & Infrastructure Center
            </div>
        </div>
    </div>
    
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const errorCode = urlParams.get('error');
        const errorEl   = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        if (errorCode === 'not_found' || errorCode === 'invalid') {
            errorText.textContent = 'Account not found or invalid email. Please check.';
            errorEl.classList.add('show');
        } else if (errorCode === 'invalid_csrf') {
            errorText.textContent = 'Security token expired. Please refresh and try again.';
            errorEl.classList.add('show');
        } else if (errorCode === 'db_error') {
            errorText.textContent = 'System error. Please try again later.';
            errorEl.classList.add('show');
        }

        document.getElementById('forgotForm').addEventListener('submit', function(e) {
            const button = this.querySelector('.login-button');
            button.classList.add('loading');
            button.textContent = 'Processing...';
        });

        document.getElementById('email').addEventListener('input', () => {
             errorEl.classList.remove('show');
        });
    </script>
</body>
</html>
