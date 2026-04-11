<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<style>

@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap');

:root {
    --sb-w           : 280px;
    --sb-w-collapsed : 70px;
    --sb-font        : 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    --sb-bg          : #111827;
    --sb-bg-footer   : #0d1117;
    --sb-border      : rgba(255,255,255,0.07);
    --sb-accent      : #34d318;
    --sb-accent-deep : #10b981;
    --sb-accent-rgb  : 52,211,153;
    --sb-txt-hi      : #f1f5f9;
    --sb-txt-nav     : #cbd5e1;
    --sb-txt-muted   : #94a3b8;
    --sb-hover       : rgba(255,255,255,0.11);
    --sb-active      : rgba(52,211,153,0.18);
    --sb-active-bdr  : rgba(52,211,153,0.40);
    --ease           : cubic-bezier(0.4, 0, 0.2, 1);
}

.sidebar {
    width          : var(--sb-w) !important;
    height         : 100vh !important;
    position       : fixed !important;
    left: 0 !important; top: 0 !important;
    background     : var(--sb-bg) !important;
    display        : flex !important;
    flex-direction : column !important;
    z-index        : 1000 !important;
    font-family    : var(--sb-font) !important;
    overflow       : hidden !important;
    transition     : width 0.3s var(--ease), transform 0.3s var(--ease) !important;
    box-shadow     : 4px 0 30px rgba(0,0,0,0.35) !important;
    padding        : 0 !important;
    border         : none !important;
}

.sidebar.collapsed {
    width: var(--sb-w-collapsed) !important;
}

/* Mobile overlay backdrop */
.sb-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 999;
    backdrop-filter: blur(2px);
    opacity: 0;
    transition: opacity 0.3s ease;
}
.sb-overlay.active {
    display: block;
    opacity: 1;
}

/* Mobile close button inside sidebar */
.sb-close-mobile {
    display: none;
    position: absolute;
    top: 16px;
    right: 14px;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    border: 1px solid rgba(255,255,255,0.15);
    color: #f1f5f9;
    font-size: 16px;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: background 0.2s;
    line-height: 1;
}
.sb-close-mobile:hover { background: rgba(255,255,255,0.2); }

@media (max-width: 768px) {
    .sidebar {
        transform: translateX(-100%) !important;
        width: var(--sb-w) !important;
        z-index: 1100 !important;
    }
    .sidebar.mobile-open {
        transform: translateX(0) !important;
    }
    .sidebar.collapsed {
        width: var(--sb-w) !important;
        transform: translateX(-100%) !important;
    }
    .sidebar.collapsed.mobile-open {
        transform: translateX(0) !important;
    }
    .sb-toggle { display: none !important; }
    .sb-close-mobile { display: flex; }
    .sidebar-feather { display: none !important; }
}

.sb-inner {
    display        : flex;
    flex-direction : column;
    height         : 100%;
    overflow-y     : auto;
    overflow-x     : hidden;
    background     : rgba(255,255,255,0.04);
}

.sb-inner::-webkit-scrollbar { width: 3px; }
.sb-inner::-webkit-scrollbar-track { background: transparent; }
.sb-inner::-webkit-scrollbar-thumb { background: rgba(var(--sb-accent-rgb), 0.2); border-radius: 4px; }

.sb-brand {
    display       : flex;
    align-items   : center;
    gap           : 14px;
    padding       : 26px 20px 22px;
    border-bottom : 1px solid var(--sb-border);
    flex-shrink   : 0;
    position      : relative;
    transition    : padding 0.3s var(--ease);
}

.sidebar.collapsed .sb-brand {
    padding: 26px 0 22px;
    justify-content: center;
}

.sb-logo-ring {
    position      : relative;
    width         : 56px;
    height        : 56px;
    flex-shrink   : 0;
    transition    : transform 0.3s var(--ease);
}

.sidebar.collapsed .sb-logo-ring {
    transform: scale(0.8);
}

.sb-logo-ring::before {
    content       : '';
    position      : absolute;
    inset         : -2px;
    border-radius : 13px;
    background    : conic-gradient(var(--sb-accent-deep) 0deg, var(--sb-accent) 90deg, transparent 160deg, transparent 360deg);
    animation     : spin-ring 6s linear infinite;
    opacity       : 0.7;
}

