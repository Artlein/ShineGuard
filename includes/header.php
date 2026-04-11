<meta name="csrf-token" content="<?php echo generateCsrfToken(); ?>">
<?php
$system_name = 'Shine Guard Hulo';
$organization_name = 'Barangay Hulo';

if (isset($conn)) {
    $config_result = $conn->query("SELECT config_key, config_value FROM system_config WHERE config_key IN ('system_name', 'organization_name')");
    if ($config_result) {
        while ($row = $config_result->fetch_assoc()) {
            if ($row['config_key'] == 'system_name') $system_name = $row['config_value'];
            if ($row['config_key'] == 'organization_name') $organization_name = $row['config_value'];
        }
    }
}
?>
<script>
// Prevent white flash by applying dark mode class immediately
(function() {
    const theme = localStorage.getItem('sg_theme');
    if (theme === 'dark') {
        document.documentElement.classList.add('dark-mode');
    }
})();
</script>
<!-- Global mobile stylesheet — loaded after per-page styles to ensure overrides work -->
<style><?php include __DIR__ . '/../assets/mobile.css'; ?></style>


<div id="pageLoader" style="
    display: none;
    position: fixed; inset: 0; z-index: 99999;
    background: rgba(15,23,42,0.3);
    backdrop-filter: blur(4px);
    align-items: center;
    justify-content: center;
    flex-direction: column;
    gap: 20px;
    font-family: 'Inter', sans-serif;
">
    <div style="position: relative; width: 120px; height: 120px;">
        
        <svg style="position: absolute; inset: 0; animation: spinRing 0.9s linear infinite;" viewBox="0 0 120 120" fill="none" xmlns="http://www.w3.org/2000/svg">
            <circle cx="60" cy="60" r="52" stroke="rgba(255,255,255,0.15)" stroke-width="5"/>
            <circle cx="60" cy="60" r="52" stroke="url(#loaderGrad)" stroke-width="5"
                stroke-linecap="round" stroke-dasharray="90 240"/>
            <defs>
                <linearGradient id="loaderGrad" x1="0" y1="0" x2="120" y2="120" gradientUnits="userSpaceOnUse">
                    <stop stop-color="#10b981"/>
                    <stop offset="1" stop-color="#3b82f6"/>
                </linearGradient>
            </defs>
        </svg>
        
        <img src="img/ShineGuard3.png" alt="Loading" style="
            position: absolute;
            width: 76px; height: 76px;
            top: 50%; left: 50%;
            transform: translate(-50%, -50%);
            border-radius: 50%;
            object-fit: contain;
            box-shadow: 0 0 0 6px rgba(255,255,255,0.08);
        ">
    </div>
    <span style="font-size: 0.78rem; font-weight: 700; color: rgba(255,255,255,0.7); letter-spacing: 0.12em; text-transform: uppercase;">Loading...</span>
</div>
<style>
@keyframes spinRing { to { transform: rotate(360deg); } }
</style>
<script>
(function() {
    const loader = document.getElementById('pageLoader');
    const MIN_SHOW_MS = 700;
    const SK = 'sg_loader_ts';

    function showLoader() {
        loader.style.opacity = '0';
        loader.style.display = 'flex';
        loader.style.transition = 'opacity 0.2s';
        setTimeout(() => { loader.style.opacity = '1'; }, 10);
    }

    function hideLoader(delay) {
        setTimeout(() => {
            loader.style.transition = 'opacity 0.35s';
            loader.style.opacity = '0';
            setTimeout(() => {
                loader.style.display = 'none';
                loader.style.opacity = '1';
            }, 350);
        }, delay);
    }

    document.addEventListener('click', (e) => {
        const a = e.target.closest('a');
        if (!a) return;
        const href = a.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript') || href.startsWith('mailto') || a.target === '_blank') return;
        sessionStorage.setItem(SK, Date.now());
    });

    window.addEventListener('load', () => {
        const ts = sessionStorage.getItem(SK);
        if (!ts) return;
        sessionStorage.removeItem(SK);
        const elapsed = Date.now() - parseInt(ts);
        const remaining = Math.max(0, MIN_SHOW_MS - elapsed);
        showLoader();
        hideLoader(remaining);
    });
})();
</script>

<?php

$toast = null;
$success_param = $_GET['success'] ?? null;
$error_param   = $_GET['error']   ?? null;

