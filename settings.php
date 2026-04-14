<?php
require_once 'dbconnect.php';
requireLogin();

// Role-based access control for administrative tabs
$isAdmin = (getUserRole() === 'System Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: settings.php?tab=preferences&error=invalid_csrf');
    exit();
}
    $updates = [
        'system_name'      => $_POST['system_name'],
        'organization_name'=> $_POST['organization_name'],
        'location'         => $_POST['location'],
        'timezone'         => $_POST['timezone'],
        'language'         => $_POST['language'],
        'theme_color'      => $_POST['theme_color'],
        'logo_text'        => $_POST['logo_text'],
        'contact_email'    => $_POST['contact_email'],
        'contact_phone'    => $_POST['contact_phone'],
        'map_center_lat'   => $_POST['map_center_lat'],
        'map_center_lng'   => $_POST['map_center_lng'],
        'map_zoom_level'   => $_POST['map_zoom_level']
    ];
    foreach ($updates as $key => $value) {
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("INSERT INTO system_config (config_key, config_value, description, updated_by) 
                     VALUES ('$key', '$value_escaped', 'System preference', {$_SESSION['user_id']})
                     ON DUPLICATE KEY UPDATE config_value = '$value_escaped', updated_by = {$_SESSION['user_id']}");
    }
    logActivity($conn, $_SESSION['user_id'], 'System Preferences Updated', 'Updated system preferences and branding');
    header('Location: settings.php?tab=preferences&success=settings_saved');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_thresholds'])) {
if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: settings.php?tab=thresholds&error=invalid_csrf');
    exit();
}
    $thresholds = [
        'lux_threshold_min'              => $_POST['lux_threshold_min'],
        'lux_threshold_critical'         => $_POST['lux_threshold_critical'],
        'temperature_threshold_max'      => $_POST['temperature_threshold_max'],
        'temperature_threshold_critical' => $_POST['temperature_threshold_critical'],
        'current_threshold_max'          => $_POST['current_threshold_max'],
        'current_threshold_critical'     => $_POST['current_threshold_critical'],
        'voltage_threshold_min'          => $_POST['voltage_threshold_min'],
        'voltage_threshold_critical'     => $_POST['voltage_threshold_critical'],
        'humidity_threshold_max'         => $_POST['humidity_threshold_max'],
        'humidity_threshold_critical'    => $_POST['humidity_threshold_critical']
    ];
    foreach ($thresholds as $key => $value) {
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("UPDATE system_config SET config_value = '$value_escaped', updated_by = {$_SESSION['user_id']} 
                     WHERE config_key = '$key'");
    }
    logActivity($conn, $_SESSION['user_id'], 'Thresholds Updated', 'Updated predictive maintenance thresholds');
    header('Location: settings.php?tab=thresholds&success=settings_saved');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_alerts'])) {
if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: settings.php?tab=alerts&error=invalid_csrf');
    exit();
}
    $alerts = [
        'alert_email_enabled'    => isset($_POST['alert_email_enabled'])    ? '1' : '0',
        'alert_sms_enabled'      => isset($_POST['alert_sms_enabled'])      ? '1' : '0',
        'alert_email_recipients' => $_POST['alert_email_recipients'],
        'alert_sms_recipients'   => $_POST['alert_sms_recipients'],
        'critical_alert_sound'   => isset($_POST['critical_alert_sound'])   ? '1' : '0',
        'alert_retention_days'   => $_POST['alert_retention_days']
    ];
    foreach ($alerts as $key => $value) {
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("INSERT INTO system_config (config_key, config_value, description, updated_by) 
                     VALUES ('$key', '$value_escaped', 'Alert preference', {$_SESSION['user_id']})
                     ON DUPLICATE KEY UPDATE config_value = '$value_escaped', updated_by = {$_SESSION['user_id']}");
    }
    logActivity($conn, $_SESSION['user_id'], 'Alert Settings Updated', 'Updated alert and notification preferences');
    header('Location: settings.php?tab=alerts&success=settings_saved');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_automation'])) {
if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: settings.php?tab=automation&error=invalid_csrf');
    exit();
}
    $automation = [
        'auto_dim_enabled'                => isset($_POST['auto_dim_enabled'])                ? '1' : '0',
        'default_dimming_level'           => $_POST['default_dimming_level'],
        'auto_sync_interval'              => $_POST['auto_sync_interval'],
        'predictive_maintenance_enabled'  => isset($_POST['predictive_maintenance_enabled'])  ? '1' : '0',
        'auto_create_work_orders'         => isset($_POST['auto_create_work_orders'])         ? '1' : '0',
        'maintenance_prediction_days'     => $_POST['maintenance_prediction_days']
    ];
    foreach ($automation as $key => $value) {
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("INSERT INTO system_config (config_key, config_value, description, updated_by) 
                     VALUES ('$key', '$value_escaped', 'Automation setting', {$_SESSION['user_id']})
                     ON DUPLICATE KEY UPDATE config_value = '$value_escaped', updated_by = {$_SESSION['user_id']}");
    }
    logActivity($conn, $_SESSION['user_id'], 'Automation Settings Updated', 'Updated automation preferences');
    header('Location: settings.php?tab=automation&success=settings_saved');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_data'])) {
if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    header('Location: settings.php?tab=data&error=invalid_csrf');
    exit();
}
    $data = [
        'data_retention_days'     => $_POST['data_retention_days'],
        'footage_retention_days'  => $_POST['footage_retention_days'],
        'cloud_backup_enabled'    => isset($_POST['cloud_backup_enabled']) ? '1' : '0',
        'backup_frequency'        => $_POST['backup_frequency'],
        'export_format_default'   => $_POST['export_format_default']
    ];
    foreach ($data as $key => $value) {
        $value_escaped = $conn->real_escape_string($value);
        $conn->query("INSERT INTO system_config (config_key, config_value, description, updated_by) 
                     VALUES ('$key', '$value_escaped', 'Data setting', {$_SESSION['user_id']})
                     ON DUPLICATE KEY UPDATE config_value = '$value_escaped', updated_by = {$_SESSION['user_id']}");
    }
    logActivity($conn, $_SESSION['user_id'], 'Data Settings Updated', 'Updated data retention and backup settings');
    header('Location: settings.php?tab=data&success=settings_saved');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_mfa'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=security&error=invalid_csrf');
        exit();
    }
    
    $action = $_POST['mfa_action'] ?? '';
    
    if ($action === 'enable') {
        require_once 'src/Services/TOTPService.php';
        $secret = \ShineGuard\Services\TOTPService::generateSecret();
        $_SESSION['mfa_setup_secret'] = $secret;
        
        logActivity($conn, $_SESSION['user_id'], 'Security Setup', 'User initiated MFA setup');
        header('Location: settings.php?tab=security&setup=1');
        exit();
    } elseif ($action === 'disable') {
        $password = $_POST['mfa_password'] ?? '';
        $totp_code = $_POST['mfa_totp_code'] ?? '';
        $user_id = (int)$_SESSION['user_id'];

        // 1. Verify Password
        $auth_stmt = $conn->prepare("SELECT password_hash, mfa_secret FROM users WHERE user_id = ? LIMIT 1");
        $auth_stmt->bind_param("i", $user_id);
        $auth_stmt->execute();
        $user_data = $auth_stmt->get_result()->fetch_assoc();
        $auth_stmt->close();

        if (!$user_data || !password_verify($password, $user_data['password_hash'])) {
            header('Location: settings.php?tab=security&error=invalid_password');
            exit();
        }

        // 2. Verify TOTP Code (Double-Lock)
        require_once 'src/Services/TOTPService.php';
        if (!\ShineGuard\Services\TOTPService::verifyCode($user_data['mfa_secret'], $totp_code)) {
            header('Location: settings.php?tab=security&error=invalid_mfa_code');
            exit();
        }

        // 3. SEC-POLICY: Restricted Roles cannot disable their own MFA
        $user_role = getUserRole();
        if ($user_role === 'System Observer' || $user_role === 'Maintenance Operator') {
            header('Location: settings.php?tab=security&error=mfa_mandatory_role');
            exit();
        }

        // Success: Disable
        $stmt = $conn->prepare("UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        
        logActivity($conn, $user_id, 'Security Update', 'User disabled MFA after password/TOTP verification');
        header('Location: settings.php?tab=security&success=mfa_disabled');
        exit();
    }
}

// ── SECURITY FEATURE: MFA SETUP VERIFICATION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_mfa_setup'])) {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=security&error=invalid_csrf');
        exit();
    }

    $setup_secret = $_SESSION['mfa_setup_secret'] ?? '';
    $verify_code = $_POST['mfa_verify_code'] ?? '';

    if (empty($setup_secret)) {
        header('Location: settings.php?tab=security&error=mfa_timeout');
        exit();
    }

    require_once 'src/Services/TOTPService.php';
    if (\ShineGuard\Services\TOTPService::verifyCode($setup_secret, $verify_code)) {
        $stmt = $conn->prepare("UPDATE users SET mfa_secret = ?, mfa_enabled = 1 WHERE user_id = ?");
        $stmt->bind_param("si", $setup_secret, $_SESSION['user_id']);
        $stmt->execute();
        
        unset($_SESSION['mfa_setup_secret']);
        logActivity($conn, $_SESSION['user_id'], 'Security Update', 'User verified and enabled MFA pairing');
        header('Location: settings.php?tab=security&success=mfa_enabled');
        exit();
    } else {
        header('Location: settings.php?tab=security&setup=1&error=invalid_mfa_code');
        exit();
    }
}