@keyframes spin-ring { to { transform: rotate(360deg); } }

.sb-logo-inner {
    position       : absolute;
    inset          : 2px;
    border-radius  : 12px;
    background     : transparent;
    display        : flex;
    align-items    : center;
    justify-content: center;
    overflow       : hidden;
    padding        : 5px;
}

.sb-logo-inner img { width: 115%; height: 115%; object-fit: contain; }

.sb-brand-copy {
    transition: opacity 0.2s var(--ease);
}

.sb-brand-copy h2 {
    font-size: 21px; font-weight: 900; color: var(--sb-txt-hi); margin: 0 0 5px; letter-spacing: -0.04em; line-height: 1;
}

.sb-brand-copy p {
    font-size: 11px; font-weight: 600; color: rgba(255,255,255,0.6); margin: 0;
}

.sb-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    width: 26px;
    height: 26px;
    border-radius: 50%;
    background: var(--sb-bg-footer);
    border: 1px solid var(--sb-border);
    color: var(--sb-txt-muted);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    z-index: 10;
    transition: all 0.3s var(--ease);
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
}

.sb-toggle:hover {
    background: var(--sb-accent-deep);
    color: white;
    border-color: var(--sb-accent);
}

.sidebar.collapsed .sb-toggle {
    right         : -10px;
    width         : 24px;
    height        : 24px;
    transform     : translateY(-50%) rotate(180deg);
    background    : var(--sb-accent);
    color         : white;
    box-shadow    : 0 2px 10px rgba(0,0,0,0.4);
    border        : 2px solid var(--sb-bg);
}

.sidebar.collapsed .sb-brand-copy,
.sidebar.collapsed .sb-nav-lbl,
.sidebar.collapsed .sb-lbl,
.sidebar.collapsed .sb-badge,
.sidebar.collapsed .sb-um,
.sidebar.collapsed .sb-out span:last-child {
    display: none !important;
}