$toast_map = [
    
    'login'            => ['👋', 'Logged in',            'Welcome back, ' . htmlspecialchars($_SESSION['full_name'] ?? 'User') . '!', '#10b981', '#ecfdf5'],
    
    'camera_added'     => ['📷', 'Camera Added',         'The camera was successfully added to the system.', '#10b981', '#ecfdf5'],
    'camera_deleted'   => ['🗑️',  'Camera Removed',       'The camera has been deleted from the system.', '#ef4444', '#fef2f2'],
    'settings_updated' => ['⚙️',  'Settings Saved',       'Camera settings have been updated successfully.', '#3b82f6', '#eff6ff'],
    
    'created'          => ['📋', 'Work Order Created',   'A new work order has been successfully submitted.', '#10b981', '#ecfdf5'],
    'updated'          => ['✅', 'Work Order Updated',   'The work order status has been updated successfully.', '#10b981', '#ecfdf5'],
    
    'schedule_created' => ['📅', 'Schedule Saved',       'The schedule has been saved successfully.', '#10b981', '#ecfdf5'],
    'edit'             => ['✏️',  'Schedule Updated',     'The schedule has been updated successfully.', '#3b82f6', '#eff6ff'],
    'delete'           => ['🗑️',  'Schedule Deleted',     'The schedule has been removed successfully.', '#ef4444', '#fef2f2'],
    
    'wo'               => ['📋', 'Work Order Created',   'A new work order has been created from this alert.', '#10b981', '#ecfdf5'],
    '1'                => ['🔔', 'Alert Acknowledged',   'The alert has been marked as acknowledged.', '#f59e0b', '#fffbeb'],
    'acknowledged'     => ['🔔', 'Alert Acknowledged',   'The alert has been marked as acknowledged.', '#f59e0b', '#fffbeb'],
    'resolved'         => ['✅', 'Alert Resolved',       'The alert has been marked as resolved.', '#10b981', '#ecfdf5'],
    
    'settings_saved'   => ['💾', 'Settings Saved',       'Your system settings have been saved successfully.', '#10b981', '#ecfdf5'],
    
    'report_generated' => ['📊', 'Report Generated',     'The system has successfully compiled the analytics data.', '#3b82f6', '#eff6ff'],
    'report_exported'  => ['📥', 'Export Started',        'Your CSV report is being generated and downloaded.', '#10b981', '#ecfdf5'],
    'diagnostic_run'   => ['🔧', 'Diagnostic Complete',  'The self-check test has been completed successfully.', '#3b82f6', '#eff6ff'],
    'bulk_success'     => ['🎛️', 'Control Updated',     'Bulk operation executed successfully.', '#10b981', '#ecfdf5'],
    
    'user_added'       => ['👤', 'User Registered',      'New user account has been created successfully.', '#10b981', '#ecfdf5'],
    'user_updated'     => ['👤', 'User Updated',         'User account details have been updated successfully.', '#10b981', '#ecfdf5'],
    'user_deleted'     => ['🗑️',  'User Removed',         'The user account has been permanently deleted.', '#ef4444', '#fef2f2'],
];

$error_map = [
    
    'invalid_password'   => ['🔑', 'Authentication Failed', 'The password provided is incorrect. Please try again.'],
    'unauthorized'       => ['⚠️', 'Access Denied',      'You are not authorised to perform this action.'],
    'invalid_csrf'       => ['⚠️', 'Session Error',      'Security token mismatch. Please refresh and try again.'],
    'db_error'           => ['💾', 'Database Error',     'There was a problem processing your request.'],
    'missing_fields'     => ['⚠️', 'Missing Info',       'Please fill in all required fields.'],

    'duplicate_username' => ['👤', 'Username Taken',    'This username is already in use by another account.'],
    'duplicate_email'    => ['📧', 'Email Registered',   'This email address is already registered.'],
    'duplicate_entry'    => ['⚠️', 'User Exists',        'A user with this information already exists.'],
    'invalid_username'   => ['⚠️', 'Invalid Username',   'Username must be 3–30 chars (letters, numbers, underscores).'],
    'invalid_email'      => ['⚠️', 'Invalid Email',      'Please enter a valid email address.'],
    'weak_password'      => ['🔑', 'Weak Password',      'Password must be at least 8 characters long.'],
    'password_mismatch'  => ['🔑', 'Password Mismatch',  'The passwords provided do not match.'],
    'invalid_role'       => ['⚠️', 'Invalid Role',       'The selected role is not valid.'],
    'invalid_domain'     => ['📧', 'Invalid Domain',     'Email must belong to the official @hulo.gov.ph domain.'],
    'self_delete'        => ['⚠️', 'Action Blocked',     'Security policy: You cannot delete your own administrative account.'],
    'invalid_admin_password' => ['🔑', 'Auth Failed',      'The administrator password you entered is incorrect.'],
    'self_deactivate'    => ['⚠️', 'Action Blocked',     'You cannot deactivate your own account.'],
    'self_demote'        => ['⚠️', 'Action Blocked',     'You cannot change your own role away from System Admin.'],
    'rate_limit'         => ['❄️', 'Cooling Period',      'Too many requests. Please wait a moment before trying again.'],
];

if ($success_param && isset($toast_map[$success_param])) {
    [$icon, $title, $msg, $color, $bg] = $toast_map[$success_param];
    $toast = ['type'=>'success', 'icon'=>$icon, 'title'=>$title, 'msg'=>$msg, 'color'=>$color, 'bg'=>$bg];
} elseif ($error_param) {
    if (isset($error_map[$error_param])) {
        [$icon, $title, $msg] = $error_map[$error_param];
        $toast = ['type'=>'error', 'icon'=>$icon, 'title'=>$title, 'msg'=>$msg, 'color'=>'#ef4444', 'bg'=>'rgba(239, 68, 68, 0.1)'];
    } else {
        $toast = ['type'=>'error', 'icon'=>'⚠️', 'title'=>'Something went wrong', 'msg'=>'An unexpected error occurred. Please try again.', 'color'=>'#ef4444', 'bg'=>'rgba(239, 68, 68, 0.1)'];
    }
}

if ($toast):
?>
<div id="globalToast" class="toast-item" style="
    position:fixed; top:24px; right:24px; z-index:99999;
    background:white; border-radius:16px;
    box-shadow:0 8px 32px rgba(0,0,0,0.12),0 2px 8px rgba(0,0,0,0.08);
    padding:18px 24px; display:flex; align-items:center; gap:16px;
    max-width:380px; border-left:4px solid <?php echo $toast['color']; ?>;
    animation:slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
    font-family:'Inter',sans-serif;
">
    <div style="background:<?php echo $toast['bg']; ?>;width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
        <?php echo $toast['icon']; ?>
    </div>
    <div style="flex:1;">
        <div style="font-weight:800;color:#0f172a;font-size:0.9rem;margin-bottom:2px;"><?php echo $toast['title']; ?></div>
        <div style="color:#64748b;font-size:0.8rem;"><?php echo $toast['msg']; ?></div>
    </div>
    <button onclick="this.closest('.toast-item').remove()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;flex-shrink:0;">✕</button>