// ── SECURITY FEATURE: ADMINISTRATIVE MFA RESET ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['admin_reset_mfa'])) {
    if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=users&error=invalid_csrf');
        exit();
    }

    $target_user_id = intval($_POST['target_user_id']);
    $admin_password = $_POST['admin_password'] ?? '';
    $admin_totp     = $_POST['admin_totp_code'] ?? '';
    $active_admin_id = (int)$_SESSION['user_id'];

    // 1. Verify Admin Password & MFA Status
    $auth_stmt = $conn->prepare("SELECT password_hash, mfa_secret, mfa_enabled FROM users WHERE user_id = ? LIMIT 1");
    $auth_stmt->bind_param("i", $active_admin_id);
    $auth_stmt->execute();
    $admin_data = $auth_stmt->get_result()->fetch_assoc();
    $auth_stmt->close();

    if (!$admin_data || !password_verify($admin_password, $admin_data['password_hash'])) {
        header('Location: settings.php?tab=users&error=invalid_admin_password');
        exit();
    }

    // 2. Double-Lock: Admin MUST have mfa enabled themselves to reset others
    if (!$admin_data['mfa_enabled']) {
        header('Location: settings.php?tab=users&error=admin_mfa_required');
        exit();
    }

    require_once 'src/Services/TOTPService.php';
    if (!\ShineGuard\Services\TOTPService::verifyCode($admin_data['mfa_secret'], $admin_totp)) {
        header('Location: settings.php?tab=users&error=invalid_mfa_code');
        exit();
    }

    // Success: Reset Target User
    $reset_stmt = $conn->prepare("UPDATE users SET mfa_secret = NULL, mfa_enabled = 0 WHERE user_id = ?");
    $reset_stmt->bind_param("i", $target_user_id);
    
    if ($reset_stmt->execute()) {
        logActivity($conn, $active_admin_id, 'MFA Forced Reset', "Administrator forcefully removed MFA security for user ID: $target_user_id (Authorized via Admin MFA/Pass)");
        header('Location: settings.php?tab=users&success=mfa_reset_success');
    } else {
        header('Location: settings.php?tab=users&error=db_error');
    }
    $reset_stmt->close();
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
    checkRateLimit('add_user', 10, 1);
    
    if (getUserRole() !== 'System Admin') {
        // Redundant role check removed - handled by requireLogin at top
        exit();
    }

    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=users&error=invalid_csrf');
        exit();
    }


    $username   = sanitize($_POST['username']   ?? '');
    $full_name  = sanitize($_POST['full_name']  ?? '');
    $email_raw  = trim($_POST['email']          ?? '');
    $email      = sanitize($email_raw);
    $role       = $_POST['role']                ?? '';
    $password   = $_POST['password']            ?? '';
    $confirm_pw = $_POST['confirm_password']    ?? '';
    $phone      = sanitize($_POST['phone']      ?? '');

    if (empty($username) || empty($full_name) || empty($email) || empty($password)) {
        header('Location: settings.php?tab=users&error=missing_fields');
        exit();
    }

    if (!preg_match('/^[a-zA-Z0-9_]{3,30}$/', $username)) {
        header('Location: settings.php?tab=users&error=invalid_username');
        exit();
    }

    if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        header('Location: settings.php?tab=users&error=invalid_email');
        exit();
    }
    if (!str_ends_with(strtolower($email_raw), '@hulo.gov.ph')) {
        header('Location: settings.php?tab=users&error=invalid_domain');
        exit();
    }

    if (strlen($password) < 8 || 
        !preg_match('/[A-Z]/', $password) || 
        !preg_match('/[0-9]/', $password) || 
        !preg_match('/[^a-zA-Z0-9]/', $password)) {
        header('Location: settings.php?tab=users&error=weak_password');
        exit();
    }

    if ($password !== $confirm_pw) {
        header('Location: settings.php?tab=users&error=password_mismatch');
        exit();
    }

    $allowed_roles = ['System Admin', 'Maintenance Operator', 'System Observer'];
    if (!in_array($role, $allowed_roles, true)) {
        header('Location: settings.php?tab=users&error=invalid_role');
        exit();
    }

    $password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

    $stmt = $conn->prepare("INSERT INTO users (username, full_name, email, role, password_hash, phone) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssss", $username, $full_name, $email, $role, $password_hash, $phone);

    try {
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'User Created', "Added new user: $username ($role)");
            header('Location: settings.php?tab=users&success=user_added');
            exit();
        } else {
            header('Location: settings.php?tab=users&error=db_error');
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $msg = $e->getMessage();
            if (stripos($msg, 'username') !== false) {
                header('Location: settings.php?tab=users&error=duplicate_username');
            } elseif (stripos($msg, 'email') !== false) {
                header('Location: settings.php?tab=users&error=duplicate_email');
            } else {
                header('Location: settings.php?tab=users&error=duplicate_entry');
            }
        } else {
            header('Location: settings.php?tab=users&error=db_error');
        }
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
    checkRateLimit('update_user', 10, 1);
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=users&error=invalid_csrf');
        exit();
    }


    $target_user_id = intval($_POST['target_user_id']);
    $full_name  = sanitize($_POST['full_name']);
    $email_raw  = trim($_POST['email'] ?? '');
    $email      = sanitize($email_raw);
    $role       = $_POST['role']  ?? '';
    $phone      = sanitize($_POST['phone'] ?? '');
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($full_name) || empty($email) || empty($role)) {
        header('Location: settings.php?tab=users&error=missing_fields');
        exit();
    }

    if (!filter_var($email_raw, FILTER_VALIDATE_EMAIL)) {
        header('Location: settings.php?tab=users&error=invalid_email');
        exit();
    }

    if ($target_user_id === (int)$_SESSION['user_id']) {
        if ($is_active === 0) {
            header('Location: settings.php?tab=users&error=self_deactivate');
            exit();
        }
        if ($role !== 'System Admin') {
            header('Location: settings.php?tab=users&error=self_demote');
            exit();
        }
    }

    $allowed_roles = ['System Admin', 'Maintenance Operator', 'System Observer'];
    if (!in_array($role, $allowed_roles, true)) {
        header('Location: settings.php?tab=users&error=invalid_role');
        exit();
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, email = ?, role = ?, phone = ?, is_active = ? WHERE user_id = ?");
    $stmt->bind_param("ssssii", $full_name, $email, $role, $phone, $is_active, $target_user_id);
    try {
        if ($stmt->execute()) {
            logActivity($conn, $_SESSION['user_id'], 'User Updated', "Updated user ID: $target_user_id");
            header('Location: settings.php?tab=users&success=user_updated');
            exit();
        } else {
            header('Location: settings.php?tab=users&error=db_error');
            exit();
        }
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $msg = $e->getMessage();
            if (stripos($msg, 'username') !== false) {
                header('Location: settings.php?tab=users&error=duplicate_username');
            } elseif (stripos($msg, 'email') !== false) {
                header('Location: settings.php?tab=users&error=duplicate_email');
            } else {
                header('Location: settings.php?tab=users&error=duplicate_entry');
            }
        } else {
            header('Location: settings.php?tab=users&error=db_error');
        }
        exit();
    }
}

// ── SECURITY FEATURE: SECURE USER DELETION ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    if (!$isAdmin) { header('Location: settings.php?error=unauthorized'); exit(); }
    checkRateLimit('delete_user', 5, 2);
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        header('Location: settings.php?tab=users&error=invalid_csrf');
        exit();
    }


    $target_user_id = intval($_POST['target_user_id']);
    $entered_pass   = $_POST['admin_password'] ?? '';
    $active_admin_id = (int)$_SESSION['user_id'];

    // 1. You cannot delete yourself
    if ($target_user_id === $active_admin_id) {
        header('Location: settings.php?tab=users&error=self_delete');
        exit();
    }

    // 2. Fetch the active Admin's secure password hash from the database
    $auth_stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ? LIMIT 1");
    $auth_stmt->bind_param("i", $active_admin_id);
    $auth_stmt->execute();
    $auth_res = $auth_stmt->get_result();
    $admin_data = $auth_res->fetch_assoc();
    $auth_stmt->close();

    // 3. Verify the password inputted in the modal matches the Admin's true password
    if (!$admin_data || !password_verify($entered_pass, $admin_data['password_hash'])) {
        header('Location: settings.php?tab=users&error=invalid_admin_password');
        exit();
    }
    setAuthorized();

    // 4. Password verified. Proceed with destructive deletion.
    $del_stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $del_stmt->bind_param("i", $target_user_id);
    if ($del_stmt->execute()) {
        logActivity($conn, $active_admin_id, 'User Deleted', "Permanently deleted user ID: $target_user_id");
        header('Location: settings.php?tab=users&success=user_deleted');
    } else {
        header('Location: settings.php?tab=users&error=db_error');
    }
    $del_stmt->close();
    exit();
}

$settings_query = "SELECT config_key, config_value FROM system_config";
$settings_result = $conn->query($settings_query);
$settings = [];
while ($row = $settings_result->fetch_assoc()) {
    $settings[$row['config_key']] = $row['config_value'];
}

$defaults = [
    'system_name' => 'Shine Guard Hulo',
    'organization_name' => 'Barangay Hulo',
    'location' => 'Malolos, Bulacan',
    'timezone' => 'Asia/Manila',
    'language' => 'English',
    'theme_color' => '#10b981',
    'logo_text' => '💡',
    'contact_email' => 'admin@hulo.barangay.ph',
    'contact_phone' => '+63 XXX XXX XXXX',
    'map_center_lat' => '14.6507',
    'map_center_lng' => '121.0494',
    'map_zoom_level' => '15',
    'alert_retention_days' => '90',
    'auto_sync_interval' => '30',
    'backup_frequency' => 'daily',
    'export_format_default' => 'csv'
];

foreach ($defaults as $key => $value) {
    if (!isset($settings[$key])) {
        $settings[$key] = $value;
    }
}

$active_tab = $_GET['tab'] ?? ($isAdmin ? 'preferences' : 'security');

// Ensure non-admins cannot "sneak" into admin tabs via URL parameter
if (!$isAdmin && $active_tab !== 'security') {
    $active_tab = 'security';
}
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>System Settings - <?php echo $settings['system_name']; ?></title>
<link rel="icon" type="image/png" href="img/ShineGuard3.png">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
<?php include 'assets/style.css'; ?>
:root { 
    --theme-color: <?php echo $theme_color; ?>; 
}

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

html, body {
    margin: 0 !important;
    padding: 0 !important;
    height: 100%;
    font-family: system-ui, -apple-system, 'Inter', sans-serif;
}

:root {
  --bg:             var(--sg-bg, #f8fafc);
  --surface:        rgba(255, 255, 255, 0.85);
  --surface-elev:   #ffffff;
  --border:         rgba(15, 23, 42, 0.08);
  --text-primary:   #0f172a;
  --text-secondary: #475569;
  --text-muted:     #64748b;
  --blue:           #3b82f6;
  --green:          #10b981;
  --red:            #ef4444;
  --amber:          #f59e0b;
  --purple:         #8b5cf6;
  --radius:         24px;
  --radius-sm:      12px;
  --glass:          blur(12px) saturate(180%);
  --shadow-sm:      0 2px 4px rgba(0,0,0,0.02);
  --shadow-md:      0 12px 40px rgba(15, 23, 42, 0.06);
}

.dark-mode {
  --bg:             #0b0f1a;
  --surface:        rgba(17, 24, 39, 0.8);
  --surface-elev:   #1f2937;
  --border:         rgba(255, 255, 255, 0.08);
  --text-primary:   #f8fafc;
  --text-secondary: #94a3b8;
  --text-muted:     #64748b;
}

body {
  background: var(--bg);
  font-family: system-ui, -apple-system, sans-serif;
  color: var(--text-primary);
  line-height: 1.5;
  -webkit-font-smoothing: antialiased;
}

.main-content {
  padding: 2.2rem 2.6rem;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 2.2rem;
  font-weight: 900;
  letter-spacing: -0.05em;
  color: var(--text-primary);
  text-transform: uppercase;
  margin-bottom: 0.4rem;
}

.page-header p {
  font-size: 0.875rem;
  color: var(--text-secondary);
}

.alert-success {
  background: var(--green-dim);
  border: 1px solid var(--green-border);
  border-left: 4px solid var(--green);
  border-radius: var(--radius-sm);
  padding: 0.85rem 1.2rem;
  margin-bottom: 1.4rem;
  color: #065f46;
  font-size: 0.875rem;
  font-weight: 600;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tabs {
  display: flex;
  gap: 12px;
  background: var(--surface);
  backdrop-filter: var(--glass);
  padding: 8px;
  border-radius: 100px;
  border: 1px solid var(--border);
  margin-bottom: 2.5rem;
  overflow-x: auto;
  scrollbar-width: none;
  box-shadow: var(--shadow-sm);
  width: fit-content;
  margin-left: auto;
  margin-right: auto;
}

.tabs::-webkit-scrollbar { display: none; }

.tab {
  padding: 0.7rem 1.8rem;
  background: transparent;
  border: none;
  cursor: pointer;
  font-family: system-ui, sans-serif;
  font-size: 0.85rem;
  font-weight: 800;
  color: var(--text-secondary);
  transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  border-radius: 100px;
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.tab:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.1);
}

.tab.active {
  color: #ffffff;
  background: var(--green);
  box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
}

.tab-content { display: none; }
.tab-content.active { 
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
  gap: 1.5rem;
  animation: fadeIn 0.4s cubic-bezier(0.16, 1, 0.3, 1); 
}

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(10px); }
  to { opacity: 1; transform: translateY(0); }
}

.setting-group {
  background: var(--surface);
  backdrop-filter: var(--glass);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  padding: 2.5rem;
  margin-bottom: 0;
  position: relative;
  overflow: hidden;
  transition: all 0.3s ease;
  box-shadow: var(--shadow-md);
}

.setting-group:hover {
  transform: translateY(-2px);
  box-shadow: 0 20px 50px rgba(15, 23, 42, 0.1);
  border-color: rgba(16, 185, 129, 0.2);
}

.setting-group h2 {
  font-size: 1.25rem;
  font-weight: 900;
  color: var(--text-primary);
  margin-bottom: 2rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  letter-spacing: -0.02em;
}

.setting-group h2 i {
  color: var(--green);
}

.setting-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.1rem 1.6rem;
  margin-bottom: 1.1rem;
}

.setting-row:last-of-type { margin-bottom: 0; }

.setting-item {
  display: flex;
  flex-direction: column;
  gap: 0.35rem;
}

.setting-item label {
  font-size: 0.8rem;
  font-weight: 800;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.04em;
}

.setting-item input[type="text"],
.setting-item input[type="password"],
.setting-item input[type="email"],
.setting-item input[type="tel"],
.setting-item input[type="number"],
.setting-item input[type="color"],
.setting-item select,
.setting-item textarea {
  background: var(--surface-elev);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-family: system-ui, sans-serif;
  font-size: 0.95rem;
  font-weight: 600;
  padding: 0.8rem 1rem;
  outline: none;
  transition: all 0.2s ease;
  width: 100%;
}

.setting-item textarea {
  resize: vertical;
  min-height: 80px;
}

.setting-item input:focus,
.setting-item select:focus,
.setting-item textarea:focus {
  border-color: var(--green);
  box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
  transform: translateY(-1px);
}

.setting-item small {
  font-size: 0.75rem;
  color: var(--text-muted);
  line-height: 1.5;
  margin-top: 0.2rem;
}

