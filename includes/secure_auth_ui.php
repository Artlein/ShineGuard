<style>
    .secure-auth-container {
        position: fixed;
        top: 0; left: 0; width: 100%; height: 100%;
        background: radial-gradient(circle at center, rgba(15, 23, 42, 0.9), rgba(2, 6, 23, 1));
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        z-index: 9999;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 20px;
        animation: fadeIn 0.5s ease-out;
    }

    .auth-card {
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
    }

    .auth-card::before {
        content: '';
        position: absolute;
        top: -50%; left: -50%; width: 200%; height: 200%;
        background: conic-gradient(from 0deg, transparent, rgba(239, 68, 68, 0.1), transparent);
        animation: rotate 6s linear infinite;
        z-index: -1;
    }

    @keyframes rotate { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

    .lock-shield {
        width: 80px; height: 80px;
        background: rgba(239, 68, 68, 0.1);
        border-radius: 20px;
        display: flex; align-items: center; justify-content: center;
        margin: 0 auto 24px;
        border: 2px solid rgba(239, 68, 68, 0.3);
        font-size: 40px;
        box-shadow: 0 0 30px rgba(239, 68, 68, 0.2);
    }

    .auth-card h2 { font-size: 1.8rem; font-weight: 800; margin-bottom: 12px; letter-spacing: -0.02em; }
    .auth-card p { color: rgba(255, 255, 255, 0.6); margin-bottom: 32px; line-height: 1.6; font-size: 15px; }

    .password-field { position: relative; margin-bottom: 20px; }
    .password-field input {
        width: 100%;
        background: rgba(255, 255, 255, 0.05);
        border: 2px solid rgba(255, 255, 255, 0.1);
        padding: 16px 20px;
        border-radius: 16px;
        color: white;
        font-size: 16px;
        font-weight: 600;
        outline: none;
        transition: all 0.3s;
        text-align: center;
        letter-spacing: 0.1em;
    }
    .password-field input:focus { border-color: #ef4444; background: rgba(25, 25, 25, 0.2); box-shadow: 0 0 20px rgba(239, 68, 68, 0.1); }

    .btn-verify {
        width: 100%;
        background: #ef4444;
        color: white;
        border: none;
        padding: 16px;
        border-radius: 16px;
        font-weight: 800;
        font-size: 16px;
        cursor: pointer;
        transition: all 0.3s;
        display: flex; align-items: center; justify-content: center; gap: 10px;
        box-shadow: 0 10px 20px rgba(239, 68, 68, 0.2);
    }
    .btn-verify:hover { background: #dc2626; transform: translateY(-2px); box-shadow: 0 12px 24px rgba(239, 68, 68, 0.3); }
    .btn-verify:active { transform: translateY(0); }

    .btn-verify:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

    .auth-footer { margin-top: 32px; display: flex; flex-direction: column; gap: 16px; }
    .btn-cancel { color: rgba(255, 255, 255, 0.4); text-decoration: none; font-size: 14px; font-weight: 600; transition: color 0.2s; }
    .btn-cancel:hover { color: white; }

    .error-msg {
        color: #fca5a5; background: rgba(239, 68, 68, 0.1);
        padding: 12px; border-radius: 12px; margin-bottom: 20px;
        font-size: 14px; font-weight: 600; display: none;
        animation: shake 0.4s ease-in-out;
    }

    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
</style>

<div class="secure-auth-container" id="authScreen">
    <div class="auth-card">
        <div class="lock-shield">🛡️</div>
        <h2>Critical Access Required</h2>
        <p>This area contains sensitive forensic logs. Please verify your administrator password to unlock this session.</p>
        
        <div id="authError" class="error-msg">Invalid password. Access denied.</div>
        
        <form id="criticalAuthForm">
            <div class="password-field">
                <input type="password" id="adminPassword" placeholder="Enter Administrator Password" autofocus required>
            </div>
            <button type="submit" class="btn-verify" id="verifyBtn">
                <span>Unlock Access</span>
                <span id="loader" style="display:none;">⏳</span>
            </button>
        </form>

        <div class="auth-footer">
            <a href="dashboard.php" class="btn-cancel">← Return to Dashboard</a>
        </div>
    </div>
</div>

<script>
document.getElementById('criticalAuthForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const btn = document.getElementById('verifyBtn');
    const err = document.getElementById('authError');
    const password = document.getElementById('adminPassword').value;

    btn.disabled = true;
    err.style.display = 'none';

    const formData = new FormData();
    formData.append('action', 'critical_auth');
    formData.append('admin_password', password);
    formData.append('csrf_token', '<?php echo $_SESSION['csrf_token'] ?? ''; ?>');

    fetch('activity_logs.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            err.textContent = data.error || 'Invalid password. Access denied.';
            err.style.display = 'block';
            btn.disabled = false;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        err.textContent = 'A system error occurred. Please try again.';
        err.style.display = 'block';
        btn.disabled = false;
    });
});
</script>
