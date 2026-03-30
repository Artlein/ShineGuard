<?php
require_once 'dbconnect.php';
requireLogin('System Admin');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_preferences'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=preferences&error=invalid_csrf');
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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=thresholds&error=invalid_csrf');
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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=alerts&error=invalid_csrf');
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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=automation&error=invalid_csrf');
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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=data&error=invalid_csrf');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    
    if (getUserRole() !== 'System Admin') {
        // Redundant role check removed - handled by requireLogin at top
        exit();
    }

    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=users&error=invalid_csrf');

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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=users&error=invalid_csrf');

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
    verifyCsrfToken($_POST['csrf_token'] ?? '', 'settings.php?tab=users&error=invalid_csrf');

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

$active_tab = $_GET['tab'] ?? 'preferences';
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
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Inter', sans-serif;
}

:root {
  --bg:             var(--bg);
  --surface:        var(--panel);
  --surface-2:      var(--muted);
  --border:         var(--border);
  --border-light:   var(--border);
  --text-primary:   var(--text);
  --text-secondary: var(--dim);
  --text-muted:     #a0aec0;
  --blue:           #3b82f6;
  --blue-dim:       rgba(59, 130, 246, 0.1);
  --blue-border:    rgba(59, 130, 246, 0.2);
  --green:          #22c55e;
  --green-dim:      rgba(34, 197, 94, 0.1);
  --green-border:   rgba(34, 197, 94, 0.2);
  --red:            #ef4444;
  --red-dim:        rgba(239, 68, 68, 0.1);
  --red-border:     rgba(239, 68, 68, 0.2);
  --amber:          #f59e0b;
  --amber-dim:      rgba(245, 158, 11, 0.1);
  --amber-border:   rgba(245, 158, 11, 0.2);
  --purple:         #8b5cf6;
  --purple-dim:     rgba(139, 92, 246, 0.1);
  --radius:         16px;
  --radius-sm:      10px;
  --shadow:         var(--shadow);
  --shadow-md:      0 8px 30px var(--shadow);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}

.main-content {
  padding: 2.2rem 2.6rem;
}

.page-header {
  text-align: center;
  margin-bottom: 2rem;
}

.page-header h1 {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--text-primary);
  text-transform: uppercase;
  margin-bottom: 0.3rem;
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
  gap: 8px;
  background: var(--surface-2);
  padding: 8px;
  border-radius: var(--radius);
  border: 1px solid var(--border);
  margin-bottom: 2rem;
  overflow-x: auto;
  scrollbar-width: none;
}

.tabs::-webkit-scrollbar { display: none; }

.tab {
  padding: 0.8rem 1.4rem;
  background: transparent;
  border: none;
  cursor: pointer;
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  color: var(--text-secondary);
  transition: all .2s cubic-bezier(0.4, 0, 0.2, 1);
  white-space: nowrap;
  border-radius: var(--radius-sm);
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.tab:hover {
  color: var(--text-primary);
  background: rgba(255, 255, 255, 0.5);
}

.tab.active {
  color: var(--blue);
  background: var(--surface);
  box-shadow: var(--shadow);
}

.tab-content { display: none; }
.tab-content.active { display: block; animation: fadeIn 0.3s ease; }

@keyframes fadeIn {
  from { opacity: 0; transform: translateY(5px); }
  to { opacity: 1; transform: translateY(0); }
}

.setting-group {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 2rem 2.2rem;
  margin-bottom: 1.6rem;
  position: relative;
  overflow: hidden;
  transition: box-shadow 0.2s;
}

.setting-group:hover {
  box-shadow: var(--shadow-md);
}

.setting-group::before {
  content: '';
  position: absolute;
  top: 0; left: 0; right: 0;
  height: 4px;
}

.setting-group.group-org::before      { background: var(--blue); }
.setting-group.group-brand::before    { background: var(--purple); }
.setting-group.group-contact::before  { background: var(--green); }
.setting-group.group-map::before      { background: var(--amber); }
.setting-group.group-threshold::before{ background: var(--red); }
.setting-group.group-alerts::before   { background: var(--amber); }
.setting-group.group-auto::before     { background: var(--blue); }
.setting-group.group-data::before     { background: var(--green); }
.setting-group.group-users::before    { background: var(--purple); }

.setting-group h2 {
  font-size: 1.05rem;
  font-weight: 800;
  color: var(--text-primary);
  margin-bottom: 1.6rem;
  display: flex;
  align-items: center;
  gap: 0.5rem;
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
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--text-secondary);
  display: flex;
  align-items: center;
  gap: 0.4rem;
}