.checkbox-group {
  display: flex;
  align-items: center;
  gap: 0.7rem;
  padding: 0.75rem 1rem;
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: border-color .15s, background .15s;
  margin-bottom: 0.75rem;
}

.checkbox-group:last-of-type { margin-bottom: 0; }

.checkbox-group:hover {
  background: #f0f7ff;
  border-color: var(--blue-border);
}

.checkbox-group input[type="checkbox"] {
  width: 17px;
  height: 17px;
  cursor: pointer;
  accent-color: var(--blue);
  flex-shrink: 0;
}

.checkbox-group label {
  font-size: 0.86rem;
  font-weight: 500;
  color: var(--text-primary);
  cursor: pointer;
  margin: 0;
  user-select: none;
}

.color-row {
  display: flex;
  gap: 0.75rem;
  align-items: center;
}

.color-row input[type="color"] {
  width: 52px !important;
  height: 40px;
  padding: 3px !important;
  cursor: pointer;
}

.color-preview {
  width: 40px;
  height: 40px;
  border-radius: var(--radius-sm);
  border: 1.5px solid var(--border);
  flex-shrink: 0;
}

.form-footer {
  display: flex;
  justify-content: flex-end;
  padding-top: 1rem;
  margin-top: 0.4rem;
}

.btn-primary {
  background: var(--green);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: system-ui, sans-serif;
  font-size: 0.875rem;
  font-weight: 900;
  padding: 0.85rem 1.8rem;
  cursor: pointer;
  transition: all 0.2s cubic-bezier(0.16, 1, 0.3, 1);
  box-shadow: 0 8px 20px -5px rgba(16, 185, 129, 0.4);
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  text-transform: uppercase;
  letter-spacing: 0.02em;
}

.btn-primary:hover {
  background: #059669;
  transform: translateY(-2px);
  box-shadow: 0 12px 25px -5px rgba(16, 185, 129, 0.5);
}

.btn-secondary {
  background: var(--surface);
  backdrop-filter: var(--glass);
  color: var(--text-primary);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: system-ui, sans-serif;
  font-size: 0.875rem;
  font-weight: 800;
  padding: 0.85rem 1.6rem;
  cursor: pointer;
  transition: all 0.2s ease;
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
}

.btn-secondary:hover {
  background: var(--surface-elev);
  border-color: var(--green);
  color: var(--green);
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-top: 1.5rem;
  background: var(--surface);
  backdrop-filter: var(--glass);
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.95rem;
}

thead tr {
  background: rgba(15, 23, 42, 0.02);
  border-bottom: 2px solid var(--border);
}

thead th {
  font-size: 0.75rem;
  font-weight: 900;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: var(--text-secondary);
  padding: 1.2rem 1.5rem;
  text-align: left;
}

tbody tr {
  border-bottom: 1px solid var(--border);
  transition: all 0.2s ease;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: rgba(16, 185, 129, 0.03); }

tbody td {
  padding: 1.2rem 1.5rem;
  color: var(--text-primary);
  font-weight: 700;
  vertical-align: middle;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  font-size: 0.75rem;
  font-weight: 900;
  letter-spacing: 0.05em;
  text-transform: uppercase;
  padding: 0.4rem 0.8rem;
  border-radius: 100px;
  white-space: nowrap;
}

.badge.ok {
  background: rgba(16, 185, 129, 0.1);
  color: #10b981;
  border: 1px solid rgba(16, 185, 129, 0.2);
}

.badge.ok::before { content: '●'; font-size: 0.6rem; }

.badge.fail {
  background: rgba(239, 68, 68, 0.1);
  color: #ef4444;
  border: 1px solid rgba(239, 68, 68, 0.2);
}

.badge.fail::before { content: '●'; font-size: 0.6rem; }

.users-subtext {
  font-size: 0.83rem;
  color: var(--text-secondary);
  margin-bottom: 0.5rem;
}

.users-footer {
  margin-top: 1.2rem;
}

/* ── TOAST NOTIFICATION STYLES ── */
#toast-container {
  position: fixed;
  top: 24px;
  right: 24px;
  z-index: 99999;
  display: flex;
  flex-direction: column;
  gap: 10px;
  pointer-events: none;
}

.toast {
  background: white;
  border-radius: 12px;
  box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
  padding: 16px 20px;
  display: flex;
  align-items: center;
  gap: 14px;
  min-width: 300px;
  max-width: 400px;
  transform: translateX(120%);
  opacity: 0;
  transition: all 0.4s cubic-bezier(0.68, -0.55, 0.265, 1.55);
  pointer-events: auto;
  font-family: 'Inter', sans-serif;
}

.toast.show {
  transform: translateX(0);
  opacity: 1;
}

.toast.removing {
  transform: translateX(120%);
  opacity: 0;
}

.toast-icon {
  width: 32px;
  height: 32px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 16px;
  flex-shrink: 0;
}

.toast.success { border-left: 4px solid var(--green); }
.toast.success .toast-icon { background: var(--green-dim); color: var(--green); }

.toast.error { border-left: 4px solid var(--red); }
.toast.error .toast-icon { background: var(--red-dim); color: var(--red); }

.toast-content { flex: 1; }
.toast-title { font-weight: 700; color: var(--text-primary); font-size: 0.95rem; margin-bottom: 2px; }
.toast-msg { color: var(--text-secondary); font-size: 0.85rem; line-height: 1.4; }

