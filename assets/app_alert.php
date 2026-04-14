
<div id="sgGlobalAlertModal" style="display: none; position: fixed; inset: 0; z-index: 99999; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center; opacity: 0; transition: opacity 0.2s ease;">
    <div id="sgGlobalAlertContent" style="background: white; border-radius: 16px; width: 90%; max-width: 400px; padding: 24px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); transform: scale(0.95) translateY(10px); transition: all 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);">
        
        <div style="display: flex; align-items: flex-start; gap: 16px;">
            <div id="sgGlobalAlertIcon" style="width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; flex-shrink: 0; background: #fef2f2; color: #ef4444;">
                ⚠️
            </div>
            
            <div style="flex-grow: 1;">
                <h3 id="sgGlobalAlertTitle" style="margin: 0 0 8px 0; font-size: 1.1rem; font-weight: 700; color: #0f172a;">Application Alert</h3>
                <p id="sgGlobalAlertMessage" style="margin: 0; font-size: 0.9rem; color: #475569; line-height: 1.5;">
                    Alert message goes here.
                </p>
            </div>
        </div>
        
        <div id="sgAlertButtonGroup" style="margin-top: 24px; display: flex; justify-content: flex-end; gap: 12px;">
            <button id="sgAlertCancelBtn" onclick="closeAppAlert()" style="display: none; background: transparent; color: #64748b; border: 1px solid #e2e8f0; padding: 10px 20px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; transition: all 0.2s ease;">
                Cancel
            </button>
            <button id="sgAlertConfirmBtn" onclick="closeAppAlert()" style="background: #3b82f6; color: white; border: none; padding: 10px 24px; border-radius: 8px; font-weight: 600; font-size: 0.9rem; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2); transition: background 0.2s ease;">
                OK
            </button>
        </div>
        
    </div>
</div>

<script>
let sgConfirmCallback = null;

function showAppAlert(message, type = 'info', title = null) {
    prepareAlert(message, type, title, false);
}

function showAppConfirm(message, onConfirm, type = 'warning', title = 'Confirm Action') {
    sgConfirmCallback = onConfirm;
    prepareAlert(message, type, title, true);
}

function prepareAlert(message, type, title, isConfirm) {
    const modal = document.getElementById('sgGlobalAlertModal');
    const content = document.getElementById('sgGlobalAlertContent');
    const iconContainer = document.getElementById('sgGlobalAlertIcon');
    const titleEl = document.getElementById('sgGlobalAlertTitle');
    const msgEl = document.getElementById('sgGlobalAlertMessage');
    const confirmBtn = document.getElementById('sgAlertConfirmBtn');
    const cancelBtn = document.getElementById('sgAlertCancelBtn');

    msgEl.innerHTML = message;
    cancelBtn.style.display = isConfirm ? 'block' : 'none';
    confirmBtn.textContent = isConfirm ? 'Confirm' : 'OK';

    let defaultTitle = 'Alert';
    let icon = 'ℹ️';
    let iconBg = '#eff6ff';
    let btnBg = '#3b82f6';
    let btnHover = '#2563eb';
    
    switch(type) {
        case 'error':
            defaultTitle = 'Action Failed';
            icon = '⚠️';
            iconBg = '#fef2f2';
            btnBg = '#ef4444'; 
            btnHover = '#dc2626';
            break;
        case 'warning':
            defaultTitle = 'Warning';
            icon = '⚡';
            iconBg = '#fffbeb';
            btnBg = '#f59e0b'; 
            btnHover = '#d97706';
            break;
        case 'success':
            defaultTitle = 'Success';
            icon = '✅';
            iconBg = '#f0fdf4';
            btnBg = '#10b981'; 
            btnHover = '#059669';
            break;
        case 'info':
        default:
            defaultTitle = 'Information';
            icon = 'ℹ️';
            iconBg = '#eff6ff';
            btnBg = '#3b82f6'; 
            btnHover = '#2563eb';
            break;
    }
    
    titleEl.textContent = title || defaultTitle;
    iconContainer.textContent = icon;
    iconContainer.style.background = iconBg;
    confirmBtn.style.background = btnBg;

    confirmBtn.onmouseover = () => confirmBtn.style.background = btnHover;
    confirmBtn.onmouseout = () => confirmBtn.style.background = btnBg;

    // Handle button clicks
    confirmBtn.onclick = function() {
        closeAppAlert();
        if (isConfirm && typeof sgConfirmCallback === 'function') {
            sgConfirmCallback();
        }
    };

    modal.style.display = 'flex';
    void modal.offsetWidth;
    modal.style.opacity = '1';
    content.style.transform = 'scale(1) translateY(0)';
}

function closeAppAlert() {
    const modal = document.getElementById('sgGlobalAlertModal');
    const content = document.getElementById('sgGlobalAlertContent');

    modal.style.opacity = '0';
    content.style.transform = 'scale(0.95) translateY(10px)';
    
    setTimeout(() => {
        modal.style.display = 'none';
        window.getSelection()?.removeAllRanges();
        sgConfirmCallback = null;
    }, 200);
}

document.addEventListener('keydown', function(event) {
    if (event.key === "Escape") {
        closeAppAlert();
    }
});
</script>