</div>
<style>
@keyframes slideInRight {
    from { opacity:0; transform:translateX(60px); }
    to   { opacity:1; transform:translateX(0); }
}
</style>
<script>
(function() {
    const t = document.getElementById('globalToast');
    if (t) {
        setTimeout(() => {
            t.style.transition='opacity 0.4s'; t.style.opacity='0'; 
            setTimeout(()=>t.remove(), 400); 
        }, 4000);
    }
})();

window.sgToast = function(icon, title, msg, color, bg) {
    const id = 'toast-' + Date.now();
    const html = `
        <div id="${id}" class="toast-item" style="
            position:fixed; top:24px; right:24px; z-index:99999;
            background:white; border-radius:16px;
            box-shadow:0 8px 32px rgba(0,0,0,0.12),0 2px 8px rgba(0,0,0,0.08);
            padding:18px 24px; display:flex; align-items:center; gap:16px;
            max-width:380px; border-left:4px solid ${color};
            animation:slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
            font-family:'Inter',sans-serif;
        ">
            <div style="background:${bg};width:42px;height:42px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:20px;flex-shrink:0;">
                ${icon}
            </div>
            <div style="flex:1;">
                <div style="font-weight:800;color:#0f172a;font-size:0.9rem;margin-bottom:2px;">${title}</div>
                <div style="color:#64748b;font-size:0.8rem;">${msg}</div>
            </div>
            <button onclick="this.closest('.toast-item').remove()" style="background:none;border:none;cursor:pointer;color:#94a3b8;font-size:18px;line-height:1;padding:0;flex-shrink:0;">✕</button>
        </div>
    `;
    const div = document.createElement('div');
    div.innerHTML = html;
    document.body.appendChild(div.firstElementChild);
    
    setTimeout(() => {
        const t = document.getElementById(id);
        if (t) {
            t.style.transition='opacity 0.4s'; t.style.opacity='0'; 
            setTimeout(()=>t.remove(), 400); 
        }
    }, 4000);
};
</script>
<?php endif; ?>

<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap');

header {
    font-family    : 'Inter', -apple-system, sans-serif;
    background     : #ffffff;
    border-bottom  : 1px solid #e9eef5;
    padding        : 0 36px;
    height         : 80px;
    display        : flex;
    align-items    : center;
    justify-content: space-between;
    position       : sticky;
    top            : 0;
    z-index        : 998;
    box-shadow     : 0 1px 0 #e9eef5,
                     0 4px 20px rgba(0,0,0,0.03);
    gap            : 20px;
}

.hdr-left {
    display       : flex;
    flex-direction: column;
    gap           : 4px;
    min-width     : 0;
}

.hdr-title {
    font-size     : 26px;
    font-weight   : 800;
    color         : #0f172a;
    letter-spacing: -0.04em;
    line-height   : 1;
    margin        : 0;
    white-space   : nowrap;
}

.hdr-sub {
    font-size    : 14px;
    color        : #64748b;
    font-weight  : 500;
    margin       : 0;
    display      : flex;
    align-items  : center;
    gap          : 8px;
}

.hdr-sub-dot {
    width        : 4px;
    height       : 4px;
    border-radius: 50%;
    background   : #cbd5e1;
}

.hdr-center {
    display    : flex;
    align-items: center;
    gap        : 12px;
    flex-shrink: 0;
}

.hdr-search {
    position    : relative;
    display     : flex;
    align-items : center;
}

.hdr-search input {
    width        : 420px;
    padding      : 10px 16px 10px 38px;
    border-radius: 10px;
    border       : 1px solid #e2e8f0;
    background   : #f8fafc;
    font-family  : 'Inter', sans-serif;
    font-size    : 15px;
    font-weight  : 500;
    color        : #0f172a;
    transition   : all 0.2s;
    outline      : none;
}

.hdr-search input:focus {
    background  : #ffffff;
    border-color: #3b82f6;
    box-shadow  : 0 0 0 3px rgba(59, 130, 246, 0.1);
}

.hdr-search input::placeholder {
    color: #94a3b8;
}

.hdr-search-icon {
    position: absolute;
    left    : 12px;
    color   : #94a3b8;
    font-size: 15px;
    pointer-events: none;
}

.hdr-search-shortcut {
    position     : absolute;
    right        : 10px;
    font-size    : 11px;
    font-weight  : 600;
    color        : #94a3b8;
    background   : #e2e8f0;
    padding      : 2px 6px;
    border-radius: 4px;
    letter-spacing: 0.5px;
    pointer-events: none;
}

.hdr-right {
    display    : flex;
    align-items: center;
    gap        : 14px;
    flex-shrink: 0;
}

.hdr-icon-btn {
    width          : 42px;
    height         : 42px;
    border-radius  : 10px;
    background     : #ffffff;
    border         : 1px solid #e2e8f0;
    display        : flex;
    align-items    : center;
    justify-content: center;
    font-size      : 18px;
    color          : #64748b;
    cursor         : pointer;
    transition     : all 0.2s ease;
    text-decoration: none;
}

.hdr-icon-btn:hover {
    background  : #f8fafc;
    border-color: #cbd5e1;
    color       : #0f172a;
    transform   : translateY(-1px);
}

.hdr-notif {
    position       : relative;
    width          : 42px;
    height         : 42px;
    border-radius  : 10px;
    background     : var(--panel);
    border         : 1px solid var(--border);
    display        : flex;
    align-items    : center;
    justify-content: center;
    font-size      : 18px;
    cursor         : pointer;
    transition     : all 0.2s ease;
    text-decoration: none;
}