.sb-nav {
    flex: 1;
    padding: 18px 12px;
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.sidebar.collapsed .sb-nav {
    padding: 18px 0;
}

.sb-lbl {
    font-size: 10px; font-weight: 700; text-transform: uppercase; color: var(--sb-txt-muted); padding: 20px 10px 8px; display: flex; align-items: center; gap: 8px;
}

.sb-lbl::after { content: ''; flex: 1; height: 1px; background: rgba(255,255,255,0.1); }

.sb-nav a {
    display: flex; align-items: center; gap: 12px; padding: 12px; border-radius: 12px; color: var(--sb-txt-nav); text-decoration: none; font-size: 15px; font-weight: 500; transition: all 0.2s var(--ease);
}

.sidebar.collapsed .sb-nav a {
    justify-content: center;
    padding: 14px 0;
    width: 100%;
}

.sidebar.collapsed .sb-ico {
    margin: 0 !important;
    font-size: 20px;
    width: auto;
    height: auto;
    display: flex;
    align-items: center;
    justify-content: center;
    background: transparent;
    box-shadow: none;
}

.sb-nav a:hover { background: var(--sb-hover); color: var(--sb-txt-hi); }
.sb-nav a.active { background: var(--sb-active); color: var(--sb-accent); font-weight: 700; border: 1px solid var(--sb-active-bdr); }
.sb-nav a.active .sb-ico { background: rgba(var(--sb-accent-rgb), 0.2); box-shadow: 0 0 15px rgba(var(--sb-accent-rgb), 0.4); }

.sb-footer {
    padding: 16px; border-top: 1px solid var(--sb-border); background: var(--sb-bg-footer); display: flex; flex-direction: column; gap: 12px; transition: padding 0.3s var(--ease);
}

.sidebar.collapsed .sb-footer {
    padding: 16px 0; align-items: center;
}

.sb-footer-user { display: flex; align-items: center; gap: 12px; }
.sb-av { width: 38px; height: 38px; border-radius: 10px; background: linear-gradient(135deg, var(--sb-accent-deep), var(--sb-accent)); display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
.sb-um strong { display: block; font-size: 14px; font-weight: 700; color: var(--sb-txt-hi); line-height: 1.2; }
.sb-um span { font-size: 11px; color: var(--sb-txt-muted); }

.sb-out {
    width: 100%; padding: 10px; border-radius: 10px; background: rgba(239,68,68,0.1); color: #f87171; display: flex; align-items: center; justify-content: center; gap: 8px; font-weight: 600; text-decoration: none; transition: all 0.2s;
}
.sb-out:hover { background: rgba(239,68,68,0.2); color: #fca5a5; }

.sidebar.collapsed .sb-out {
    width: 40px; height: 40px; border-radius: 10px; padding: 0;
}
</style>

<div class="sb-overlay" onclick="closeMobileSidebar()"></div>
<aside class="sidebar">
    <div class="sb-inner">
        
        <div class="sb-brand">
            <div class="sb-logo-ring">
                <div class="sb-logo-inner">
                    <img src="img/ShineGuard3.png" alt="SG">
                </div>
            </div>
            <div class="sb-brand-copy">
                <h2>Shine Guard</h2>
                <p>Mandaluyong City</p>
            </div>
            <button class="sb-toggle" onclick="toggleSidebar()" title="Toggle Sidebar">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"></polyline></svg>
            </button>
            <button class="sb-close-mobile" onclick="closeMobileSidebar()" title="Close Menu">✕</button>
        </div>

        <nav class="sb-nav">
            <div class="sb-lbl">Main</div>
            <a href="dashboard.php" class="<?php echo $current_page=='dashboard.php'?'active':''; ?>">
                <div class="sb-ico">🏠</div>
                <span class="sb-nav-lbl">Dashboard</span>
            </a>
            <a href="streetlights.php" class="<?php echo $current_page=='streetlights.php'?'active':''; ?>">
                <div class="sb-ico">💡</div>
                <span class="sb-nav-lbl">Streetlights</span>
            </a>
            <a href="cctv.php" class="<?php echo $current_page=='cctv.php'?'active':''; ?>">
                <div class="sb-ico">🎥</div>
                <span class="sb-nav-lbl">CCTV</span>
            </a>

            <div class="sb-lbl">Operations</div>
            <a href="alerts.php" class="<?php echo $current_page=='alerts.php'?'active':''; ?>">
                <div class="sb-ico">🚨</div>
                <span class="sb-nav-lbl">Alerts</span>
                <?php
                $acq = $conn->query("SELECT COUNT(*) as count FROM alerts WHERE status='Open'");
                $ac  = $acq->fetch_assoc()['count'];
                if ($ac > 0): ?>
                <span class="sb-badge"><?php echo $ac; ?></span>
                <?php endif; ?>
            </a>
            <a href="work_orders.php" class="<?php echo $current_page=='work_orders.php'?'active':''; ?>">
                <div class="sb-ico">🔧</div>
                <span class="sb-nav-lbl">Work Orders</span>
            </a>
            <?php if (canDo('manage_schedules')): ?>
            <a href="schedule.php" class="<?php echo $current_page=='schedule.php'?'active':''; ?>">
                <div class="sb-ico">📅</div>
                <span class="sb-nav-lbl">Schedules</span>
            </a>
            <?php endif; ?>
            <a href="reports.php" class="<?php echo $current_page=='reports.php'?'active':''; ?>">
                <div class="sb-ico">📊</div>
                <span class="sb-nav-lbl">Reports</span>
            </a>

            <?php if (canDo('manage_firebase') || canDo('view_settings')): ?>
            <div class="sb-lbl">System</div>
            <?php if (canDo('manage_firebase')): ?>
            <a href="firebase_dashboard.php" class="<?php echo $current_page=='firebase_dashboard.php'?'active':''; ?>">
                <div class="sb-ico">🔥</div>
                <span class="sb-nav-lbl">Firebase IoT</span>
            </a>
            <?php endif; ?>
            <?php if (canDo('view_settings')): ?>
            <a href="settings.php" class="<?php echo $current_page=='settings.php'?'active':''; ?>">
                <div class="sb-ico">⚙️</div>
                <span class="sb-nav-lbl">Settings</span>
            </a>
            <?php endif; ?>
            <?php if (canDo('view_activity_logs')): ?>
            <a href="activity_logs.php" class="<?php echo $current_page=='activity_logs.php'?'active':''; ?>">
                <div class="sb-ico">🛡️</div>
                <span class="sb-nav-lbl">Activity Logs</span>
            </a>
            <?php endif; ?>
            <?php if (getUserRole() === 'System Admin'): ?>
            <a href="dev_simulator.php" class="<?php echo $current_page=='dev_simulator.php'?'active':''; ?>">
                <div class="sb-ico">🧪</div>
                <span class="sb-nav-lbl">Developer Lab</span>
            </a>
            <?php endif; ?>
            <?php endif; ?>
        </nav>

    </div>
</aside>

<div class="sidebar-feather" style="position:fixed; top:0; bottom:0; left:280px; width:28px; background:linear-gradient(90deg, rgba(13,17,23,0.1) 0%, transparent 100%); pointer-events:none; z-index:999; transition: left 0.3s var(--ease);"></div>

<script>
function isMobile() {
    return window.innerWidth <= 768;
}

function openMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sb-overlay');
    sidebar.classList.add('mobile-open');
    if (overlay) overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeMobileSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.querySelector('.sb-overlay');
    sidebar.classList.remove('mobile-open');
    if (overlay) overlay.classList.remove('active');
    document.body.style.overflow = '';
}

function toggleSidebar() {
    if (isMobile()) {
        const sidebar = document.querySelector('.sidebar');
        if (sidebar.classList.contains('mobile-open')) {
            closeMobileSidebar();
        } else {
            openMobileSidebar();
        }
        return;
    }
    // Desktop collapse behaviour
    const sidebar = document.querySelector('.sidebar');
    const layout = document.querySelector('.layout');
    const feather = document.querySelector('.sidebar-feather');
    const isCollapsed = sidebar.classList.toggle('collapsed');
    if (layout) layout.classList.toggle('sidebar-collapsed', isCollapsed);
    if (feather) feather.style.left = isCollapsed ? '70px' : '280px';
    localStorage.setItem('sidebarCollapsed', isCollapsed);
}

// Close sidebar when a nav link is tapped on mobile
document.addEventListener('DOMContentLoaded', () => {
    if (isMobile()) {
        document.querySelectorAll('.sb-nav a').forEach(link => {
            link.addEventListener('click', () => closeMobileSidebar());
        });
    }
});

(function() {
    if (isMobile()) return; // Don't restore collapsed state on mobile
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    if (isCollapsed) {
        const sidebar = document.querySelector('.sidebar');
        const layout = document.querySelector('.layout');
        const feather = document.querySelector('.sidebar-feather');
        if (sidebar) sidebar.classList.add('collapsed');
        if (layout) layout.classList.add('sidebar-collapsed');
        if (feather) feather.style.left = '70px';
    }
})();
</script>

<?php
/* Bottom navigation bar — mobile only */
$p = $current_page;
$al_query = isset($conn) ? $conn->query("SELECT COUNT(*) as c FROM alerts WHERE status='Open'") : false;
$al_count  = ($al_query) ? (int)$al_query->fetch_assoc()['c'] : 0;
?>
<nav class="mobile-bottom-nav">
    <a href="dashboard.php"    class="mob-nav-item <?php echo $p=='dashboard.php'?'active':''; ?>">
        <span class="mob-nav-icon">🏠</span><span>Home</span>
    </a>
    <a href="streetlights.php" class="mob-nav-item <?php echo $p=='streetlights.php'?'active':''; ?>">
        <span class="mob-nav-icon">💡</span><span>Lights</span>
    </a>
    <a href="alerts.php"       class="mob-nav-item <?php echo $p=='alerts.php'?'active':''; ?>">
        <span class="mob-nav-icon">🚨</span><span>Alerts</span>
        <?php if ($al_count > 0): ?>
        <span class="mob-nav-badge"><?php echo $al_count; ?></span>
        <?php endif; ?>
    </a>
    <a href="work_orders.php"  class="mob-nav-item <?php echo $p=='work_orders.php'?'active':''; ?>">
        <span class="mob-nav-icon">🔧</span><span>Orders</span>
    </a>
    <?php if (canDo('view_settings')): ?>
    <a href="settings.php"     class="mob-nav-item <?php echo $p=='settings.php'?'active':''; ?>">
        <span class="mob-nav-icon">⚙️</span><span>Settings</span>
    </a>
    <?php else: ?>
    <a href="reports.php"      class="mob-nav-item <?php echo $p=='reports.php'?'active':''; ?>">
        <span class="mob-nav-icon">📊</span><span>Reports</span>
    </a>
    <?php endif; ?>
</nav>