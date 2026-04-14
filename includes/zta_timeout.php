<?php
/**
 * ZTA ACTIVE IDLE TIMEOUT
 * Implements persistent activity sensing and automatic session termination.
 */
?>
<style>
/* ZTA Timeout Modal Styles */
#zta-timeout-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(15, 23, 42, 0.85);
    backdrop-filter: blur(20px) saturate(160%);
    z-index: 100000;
    display: none;
    align-items: center;
    justify-content: center;
    color: white;
    font-family: 'Inter', sans-serif;
    animation: ztaFadeIn 0.5s cubic-bezier(0.16, 1, 0.3, 1);
}

@keyframes ztaFadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

.zta-timeout-card {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 32px;
    padding: 40px;
    max-width: 480px;
    width: 90%;
    text-align: center;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    position: relative;
}

.zta-timeout-icon {
    width: 80px;
    height: 80px;
    background: rgba(239, 68, 68, 0.1);
    border: 2px solid rgba(239, 68, 68, 0.3);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 32px;
    animation: ztaPulse 2s infinite;
}

@keyframes ztaPulse {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    70% { box-shadow: 0 0 0 20px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.zta-timeout-card h2 {
    font-size: 1.75rem;
    font-weight: 800;
    margin-bottom: 12px;
    letter-spacing: -0.02em;
}

.zta-timeout-card p {
    color: rgba(255, 255, 255, 0.7);
    line-height: 1.6;
    margin-bottom: 30px;
}

.zta-countdown-circle {
    position: relative;
    width: 120px;
    height: 120px;
    margin: 0 auto 30px;
}

.zta-countdown-number {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    font-size: 2.5rem;
    font-weight: 900;
    color: #ef4444;
}

.zta-timeout-btn {
    width: 100%;
    padding: 16px;
    background: #10b981;
    color: white;
    border: none;
    border-radius: 16px;
    font-weight: 800;
    font-size: 1rem;
    cursor: pointer;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    box-shadow: 0 10px 20px rgba(16, 185, 129, 0.2);
}

.zta-timeout-btn:hover {
    background: #059669;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(16, 185, 129, 0.3);
}

.zta-badge {
    display: inline-block;
    padding: 4px 12px;
    background: rgba(16, 185, 129, 0.1);
    color: #10b981;
    border-radius: 100px;
    font-size: 0.75rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    margin-bottom: 16px;
}
</style>

<div id="zta-timeout-overlay">
    <div class="zta-timeout-card">
        <div class="zta-badge">ZTA Security Protocol</div>
        <div class="zta-timeout-icon">⌛</div>
        <h2>Session Inactivity</h2>
        <p>This terminal has been idle for over 10 minutes. For your security, the current dashboard session will be terminated shortly.</p>
        
        <div class="zta-countdown-circle">
            <div id="zta-timer-val" class="zta-countdown-number">60</div>
            <svg width="120" height="120" viewBox="0 0 120 120">
                <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.1)" stroke-width="8" />
                <circle id="zta-svg-progress" cx="60" cy="60" r="54" fill="none" stroke="#ef4444" stroke-width="8" 
                        stroke-dasharray="339.29" stroke-dashoffset="0" stroke-linecap="round" 
                        style="transition: stroke-dashoffset 1s linear; transform: rotate(-90deg); transform-origin: center;" />
            </svg>
        </div>

        <button type="button" class="zta-timeout-btn" onclick="resetZtaSession()">I Am Still Active</button>
    </div>
</div>

<script>
(function() {
    const IDLE_LIMIT = 10 * 60 * 1000; // 10 Minutes
    const COUNTDOWN_LIMIT = 60; // 60 Seconds
    
    let idleTimer;
    let countdownTimer;
    let secondsRemaining = COUNTDOWN_LIMIT;
    
    const overlay = document.getElementById('zta-timeout-overlay');
    const timerVal = document.getElementById('zta-timer-val');
    const svgProgress = document.getElementById('zta-svg-progress');
    const dashArray = 339.29;

    function resetIdleTimer() {
        if (overlay.style.display === 'flex') return; // Don't reset if modal is already up
        
        clearTimeout(idleTimer);
        idleTimer = setTimeout(showTimeoutWarning, IDLE_LIMIT);
    }

    function showTimeoutWarning() {
        overlay.style.display = 'flex';
        secondsRemaining = COUNTDOWN_LIMIT;
        startCountdown();
    }

    function startCountdown() {
        updateCountdownUI();
        clearInterval(countdownTimer);
        
        countdownTimer = setInterval(() => {
            secondsRemaining--;
            updateCountdownUI();
            
            if (secondsRemaining <= 0) {
                clearInterval(countdownTimer);
                terminateSession();
            }
        }, 1000);
    }

    function updateCountdownUI() {
        timerVal.textContent = secondsRemaining;
        const offset = dashArray - (secondsRemaining / COUNTDOWN_LIMIT) * dashArray;
        svgProgress.style.strokeDashoffset = offset;
    }

    window.resetZtaSession = function() {
        clearInterval(countdownTimer);
        overlay.style.display = 'none';
        
        // Ping the server to refresh PHP session activity
        fetch('api/auth_session.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'action=authorize&admin_password=keep_alive_ping' 
            // Note: This ping won't elevate SBA but will keep the base PHP session alive
        }).catch(() => {});
        
        resetIdleTimer();
    };

    function terminateSession() {
        window.location.href = 'logout.php?reason=timeout';
    }

    // Set up activity listeners
    ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart'].forEach(evt => {
        window.addEventListener(evt, resetIdleTimer, true);
    });

    // Initial start
    resetIdleTimer();
})();
</script>
