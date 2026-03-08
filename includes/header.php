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
    'self_deactivate'    => ['⚠️', 'Action Blocked',     'You cannot deactivate your own account.'],
    'self_demote'        => ['⚠️', 'Action Blocked',     'You cannot change your own role away from System Admin.'],
];

if ($success_param && isset($toast_map[$success_param])) {
    [$icon, $title, $msg, $color, $bg] = $toast_map[$success_param];
    $toast = ['type'=>'success', 'icon'=>$icon, 'title'=>$title, 'msg'=>$msg, 'color'=>$color, 'bg'=>$bg];
} elseif ($error_param) {
    if (isset($error_map[$error_param])) {
        [$icon, $title, $msg] = $error_map[$error_param];
        $toast = ['type'=>'error', 'icon'=>$icon, 'title'=>$title, 'msg'=>$msg, 'color'=>'#ef4444', 'bg'=>'#fef2f2'];
    } else {
        $toast = ['type'=>'error', 'icon'=>'⚠️', 'title'=>'Something went wrong', 'msg'=>'An unexpected error occurred. Please try again.', 'color'=>'#ef4444', 'bg'=>'#fef2f2'];
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
    background     : #ffffff;
    border         : 1px solid #e2e8f0;
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
    width        : 18px;
    height       : 18px;
    border-radius: 50%;
    background   : #ef4444;
    color        : white;
    font-size    : 10px;
    font-weight  : 800;
    display      : flex;
    align-items  : center;
    justify-content: center;
    border       : 2px solid white;
    box-shadow   : 0 2px 4px rgba(239, 68, 68, 0.3);
}

.hdr-notif-pill {
    display: flex;
    align-items: center;
    gap: 8px;
    height: 42px;
    padding: 0 16px;
    border-radius: 10px;
    background: #fef2f2;
    border: 1px solid #fca5a5;
    color: #ef4444;
    font-size: 15px;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.2s ease;
    animation: pulseAlert 2s infinite;
}

.hdr-notif-pill:hover {
    background: #fee2e2;
    transform: translateY(-1px);
}

@keyframes pulseAlert {
    0% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.4); }
    70% { box-shadow: 0 0 0 10px rgba(239, 68, 68, 0); }
    100% { box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
}

.hdr-user {
    display      : flex;
    align-items  : center;
    gap          : 10px;
    padding      : 5px 14px 5px 6px;
    background   : #f8fafc;
    border       : 1px solid #e2e8f0;
    border-radius: 30px;
    transition   : all 0.18s ease;
    cursor       : default;
}

.hdr-user:hover {
    background  : #f1f5f9;
    border-color: #cbd5e1;
}

.hdr-av {
    width          : 36px;
    height         : 36px;
    border-radius  : 50%;
    background     : linear-gradient(135deg, #10b981, #34d399);
    display        : flex;
    align-items    : center;
    justify-content: center;
    font-size      : 15px;
    flex-shrink    : 0;
    box-shadow     : 0 2px 6px rgba(16,185,129,0.3);
    color          : white;
    font-weight    : 500;
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
    .hdr-user { padding: 4px 6px; border-radius: 50%; }
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

        <a href="reports.php" class="hdr-icon-btn" title="System Reports">
            📊
        </a>

        <a href="settings.php" class="hdr-icon-btn" title="Global Settings">
            ⚙️
        </a>

        <?php 
        $open_alerts_query = $conn->query("SELECT COUNT(*) as count FROM alerts WHERE status='Open'");
        $alerts_count = $open_alerts_query ? $open_alerts_query->fetch_assoc()['count'] : 0;
        ?>
        <?php if ($alerts_count > 0): ?>
        <a href="alerts.php" class="hdr-notif-pill" title="Active Alerts">
            <span style="font-size:18px; line-height:1;">🚨</span> Alerts <?php echo $alerts_count; ?>
        </a>
        <?php else: ?>
        <a href="alerts.php" class="hdr-notif" title="Alerts">
            🔔
        </a>
        <?php endif; ?>

        <div class="hdr-user">
            <div class="hdr-av">
                <?php echo strtoupper(substr($_SESSION['full_name'] ?? 'User', 0, 1)); ?>
            </div>
            <div class="hdr-user-meta">
                <span class="hdr-user-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'User'); ?></span>
                <span class="hdr-user-role"><?php echo ucfirst($_SESSION['role'] ?? 'Admin'); ?></span>
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

        setInterval(() => {
            fetch('firebase_sync_silent.php')
                .catch(err => console.error("Sync heartbeat missed:", err));
        }, 15000);
        
    })();
    </script>
</header>