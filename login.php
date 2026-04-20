<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shine Guard - Login</title>
    <link rel="stylesheet" href="assets/css/login.css">
</head>

<body>
    <div class="gradient-bg"></div>
    <div class="orb"></div>
    <div class="orb"></div>

    <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
        <div id="logoutToast" style="
        position: fixed; top: 24px; right: 24px; z-index: 99999;
        background: white; border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
        padding: 18px 24px; display: flex; align-items: center; gap: 16px;
        max-width: 380px; border-left: 4px solid #3b82f6;
        animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
        font-family: 'Inter', sans-serif;
    ">
            <div
                style="background: #eff6ff; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                👋</div>
            <div style="flex: 1;">
                <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem; margin-bottom: 2px;">Logged out
                    successfully</div>
                <div style="color: #64748b; font-size: 0.8rem;">You have been signed out safely.</div>
            </div>
            <button onclick="document.getElementById('logoutToast').style.display='none'"
                style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
        </div>
        <script>
            setTimeout(() => {
                const t = document.getElementById('logoutToast');
                if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
            }, 4000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['success']) && $_GET['success'] === 'password_reset'): ?>
        <div id="resetToast" style="
        position: fixed; top: 24px; right: 24px; z-index: 99999;
        background: white; border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
        padding: 18px 24px; display: flex; align-items: center; gap: 16px;
        max-width: 380px; border-left: 4px solid #10b981;
        animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
        font-family: 'Inter', sans-serif;
    ">
            <div
                style="background: #ecfdf5; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                🗝️</div>
            <div style="flex: 1;">
                <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem; margin-bottom: 2px;">Password updated</div>
                <div style="color: #64748b; font-size: 0.8rem;">You can now sign in with your new password.</div>
            </div>
            <button onclick="document.getElementById('resetToast').style.display='none'"
                style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
        </div>
        <script>
            setTimeout(() => {
                const t = document.getElementById('resetToast');
                if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
            }, 6000);
        </script>
    <?php endif; ?>

    <?php if (isset($_GET['error']) && $_GET['error'] === 'device_blocked'): ?>
        <div id="blockedToast" style="
        position: fixed; top: 24px; right: 24px; z-index: 99999;
        background: white; border-radius: 16px;
        box-shadow: 0 8px 32px rgba(239,68,68,0.2), 0 2px 8px rgba(0,0,0,0.08);
        padding: 18px 24px; display: flex; align-items: center; gap: 16px;
        max-width: 380px; border-left: 4px solid #ef4444;
        animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
        font-family: 'Inter', sans-serif;
    ">
            <div style="background: #fef2f2; color: #ef4444; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                🚨</div>
            <div style="flex: 1;">
                <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem; margin-bottom: 2px;">Access Denied</div>
                <div style="color: #64748b; font-size: 0.8rem;">This device has been permanently revoked and blocked by the system administrator.</div>
            </div>
            <button onclick="document.getElementById('blockedToast').style.display='none'"
                style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
        </div>
        <script>
            setTimeout(() => {
                const t = document.getElementById('blockedToast');
                if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
            }, 8000);
        </script>
    <?php endif; ?>

    <div class="login-container">
        <div class="login-left">
            <div class="logo-wrapper">
                <div class="logo-circle">
                    <img src="img/ShineGuard3.png" alt="Barangay Hulo Logo">
                </div>
            </div>

            <div class="brand-text">
                <h1>Shine Guard </h1>
                <p>Smart Streetlight<br>Command Center</p>
            </div>

            <div class="tagline">
                💡 Real-time • 🔧 Predictive • 📊 Analytics
            </div>
        </div>

        <div class="login-right">
            <div class="login-header">
                <h2>Welcome Back</h2>
                <p>Sign in to access your dashboard</p>
            </div>

            <div class="error-message" id="errorMessage">
                <span>⚠️</span>
                <span id="errorText">Invalid username or password. Please try again.</span>
            </div>

            <form id="loginForm" method="POST" action="login_process.php">
                <div class="form-group">
                    <label for="email">EMAIL ADDRESS</label>
                    <div class="input-wrapper">
                        <input type="email" id="email" name="email" placeholder="Enter your email (@hulo.gov.ph)"
                            required autocomplete="email">
                        <span class="input-icon">✉️</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <div class="input-wrapper">
                        <input type="password" id="password" name="password" placeholder="Enter your password" required
                            autocomplete="current-password">
                        <span class="input-icon" id="togglePassword"
                            style="cursor: pointer; user-select: none; transition: transform 0.2s;"
                            title="Show Password">👁️</span>
                    </div>
                </div>

                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="forgot_password.php" class="forgot-password">Forgot password?</a>
                </div>

                <button type="submit" class="login-button">
                    Sign In
                </button>
            </form>

            <div class="footer-text">
                <span class="highlight">Barangay Hulo</span> © 2026<br>
                Mandaluyong City • IoT Infrastructure
            </div>
        </div>
    </div>

    <script>

        const urlParams = new URLSearchParams(window.location.search);
        const errorCode = urlParams.get('error');
        const errorEl = document.getElementById('errorMessage');
        const errorText = document.getElementById('errorText');

        if (errorCode === '1') {
            errorEl.classList.add('show');
        } else if (errorCode === 'account_locked' || errorCode === 'locked') {
            const mins = urlParams.get('mins') || '15';
            errorText.textContent = '🔒 Account locked due to failed attempts. Try again in ' + mins + ' minute(s).';
            errorEl.classList.add('show');
        } else if (errorCode === 'inactive') {
            errorText.textContent = '🚫 Your account has been deactivated. Contact your system admin.';
            errorEl.classList.add('show');
        } else if (errorCode === 'session_expired') {
            errorText.textContent = '⏱️ Your session expired due to inactivity. Please log in again.';
            errorEl.classList.add('show');
        }

        document.getElementById('loginForm').addEventListener('submit', function (e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;

            if (email === '' || password === '') {
                e.preventDefault();
                const errorMsg = document.getElementById('errorMessage');
                errorMsg.innerHTML = '<span>⚠️</span><span>Please fill in all fields</span>';
                errorMsg.classList.add('show');
                return;
            }

            const button = this.querySelector('.login-button');
            button.classList.add('loading');
            button.textContent = 'Signing in...';
        });

        document.getElementById('email').addEventListener('input', hideError);
        document.getElementById('password').addEventListener('input', hideError);

        function hideError() {
            document.getElementById('errorMessage').classList.remove('show');
        }

        const inputs = document.querySelectorAll('input[type="text"], input[type="password"], input[type="email"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function () {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', function () {
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');

        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);

            this.textContent = type === 'password' ? '👁️' : '🙈';
            this.title = type === 'password' ? 'Show Password' : 'Hide Password';

            // Add a little pop animation when clicked
            this.style.transform = 'translateY(-50%) scale(1.2)';
            setTimeout(() => {
                this.style.transform = 'translateY(-50%) scale(1)';
            }, 150);
        });
    </script>
</body>

</html>