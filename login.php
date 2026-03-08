<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shine Guard  - Login</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .gradient-bg {
            position: absolute;
            inset: 0;
            background: 
                radial-gradient(circle at 20% 30%, rgba(16, 185, 129, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, rgba(59, 130, 246, 0.12) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(16, 185, 129, 0.08) 0%, transparent 50%);
            animation: gradientShift 15s ease infinite;
        }
        
        @keyframes gradientShift {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.1); }
        }

        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(60px);
            opacity: 0.3;
            animation: float 20s infinite;
        }
        
        .orb:nth-child(1) {
            width: 300px;
            height: 300px;
            background: #10b981;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }
        
        .orb:nth-child(2) {
            width: 250px;
            height: 250px;
            background: #3b82f6;
            bottom: -80px;
            right: -80px;
            animation-delay: 10s;
        }
        
        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            33% { transform: translate(30px, -30px) scale(1.1); }
            66% { transform: translate(-20px, 20px) scale(0.9); }
        }
        
        .login-container {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: 
                0 20px 60px rgba(0, 0, 0, 0.4),
                0 0 100px rgba(16, 185, 129, 0.1),
                inset 0 1px 0 rgba(255, 255, 255, 0.8);
            overflow: hidden;
            display: grid;
            grid-template-columns: 380px 420px;
            max-width: 800px;
            width: 90%;
            position: relative;
            z-index: 10;
            animation: slideUp 0.6s cubic-bezier(0.16, 1, 0.3, 1);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(40px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .login-left {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            padding: 50px 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .login-left::before {
            content: '';
            position: absolute;
            inset: 0;
            background: 
                linear-gradient(45deg, transparent 48%, rgba(255,255,255,0.03) 49%, rgba(255,255,255,0.03) 51%, transparent 52%),
                linear-gradient(-45deg, transparent 48%, rgba(255,255,255,0.03) 49%, rgba(255,255,255,0.03) 51%, transparent 52%);
            background-size: 20px 20px;
            opacity: 0.6;
        }

        .login-left::after {
            content: '';
            position: absolute;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 0%, transparent 70%);
            top: -100px;
            right: -100px;
            animation: pulse 6s ease-in-out infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }
        
        .logo-wrapper {
            position: relative;
            z-index: 1;
            margin-bottom: 20px;
        }
        
        .logo-circle {
            width: 100px;
            height: 100px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 
                0 10px 30px rgba(0, 0, 0, 0.2),
                0 0 0 8px rgba(255, 255, 255, 0.1),
                0 0 0 16px rgba(255, 255, 255, 0.05);
            position: relative;
            overflow: hidden;
            padding: 8px;
            animation: logoFloat 3s ease-in-out infinite;
        }
        
        @keyframes logoFloat {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-8px); }
        }
        
        .logo-circle img {
            width: 100%;
            height: 100%;
            object-fit: contain;
            border-radius: 50%;
        }
        
        .brand-text {
            position: relative;
            z-index: 1;
        }
        
        .brand-text h1 {
            font-size: 26px;
            margin-bottom: 8px;
            font-weight: 800;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }
        
        .brand-text p {
            font-size: 14px;
            opacity: 0.9;
            font-weight: 500;
            line-height: 1.5;
        }
        
        .tagline {
            margin-top: 30px;
            padding: 15px 20px;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 12px;
            backdrop-filter: blur(10px);
            font-size: 13px;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.2);
            position: relative;
            z-index: 1;
        }
        
        .login-right {
            padding: 50px 40px;
            background: #ffffff;
        }
        
        .login-header {
            margin-bottom: 32px;
        }
        
        .login-header h2 {
            font-size: 28px;
            color: #0f172a;
            margin-bottom: 6px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        
        .login-header p {
            color: #64748b;
            font-size: 14px;
            font-weight: 500;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #0f172a;
            font-weight: 700;
            font-size: 13px;
            letter-spacing: 0.3px;
        }
        
        .input-wrapper {
            position: relative;
        }
        
        .input-wrapper input {
            width: 100%;
            padding: 13px 40px 13px 16px;
            border: 2px solid #cbd5e1;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            background: #ffffff;
            font-weight: 500;
            color: #0f172a;
        }
        
        .input-wrapper input:focus {
            outline: none;
            border-color: #10b981;
            background: #ffffff;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
            transform: translateY(-1px);
        }
        
        .input-wrapper input::placeholder {
            color: #94a3b8;
            font-weight: 400;
        }
        
        .input-icon {
            position: absolute;
            right: 14px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 16px;
            opacity: 0.6;
            transition: transform 0.3s;
        }
        
        .input-wrapper input:focus + .input-icon {
            transform: translateY(-50%) scale(1.1);
        }
        
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
            font-size: 13px;
        }
        
        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-weight: 600;
            color: #475569;
            transition: color 0.2s;
        }
        
        .remember-me:hover {
            color: #10b981;
        }
        
        .remember-me input[type="checkbox"] {
            width: 16px;
            height: 16px;
            cursor: pointer;
            accent-color: #10b981;
        }
        
        .forgot-password {
            color: #10b981;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s;
        }
        
        .forgot-password:hover {
            color: #059669;
            transform: translateX(2px);
        }
        
        .login-button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 4px 12px rgba(16, 185, 129, 0.3),
                0 1px 3px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
        }
        
        .login-button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.6s;
        }
        
        .login-button:hover::before {
            left: 100%;
        }
        
        .login-button:hover {
            transform: translateY(-2px);
            box-shadow: 
                0 6px 20px rgba(16, 185, 129, 0.4),
                0 2px 6px rgba(0, 0, 0, 0.15);
        }
        
        .login-button:active {
            transform: translateY(0);
        }
        
        .error-message {
            background: #fee2e2;
            color: #991b1b;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            border: 1px solid #fca5a5;
            font-size: 13px;
            display: none;
            font-weight: 600;
        }
        
        .error-message.show {
            display: flex;
            align-items: center;
            gap: 8px;
            animation: shake 0.4s, fadeIn 0.3s;
        }
        
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            25% { transform: translateX(-8px); }
            75% { transform: translateX(8px); }
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .footer-text {
            text-align: center;
            margin-top: 24px;
            color: #94a3b8;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.6;
        }
        
        .footer-text .highlight {
            color: #10b981;
            font-weight: 700;
        }
        
        .divider {
            display: flex;
            align-items: center;
            gap: 16px;
            margin: 24px 0;
            color: #cbd5e1;
            font-size: 12px;
            font-weight: 600;
        }
        
        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            height: 1px;
            background: linear-gradient(to right, transparent, #e2e8f0, transparent);
        }

        .login-button.loading {
            pointer-events: none;
            opacity: 0.8;
        }
        
        .login-button.loading::after {
            content: '';
            position: absolute;
            width: 18px;
            height: 18px;
            border: 2px solid rgba(255,255,255,0.3);
            border-top-color: white;
            border-radius: 50%;
            right: 16px;
            top: 50%;
            transform: translateY(-50%);
            animation: spin 0.6s linear infinite;
        }
        
        @keyframes spin {
            to { transform: translateY(-50%) rotate(360deg); }
        }
        
        @media (max-width: 768px) {
            body {
                align-items: center;
                padding: 20px 16px;
            }

            .login-container {
                grid-template-columns: 1fr;
                max-width: 360px;
                width: 100%;
                border-radius: 20px;
                /* Not full-height — let background show */
            }

            /* Compact green brand panel */
            .login-left {
                padding: 24px 24px 20px;
                flex-direction: row;
                align-items: center;
                gap: 16px;
                text-align: left;
            }

            .logo-wrapper {
                margin-bottom: 0;
                flex-shrink: 0;
            }

            .logo-circle {
                width: 56px;
                height: 56px;
                animation: none; /* skip float animation on mobile */
            }

            .brand-text h1 {
                font-size: 18px;
                margin-bottom: 2px;
            }

            .brand-text p {
                font-size: 12px;
                opacity: 0.85;
            }

            .tagline {
                display: none;
            }

            /* Tighted form panel */
            .login-right {
                padding: 24px 24px 28px;
            }

            .login-header {
                margin-bottom: 20px;
            }

            .login-header h2 {
                font-size: 20px;
                margin-bottom: 4px;
            }

            .login-header p {
                font-size: 13px;
            }

            .form-group {
                margin-bottom: 14px;
            }

            .form-group label {
                font-size: 11px;
                margin-bottom: 6px;
            }

            .input-wrapper input {
                padding: 12px 38px 12px 14px;
                font-size: 15px; /* prevent iOS zoom */
                border-radius: 10px;
            }

            .remember-forgot {
                margin-bottom: 18px;
                font-size: 13px;
            }

            .login-button {
                padding: 14px;
                font-size: 15px;
                border-radius: 10px;
                touch-action: manipulation;
                min-height: 50px;
            }

            .footer-text {
                font-size: 11px;
                margin-top: 16px;
            }
        }

        @media (max-width: 380px) {
            body { padding: 16px 12px; }
            .login-container { max-width: 100%; }
            .login-left { padding: 18px 16px; }
            .login-right { padding: 20px 16px 24px; }
        }

        ::selection {
            background: rgba(16, 185, 129, 0.2);
            color: #0f172a;
        }
        
        input:-webkit-autofill,
        input:-webkit-autofill:hover,
        input:-webkit-autofill:focus {
            -webkit-box-shadow: 0 0 0 1000px #ffffff inset;
            box-shadow: 0 0 0 1000px #ffffff inset;
            -webkit-text-fill-color: #0f172a;
        }
    </style>