.setting-item input[type="text"],
.setting-item input[type="password"],
.setting-item input[type="email"],
.setting-item input[type="tel"],
.setting-item input[type="number"],
.setting-item input[type="color"],
.setting-item select,
.setting-item textarea {
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-family: 'Inter', sans-serif;
  font-size: 0.875rem;
  font-weight: 500;
  padding: 0.65rem 0.9rem;
  outline: none;
  transition: border-color .15s, box-shadow .15s, background .15s;
  width: 100%;
}

.setting-item textarea {
  resize: vertical;
  min-height: 80px;
}

.setting-item input:focus,
.setting-item select:focus,
.setting-item textarea:focus {
  border-color: var(--blue);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
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
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 0.75rem 1.8rem;
  cursor: pointer;
  transition: all .15s;
  box-shadow: 0 4px 6px -1px rgba(34, 197, 94, 0.4);
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
}

.btn-primary:hover {
  background: #16a34a;
  transform: translateY(-1px);
  box-shadow: 0 6px 8px -1px rgba(34, 197, 94, 0.5);
}

.btn-secondary {
  background: var(--surface);
  color: var(--text-secondary);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 600;
  padding: 0.75rem 1.5rem;
  cursor: pointer;
  transition: all .15s;
  display: inline-flex;
  align-items: center;
  gap: 0.4rem;
  box-shadow: var(--shadow);
}

.btn-secondary:hover {
  background: var(--surface-2);
  border-color: var(--border-light);
  color: var(--text-primary);
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
  margin-top: 1rem;
}

table {
  width: 100%;
  border-collapse: collapse;
  font-size: 0.875rem;
}

thead tr {
  background: var(--surface-2);
  border-bottom: 1px solid var(--border);
}

thead th {
  font-size: 0.71rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-secondary);
  padding: 0.8rem 1.1rem;
  text-align: left;
  white-space: nowrap;
}

tbody tr {
  border-bottom: 1px solid var(--border-light);
  transition: background .1s;
}