.hdr-notif:hover {
    background  : #f8fafc;
    border-color: #cbd5e1;
    transform   : translateY(-1px);
}

.hdr-notif-badge {
    position     : absolute;
    top          : -5px;
    right        : -5px;
    width        : 20px;
    height       : 20px;
    border-radius: 50%;
    background   : #ffffff;
    color        : #ef4444;
    font-size    : 11px;
    font-weight  : 900;
    display      : flex;
    align-items  : center;
    justify-content: center;
    border       : 2px solid #ef4444;
    box-shadow   : 0 2px 4px rgba(0,0,0,0.2);
}

.hdr-notif.has-alerts {
    background: #ef4444;
    border-color: #ef4444;
    color: white;
    animation: pulseAlert 2s infinite;
}

@keyframes pulseAlert {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.hdr-user-container {
    position: relative;
}

.hdr-user {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 40px;
    height: 40px;
    background: var(--muted);
    border: 1px solid var(--border);
    border-radius: 10px;
    transition: all 0.18s ease;
    cursor: pointer;
}

.hdr-user:hover {
    background: var(--panel);
    border-color: var(--dim);
}

.hdr-user-dropdown {
    position: absolute;
    top: calc(100% + 12px);
    right: 0;
    width: 240px;
    background: var(--panel);
    border: 1px solid var(--border);
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    opacity: 0;
    visibility: hidden;
    transform: translateY(10px);
    transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
    z-index: 1000;
    overflow: hidden;
}

.hdr-user-dropdown.active {
    opacity: 1;
    visibility: visible;
    transform: translateY(0);
}

.dropdown-header {
    padding: 16px;
    border-bottom: 1px solid var(--border);
    background: rgba(var(--sb-accent-rgb), 0.03);
}

.dropdown-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    color: var(--text);
    text-decoration: none;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.2s;
}

.dropdown-item:hover {
    background: var(--muted);
    color: var(--accent);
}

.dropdown-item.logout {
    color: #ef4444;
    border-top: 1px solid var(--border);
}

.dropdown-item.logout:hover {
    background: #fef2f2;
}

.hdr-av {
    width          : 36px;
    height         : 36px;
    border-radius  : 8px;
    background     : linear-gradient(135deg, var(--sb-accent-deep), var(--sb-accent));
    display        : flex;
    align-items    : center;
    justify-content: center;
    font-size      : 15px;
    flex-shrink    : 0;
    box-shadow     : 0 2px 6px rgba(var(--sb-accent-rgb), 0.3);
    color          : white;
    font-weight    : 600;
}

.hdr-search-results {
    position: absolute;
    top: calc(100% + 10px);
    left: 0;
    width: 100%;
    background: white;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    max-height: 400px;
    overflow-y: auto;
    z-index: 9999;
    display: none;
    flex-direction: column;
}

.hdr-search-results.active {
    display: flex;
}

.search-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 16px;
    text-decoration: none;
    color: #0f172a;
    border-bottom: 1px solid #f1f5f9;
    transition: background 0.2s;
}

.search-item:last-child {
    border-bottom: none;
}

.search-item:hover {
    background: #f8fafc;
}

.search-item .icon {
    font-size: 20px;
    background: #f1f5f9;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
}

.search-item .details {
    display: flex;
    flex-direction: column;
    flex: 1;
}

.search-item .title {
    font-weight: 600;
    font-size: 14px;
}

.search-item .sub {
    font-size: 12px;
    color: #64748b;
    margin-top: 2px;
}

.search-item .badge {
    font-size: 10px;
    padding: 2px 8px;
    border-radius: 12px;
    font-weight: 600;
    text-transform: uppercase;
}