</head>
<body>
    <div class="gradient-bg"></div>
    <div class="orb"></div>
    <div class="orb"></div>

    <?php if(isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
    <div id="logoutToast" style="
        position: fixed; top: 24px; right: 24px; z-index: 99999;
        background: white; border-radius: 16px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.12), 0 2px 8px rgba(0,0,0,0.08);
        padding: 18px 24px; display: flex; align-items: center; gap: 16px;
        max-width: 380px; border-left: 4px solid #3b82f6;
        animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
        font-family: 'Inter', sans-serif;
    ">
        <div style="background: #eff6ff; width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">👋</div>
        <div style="flex: 1;">
            <div style="font-weight: 800; color: #0f172a; font-size: 0.9rem; margin-bottom: 2px;">Logged out successfully</div>
            <div style="color: #64748b; font-size: 0.8rem;">You have been signed out safely.</div>
        </div>
        <button onclick="document.getElementById('logoutToast').style.display='none'" style="background: none; border: none; cursor: pointer; color: #94a3b8; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
    </div>
    <style>
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(60px); }
            to   { opacity: 1; transform: translateX(0); }
        }
    </style>
    <script>
        setTimeout(() => {
            const t = document.getElementById('logoutToast');
            if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
        }, 4000);
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
                        <input 
                            type="email" 
                            id="email" 
                            name="email" 
                            placeholder="Enter your email (@hulo.gov.ph)" 
                            required
                            autocomplete="email"
                        >
                        <span class="input-icon">✉️</span>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="password">PASSWORD</label>
                    <div class="input-wrapper">
                        <input 
                            type="password" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password" 
                            required
                            autocomplete="current-password"
                        >
                        <span class="input-icon">🔒</span>
                    </div>
                </div>
                
                <div class="remember-forgot">
                    <label class="remember-me">
                        <input type="checkbox" name="remember" id="remember">
                        <span>Remember me</span>
                    </label>
                    <a href="#" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" class="login-button">
                    Sign In
                </button>
            </form>
            
            <div class="footer-text">
                <span class="highlight">Barangay Hulo</span> © 2025<br>
                Mandaluyong City • IoT Infrastructure
            </div>
        </div>
    </div>
    
    <script>

        const urlParams = new URLSearchParams(window.location.search);
        const errorCode = urlParams.get('error');
        const errorEl   = document.getElementById('errorMessage');
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

        document.getElementById('loginForm').addEventListener('submit', function(e) {
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

        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'scale(1.01)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'scale(1)';
            });
        });
    </script>
</body>
</html>