tbody tr:last-child { border-bottom: none; }
tbody tr:hover { background: #f8fafc; }

tbody td {
  padding: 0.85rem 1.1rem;
  color: var(--text-primary);
  font-weight: 500;
  vertical-align: middle;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.3rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 0.26rem 0.65rem;
  border-radius: 30px;
  white-space: nowrap;
}

.badge.ok {
  background: var(--green-dim);
  color: var(--green);
  border: 1px solid var(--green-border);
}

.badge.ok::before { content: '●'; font-size: 0.5rem; }

.badge.fail {
  background: var(--red-dim);
  color: var(--red);
  border: 1px solid var(--red-border);
}

.badge.fail::before { content: '●'; font-size: 0.5rem; }

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
    <button class="tab <?php echo $active_tab === 'preferences' ? 'active' : ''; ?>" onclick="switchTab('preferences', this)">🎨 System Preferences</button>
    <button class="tab <?php echo $active_tab === 'thresholds'  ? 'active' : ''; ?>" onclick="switchTab('thresholds', this)">📊 Predictive Thresholds</button>
    <button class="tab <?php echo $active_tab === 'alerts'      ? 'active' : ''; ?>" onclick="switchTab('alerts', this)">🚨 Alerts & Notifications</button>
    <button class="tab <?php echo $active_tab === 'automation'  ? 'active' : ''; ?>" onclick="switchTab('automation', this)">🤖 Automation</button>
    <button class="tab <?php echo $active_tab === 'data'        ? 'active' : ''; ?>" onclick="switchTab('data', this)">💾 Data & Backup</button>
    <?php if (getUserRole() === 'System Admin'): ?>
    <button class="tab <?php echo $active_tab === 'users'       ? 'active' : ''; ?>" onclick="switchTab('users', this)">👥 User Management</button>
    <?php endif; ?>
  </div>

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
              <th>Last Login</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($user = $users_result->fetch_assoc()): ?>
            <tr>
              <td><strong><?php echo htmlspecialchars($user['username']); ?></strong></td>
              <td><?php echo htmlspecialchars($user['full_name']); ?></td>
              <td><?php echo htmlspecialchars($user['email']); ?></td>
              <td><span class="badge ok"><?php echo htmlspecialchars($user['role']); ?></span></td>
              <td><span class="badge <?php echo $user['is_active'] ? 'ok' : 'fail'; ?>"><?php echo $user['is_active'] ? 'Active' : 'Inactive'; ?></span></td>
              <td><?php echo $user['last_login'] ? date('M d, Y H:i', strtotime($user['last_login'])) : 'Never'; ?></td>
              <td>
                <div style="display: flex; flex-wrap: nowrap; align-items: center; gap: 8px; width: max-content;">
                  <button class="btn-secondary" style="font-size: 0.76rem; padding: 0.4rem 0.8rem; height: 32px; margin: 0;" 
                    onclick="openEditModal(<?php echo htmlspecialchars(json_encode($user)); ?>)">✏️ Edit</button>
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
  openModal('editUserModal');
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

(function() {
  const params = new URLSearchParams(window.location.search);
  
  const errorMap = {
    'missing_fields': 'Please fill in all required fields.',
    'invalid_username': 'Username must be 3–30 chars (letters, numbers, underscores).',
    'invalid_email': 'Please enter a valid email address.',
    'weak_password': 'Password must be at least 8 characters long.',
    'password_mismatch': 'The passwords provided do not match.',
    'invalid_role': 'The selected role is not valid.',
    'invalid_csrf': 'Security token mismatch. Please refresh and try again.',
    'duplicate_username': 'This username is already in use by another account.',
    'duplicate_email': 'This email address is already registered.',
    'duplicate_entry': 'A user with this information already exists.',
    'db_error': 'There was a problem processing your request.',
    'self_delete': 'Security Error: You cannot delete your own active Admin account.',
    'invalid_admin_password': 'Authorization Failed: The Admin password you entered was incorrect.',
    'self_deactivate': 'Security Error: You cannot deactivate your own account.',
    'self_demote': 'Security Error: You cannot change your own role away from System Admin.'
  };

  const successMap = {
    'user_deleted': 'User successfully deleted.',
    'user_added': 'New user account has been created successfully.',
    'user_updated': 'User account details have been updated successfully.'
  };

  if (params.get('tab') === 'users') {
    const error = params.get('error');
    const success = params.get('success');

    if (error && errorMap[error]) {
      showToast(errorMap[error], 'error');
    } else if (success && successMap[success]) {
      showToast(successMap[success], 'success');
    }
  }
})();

function showToast(message, type = 'success') {
  let container = document.getElementById('toast-container');
  if (!container) {
    container = document.createElement('div');
    container.id = 'toast-container';
    document.body.appendChild(container);
  }

  const toast = document.createElement('div');
  toast.className = `toast ${type}`;
  
  const iconHtml = type === 'success' ? '✅' : '❌';
  const titleHtml = type === 'success' ? 'Success' : 'Action Failed';

  toast.innerHTML = `
    <div class="toast-icon">${iconHtml}</div>
    <div class="toast-content">
      <div class="toast-title">${titleHtml}</div>
      <div class="toast-msg">${message}</div>
    </div>
  `;

  container.appendChild(toast);
  
  // Trigger animation
  requestAnimationFrame(() => {
    toast.classList.add('show');
  });

  // Auto remove
  setTimeout(() => {
    toast.classList.remove('show');
    toast.classList.add('removing');
    setTimeout(() => toast.remove(), 400); // Wait for exit animation
  }, 4000);
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
</body>
</html>