.search-item .badge.online { background: #dcfce7; color: #166534; }
.search-item .badge.offline { background: #fee2e2; color: #991b1b; }
.search-item .badge.high { background: #fee2e2; color: #991b1b; }
.search-item .badge.medium { background: #fef3c7; color: #92400e; }
.search-item .badge.user { background: #e0e7ff; color: #3730a3; }

.search-empty {
    padding: 24px;
    text-align: center;
    color: #64748b;
    font-size: 14px;
}

.hdr-user-meta { display: flex; flex-direction: column; gap: 1px; }

.hdr-user-name {
    font-size  : 15px;
    font-weight: 700;
    color      : #0f172a;
    line-height: 1;
    white-space: nowrap;
}

.hdr-user-role {
    font-size  : 12.5px;
    color      : #64748b;
    font-weight: 500;
    line-height: 1;
}

@keyframes modalSpringUp {
    0%   { opacity: 0; transform: translateY(32px) scale(0.95); }
    60%  { opacity: 1; transform: translateY(-4px) scale(1.01); }
    100% { opacity: 1; transform: translateY(0)   scale(1); }
}
.modal-spring {
    animation: modalSpringUp 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
}

@media (max-width: 1100px) { .hdr-health { display: none; } }
@media (max-width: 900px)  { .hdr-center { display: none; } }
@media (max-width: 768px)  {
    header { padding: 0 14px; height: 62px; gap: 10px; }
    .hdr-title { font-size: 17px; }
    .hdr-sub { display: none; }
    .hdr-user-meta { display: none; }
    .hdr-user { border: none; background: transparent; }
    .hdr-icon-btn { width: 38px; height: 38px; font-size: 16px; }
    .hdr-notif  { width: 38px; height: 38px; font-size: 16px; }
    .hdr-notif-pill { height: 38px; padding: 0 10px; font-size: 13px; }
    .hdr-hamburger { display: flex !important; }
    .hdr-right { gap: 8px; }
}

.hdr-hamburger {
    display: none;
    width: 42px;
    height: 42px;
    border-radius: 10px;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    flex-shrink: 0;
    font-size: 20px;
    color: #0f172a;
    transition: background 0.2s;
}
.hdr-hamburger:hover { background: #f1f5f9; }
</style>

<header>

    <button class="hdr-hamburger" onclick="toggleSidebar()" title="Open Menu" aria-label="Open navigation menu">
        ☰
    </button>

    <div class="hdr-left">
        <h1 class="hdr-title"><?php echo htmlspecialchars($system_name); ?></h1>
        <p class="hdr-sub">
            <?php echo htmlspecialchars($organization_name); ?>
            <span class="hdr-sub-dot"></span>
            System Administration
        </p>
    </div>

    <div class="hdr-center">
        
        <div class="hdr-search" style="position: relative;">
            <span class="hdr-search-icon">🔍</span>
            <input type="text" id="globalSearchInput" placeholder="Search nodes, alerts, users..." autocomplete="off">
            <span class="hdr-search-shortcut">⌘K</span>
            
            <div id="searchResults" class="hdr-search-results"></div>
        </div>
    </div>

    <div class="hdr-right">


        <button id="darkModeToggle" class="hdr-icon-btn" onclick="toggleDarkMode()" title="Toggle Dark Mode" style="font-size:18px; cursor:pointer; border:none;">
            ☾
        </button>

        <?php 
        $open_alerts_query = $conn->query("SELECT COUNT(*) as count FROM alerts WHERE status='Open'");
        $alerts_count = $open_alerts_query ? $open_alerts_query->fetch_assoc()['count'] : 0;
        ?>
        <a href="alerts.php" class="hdr-notif <?php echo $alerts_count > 0 ? 'has-alerts' : ''; ?>" title="<?php echo $alerts_count > 0 ? $alerts_count . ' Active Alerts' : 'Alerts'; ?>">
            <?php echo $alerts_count > 0 ? '🚨' : '🔔'; ?>
            <?php if ($alerts_count > 0): ?>
            <span class="hdr-notif-badge"><?php echo $alerts_count; ?></span>
            <?php endif; ?>
        </a>

        <?php if (isRecentlyAuthorized()): ?>
        <div style="display:flex; align-items:center; gap:6px; position:relative;">
            <button id="lockSessionBtn" class="hdr-icon-btn" onclick="revokeAuth()" title="Secure Session Active — Click to Cancel" style="background: #3b82f6; color: #fff; border: none; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.4);">
                🔓
            </button>
            <div id="sbaTimerBadge" style="
                background: linear-gradient(135deg, #3b82f6, #2563eb);
                color: #fff;
                font-family: 'Inter', monospace;
                font-size: 12px;
                font-weight: 700;
                padding: 4px 10px;
                border-radius: 8px;
                letter-spacing: 0.5px;
                white-space: nowrap;
                box-shadow: 0 2px 8px rgba(59, 130, 246, 0.35);
                animation: sbaTimerPulse 2s ease-in-out infinite;
                cursor: default;
                user-select: none;
            " title="Time remaining in secure session">
                <span id="sbaTimerText">5:00</span>
            </div>
        </div>
        <?php else: ?>
        <button id="lockSessionBtn" class="hdr-icon-btn" title="Session Locked" style="color: #94a3b8; border-color: rgba(148, 163, 184, 0.2); background: rgba(148, 163, 184, 0.05); cursor: default;" disabled>
            🔒
        </button>
        <?php endif; ?>
        <style>
            @keyframes sbaTimerPulse {
                0%, 100% { opacity: 1; }
                50% { opacity: 0.85; }
            }
            @keyframes sbaTimerUrgent {
                0%, 100% { opacity: 1; transform: scale(1); }
                50% { opacity: 0.9; transform: scale(1.05); }
            }
        </style>

        <div class="hdr-user-container">
            <div class="hdr-user" onclick="toggleUserDropdown(event)" title="<?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User Profile'); ?>">
                <div class="hdr-av">
                    <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'U', 0, 1)); ?>
                </div>
            </div>
            
            <div id="userDropdown" class="hdr-user-dropdown">
                <div class="dropdown-header">
                    <div style="font-weight: 800; color: var(--text); font-size: 0.9rem;"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></div>
                    <div style="font-size: 0.75rem; color: var(--dim);"><?php echo htmlspecialchars($_SESSION['email'] ?? 'admin@hulo.gov.ph'); ?></div>
                </div>
                
                <a href="settings.php" class="dropdown-item">
                    <span>👤</span> Information
                </a>
                <a href="settings.php?tab=preferences" class="dropdown-item">
                    <span>⚙️</span> Settings
                </a>
                <a href="logout.php" class="dropdown-item logout">
                    <span>🚪</span> Logout
                </a>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('globalSearchInput');
        const resultsBox = document.getElementById('searchResults');
        let debounceTimer;

        document.addEventListener('keydown', (e) => {
            if ((e.metaKey || e.ctrlKey) && e.key === 'k') {
                e.preventDefault();
                input.focus();
            }

            if (e.key === 'Escape') {
                resultsBox.classList.remove('active');
                input.blur();
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target.closest('.hdr-search')) {
                resultsBox.classList.remove('active');
            }
        });

        input.addEventListener('focus', () => {
            if (input.value.trim().length >= 2) resultsBox.classList.add('active');
        });

        input.addEventListener('input', (e) => {
            clearTimeout(debounceTimer);
            const query = e.target.value.trim();

            if (query.length < 2) {
                resultsBox.classList.remove('active');
                return;
            }

            debounceTimer = setTimeout(() => {
                fetch(`api/search.php?q=${encodeURIComponent(query)}`)
                    .then(res => res.json())
                    .then(data => {
                        resultsBox.innerHTML = '';
                        resultsBox.classList.add('active');

                        if (!data.results || data.results.length === 0) {
                            resultsBox.innerHTML = `<div class="search-empty">No results found for "${query}"</div>`;
                            return;
                        }

                        data.results.forEach(item => {
                            const a = document.createElement('a');
                            a.href = item.url;
                            a.className = 'search-item';
                            a.innerHTML = `
                                <div class="icon">${item.icon}</div>
                                <div class="details">
                                    <div class="title">${highlightText(item.title, query)}</div>
                                    <div class="sub">${highlightText(item.sub, query)}</div>
                                </div>
                                ${item.badge ? `<span class="badge ${item.badge}">${item.badge}</span>` : ''}
                            `;
                            resultsBox.appendChild(a);
                        });
                    })
                    .catch(err => console.error('Search error:', err));
            }, 300); // 300ms debounce
        });

        function highlightText(text, term) {
            if (!text) return '';
            const regex = new RegExp(`(${term})`, 'gi');
            return text.replace(regex, '<span style="background: rgba(250,204,21,0.4); border-radius: 2px;">$1</span>');
        }
    });
    </script>
    <script>
    function toggleDarkMode() {
        const isDark = document.documentElement.classList.toggle('dark-mode');
        localStorage.setItem('sg_theme', isDark ? 'dark' : 'light');
        const btn = document.getElementById('darkModeToggle');
        if (btn) {
            btn.textContent = isDark ? '☀' : '☾';
            btn.title = isDark ? 'Switch to Light Mode' : 'Switch to Dark Mode';
        }
    }
    // Update button icon on page load
    document.addEventListener('DOMContentLoaded', () => {
        if (localStorage.getItem('sg_theme') === 'dark') {
            const btn = document.getElementById('darkModeToggle');
            if (btn) {
                btn.textContent = '☀';
                btn.title = 'Switch to Light Mode';
            }
        }
    });

    function toggleUserDropdown(e) {
        e.stopPropagation();
        const dropdown = document.getElementById('userDropdown');
        dropdown.classList.toggle('active');
        
        // Close search results if open
        const resultsBox = document.getElementById('searchResults');
        if (resultsBox) resultsBox.classList.remove('active');
    }

    // Close dropdown on click outside
    document.addEventListener('click', (e) => {
        const dropdown = document.getElementById('userDropdown');
        if (dropdown && dropdown.classList.contains('active')) {
            if (!e.target.closest('.hdr-user-container')) {
                dropdown.classList.remove('active');
            }
        }
    });
    </script>
    
    <div id="idleWarningModal" style="
        display:none; position:fixed; inset:0; z-index:999999;
        background:rgba(15,23,42,0.55); backdrop-filter:blur(4px);
        align-items:center; justify-content:center;
    ">
        <div style="
            background:#fff; border-radius:20px; padding:2.5rem 2.2rem;
            max-width:420px; width:90%; text-align:center;
            box-shadow:0 24px 60px rgba(0,0,0,0.18);
            font-family:'Inter',sans-serif;
        ">
            <div style="font-size:3rem; margin-bottom:1rem;">⏱️</div>
            <h2 style="font-size:1.25rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">
                Session Expiring Soon
            </h2>
            <p style="color:#64748b; font-size:0.9rem; margin-bottom:1.5rem;">
                You've been inactive. Your session will expire in
                <strong id="idleCountdown" style="color:#ef4444;">5:00</strong>.
            </p>
            <div style="display:flex; gap:12px; justify-content:center;">
                <button onclick="resetIdleTimer()" style="
                    background:#22c55e; color:#fff; border:none; border-radius:10px;
                    padding:0.75rem 1.8rem; font-family:'Inter',sans-serif;
                    font-size:0.9rem; font-weight:700; cursor:pointer;
                ">✅ Stay Logged In</button>
                <a href="logout.php" style="
                    background:#fee2e2; color:#ef4444; border:none; border-radius:10px;
                    padding:0.75rem 1.8rem; font-family:'Inter',sans-serif;
                    font-size:0.9rem; font-weight:700; cursor:pointer;
                    text-decoration:none; display:inline-flex; align-items:center;
                ">🚪 Logout Now</a>
            </div>
        </div>
    </div>
    <script>
    (function() {
        const IDLE_LIMIT   = 30 * 60 * 1000; // 30 min total
        const WARN_BEFORE  =  5 * 60 * 1000; // warn at 25 min (5 min left)
        const WARN_LIMIT   = IDLE_LIMIT - WARN_BEFORE;

        let idleTimer, countdownTimer, warnShown = false;
        const modal    = document.getElementById('idleWarningModal');
        const cdEl     = document.getElementById('idleCountdown');

        function fmtTime(ms) {
            const tot = Math.ceil(ms / 1000);
            const m   = Math.floor(tot / 60);
            const s   = tot % 60;
            return m + ':' + String(s).padStart(2, '0');
        }

        function startCountdown(endAt) {
            clearInterval(countdownTimer);
            countdownTimer = setInterval(() => {
                const left = endAt - Date.now();
                if (left <= 0) {
                    clearInterval(countdownTimer);
                    window.location.href = 'logout.php?reason=idle';
                } else {
                    cdEl.textContent = fmtTime(left);
                }
            }, 500);
        }

        window.resetIdleTimer = function() {
            clearTimeout(idleTimer);
            clearInterval(countdownTimer);
            warnShown = false;
            modal.style.display = 'none';
            idleTimer = setTimeout(showWarning, WARN_LIMIT);
        };

        function showWarning() {
            if (warnShown) return;
            warnShown = true;
            modal.style.display = 'flex';
            startCountdown(Date.now() + WARN_BEFORE);
        }

        ['mousemove','keydown','click','scroll','touchstart'].forEach(ev =>
            document.addEventListener(ev, resetIdleTimer, { passive: true })
        );

        resetIdleTimer(); // start the clock
    })();
    </script>
    <div id="globalAuthModal" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(15,23,42,0.55); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:20px; padding:2.5rem; max-width:440px; width:90%; box-shadow:0 24px 60px rgba(0,0,0,0.2); font-family:'Inter',sans-serif; text-align:center;">
            <div style="font-size:3rem; margin-bottom:1rem;">🔐</div>
            <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Secure Session Authorization</h2>
            <p style="color:#64748b; font-size:1rem; margin-bottom:2rem;">Enter your administrator password to unlock sensitive controls for 5 minutes.</p>
            <div style="text-align:left; margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">Administrator Password</label>
                <input type="password" id="globalAuthPwd" placeholder="••••••••" style="width:100%; padding:12px 16px; border-radius:10px; border:1px solid #cbd5e1; font-size:1rem; outline:none; transition:all 0.2s;">
                <div id="globalAuthError" style="color:#ef4444; font-size:0.875rem; margin-top:8px; display:none;">Invalid password.</div>
            </div>
            <div style="display:flex; gap:12px;">
                <button onclick="closeAuthModal()" style="flex:1; padding:12px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; font-weight:600; color:#64748b; cursor:pointer;">Cancel</button>
                <button onclick="confirmAuth()" id="confirmAuthBtn" style="flex:2; padding:12px; border-radius:10px; border:none; background:#3b82f6; color:#fff; font-weight:600; cursor:pointer; box-shadow:0 4px 12px rgba(59,130,246,0.3);">Unlock Features</button>
            </div>
        </div>
    </div>

    <!-- Global Revoke Confirmation Modal -->
    <div id="globalRevokeModal" style="display:none; position:fixed; inset:0; z-index:999999; background:rgba(15,23,42,0.55); backdrop-filter:blur(6px); align-items:center; justify-content:center;">
        <div style="background:#fff; border-radius:20px; padding:2.5rem; max-width:400px; width:90%; box-shadow:0 24px 60px rgba(0,0,0,0.2); font-family:'Inter',sans-serif; text-align:center;">
            <div style="font-size:3rem; margin-bottom:1rem;">🔒</div>
            <h2 style="font-size:1.5rem; font-weight:800; color:#0f172a; margin-bottom:0.5rem;">Lock Secure Session?</h2>
            <p style="color:#64748b; font-size:1rem; margin-bottom:1.5rem;">Please confirm your administrator password to end the secure session.</p>
            <div style="text-align:left; margin-bottom:1.5rem;">
                <label style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">Administrator Password</label>
                <input type="password" id="globalRevokePwd" placeholder="••••••••" style="width:100%; padding:12px 16px; border-radius:10px; border:1px solid #cbd5e1; font-size:1rem; outline:none; transition:all 0.2s;">
                <div id="globalRevokeError" style="color:#ef4444; font-size:0.875rem; margin-top:8px; display:none;">Invalid password.</div>
            </div>
            <div style="display:flex; gap:12px;">
                <button onclick="closeRevokeModal()" style="flex:1; padding:12px; border-radius:10px; border:1px solid #e2e8f0; background:#fff; font-weight:600; color:#64748b; cursor:pointer;">Cancel</button>
                <button onclick="confirmRevokeAction()" id="confirmRevokeBtn" style="flex:1; padding:12px; border-radius:10px; border:none; background:#ef4444; color:#fff; font-weight:600; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.3);">End Session</button>
            </div>
        </div>
    </div>

    <script>
    const csrfToken = '<?php echo generateCsrfToken(); ?>';
    const isAuthorized = <?php echo isRecentlyAuthorized() ? 'true' : 'false'; ?>;
    const sbaAuthTime = <?php echo isset($_SESSION['last_auth_time']) ? (int)$_SESSION['last_auth_time'] : '0'; ?>;
    const SBA_WINDOW = 300; // 5 minutes in seconds

    // SBA Countdown Timer
    (function() {
        if (!isAuthorized || !sbaAuthTime) return;

        const timerText  = document.getElementById('sbaTimerText');
        const timerBadge = document.getElementById('sbaTimerBadge');
        if (!timerText || !timerBadge) return;

        const expiresAt = sbaAuthTime + SBA_WINDOW;

        function updateTimer() {
            const now       = Math.floor(Date.now() / 1000);
            const remaining = expiresAt - now;

            if (remaining <= 0) {
                timerText.textContent = '0:00';
                clearInterval(sbaInterval);
                // Auto-reload to reflect locked state
                location.reload();
                return;
            }

            const mins = Math.floor(remaining / 60);
            const secs = remaining % 60;
            timerText.textContent = mins + ':' + String(secs).padStart(2, '0');

            // Visual urgency when under 60 seconds
            if (remaining <= 60) {
                timerBadge.style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
                timerBadge.style.boxShadow  = '0 2px 8px rgba(239, 68, 68, 0.4)';
                timerBadge.style.animation   = 'sbaTimerUrgent 1s ease-in-out infinite';
            } else if (remaining <= 120) {
                timerBadge.style.background = 'linear-gradient(135deg, #f59e0b, #d97706)';
                timerBadge.style.boxShadow  = '0 2px 8px rgba(245, 158, 11, 0.35)';
            }
        }

        updateTimer(); // run immediately
        const sbaInterval = setInterval(updateTimer, 1000);
    })();

    function openAuthModal() {
        document.getElementById('globalAuthModal').style.display = 'flex';
        const input = document.getElementById('globalAuthPwd');
        input.value = '';
        input.focus();
    }

    function closeAuthModal() {
        document.getElementById('globalAuthModal').style.display = 'none';
        document.getElementById('globalAuthError').style.display = 'none';
        document.getElementById('globalAuthPwd').style.borderColor = '#cbd5e1';
    }

    async function confirmAuth() {
        const pwd = document.getElementById('globalAuthPwd').value;
        const btn = document.getElementById('confirmAuthBtn');
        const err = document.getElementById('globalAuthError');
        
        if (!pwd) return;

        btn.disabled = true;
        btn.textContent = 'Verifying...';

        try {
            const formData = new URLSearchParams();
            formData.append('admin_password', pwd);
            formData.append('csrf_token', csrfToken);

            const response = await fetch('api/auth_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData.toString()
            });

            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                err.style.display = 'block';
                err.textContent = data.error || 'Invalid password.';
                document.getElementById('globalAuthPwd').style.borderColor = '#ef4444';
            }
        } catch (e) {
            err.style.display = 'block';
            err.textContent = 'Connection error.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'Unlock Features';
        }
    }

    function openRevokeModal() {
        document.getElementById('globalRevokeModal').style.display = 'flex';
        const input = document.getElementById('globalRevokePwd');
        input.value = '';
        input.focus();
    }

    function closeRevokeModal() {
        document.getElementById('globalRevokeModal').style.display = 'none';
        document.getElementById('globalRevokeError').style.display = 'none';
        document.getElementById('globalRevokePwd').style.borderColor = '#cbd5e1';
    }

    function revokeAuth() {
        openRevokeModal();
    }

    async function confirmRevokeAction() {
        const pwd = document.getElementById('globalRevokePwd').value;
        const btn = document.getElementById('confirmRevokeBtn');
        const err = document.getElementById('globalRevokeError');
        
        if (!pwd) return;

        btn.disabled = true;
        btn.textContent = 'Verifying...';
        
        try {
            const formData = new URLSearchParams();
            formData.append('admin_password', pwd);
            formData.append('action', 'revoke');
            formData.append('csrf_token', csrfToken);

            const response = await fetch('api/auth_session.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData.toString()
            });
            const data = await response.json();
            if (data.success) {
                location.reload();
            } else {
                err.style.display = 'block';
                err.textContent = data.error || 'Invalid password.';
                document.getElementById('globalRevokePwd').style.borderColor = '#ef4444';
            }
        } catch (e) {
            err.style.display = 'block';
            err.textContent = 'Connection error.';
        } finally {
            btn.disabled = false;
            btn.textContent = 'End Session';
        }
    }

    // Header Lock Button Handling
    document.addEventListener('DOMContentLoaded', () => {
        const lockBtn = document.getElementById('lockSessionBtn');
        if (lockBtn && lockBtn.hasAttribute('disabled')) {
            lockBtn.removeAttribute('disabled');
            lockBtn.style.cursor = 'pointer';
            lockBtn.onclick = openAuthModal;
        }
    });

    // Global Rate Limit Modal (Cooling Period)
    <?php if ($error_param === 'rate_limit'): ?>
    document.addEventListener('DOMContentLoaded', () => {
        const modalHtml = `
        <div id="rateLimitModal" style="position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.4); backdrop-filter:blur(8px); z-index:999999; display:flex; align-items:center; justify-content:center; font-family:'Inter',sans-serif;">
            <div style="background:white; border-radius:24px; padding:40px; max-width:400px; text-align:center; box-shadow:0 20px 50px rgba(0,0,0,0.2); animation:modalPop 0.4s cubic-bezier(0.34,1.56,0.64,1);">
                <div style="font-size:64px; margin-bottom:20px; animation:iconFloat 3s ease-in-out infinite;">❄️</div>
                <h2 style="color:#0f172a; margin-bottom:12px; font-weight:800;">Cooling Period</h2>
                <p style="color:#64748b; line-height:1.6; margin-bottom:24px;">The system has detected too many requests from your connection. For security, this action is temporarily frozen.</p>
                <div id="coolingTimer" style="background:#eff6ff; color:#3b82f6; font-weight:700; padding:12px 24px; border-radius:12px; display:inline-block; font-size:1.1rem; margin-bottom:24px;">Please wait 60s</div>
                <button onclick="document.getElementById('rateLimitModal').remove()" style="display:block; width:100%; background:#f1f5f9; color:#475569; border:none; padding:12px; border-radius:12px; font-weight:700; cursor:pointer; transition:all 0.2s;">Dismiss</button>
            </div>
        </div>
        <style>
            @keyframes modalPop { from { opacity:0; transform:scale(0.9); } to { opacity:1; transform:scale(1); } }
            @keyframes iconFloat { 0%, 100% { transform:translateY(0); } 50% { transform:translateY(-10px); } }
        </style>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHtml);
        
        let seconds = 60;
        const timerText = document.getElementById('coolingTimer');
        const interval = setInterval(() => {
            seconds--;
            if (seconds <= 0) {
                clearInterval(interval);
                timerText.textContent = "You can try again now";
                timerText.style.background = "#ecfdf5";
                timerText.style.color = "#10b981";
            } else {
                timerText.textContent = "Please wait " + seconds + "s";
            }
        }, 1000);
    });
    <?php endif; ?>

    // Enter key support
    document.getElementById('globalAuthPwd').addEventListener('keypress', (e) => {
        if (e.key === 'Enter') confirmAuth();
    });
    </script>
</header>