</style>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>
<main class="main-content">

  <div class="page-header">
    <br>
    <br>
    <h1>⚙️ System Settings</h1>
    <p>Customize and configure your Shine Guard system</p>
  </div>

  <div class="tabs">
    <?php if ($isAdmin): ?>
    <button class="tab <?php echo $active_tab === 'preferences' ? 'active' : ''; ?>" onclick="switchTab('preferences', this)">🎨 System Preferences</button>
    <button class="tab <?php echo $active_tab === 'thresholds'  ? 'active' : ''; ?>" onclick="switchTab('thresholds', this)">📊 Predictive Thresholds</button>
    <button class="tab <?php echo $active_tab === 'alerts'      ? 'active' : ''; ?>" onclick="switchTab('alerts', this)">🚨 Alerts & Notifications</button>
    <button class="tab <?php echo $active_tab === 'automation'  ? 'active' : ''; ?>" onclick="switchTab('automation', this)">🤖 Automation</button>
    <button class="tab <?php echo $active_tab === 'data'        ? 'active' : ''; ?>" onclick="switchTab('data', this)">💾 Data & Backup</button>
    <?php endif; ?>
    <button class="tab <?php echo $active_tab === 'security'    ? 'active' : ''; ?>" onclick="switchTab('security', this)">🛡️ Security</button>
    <?php if ($isAdmin): ?>
    <button class="tab <?php echo $active_tab === 'users'       ? 'active' : ''; ?>" onclick="switchTab('users', this)">👥 User Management</button>
    <?php endif; ?>
  </div>

  <?php if ($isAdmin): ?>
  <div id="preferences" class="tab-content <?php echo $active_tab === 'preferences' ? 'active' : ''; ?>">
    <form method="POST">
      <input type="hidden" name="update_preferences" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="setting-group group-org">
        <h2>🏢 Organization Information</h2>
        <div class="setting-row">
          <div class="setting-item">
            <label>System Name</label>
            <input type="text" name="system_name" value="<?php echo htmlspecialchars($settings['system_name']); ?>" required>
            <small>Display name shown throughout the system</small>
          </div>
          <div class="setting-item">
            <label>Organization Name</label>
            <input type="text" name="organization_name" value="<?php echo htmlspecialchars($settings['organization_name']); ?>" required>
            <small>Your barangay or organization name</small>
          </div>
        </div>
        <div class="setting-row">
          <div class="setting-item">
            <label>Location</label>
            <input type="text" name="location" value="<?php echo htmlspecialchars($settings['location']); ?>" required>
            <small>City, Province, Country</small>
          </div>
          <div class="setting-item">
            <label>Timezone</label>
            <select name="timezone" required>
              <option value="Asia/Manila"     <?php echo $settings['timezone'] === 'Asia/Manila'     ? 'selected' : ''; ?>>Asia/Manila (PHT)</option>
              <option value="Asia/Singapore"  <?php echo $settings['timezone'] === 'Asia/Singapore'  ? 'selected' : ''; ?>>Asia/Singapore (SGT)</option>
              <option value="UTC"             <?php echo $settings['timezone'] === 'UTC'             ? 'selected' : ''; ?>>UTC</option>
            </select>
            <small>System timezone for timestamps</small>
          </div>
        </div>
      </div>

      <div class="setting-group group-brand">
        <h2>🎨 Branding & Appearance</h2>
        <div class="setting-row">
          <div class="setting-item">
            <label>Theme Color</label>
            <div class="color-row">
              <input type="color" name="theme_color" value="<?php echo $settings['theme_color']; ?>">
              <div class="color-preview" style="background: <?php echo $settings['theme_color']; ?>;"></div>
            </div>
            <small>Primary color for buttons and highlights</small>
          </div>
          <div class="setting-item">
            <label>Logo/Icon</label>
            <input type="text" name="logo_text" value="<?php echo htmlspecialchars($settings['logo_text']); ?>" maxlength="5">
            <small>Emoji or text (max 5 characters)</small>
          </div>
        </div>
        <div class="setting-row">
          <div class="setting-item">
            <label>Language</label>
            <select name="language">
              <option value="English"  <?php echo $settings['language'] === 'English'  ? 'selected' : ''; ?>>English</option>
              <option value="Filipino" <?php echo $settings['language'] === 'Filipino' ? 'selected' : ''; ?>>Filipino</option>
              <option value="Tagalog"  <?php echo $settings['language'] === 'Tagalog'  ? 'selected' : ''; ?>>Tagalog</option>
            </select>
            <small>System interface language</small>
          </div>
        </div>
      </div>

      <div class="setting-group group-contact">
        <h2>📞 Contact Information</h2>
        <div class="setting-row">
          <div class="setting-item">
            <label>Contact Email</label>
            <input type="email" name="contact_email" value="<?php echo htmlspecialchars($settings['contact_email']); ?>">
            <small>Primary contact email address</small>
          </div>
          <div class="setting-item">
            <label>Contact Phone</label>
            <input type="tel" name="contact_phone" value="<?php echo htmlspecialchars($settings['contact_phone']); ?>">
            <small>Primary contact phone number</small>
          </div>
        </div>
      </div>

      <div class="setting-group group-map">
        <h2>🗺️ Map Configuration</h2>
        <div class="setting-row">
          <div class="setting-item">
            <label>Map Center Latitude</label>
            <input type="number" step="0.000001" name="map_center_lat" value="<?php echo $settings['map_center_lat']; ?>" required>
            <small>Center point for map display</small>
          </div>
          <div class="setting-item">
            <label>Map Center Longitude</label>
            <input type="number" step="0.000001" name="map_center_lng" value="<?php echo $settings['map_center_lng']; ?>" required>
            <small>Center point for map display</small>
          </div>
        </div>
        <div class="setting-row">
          <div class="setting-item">
            <label>Default Zoom Level</label>
            <input type="number" min="1" max="20" name="map_zoom_level" value="<?php echo $settings['map_zoom_level']; ?>" required>
            <small>Zoom level (1–20, higher = closer)</small>
          </div>
        </div>
      </div>

      <div class="form-footer">
        <button type="submit" class="btn-primary">💾 Save Preferences</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div id="thresholds" class="tab-content <?php echo $active_tab === 'thresholds' ? 'active' : ''; ?>">
    <form method="POST">
      <input type="hidden" name="update_thresholds" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <?php
      $threshold_groups = [
        ['title' => '💡 Brightness Thresholds', 'fields' => [
          ['label' => '⚠️ Warning Level (lux)',  'name' => 'lux_threshold_min',      'step' => '0.1', 'default' => 20,  'hint' => 'Below this value triggers yellow warning'],
          ['label' => '🔴 Critical Level (lux)', 'name' => 'lux_threshold_critical', 'step' => '0.1', 'default' => 10,  'hint' => 'Below this value triggers critical alert'],
        ]],
        ['title' => '🌡️ Temperature Thresholds', 'fields' => [
          ['label' => '⚠️ Warning Level (°C)',  'name' => 'temperature_threshold_max',      'step' => '0.1', 'default' => 45, 'hint' => 'Above this value triggers yellow warning'],
          ['label' => '🔴 Critical Level (°C)', 'name' => 'temperature_threshold_critical', 'step' => '0.1', 'default' => 55, 'hint' => 'Above this value triggers critical alert'],
        ]],
        ['title' => '⚡ Current Thresholds', 'fields' => [
          ['label' => '⚠️ Warning Level (A)',  'name' => 'current_threshold_max',      'step' => '0.01', 'default' => 0.5, 'hint' => 'Above this value triggers yellow warning'],
          ['label' => '🔴 Critical Level (A)', 'name' => 'current_threshold_critical', 'step' => '0.01', 'default' => 0.7, 'hint' => 'Above this value triggers critical alert'],
        ]],
        ['title' => '🔋 Voltage Thresholds', 'fields' => [
          ['label' => '⚠️ Warning Level (V)',  'name' => 'voltage_threshold_min',      'step' => '0.1', 'default' => 2.0, 'hint' => 'Below this value triggers yellow warning'],
          ['label' => '🔴 Critical Level (V)', 'name' => 'voltage_threshold_critical', 'step' => '0.1', 'default' => 1.5, 'hint' => 'Below this value triggers critical alert'],
        ]],
        ['title' => '💧 Humidity Thresholds', 'fields' => [
          ['label' => '⚠️ Warning Level (%)',  'name' => 'humidity_threshold_max',      'step' => '1', 'default' => 80, 'hint' => 'Above this value triggers yellow warning'],
          ['label' => '🔴 Critical Level (%)', 'name' => 'humidity_threshold_critical', 'step' => '1', 'default' => 90, 'hint' => 'Above this value triggers critical alert'],
        ]],
      ];
      foreach ($threshold_groups as $group):
      ?>
      <div class="setting-group group-threshold">
        <h2><?php echo $group['title']; ?></h2>
        <div class="setting-row">
          <?php foreach ($group['fields'] as $f): ?>
          <div class="setting-item">
            <label><?php echo $f['label']; ?></label>
            <input type="number" step="<?php echo $f['step']; ?>" name="<?php echo $f['name']; ?>"
              value="<?php echo $settings[$f['name']] ?? $f['default']; ?>" required>
            <small><?php echo $f['hint']; ?></small>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endforeach; ?>

      <div class="form-footer">
        <button type="submit" class="btn-primary">💾 Save Thresholds</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div id="alerts" class="tab-content <?php echo $active_tab === 'alerts' ? 'active' : ''; ?>">
    <form method="POST">
      <input type="hidden" name="update_alerts" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="setting-group group-alerts">
        <h2>📧 Email Alerts</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="alert_email_enabled" id="alert_email_enabled" <?php echo ($settings['alert_email_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
          <label for="alert_email_enabled">Enable email notifications for alerts</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>Email Recipients (comma-separated)</label>
          <textarea name="alert_email_recipients" rows="3" placeholder="admin@example.com, tech@example.com"><?php echo htmlspecialchars($settings['alert_email_recipients'] ?? ''); ?></textarea>
          <small>Multiple email addresses separated by commas</small>
        </div>
      </div>

      <div class="setting-group group-alerts">
        <h2>📱 SMS Alerts</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="alert_sms_enabled" id="alert_sms_enabled" <?php echo ($settings['alert_sms_enabled'] ?? '0') == '1' ? 'checked' : ''; ?>>
          <label for="alert_sms_enabled">Enable SMS notifications for critical alerts</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>SMS Recipients (comma-separated phone numbers)</label>
          <textarea name="alert_sms_recipients" rows="3" placeholder="+63XXXXXXXXXX, +63XXXXXXXXXX"><?php echo htmlspecialchars($settings['alert_sms_recipients'] ?? ''); ?></textarea>
          <small>Phone numbers with country code (+63 for Philippines)</small>
        </div>
      </div>

      <div class="setting-group group-alerts">
        <h2>🔔 System Notifications</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="critical_alert_sound" id="critical_alert_sound" <?php echo ($settings['critical_alert_sound'] ?? '1') == '1' ? 'checked' : ''; ?>>
          <label for="critical_alert_sound">Play sound on critical alerts</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>Alert Retention Period (days)</label>
          <input type="number" name="alert_retention_days" value="<?php echo $settings['alert_retention_days'] ?? 90; ?>" required>
          <small>How long to keep resolved alerts in database</small>
        </div>
      </div>

      <div class="form-footer">
        <button type="submit" class="btn-primary">💾 Save Alert Settings</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div id="automation" class="tab-content <?php echo $active_tab === 'automation' ? 'active' : ''; ?>">
    <form method="POST">
      <input type="hidden" name="update_automation" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="setting-group group-auto">
        <h2>💡 Automatic Dimming</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="auto_dim_enabled" id="auto_dim_enabled" <?php echo ($settings['auto_dim_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
          <label for="auto_dim_enabled">Enable automatic dimming based on schedule</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>Default Dimming Level (%)</label>
          <input type="number" min="0" max="100" name="default_dimming_level" value="<?php echo $settings['default_dimming_level'] ?? 70; ?>" required>
          <small>Default brightness percentage for new lights</small>
        </div>
      </div>

      <div class="setting-group group-auto">
        <h2>🔄 Firebase Sync</h2>
        <div class="setting-item">
          <label>Auto-sync Interval (seconds)</label>
          <input type="number" min="10" max="300" name="auto_sync_interval" value="<?php echo $settings['auto_sync_interval'] ?? 30; ?>" required>
          <small>How often to sync Firebase data (10–300 seconds)</small>
        </div>
      </div>

      <div class="setting-group group-auto">
        <h2>🤖 Predictive Maintenance</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="predictive_maintenance_enabled" id="predictive_maintenance_enabled" <?php echo ($settings['predictive_maintenance_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
          <label for="predictive_maintenance_enabled">Enable predictive maintenance alerts</label>
        </div>
        <div class="checkbox-group">
          <input type="checkbox" name="auto_create_work_orders" id="auto_create_work_orders" <?php echo ($settings['auto_create_work_orders'] ?? '0') == '1' ? 'checked' : ''; ?>>
          <label for="auto_create_work_orders">Automatically create work orders for critical alerts</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>Maintenance Prediction Window (days)</label>
          <input type="number" min="1" max="90" name="maintenance_prediction_days" value="<?php echo $settings['maintenance_prediction_days'] ?? 14; ?>" required>
          <small>Days ahead to predict maintenance needs</small>
        </div>
      </div>

      <div class="form-footer">
        <button type="submit" class="btn-primary">💾 Save Automation Settings</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <?php if ($isAdmin): ?>
  <div id="data" class="tab-content <?php echo $active_tab === 'data' ? 'active' : ''; ?>">
    <form method="POST">
      <input type="hidden" name="update_data" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="setting-group group-data">
        <h2>🗄️ Data Retention</h2>
        <div class="setting-row">
          <div class="setting-item">
            <label>Sensor Data Retention (days)</label>
            <input type="number" min="7" max="3650" name="data_retention_days" value="<?php echo $settings['data_retention_days'] ?? 90; ?>" required>
            <small>How long to keep sensor readings</small>
          </div>
          <div class="setting-item">
            <label>CCTV Footage Retention (days)</label>
            <input type="number" min="7" max="180" name="footage_retention_days" value="<?php echo $settings['footage_retention_days'] ?? 30; ?>" required>
            <small>How long to keep video recordings</small>
          </div>
        </div>
      </div>

      <div class="setting-group group-data">
        <h2>☁️ Cloud Backup</h2>
        <div class="checkbox-group">
          <input type="checkbox" name="cloud_backup_enabled" id="cloud_backup_enabled" <?php echo ($settings['cloud_backup_enabled'] ?? '1') == '1' ? 'checked' : ''; ?>>
          <label for="cloud_backup_enabled">Enable Firebase cloud backup</label>
        </div>
        <div class="setting-item" style="margin-top: 0.9rem;">
          <label>Backup Frequency</label>
          <select name="backup_frequency" required>
            <option value="hourly" <?php echo ($settings['backup_frequency'] ?? 'daily') === 'hourly' ? 'selected' : ''; ?>>Every Hour</option>
            <option value="daily"  <?php echo ($settings['backup_frequency'] ?? 'daily') === 'daily'  ? 'selected' : ''; ?>>Daily</option>
            <option value="weekly" <?php echo ($settings['backup_frequency'] ?? 'daily') === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
          </select>
          <small>How often to backup data to cloud</small>
        </div>
      </div>

      <div class="setting-group group-data">
        <h2>📤 Export Settings</h2>
        <div class="setting-item">
          <label>Default Export Format</label>
          <select name="export_format_default" required>
            <option value="csv"  <?php echo ($settings['export_format_default'] ?? 'csv') === 'csv'  ? 'selected' : ''; ?>>CSV (Excel Compatible)</option>
            <option value="json" <?php echo ($settings['export_format_default'] ?? 'csv') === 'json' ? 'selected' : ''; ?>>JSON</option>
            <option value="pdf"  <?php echo ($settings['export_format_default'] ?? 'csv') === 'pdf'  ? 'selected' : ''; ?>>PDF Report</option>
          </select>
          <small>Default format for data exports</small>
        </div>
      </div>

      <div class="form-footer">
        <button type="submit" class="btn-primary">💾 Save Data Settings</button>
      </div>
    </form>
  </div>
  <?php endif; ?>

  <div id="security" class="tab-content <?php echo $active_tab === 'security' ? 'active' : ''; ?>">
    <?php
    require_once 'src/Services/TOTPService.php';
    $mfa_stmt = $conn->prepare("SELECT mfa_enabled, mfa_secret, username, full_name, email FROM users WHERE user_id = ?");
    $mfa_stmt->bind_param("i", $_SESSION['user_id']);
    $mfa_stmt->execute();
    $mfa_user = $mfa_stmt->get_result()->fetch_assoc();
    $mfa_stmt->close();
    $mfa_enabled = (bool)$mfa_user['mfa_enabled'];

    // Setup Pairing View
    if (isset($_GET['setup']) && $_GET['setup'] == 1 && isset($_SESSION['mfa_setup_secret'])): 
        $setup_secret = $_SESSION['mfa_setup_secret'];
        $authUri = \ShineGuard\Services\TOTPService::getAuthUri($mfa_user['username'], $setup_secret);
    ?>
    <!-- Pairing Instruction Header -->
    <div class="setting-group group-sec">
        <div style="text-align: center; margin-bottom: 30px;">
            <div style="font-size: 3rem; margin-bottom: 15px;">🛡️</div>
            <h2 style="font-size: 1.5rem; margin-bottom: 10px;">Pair Authenticator App</h2>
            <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto;">Scan the QR code below using Google Authenticator, Authy, or any TOTP-compatible app to link your account.</p>
        </div>

        <div style="display: flex; flex-direction: column; align-items: center; gap: 25px; background: rgba(255, 255, 255, 0.03); border: 1.5px dashed var(--border); padding: 35px; border-radius: 24px; margin-bottom: 30px;">
            <div style="background: white; padding: 15px; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); width: 230px; height: 230px;">
                <div id="qrcode_container"></div>
            </div>
            
            <script>
            var QRCode;!function(){function a(a){this.mode=c.MODE_8BIT_BYTE,this.data=a,this.parsedData=[];for(var b=[],d=0,e=this.data.length;e>d;d++){var f=this.data.charCodeAt(d);f>65536?(b[0]=240|(1835008&f)>>>18,b[1]=128|(258048&f)>>>12,b[2]=128|(4032&f)>>>6,b[3]=128|63&f):f>2048?(b[0]=224|(61440&f)>>>12,b[1]=128|(4032&f)>>>6,b[2]=128|63&f):f>128?(b[0]=192|(1984&f)>>>6,b[1]=128|63&f):b[0]=f,this.parsedData=this.parsedData.concat(b)}this.parsedData.length!=this.data.length&&(this.parsedData.unshift(191),this.parsedData.unshift(187),this.parsedData.unshift(239))}function b(a,b){this.typeNumber=a,this.errorCorrectLevel=b,this.modules=null,this.moduleCount=0,this.dataCache=null,this.dataList=[]}function i(a,b){if(void 0==a.length)throw new Error(a.length+"/"+b);for(var c=0;c<a.length&&0==a[c];)c++;this.num=new Array(a.length-c+b);for(var d=0;d<a.length-c;d++)this.num[d]=a[d+c]}function j(a,b){this.totalCount=a,this.dataCount=b}function k(){this.buffer=[],this.length=0}function m(){return"undefined"!=typeof CanvasRenderingContext2D}function n(){var a=!1,b=navigator.userAgent;return/android/i.test(b)&&(a=!0,aMat=b.toString().match(/android ([0-9]\.[0-9])/i),aMat&&aMat[1]&&(a=parseFloat(aMat[1]))),a}function r(a,b){for(var c=1,e=s(a),f=0,g=l.length;g>=f;f++){var h=0;switch(b){case d.L:h=l[f][0];break;case d.M:h=l[f][1];break;case d.Q:h=l[f][2];break;case d.H:h=l[f][3]}if(h>=e)break;c++}if(c>l.length)throw new Error("Too long data");return c}function s(a){var b=encodeURI(a).toString().replace(/\%[0-9a-fA-F]{2}/g,"a");return b.length+(b.length!=a?3:0)}a.prototype={getLength:function(){return this.parsedData.length},write:function(a){for(var b=0,c=this.parsedData.length;c>b;b++)a.put(this.parsedData[b],8)}},b.prototype={addData:function(b){var c=new a(b);this.dataList.push(c),this.dataCache=null},isDark:function(a,b){if(0>a||this.moduleCount<=a||0>b||this.moduleCount<=b)throw new Error(a+","+b);return this.modules[a][b]},getModuleCount:function(){return this.moduleCount},make:function(){this.makeImpl(!1,this.getBestMaskPattern())},makeImpl:function(a,c){this.moduleCount=4*this.typeNumber+17,this.modules=new Array(this.moduleCount);for(var d=0;d<this.moduleCount;d++){this.modules[d]=new Array(this.moduleCount);for(var e=0;e<this.moduleCount;e++)this.modules[d][e]=null}this.setupPositionProbePattern(0,0),this.setupPositionProbePattern(this.moduleCount-7,0),this.setupPositionProbePattern(0,this.moduleCount-7),this.setupPositionAdjustPattern(),this.setupTimingPattern(),this.setupTypeInfo(a,c),this.typeNumber>=7&&this.setupTypeNumber(a),null==this.dataCache&&(this.dataCache=b.createData(this.typeNumber,this.errorCorrectLevel,this.dataList)),this.mapData(this.dataCache,c)},setupPositionProbePattern:function(a,b){for(var c=-1;7>=c;c++)if(!(-1>=a+c||this.moduleCount<=a+c))for(var d=-1;7>=d;d++)-1>=b+d||this.moduleCount<=b+d||(this.modules[a+c][b+d]=c>=0&&6>=c&&(0==d||6==d)||d>=0&&6>=d&&(0==c||6==c)||c>=2&&4>=c&&d>=2&&4>=d?!0:!1)},getBestMaskPattern:function(){for(var a=0,b=0,c=0;8>c;c++){this.makeImpl(!0,c);var d=f.getLostPoint(this);(0==c||a>d)&&(a=d,b=c)}return b},createMovieClip:function(a,b,c){var d=a.createEmptyMovieClip(b,c),e=1;this.make();for(var f=0;f<this.modules.length;f++)for(var g=f*e,h=0;h<this.modules[f].length;h++){var i=h*e,j=this.modules[f][h];j&&(d.beginFill(0,100),d.moveTo(i,g),d.lineTo(i+e,g),d.lineTo(i+e,g+e),d.lineTo(i,g+e),d.endFill())}return d},setupTimingPattern:function(){for(var a=8;a<this.moduleCount-8;a++)null==this.modules[a][6]&&(this.modules[a][6]=0==a%2);for(var b=8;b<this.moduleCount-8;b++)null==this.modules[6][b]&&(this.modules[6][b]=0==b%2)},setupPositionAdjustPattern:function(){for(var a=f.getPatternPosition(this.typeNumber),b=0;b<a.length;b++)for(var c=0;c<a.length;c++){var d=a[b],e=a[c];if(null==this.modules[d][e])for(var g=-2;2>=g;g++)for(var h=-2;2>=h;h++)this.modules[d+g][e+h]=-2==g||2==g||-2==h||2==h||0==g&&0==h?!0:!1}},setupTypeNumber:function(a){for(var b=f.getBCHTypeNumber(this.typeNumber),c=0;18>c;c++){var d=!a&&1==(1&b>>c);this.modules[Math.floor(c/3)][c%3+this.moduleCount-8-3]=d}for(var c=0;18>c;c++){var d=!a&&1==(1&b>>c);this.modules[c%3+this.moduleCount-8-3][Math.floor(c/3)]=d}},setupTypeInfo:function(a,b){for(var c=this.errorCorrectLevel<<3|b,d=f.getBCHTypeInfo(c),e=0;15>e;e++){var g=!a&&1==(1&d>>e);6>e?this.modules[e][8]=g:8>e?this.modules[e+1][8]=g:this.modules[this.moduleCount-15+e][8]=g}for(var e=0;15>e;e++){var g=!a&&1==(1&d>>e);8>e?this.modules[8][this.moduleCount-e-1]=g:9>e?this.modules[8][15-e-1+1]=g:this.modules[8][15-e-1]=g}this.modules[this.moduleCount-8][8]=!a},mapData:function(a,b){for(var c=-1,d=this.moduleCount-1,e=7,g=0,h=this.moduleCount-1;h>0;h-=2)for(6==h&&h--;;){for(var i=0;2>i;i++)if(null==this.modules[d][h-i]){var j=!1;g<a.length&&(j=1==(1&a[g]>>>e));var k=f.getMask(b,d,h-i);k&&(j=!j),this.modules[d][h-i]=j,e--,-1==e&&(g++,e=7)}if(d+=c,0>d||this.moduleCount<=d){d-=c,c=-c;break}}}},b.PAD0=236,b.PAD1=17,b.createData=function(a,c,d){for(var e=j.getRSBlocks(a,c),g=new k,h=0;h<d.length;h++){var i=d[h];g.put(i.mode,4),g.put(i.getLength(),f.getLengthInBits(i.mode,a)),i.write(g)}for(var l=0,h=0;h<e.length;h++)l+=e[h].dataCount;if(g.getLengthInBits()>8*l)throw new Error("code length overflow. ("+g.getLengthInBits()+">"+8*l+")");for(g.getLengthInBits()+4<=8*l&&g.put(0,4);0!=g.getLengthInBits()%8;)g.putBit(!1);for(;;){if(g.getLengthInBits()>=8*l)break;if(g.put(b.PAD0,8),g.getLengthInBits()>=8*l)break;g.put(b.PAD1,8)}return b.createBytes(g,e)},b.createBytes=function(a,b){for(var c=0,d=0,e=0,g=new Array(b.length),h=new Array(b.length),j=0;j<b.length;j++){var k=b[j].dataCount,l=b[j].totalCount-k;d=Math.max(d,k),e=Math.max(e,l),g[j]=new Array(k);for(var m=0;m<g[j].length;m++)g[j][m]=255&a.buffer[m+c];c+=k;var n=f.getErrorCorrectPolynomial(l),o=new i(g[j],n.getLength()-1),p=o.mod(n);h[j]=new Array(n.getLength()-1);for(var m=0;m<h[j].length;m++){var q=m+p.getLength()-h[j].length;h[j][m]=q>=0?p.get(q):0}}for(var r=0,m=0;m<b.length;m++)r+=b[m].totalCount;for(var s=new Array(r),t=0,m=0;d>m;m++)for(var j=0;j<b.length;j++)m<g[j].length&&(s[t++]=g[j][m]);for(var m=0;e>m;m++)for(var j=0;j<b.length;j++)m<h[j].length&&(s[t++]=h[j][m]);return s};for(var c={MODE_NUMBER:1,MODE_ALPHA_NUM:2,MODE_8BIT_BYTE:4,MODE_KANJI:8},d={L:1,M:0,Q:3,H:2},e={PATTERN000:0,PATTERN001:1,PATTERN010:2,PATTERN011:3,PATTERN100:4,PATTERN101:5,PATTERN110:6,PATTERN111:7},f={PATTERN_POSITION_TABLE:[[],[6,18],[6,22],[6,26],[6,30],[6,34],[6,22,38],[6,24,42],[6,26,46],[6,28,50],[6,30,54],[6,32,58],[6,34,62],[6,26,46,66],[6,26,48,70],[6,26,50,74],[6,30,54,78],[6,30,56,82],[6,30,58,86],[6,34,62,90],[6,28,50,72,94],[6,26,50,74,98],[6,30,54,78,102],[6,28,54,80,106],[6,32,58,84,110],[6,30,58,86,114],[6,34,62,90,118],[6,26,50,74,98,122],[6,30,54,78,102,126],[6,26,52,78,104,130],[6,30,56,82,108,134],[6,34,60,86,112,138],[6,30,58,86,114,142],[6,34,62,90,118,146],[6,30,54,78,102,126,150],[6,24,50,76,102,128,154],[6,28,54,80,106,132,158],[6,32,58,84,110,136,162],[6,26,54,82,110,138,166],[6,30,58,86,114,142,170]],G15:1335,G18:7973,G15_MASK:21522,getBCHTypeInfo:function(a){for(var b=a<<10;f.getBCHDigit(b)-f.getBCHDigit(f.G15)>=0;)b^=f.G15<<f.getBCHDigit(b)-f.getBCHDigit(f.G15);return(a<<10|b)^f.G15_MASK},getBCHTypeNumber:function(a){for(var b=a<<12;f.getBCHDigit(b)-f.getBCHDigit(f.G18)>=0;)b^=f.G18<<f.getBCHDigit(b)-f.getBCHDigit(f.G18);return a<<12|b},getBCHDigit:function(a){for(var b=0;0!=a;)b++,a>>>=1;return b},getPatternPosition:function(a){return f.PATTERN_POSITION_TABLE[a-1]},getMask:function(a,b,c){switch(a){case e.PATTERN000:return 0==(b+c)%2;case e.PATTERN001:return 0==b%2;case e.PATTERN010:return 0==c%3;case e.PATTERN011:return 0==(b+c)%3;case e.PATTERN100:return 0==(Math.floor(b/2)+Math.floor(c/3))%2;case e.PATTERN101:return 0==b*c%2+b*c%3;case e.PATTERN110:return 0==(b*c%2+b*c%3)%2;case e.PATTERN111:return 0==(b*c%3+(b+c)%2)%2;default:throw new Error("bad maskPattern:"+a)}},getErrorCorrectPolynomial:function(a){for(var b=new i([1],0),c=0;a>c;c++)b=b.multiply(new i([1,g.gexp(c)],0));return b},getLengthInBits:function(a,b){if(b>=1&&10>b)switch(a){case c.MODE_NUMBER:return 10;case c.MODE_ALPHA_NUM:return 9;case c.MODE_8BIT_BYTE:return 8;case c.MODE_KANJI:return 8;default:throw new Error("mode:"+a)}else if(27>b)switch(a){case c.MODE_NUMBER:return 12;case c.MODE_ALPHA_NUM:return 11;case c.MODE_8BIT_BYTE:return 16;case c.MODE_KANJI:return 10;default:throw new Error("mode:"+a)}else{if(!(41>b))throw new Error("type:"+b);switch(a){case c.MODE_NUMBER:return 14;case c.MODE_ALPHA_NUM:return 13;case c.MODE_8BIT_BYTE:return 16;case c.MODE_KANJI:return 12;default:throw new Error("mode:"+a)}}},getLostPoint:function(a){for(var b=a.getModuleCount(),c=0,d=0;b>d;d++)for(var e=0;b>e;e++){for(var f=0,g=a.isDark(d,e),h=-1;1>=h;h++)if(!(0>d+h||d+h>=b))for(var i=-1;1>=i;i++)0>e+i||e+i>=b||(0!=h||0!=i)&&g==a.isDark(d+h,e+i)&&f++;f>5&&(c+=3+f-5)}for(var d=0;b-1>d;d++)for(var e=0;b-1>e;e++){var j=0;a.isDark(d,e)&&j++,a.isDark(d+1,e)&&j++,a.isDark(d,e+1)&&j++,a.isDark(d+1,e+1)&&j++,(0==j||4==j)&&(c+=3)}for(var d=0;b>d;d++)for(var e=0;b-6>e;e++)a.isDark(d,e)&&!a.isDark(d,e+1)&&a.isDark(d,e+2)&&a.isDark(d,e+3)&&a.isDark(d,e+4)&&!a.isDark(d,e+5)&&a.isDark(d,e+6)&&(c+=40);for(var e=0;b>e;e++)for(var d=0;b-6>d;d++)a.isDark(d,e)&&!a.isDark(d+1,e)&&a.isDark(d+2,e)&&a.isDark(d+3,e)&&a.isDark(d+4,e)&&!a.isDark(d+5,e)&&a.isDark(d+6,e)&&(c+=40);for(var k=0,e=0;b>e;e++)for(var d=0;b>d;d++)a.isDark(d,e)&&k++;var l=Math.abs(100*k/b/b-50)/5;return c+=10*l}},g={glog:function(a){if(1>a)throw new Error("glog("+a+")");return g.LOG_TABLE[a]},gexp:function(a){for(;0>a;)a+=255;for(;a>=256;)a-=255;return g.EXP_TABLE[a]},EXP_TABLE:new Array(256),LOG_TABLE:new Array(256)},h=0;8>h;h++)g.EXP_TABLE[h]=1<<h;for(var h=8;256>h;h++)g.EXP_TABLE[h]=g.EXP_TABLE[h-4]^g.EXP_TABLE[h-5]^g.EXP_TABLE[h-6]^g.EXP_TABLE[h-8];for(var h=0;255>h;h++)g.LOG_TABLE[g.EXP_TABLE[h]]=h;i.prototype={get:function(a){return this.num[a]},getLength:function(){return this.num.length},multiply:function(a){for(var b=new Array(this.getLength()+a.getLength()-1),c=0;c<this.getLength();c++)for(var d=0;d<a.getLength();d++)b[c+d]^=g.gexp(g.glog(this.get(c))+g.glog(a.get(d)));return new i(b,0)},mod:function(a){if(this.getLength()-a.getLength()<0)return this;for(var b=g.glog(this.get(0))-g.glog(a.get(0)),c=new Array(this.getLength()),d=0;d<this.getLength();d++)c[d]=this.get(d);for(var d=0;d<a.getLength();d++)c[d]^=g.gexp(g.glog(a.get(d))+b);return new i(c,0).mod(a)}},j.RS_BLOCK_TABLE=[[1,26,19],[1,26,16],[1,26,13],[1,26,9],[1,44,34],[1,44,28],[1,44,22],[1,44,16],[1,70,55],[1,70,44],[2,35,17],[2,35,13],[1,100,80],[2,50,32],[2,50,24],[4,25,9],[1,134,108],[2,67,43],[2,33,15,2,34,16],[2,33,11,2,34,12],[2,86,68],[4,43,27],[4,43,19],[4,43,15],[2,98,78],[4,49,31],[2,32,14,4,33,15],[4,39,13,1,40,14],[2,121,97],[2,60,38,2,61,39],[4,40,18,2,41,19],[4,40,14,2,41,15],[2,146,116],[3,58,36,2,59,37],[4,36,16,4,37,17],[4,36,12,4,37,13],[2,86,68,2,87,69],[4,69,43,1,70,44],[6,43,19,2,44,20],[6,43,15,2,44,16],[4,101,81],[1,80,50,4,81,51],[4,50,22,4,51,23],[3,36,12,8,37,13],[2,116,92,2,117,93],[6,58,36,2,59,37],[4,46,20,6,47,21],[7,42,14,4,43,15],[4,133,107],[8,59,37,1,60,38],[8,44,20,4,45,21],[12,33,11,4,34,12],[3,145,115,1,146,116],[4,64,40,5,65,41],[11,36,16,5,37,17],[11,36,12,5,37,13],[5,109,87,1,110,88],[5,65,41,5,66,42],[5,54,24,7,55,25],[11,36,12],[5,122,98,1,123,99],[7,73,45,3,74,46],[15,43,19,2,44,20],[3,45,15,13,46,16],[1,135,107,5,136,108],[10,74,46,1,75,47],[1,50,22,15,51,23],[2,42,14,17,43,15],[5,150,120,1,151,121],[9,69,43,4,70,44],[17,50,22,1,51,23],[2,42,14,19,43,15],[3,141,113,4,142,114],[3,70,44,11,71,45],[17,47,21,4,48,22],[9,39,13,16,40,14],[3,135,107,5,136,108],[3,67,41,13,68,42],[15,54,24,5,55,25],[15,43,15,10,44,16],[4,144,116,4,145,117],[17,68,42],[17,50,22,6,51,23],[19,46,16,6,47,17],[2,139,111,7,140,112],[17,74,46],[7,54,24,16,55,25],[34,37,13],[4,151,121,5,152,122],[4,75,47,14,76,48],[11,54,24,14,55,25],[16,45,15,14,46,16],[6,147,117,4,148,118],[6,73,45,14,74,46],[11,54,24,16,55,25],[30,46,16,2,47,17],[8,132,106,4,133,107],[8,75,47,13,76,48],[7,54,24,22,55,25],[22,45,15,13,46,16],[10,142,114,2,143,115],[19,74,46,4,75,47],[28,50,22,6,51,23],[33,46,16,4,47,17],[8,152,122,4,153,123],[22,73,45,3,74,46],[8,53,23,26,54,24],[12,45,15,28,46,16],[3,147,117,10,148,118],[3,73,45,23,74,46],[4,54,24,31,55,25],[11,45,15,31,46,16],[7,146,116,7,147,117],[21,73,45,7,74,46],[1,53,23,37,54,24],[19,45,15,26,46,16],[5,145,115,10,146,116],[19,75,47,10,76,48],[15,54,24,25,55,25],[23,45,15,25,46,16],[13,145,115,3,146,116],[2,74,46,29,75,47],[42,54,24,1,55,25],[23,45,15,28,46,16],[17,145,115],[10,74,46,23,75,47],[10,54,24,35,55,25],[19,45,15,35,46,16],[17,145,115,1,146,116],[14,74,46,21,75,47],[29,54,24,19,55,25],[11,45,15,46,46,16],[13,145,115,6,146,116],[14,74,46,23,75,47],[44,54,24,7,55,25],[59,46,16,1,47,17],[12,151,121,7,152,122],[12,75,47,26,76,48],[39,54,24,14,55,25],[22,45,15,41,46,16],[6,151,121,14,152,122],[6,75,47,34,76,48],[46,54,24,10,55,25],[2,45,15,64,46,16],[17,152,122,4,153,123],[29,74,46,14,75,47],[49,54,24,10,55,25],[24,45,15,46,46,16],[4,152,122,18,153,123],[13,74,46,32,75,47],[48,54,24,14,55,25],[42,45,15,32,46,16],[20,147,117,4,148,118],[40,75,47,7,76,48],[43,54,24,22,55,25],[10,45,15,67,46,16],[19,148,118,6,149,119],[18,75,47,31,76,48],[34,54,24,34,55,25],[20,45,15,61,46,16]],j.getRSBlocks=function(a,b){var c=j.getRsBlockTable(a,b);if(void 0==c)throw new Error("bad rs block @ typeNumber:"+a+"/errorCorrectLevel:"+b);for(var d=c.length/3,e=[],f=0;d>f;f++)for(var g=c[3*f+0],h=c[3*f+1],i=c[3*f+2],k=0;g>k;k++)e.push(new j(h,i));return e},j.getRsBlockTable=function(a,b){switch(b){case d.L:return j.RS_BLOCK_TABLE[4*(a-1)+0];case d.M:return j.RS_BLOCK_TABLE[4*(a-1)+1];case d.Q:return j.RS_BLOCK_TABLE[4*(a-1)+2];case d.H:return j.RS_BLOCK_TABLE[4*(a-1)+3];default:return void 0}},k.prototype={get:function(a){var b=Math.floor(a/8);return 1==(1&this.buffer[b]>>>7-a%8)},put:function(a,b){for(var c=0;b>c;c++)this.putBit(1==(1&a>>>b-c-1))},getLengthInBits:function(){return this.length},putBit:function(a){var b=Math.floor(this.length/8);this.buffer.length<=b&&this.buffer.push(0),a&&(this.buffer[b]|=128>>>this.length%8),this.length++}};var l=[[17,14,11,7],[32,26,20,14],[53,42,32,24],[78,62,46,34],[106,84,60,44],[134,106,74,58],[154,122,86,64],[192,152,108,84],[230,180,130,98],[271,213,151,119],[321,251,177,137],[367,287,203,155],[425,331,241,177],[458,362,258,194],[520,412,292,220],[586,450,322,250],[644,504,364,280],[718,560,394,310],[792,624,442,338],[858,666,482,382],[929,711,509,403],[1003,779,565,439],[1091,857,611,461],[1171,911,661,511],[1273,997,715,535],[1367,1059,751,593],[1465,1125,805,625],[1528,1190,868,658],[1628,1264,908,698],[1732,1370,982,742],[1840,1452,1030,790],[1952,1538,1112,842],[2068,1628,1168,898],[2188,1722,1228,958],[2303,1809,1283,983],[2431,1911,1351,1051],[2563,1989,1423,1093],[2699,2099,1499,1139],[2809,2213,1579,1219],[2953,2331,1663,1273]],o=function(){var a=function(a,b){this._el=a,this._htOption=b};return a.prototype.draw=function(a){function g(a,b){var c=document.createElementNS("http://www.w3.org/2000/svg",a);for(var d in b)b.hasOwnProperty(d)&&c.setAttribute(d,b[d]);return c}var b=this._htOption,c=this._el,d=a.getModuleCount();Math.floor(b.width/d),Math.floor(b.height/d),this.clear();var h=g("svg",{viewBox:"0 0 "+String(d)+" "+String(d),width:"100%",height:"100%",fill:b.colorLight});h.setAttributeNS("http://www.w3.org/2000/xmlns/","xmlns:xlink","http://www.w3.org/1999/xlink"),c.appendChild(h),h.appendChild(g("rect",{fill:b.colorDark,width:"1",height:"1",id:"template"}));for(var i=0;d>i;i++)for(var j=0;d>j;j++)if(a.isDark(i,j)){var k=g("use",{x:String(i),y:String(j)});k.setAttributeNS("http://www.w3.org/1999/xlink","href","#template"),h.appendChild(k)}},a.prototype.clear=function(){for(;this._el.hasChildNodes();)this._el.removeChild(this._el.lastChild)},a}(),p="svg"===document.documentElement.tagName.toLowerCase(),q=p?o:m()?function(){function a(){this._elImage.src=this._elCanvas.toDataURL("image/png"),this._elImage.style.display="block",this._elCanvas.style.display="none"}function d(a,b){var c=this;if(c._fFail=b,c._fSuccess=a,null===c._bSupportDataURI){var d=document.createElement("img"),e=function(){c._bSupportDataURI=!1,c._fFail&&_fFail.call(c)},f=function(){c._bSupportDataURI=!0,c._fSuccess&&c._fSuccess.call(c)};return d.onabort=e,d.onerror=e,d.onload=f,d.src="data:image/gif;base64,iVBORw0KGgoAAAANSUhEUgAAAAUAAAAFCAYAAACNbyblAAAAHElEQVQI12P4//8/w38GIAXDIBKE0DHxgljNBAAO9TXL0Y4OHwAAAABJRU5ErkJggg==",void 0}c._bSupportDataURI===!0&&c._fSuccess?c._fSuccess.call(c):c._bSupportDataURI===!1&&c._fFail&&c._fFail.call(c)}if(this._android&&this._android<=2.1){var b=1/window.devicePixelRatio,c=CanvasRenderingContext2D.prototype.drawImage;CanvasRenderingContext2D.prototype.drawImage=function(a,d,e,f,g,h,i,j){if("nodeName"in a&&/img/i.test(a.nodeName))for(var l=arguments.length-1;l>=1;l--)arguments[l]=arguments[l]*b;else"undefined"==typeof j&&(arguments[1]*=b,arguments[2]*=b,arguments[3]*=b,arguments[4]*=b);c.apply(this,arguments)}}var e=function(a,b){this._bIsPainted=!1,this._android=n(),this._htOption=b,this._elCanvas=document.createElement("canvas"),this._elCanvas.width=b.width,this._elCanvas.height=b.height,a.appendChild(this._elCanvas),this._el=a,this._oContext=this._elCanvas.getContext("2d"),this._bIsPainted=!1,this._elImage=document.createElement("img"),this._elImage.style.display="none",this._el.appendChild(this._elImage),this._bSupportDataURI=null};return e.prototype.draw=function(a){var b=this._elImage,c=this._oContext,d=this._htOption,e=a.getModuleCount(),f=d.width/e,g=d.height/e,h=Math.round(f),i=Math.round(g);b.style.display="none",this.clear();for(var j=0;e>j;j++)for(var k=0;e>k;k++){var l=a.isDark(j,k),m=k*f,n=j*g;c.strokeStyle=l?d.colorDark:d.colorLight,c.lineWidth=1,c.fillStyle=l?d.colorDark:d.colorLight,c.fillRect(m,n,f,g),c.strokeRect(Math.floor(m)+.5,Math.floor(n)+.5,h,i),c.strokeRect(Math.ceil(m)-.5,Math.ceil(n)-.5,h,i)}this._bIsPainted=!0},e.prototype.makeImage=function(){this._bIsPainted&&d.call(this,a)},e.prototype.isPainted=function(){return this._bIsPainted},e.prototype.clear=function(){this._oContext.clearRect(0,0,this._elCanvas.width,this._elCanvas.height),this._bIsPainted=!1},e.prototype.round=function(a){return a?Math.floor(1e3*a)/1e3:a},e}():function(){var a=function(a,b){this._el=a,this._htOption=b};return a.prototype.draw=function(a){for(var b=this._htOption,c=this._el,d=a.getModuleCount(),e=Math.floor(b.width/d),f=Math.floor(b.height/d),g=['<table style="border:0;border-collapse:collapse;">'],h=0;d>h;h++){g.push("<tr>");for(var i=0;d>i;i++)g.push('<td style="border:0;border-collapse:collapse;padding:0;margin:0;width:'+e+"px;height:"+f+"px;background-color:"+(a.isDark(h,i)?b.colorDark:b.colorLight)+';"></td>');g.push("</tr>")}g.push("</table>"),c.innerHTML=g.join("");var j=c.childNodes[0],k=(b.width-j.offsetWidth)/2,l=(b.height-j.offsetHeight)/2;k>0&&l>0&&(j.style.margin=l+"px "+k+"px")},a.prototype.clear=function(){this._el.innerHTML=""},a}();QRCode=function(a,b){if(this._htOption={width:256,height:256,typeNumber:4,colorDark:"#000000",colorLight:"#ffffff",correctLevel:d.H},"string"==typeof b&&(b={text:b}),b)for(var c in b)this._htOption[c]=b[c];"string"==typeof a&&(a=document.getElementById(a)),this._android=n(),this._el=a,this._oQRCode=null,this._oDrawing=new q(this._el,this._htOption),this._htOption.text&&this.makeCode(this._htOption.text)},QRCode.prototype.makeCode=function(a){this._oQRCode=new b(r(a,this._htOption.correctLevel),this._htOption.correctLevel),this._oQRCode.addData(a),this._oQRCode.make(),this._el.title=a,this._oDrawing.draw(this._oQRCode),this.makeImage()},QRCode.prototype.makeImage=function(){"function"==typeof this._oDrawing.makeImage&&(!this._android||this._android>=3)&&this._oDrawing.makeImage()},QRCode.prototype.clear=function(){this._oDrawing.clear()},QRCode.CorrectLevel=d}();

            function renderQRCode() {
                const container = document.getElementById("qrcode_container");
                if (container && typeof QRCode !== 'undefined') {
                    container.innerHTML = ""; // Clear
                    new QRCode(container, {
                        text: "<?php echo $authUri; ?>",
                        width: 200,
                        height: 200,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    });
                }
            }

            // Attempt immediate render, and fallback to DOMContentLoaded
            if (document.readyState === "complete" || document.readyState === "interactive") {
                renderQRCode();
            } else {
                document.addEventListener("DOMContentLoaded", renderQRCode);
            }
            </script>
            
            <div style="text-align: center;">
                <p style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.1em; color: var(--text-muted); margin-bottom: 8px;">Manual Secret Key</p>
                <code style="background: var(--surface-2); padding: 8px 16px; border-radius: 8px; font-size: 1.1rem; letter-spacing: 0.1em; font-weight: 700; color: var(--blue); border: 1px solid var(--border);"><?php echo $setup_secret; ?></code>
            </div>
        </div>

        <form id="mfa_setup_confirm_form" method="POST" style="max-width: 400px; margin: 0 auto; text-align: center;">
            <input type="hidden" name="confirm_mfa_setup" value="1">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            
            <div class="setting-item" style="margin-bottom: 25px;">
                <label style="justify-content: center; margin-bottom: 12px;">Enter 6-Digit Verification Code</label>
                <input type="text" name="mfa_verify_code" placeholder="000 000" maxlength="6" 
                       style="text-align: center; font-size: 1.75rem; letter-spacing: 0.4em; font-weight: 800; border-color: var(--blue);" required autofocus>
                <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_mfa_code'): ?>
                    <p style="color: var(--red); font-size: 0.85rem; font-weight: 600; margin-top: 10px;">⚠️ Invalid code. Please check your app and try again.</p>
                <?php endif; ?>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <button type="button" class="btn" style="background: var(--surface-2);" onclick="window.location.href='settings.php?tab=security'">Cancel</button>
                <button type="submit" class="btn-primary">Verify & Activate</button>
            </div>
        </form>
    </div>

    <?php else: ?>
    <form id="mfa_management_form" method="POST">
      <input type="hidden" name="update_mfa" value="1">
      <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

      <div class="setting-group group-sec">
        <h2>🛡️ Multi-Factor Verification (MFA)</h2>
        <p class="users-subtext" style="margin-bottom: 25px;">Secure your account by requiring a dynamically generated 6-digit code during login.</p>
        
        <?php if (!$mfa_enabled): ?>
            <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); padding: 20px; border-radius: 16px; display: flex; align-items: flex-start; gap: 15px; margin-bottom: 25px;">
                <div style="font-size: 24px;">📱</div>
                <div>
                    <h3 style="font-size: 1rem; color: var(--text-primary); margin-bottom: 5px;">MFA is Currently Disabled</h3>
                    <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">When enabled, you will need to map a Google Authenticator app to you account. Upon your next login, you will be prompted for a 6-digit code.</p>
                </div>
            </div>
            
            <input type="hidden" name="mfa_action" value="enable">
            <button type="submit" class="btn-primary" style="background: var(--blue);">🔐 Setup Authenticator</button>

        <?php else: ?>
            <div style="background: rgba(16, 185, 129, 0.05); border: 1px solid rgba(16, 185, 129, 0.2); padding: 20px; border-radius: 16px; display: flex; flex-direction: column; gap: 15px; margin-bottom: 25px;">
                <div style="display: flex; align-items: flex-start; gap: 15px;">
                    <div style="font-size: 24px;">✅</div>
                    <div>
                        <h3 style="font-size: 1rem; color: #10b981; margin-bottom: 5px;">MFA is Active & Secured</h3>
                        <p style="font-size: 0.85rem; color: var(--text-secondary); line-height: 1.5;">Your account is currently protected by Google Authenticator. The pairing QR code has been permanently hidden for your security.</p>
                    </div>
                </div>
            </div>

            <input type="hidden" name="mfa_action" value="disable">
            
            <div style="background: var(--surface-2); padding: 25px; border-radius: 16px; border: 1px solid var(--border); margin-bottom: 25px; max-width: 450px;">
                <h4 style="font-size: 0.9rem; color: var(--red); margin-bottom: 15px;">🔒 Identity Verification Required</h4>
                
                <div class="setting-item" style="margin-bottom: 15px;">
                    <label>Current Account Password</label>
                    <input type="password" name="mfa_password" placeholder="Verify your password" required>
                </div>
                
                <div class="setting-item">
                    <label>Authenticator 6-Digit Code</label>
                    <input type="text" name="mfa_totp_code" placeholder="000 000" maxlength="6" style="text-align: center; font-size: 1.25rem; letter-spacing: 0.2em; font-weight: 700;" required>
                </div>
            </div>

            <?php 
            $user_role = getUserRole();
            $isRestricted = ($user_role === 'System Observer' || $user_role === 'Maintenance Operator');
            ?>

            <?php if ($isRestricted): ?>
                <div style="background: rgba(59, 130, 246, 0.05); border: 1px solid rgba(59, 130, 246, 0.2); border-radius: 12px; padding: 15px; display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 20px;">🛡️</div>
                    <div style="font-size: 0.85rem; color: var(--blue); font-weight: 500;">
                        MFA Protection is mandatory for the <strong><?php echo $user_role; ?></strong> role. Contact a System Admin to request a security override.
                    </div>
                </div>
            <?php else: ?>
                <button type="button" class="btn-primary" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);" 
                        onclick="showAppConfirm('WARNING: Are you sure you want to disable Multi-Factor Verification? This severely reduces your account security.', () => document.getElementById('mfa_management_form').submit(), 'error', 'Security Warning')">🔓 Verify & Disable MFA</button>
            <?php endif; ?>
        <?php endif; ?>
      </div>
    </form>
    <?php endif; ?>
  </div>

  <?php if (getUserRole() === 'System Admin'): ?>
  <div id="users" class="tab-content <?php echo $active_tab === 'users' ? 'active' : ''; ?>">
    <div class="setting-group group-users">
      <h2>👥 User Accounts</h2>
      <p class="users-subtext">Manage system users and access levels</p>

      <?php
      $users_query  = "SELECT * FROM users ORDER BY created_at DESC";
      $users_result = $conn->query($users_query);
      ?>

      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Username</th>
              <th>Full Name</th>
              <th>Email</th>
              <th>Role</th>
              <th>Status</th>
              <th>MFA</th>
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $users_result->fetch_assoc()): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
              <td><?php echo htmlspecialchars(maskPII($user['full_name'])); ?></td>
              <td><?php echo htmlspecialchars(maskPII($user['email'], 'email')); ?></td>
              <td><span class="badge ok"><?php echo htmlspecialchars($user['role']); ?></span></td>
              <td><span class="badge <?php echo $user['is_active'] ? 'ok' : 'fail'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
              <td><?php echo $user['mfa_enabled'] ? '<span title="MFA Protection Active">🛡️</span>' : '<span style="opacity:0.3">🔓</span>'; ?></td>
              <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
              <td>
                <div style="display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; width: max-content;">
                  <button class="btn-secondary" style="font-size: 0.76rem; padding: 0.4rem 0.8rem; height: 32px; margin: 0;" 
                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">✏️ Edit</button>
                  <?php if ($user['mfa_enabled']): ?>
                  <button class="btn-secondary" style="font-size: 0.76rem; padding: 0.4rem 0.8rem; height: 32px; margin: 0; background: rgba(59, 130, 246, 0.05); color: #3b82f6; border-color: rgba(59, 130, 246, 0.2);" 
                    onclick="promptMfaReset('<?php echo htmlspecialchars(addslashes($user['username'])); ?>', <?php echo $user['user_id']; ?>)">🛡️ Reset MFA</button>
                  <?php endif; ?>
                  <button class="btn-secondary" style="font-size: 0.76rem; padding: 0.4rem 0.8rem; height: 32px; margin: 0; background: #fee2e2; color: #991b1b; border-color: #fca5a5;" 
                    onclick="openDeleteModal(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')">🗑️ Delete</button>
                </div>
              </td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>

      <div class="users-footer">
        <button class="btn-primary" onclick="openModal('addUserModal')">➕ Add New User</button>
      </div>
    </div>
  </div>
  <?php endif; ?>

  <div id="addUserModal" class="modal">
    <div class="modal-content modal-spring">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 1.25rem;">👤 Add New System User</h2>
        <button type="button" class="btn-sm" onclick="closeModal('addUserModal')" style="border: none; background: none; font-size: 1.2rem; cursor: pointer;">✕</button>
      </div>
      <form id="addUserForm" method="POST">
        <input type="hidden" name="add_user" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        
        <div id="addUserError" class="danger-box" style="display:none; margin-bottom: 20px;"></div>
        <div class="modal-body-content">
          <div class="setting-row">
            <div class="setting-item">
              <label>Username <small class="text-muted" style="font-weight:400;">(letters, numbers, _ · 3–30 chars)</small></label>
              <input type="text" id="new_user_username" name="username" placeholder="e.g. jdoe"
                     pattern="[a-zA-Z0-9_]{3,30}" minlength="3" maxlength="30" required
                     autocomplete="username">
            </div>
            <div class="setting-item">
              <label>Full Name</label>
              <input type="text" name="full_name" placeholder="e.g. John Doe" required autocomplete="name">
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-item">
              <label>Email Address <small class="text-muted" style="font-weight:400;">(Must be @hulo.gov.ph)</small></label>
              <input type="email" id="new_user_email" name="email" placeholder="e.g. jdoe@hulo.gov.ph" required autocomplete="email">
            </div>
            <div class="setting-item">
              <label>Phone (Optional)</label>
              <input type="tel" name="phone" placeholder="+63 XXX XXX XXXX" autocomplete="tel">
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-item">
              <label>User Role</label>
              <select name="role" required>
                <option value="System Admin">System Admin</option>
                <option value="Maintenance Operator">Maintenance Operator</option>
                <option value="System Observer">System Observer</option>
              </select>
            </div>
            <div class="setting-item">
              <label>Initial Password <small class="text-muted" style="font-weight:400;">(Upper, Number, Symbol)</small></label>
              <input type="password" id="new_user_password" name="password" placeholder="Min 8 chars, strong" required autocomplete="new-password">
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-item" style="grid-column: 1 / -1;">
              <label>Confirm Password</label>
              <input type="password" id="new_user_confirm_password" name="confirm_password" placeholder="Re-enter password" required autocomplete="new-password">
            </div>
          </div>
          <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 20px;">
            <button type="button" class="btn" onclick="closeModal('addUserModal')">Cancel</button>
            <button type="submit" class="btn primary">✔ Create User Account</button>
          </div>
        </div>
      </form>
    </div>
  </div>

</main>
</div>

  <div id="editUserModal" class="modal">
    <div class="modal-content modal-spring">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 1.25rem;">✏️ Edit System User</h2>
        <button type="button" class="btn-sm" onclick="closeModal('editUserModal')" style="border: none; background: none; font-size: 1.2rem; cursor: pointer;">✕</button>
      </div>
      <form id="editUserForm" method="POST">
        <input type="hidden" name="update_user" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="target_user_id" id="edit_user_id">
        <div class="modal-body-content">
          <div class="setting-row">
            <div class="setting-item">
              <label>Username (Read-only)</label>
              <input type="text" id="edit_username" readonly style="background: var(--surface-2); opacity: 0.7;">
            </div>
            <div class="setting-item">
              <label>Full Name</label>
              <input type="text" name="full_name" id="edit_full_name" required>
            </div>
          </div>
          <div class="setting-row">
            <div class="setting-item">
              <label>Email Address</label>
              <input type="email" name="email" id="edit_email" required>
            </div>
            <div class="setting-item">
              <label>Phone</label>
              <input type="tel" name="phone" id="edit_phone">
            </div>
          </div>
          <div class="setting-row">
             <div class="setting-item">
              <label>User Role</label>
              <select name="role" id="edit_role" required>
                <option value="System Admin">System Admin</option>
                <option value="Maintenance Operator">Maintenance Operator</option>
                <option value="System Observer">System Observer</option>
              </select>
            </div>
            <div class="setting-item">
               <label>Account Status</label>
               <div class="checkbox-group" style="margin-bottom: 0;">
                 <input type="checkbox" name="is_active" id="edit_is_active">
                 <label for="edit_is_active">User is active</label>
               </div>
            </div>
          </div>
          <div id="mfaResetSection" style="display:none; margin-top: 1.5rem; padding: 1.5rem; background: rgba(59, 130, 246, 0.05); border: 1px dashed rgba(59, 130, 246, 0.3); border-radius: 12px;">
              <h3 style="font-size: 0.9rem; color: #3b82f6; margin-bottom: 8px;">🔐 Multi-Factor Authentication</h3>
              <p style="font-size: 0.8rem; color: var(--text-secondary); margin-bottom: 15px;">User currently has MFA enabled. If they lost their device, you can forcefully detach it here.</p>
              <button type="button" class="btn" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);" onclick="promptMfaReset(document.getElementById('edit_username').value, document.getElementById('edit_user_id').value)">Force Disable MFA Protection</button>
          </div>
          <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 20px;">
            <button type="button" class="btn" onclick="closeModal('editUserModal')">Cancel</button>
            <button type="submit" class="btn primary">Save Changes</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <div id="deleteUserModal" class="modal">
    <div class="modal-content modal-spring" style="max-width: 400px;">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 1.25rem;" class="text-danger">🗑️ Delete System User</h2>
        <button type="button" class="btn-sm" onclick="closeModal('deleteUserModal')" style="border: none; background: none; font-size: 1.2rem; cursor: pointer;">✕</button>
      </div>
      <form id="deleteUserForm" method="POST">
        <input type="hidden" name="delete_user" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="target_user_id" id="delete_user_id">
        <div class="modal-body-content" style="padding: 0 10px;">
          
          <p style="font-size: 0.95rem; color: var(--text); margin-bottom: 15px; line-height: 1.5;">
            You are about to permanently delete <strong><span id="delete_user_name_display"></span></strong> from the system. This action cannot be undone.
          </p>
          
          <div class="danger-box">
            <strong>Security verification required:</strong><br>
            Please enter your own Admin password to authorize this deletion.
          </div>

          <div class="setting-row" style="grid-template-columns: 1fr; gap: 0;">
            <div class="setting-item">
              <label>Your Admin Password</label>
              <input type="password" name="admin_password" placeholder="Enter your password" required autocomplete="current-password">
            </div>
          </div>
          
          <div style="display: flex; gap: 12px; justify-content: flex-end; margin-top: 2rem; border-top: 1px solid var(--border); padding-top: 20px;">
            <button type="button" class="btn" onclick="closeModal('deleteUserModal')">Cancel</button>
            <button type="submit" class="btn danger">Permanently Delete</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- Administrative MFA Reset Modal -->
  <div id="mfa_reset_modal" class="modal">
    <div class="modal-content modal-spring" style="max-width: 420px;">
      <div class="modal-header" style="border-bottom: 1px solid var(--border); margin-bottom: 20px; padding-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h2 style="margin: 0; font-size: 1.25rem; color: #3b82f6;">🛡️ Administrative MFA Reset</h2>
        <button type="button" class="btn-sm" onclick="closeModal('mfa_reset_modal')" style="border: none; background: none; font-size: 1.2rem; cursor: pointer;">✕</button>
      </div>
      <form method="POST">
        <input type="hidden" name="admin_reset_mfa" value="1">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
        <input type="hidden" name="target_user_id" id="target_user_id">
        <div class="modal-body-content" style="padding: 0 10px;">
          
          <div class="info-box" style="background: rgba(59, 130, 246, 0.05); color: #3b82f6; border: 1px dashed rgba(59, 130, 246, 0.3); margin-bottom: 20px; padding: 15px; border-radius: 8px; font-size: 0.85rem;">
            <strong>Double-Lock Verification:</strong><br>
            For your security, resetting another user's MFA requires your own Administrator credentials.
          </div>

          <div class="setting-item" style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem;">Your Admin Password</label>
            <input type="password" name="admin_password" placeholder="Verify your password" required style="width: 100%; padding: 10px; border-radius: 8px; border: 1px solid var(--border);">
          </div>

          <div class="setting-item" style="margin-bottom: 20px;">
            <label style="display: block; font-weight: 600; margin-bottom: 8px; font-size: 0.85rem;">Your Authenticator Code</label>
            <input type="text" name="admin_totp_code" placeholder="000 000" maxlength="6" style="width: 100%; text-align: center; font-size: 1.2rem; letter-spacing: 0.2em; font-weight: 700; padding: 10px; border-radius: 8px; border: 1px solid var(--border);" required>
          </div>
          
          <div style="display: flex; gap: 12px; justify-content: flex-end; border-top: 1px solid var(--border); padding-top: 20px;">
            <button type="button" class="btn" onclick="closeModal('mfa_reset_modal')" style="padding: 10px 20px; border-radius: 8px; border: 1px solid var(--border); background: var(--surface-2);">Cancel</button>
            <button type="submit" class="btn primary" style="background: #3b82f6; color: white; padding: 10px 24px; border-radius: 8px; border: none; font-weight: 600;">Confirm Reset</button>
          </div>
        </div>
      </form>
    </div>
  </div>

<script>

function showUserError(msg) {
  const el = document.getElementById('addUserError');
  if (!el) return;
  el.textContent = msg;
  el.style.display = 'block';
  el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}
function clearUserError() {
  const el = document.getElementById('addUserError');
  if (el) { el.textContent = ''; el.style.display = 'none'; }
}

document.getElementById('addUserForm').addEventListener('submit', function(e) {
  clearUserError();
  const username   = document.getElementById('new_user_username').value.trim();
  const email      = document.getElementById('new_user_email').value.trim();
  const pwd        = document.getElementById('new_user_password').value;
  const confirmPwd = document.getElementById('new_user_confirm_password').value;

  if (!/^[a-zA-Z0-9_]{3,30}$/.test(username)) {
    e.preventDefault();
    showUserError('⚠️ Username must be 3–30 characters: letters, numbers, and underscores only.');
    return;
  }

  if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
    e.preventDefault();
    showUserError('⚠️ Please enter a valid email address.');
    return;
  }
  if (!email.toLowerCase().endsWith('@hulo.gov.ph')) {
    e.preventDefault();
    showUserError('⚠️ Email must belong to the official @hulo.gov.ph domain.');
    return;
  }

  const pwdRegex = /^(?=.*[A-Z])(?=.*\d)(?=.*[^a-zA-Z\d]).{8,}$/;
  if (!pwdRegex.test(pwd)) {
    e.preventDefault();
    showUserError('⚠️ Password must be at least 8 characters, with 1 uppercase letter, 1 number, and 1 symbol.');
    return;
  }

  if (pwd !== confirmPwd) {
    e.preventDefault();
    showUserError('⚠️ Passwords do not match. Please re-enter.');
    return;
  }
});

['new_user_username','new_user_email','new_user_password','new_user_confirm_password'].forEach(function(id) {
  const el = document.getElementById(id);
  if (el) el.addEventListener('input', clearUserError);
});

function openEditModal(user) {
  document.getElementById('edit_user_id').value = user.user_id;
  document.getElementById('edit_username').value = user.username;
  document.getElementById('edit_full_name').value = user.full_name;
  document.getElementById('edit_email').value = user.email;
  document.getElementById('edit_phone').value = user.phone || '';
  document.getElementById('edit_role').value = user.role;
  document.getElementById('edit_is_active').checked = user.is_active == 1;
  
  // Update MFA reset section visibility
  const mfaSection = document.getElementById('mfaResetSection');
  if (user.mfa_enabled == 1) {
    mfaSection.style.display = 'block';
  } else {
    mfaSection.style.display = 'none';
  }
  
  openModal('editUserModal');
}

function promptMfaReset(username, userId) {
    showAppConfirm(
        `Are you sure you want to FORCE DISABLE MFA for user: ${username}?\n\nThis will allow them to login with just their password.`,
        function() {
            document.getElementById('target_user_id').value = userId;
            openModal('mfa_reset_modal');
        },
        'warning',
        'Administrative Override'
    );
}

// Wrapper for table-direct resets
function promptMfaResetDirect(userId, username) {
    promptMfaReset(username, userId);
}

function openDeleteModal(userId, fullName) {
  document.getElementById('delete_user_id').value = userId;
  document.getElementById('delete_user_name_display').textContent = fullName;
  openModal('deleteUserModal');
}

function switchTab(tabName, el) {
  document.querySelectorAll('.tab-content').forEach(t => t.classList.remove('active'));
  document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
  document.getElementById(tabName).classList.add('active');
  if (el) el.classList.add('active');
  const url = new URL(window.location);
  url.searchParams.set('tab', tabName);
  window.history.pushState({}, '', url);
}

function openModal(id) {
  const m = document.getElementById(id);
  if (!m) return;
  m.classList.add('open');
  const mc = m.querySelector('.modal-content');
  if (mc) { mc.classList.remove('modal-spring'); void mc.offsetWidth; mc.classList.add('modal-spring'); }
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  clearUserError();
}

const colorInput   = document.querySelector('input[name="theme_color"]');
const colorPreview = document.querySelector('.color-preview');
if (colorInput && colorPreview) {
  colorInput.addEventListener('input', e => colorPreview.style.background = e.target.value);
}

const allUsers = <?php 
    $users_result->data_seek(0);
    $users_arr = [];
    while($u = $users_result->fetch_assoc()) {
        unset($u['password_hash']); // Security: don't expose hashes to frontend JS
        $users_arr[] = $u;
    }
    echo json_encode($users_arr); 
?>;

document.addEventListener('DOMContentLoaded', () => {
    const urlParams = new URLSearchParams(window.location.search);
    const tab = urlParams.get('tab');
    const userId = urlParams.get('id');
    
    if (tab === 'users' && userId) {
        const user = allUsers.find(u => u.user_id == userId);
        if (user) {
            setTimeout(() => {
                openEditModal(user);
            }, 500);
        }
    }
});
</script>
<?php include 'assets/app_alert.php'; ?>
</body>
</html>