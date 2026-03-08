<?php
require_once 'dbconnect.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'verify_password') {
    ob_clean();
    header('Content-Type: application/json');
    $admin_password = $_POST['admin_password'] ?? '';
    
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT password_hash FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user_data = $stmt->get_result()->fetch_assoc();
    
    if ($user_data && password_verify($admin_password, $user_data['password_hash'])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    $preset_name = sanitize($_POST['preset_name']);
    $time_on = $_POST['time_on'];
    $time_off = $_POST['time_off'];
    $dimming_level = intval($_POST['dimming_level']);
    $days_of_week = implode(',', $_POST['days'] ?? []);
    
    $stmt = $conn->prepare("INSERT INTO schedule_presets (preset_name, time_on, time_off, dimming_level, days_of_week, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisi", $preset_name, $time_on, $time_off, $dimming_level, $days_of_week, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Schedule Created', "Created schedule: $preset_name");
        header('Location: schedule.php?success=schedule_created');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);
    $preset_name = sanitize($_POST['preset_name']);
    $time_on = $_POST['time_on'];
    $time_off = $_POST['time_off'];
    $dimming_level = intval($_POST['dimming_level']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $days_of_week = implode(',', $_POST['days'] ?? []);
    
    $stmt = $conn->prepare("UPDATE schedule_presets SET preset_name=?, time_on=?, time_off=?, dimming_level=?, days_of_week=?, is_active=? WHERE schedule_id=?");
    $stmt->bind_param("sssisii", $preset_name, $time_on, $time_off, $dimming_level, $days_of_week, $is_active, $schedule_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Schedule Updated', "Updated schedule: $preset_name");
        header('Location: schedule.php?success=edit');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    $schedule_id = intval($_POST['schedule_id']);

    $stmt = $conn->prepare("SELECT preset_name FROM schedule_presets WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $schedule = $result->fetch_assoc();
    $preset_name = $schedule['preset_name'];
    $stmt->close();
    
    $stmt = $conn->prepare("DELETE FROM schedule_presets WHERE schedule_id = ?");
    $stmt->bind_param("i", $schedule_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Schedule Deleted', "Deleted schedule: $preset_name");
        header('Location: schedule.php?success=delete');
        exit();
    }
}

$schedules = $conn->query("SELECT * FROM schedule_presets ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8"/>
<title>Schedules - Shine Guard Hulo</title>
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
  --bg:           #f0f4f8;
  --surface:      #ffffff;
  --surface-2:    #f7f9fc;
  --border:       #e4e9f0;
  --border-light: #edf1f7;
  --text-primary: #1a2035;
  --text-secondary: #6b7a99;
  --text-muted:   #a0aec0;
  --accent:       #e53e3e;
  --green:        #22c55e;
  --green-dim:    #f0fdf4;
  --green-border: #bbf7d0;
  --red:          #ef4444;
  --red-dim:      #fef2f2;
  --red-border:   #fecaca;
  --blue:         #3b82f6;
  --blue-dim:     #eff6ff;
  --radius:       16px;
  --radius-sm:    10px;
  --shadow:       0 1px 3px rgba(0,0,0,.07), 0 1px 2px rgba(0,0,0,.05);
  --shadow-md:    0 4px 16px rgba(0,0,0,.08), 0 1px 4px rgba(0,0,0,.04);
}

* { box-sizing: border-box; margin: 0; padding: 0; }

body {
  background: var(--bg);
  font-family: 'Inter', sans-serif;
  color: var(--text-primary);
}

.main-content {
  padding: 2.2rem 2.6rem;
  font-family: 'Inter', sans-serif;
}

.page-header {
  text-align: center;
  margin-bottom: 2.4rem;
  padding-bottom: 0;
}

.page-header h1 {
  font-size: 1.85rem;
  font-weight: 800;
  letter-spacing: -0.03em;
  color: var(--text-primary);
  margin-bottom: 0.3rem;
  text-transform: uppercase;
}

.page-header p {
  font-size: 0.875rem;
  color: var(--text-secondary);
  font-weight: 400;
}

.panel {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--radius);
  box-shadow: var(--shadow);
  padding: 1.8rem 2rem;
  margin-bottom: 1.6rem;
  position: relative;
  overflow: hidden;
}

.panel.panel-create { border-top: 3px solid #3b82f6; }
.panel.panel-list   { border-top: 3px solid #22c55e; }

.panel h2 {
  font-size: 1rem;
  font-weight: 700;
  color: var(--text-primary);
  margin-bottom: 1.5rem;
  display: flex;
  align-items: center;
  gap: 0.45rem;
}

.form-grid-2 {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1.2rem 1.6rem;
  margin-bottom: 1.4rem;
}

.form-group {
  display: flex;
  flex-direction: column;
  gap: 0.4rem;
}

.form-group label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-secondary);
  letter-spacing: 0.02em;
}

.form-group input[type="text"],
.form-group input[type="number"],
.form-group input[type="time"] {
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: var(--radius-sm);
  color: var(--text-primary);
  font-family: 'Inter', sans-serif;
  font-size: 0.9rem;
  font-weight: 500;
  padding: 0.6rem 0.9rem;
  transition: border-color .15s, box-shadow .15s;
  outline: none;
  width: 100%;
}

.form-group input:focus {
  border-color: var(--blue);
  background: #fff;
  box-shadow: 0 0 0 3px rgba(59,130,246,.12);
}

.form-group input::placeholder {
  color: var(--text-muted);
  font-weight: 400;
}

.form-days-label {
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-secondary);
  margin-bottom: 0.6rem;
}

.days-grid {
  display: flex;
  gap: 0.5rem;
  flex-wrap: wrap;
  margin-bottom: 1.5rem;
}

.day-chip {
  display: flex;
  align-items: center;
  gap: 0.4rem;
  background: var(--surface-2);
  border: 1.5px solid var(--border);
  border-radius: 8px;
  padding: 0.38rem 0.8rem;
  cursor: pointer;
  font-size: 0.76rem;
  font-weight: 600;
  color: var(--text-secondary);
  transition: all .15s;
  user-select: none;
}

.day-chip:has(input:checked) {
  background: var(--blue-dim);
  border-color: var(--blue);
  color: var(--blue);
}

.day-chip input[type="checkbox"] {
  accent-color: var(--blue);
  width: 13px; height: 13px;
  cursor: pointer;
}

.btn-primary {
  background: var(--text-primary);
  color: #fff;
  border: none;
  border-radius: var(--radius-sm);
  font-family: 'Inter', sans-serif;
  font-size: 0.85rem;
  font-weight: 700;
  letter-spacing: 0.01em;
  padding: 0.65rem 1.6rem;
  cursor: pointer;
  transition: background .15s, transform .1s, box-shadow .15s;
  box-shadow: var(--shadow);
}

.btn-primary:hover {
  background: #2d3748;
  transform: translateY(-1px);
  box-shadow: var(--shadow-md);
}

.table-wrapper {
  overflow-x: auto;
  border-radius: var(--radius-sm);
  border: 1px solid var(--border);
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
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.06em;
  text-transform: uppercase;
  color: var(--text-secondary);
  padding: 0.85rem 1.1rem;
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
  padding: 0.9rem 1.1rem;
  color: var(--text-primary);
  font-weight: 500;
  vertical-align: middle;
}

.badge {
  display: inline-flex;
  align-items: center;
  gap: 0.35rem;
  font-size: 0.7rem;
  font-weight: 700;
  letter-spacing: 0.04em;
  text-transform: uppercase;
  padding: 0.28rem 0.7rem;
  border-radius: 30px;
  white-space: nowrap;
}

.badge.ok {
  background: var(--green-dim);
  color: var(--green);
  border: 1px solid var(--green-border);
}

.badge.ok::before  { content: '●'; font-size: 0.5rem; }

.badge.fail {
  background: var(--red-dim);
  color: var(--red);
  border: 1px solid var(--red-border);
}

.badge.fail::before { content: '●'; font-size: 0.5rem; }

.actions-cell { display: flex; gap: 0.5rem; }

.btn-sm {
  background: var(--surface-2);
  border: 1px solid var(--border);
  border-radius: 7px;
  color: var(--text-secondary);
  font-family: 'Inter', sans-serif;
  font-size: 0.76rem;
  font-weight: 600;
  padding: 0.32rem 0.72rem;
  cursor: pointer;
  transition: all .13s;
  white-space: nowrap;
}

.btn-sm:hover {
  background: #edf2f7;
  border-color: #cbd5e0;
  color: var(--text-primary);
}

.btn-sm.danger:hover {
  background: var(--red-dim);
  border-color: var(--red-border);
  color: var(--red);
}
</style>
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>
<main class="main-content">

  <div class="page-header">
    <br>
    <h1>⏰ Schedule Presets</h1>
    <p>Automate streetlight ON/OFF times</p>
  </div>

  <div class="panel panel-create">
    <h2>➕ Create New Schedule</h2>
    <form id="createScheduleForm" method="POST" onsubmit="event.preventDefault(); openCreateModal();">

      <div class="form-grid-2">
        <div class="form-group">
          <label>Preset Name</label>
          <input type="text" name="preset_name" required placeholder="e.g., Night Mode">
        </div>
        <div class="form-group">
          <label>Dimming Level (%)</label>
          <input type="number" name="dimming_level" value="70" min="0" max="100" required>
        </div>
        <div class="form-group">
          <label>Turn ON Time</label>
          <input type="time" name="time_on" value="18:00" required>
        </div>
        <div class="form-group">
          <label>Turn OFF Time</label>
          <input type="time" name="time_off" value="06:00" required>
        </div>
      </div>

      <div class="form-days-label">Days of Week</div>
      <div class="days-grid">
        <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
        <label class="day-chip">
          <input type="checkbox" name="days[]" value="<?php echo $day; ?>" checked>
          <?php echo $day; ?>
        </label>
        <?php endforeach; ?>
      </div>

      <button type="submit" class="btn-primary" style="background: #10b981; color: white; border: none; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4); transition: all 0.2s;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)'; this.style.boxShadow='0 6px 8px -1px rgba(16, 185, 129, 0.5)';" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 6px -1px rgba(16, 185, 129, 0.4)';">💾 Create Schedule</button>
      
      <input type="hidden" name="create_schedule" value="1">
    </form>
  </div>

  <div class="panel panel-list">
    <h2>📋 Existing Schedules</h2>
    <div class="table-wrapper">
      <table>
        <thead>
          <tr>
            <th>Schedule Name</th>
            <th>ON Time</th>
            <th>OFF Time</th>
            <th>Dimming</th>
            <th>Days</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php while($schedule = $schedules->fetch_assoc()): ?>
          <tr>
            <td><?php echo htmlspecialchars($schedule['preset_name']); ?></td>
            <td><?php echo date('h:i A', strtotime($schedule['time_on'])); ?></td>
            <td><?php echo date('h:i A', strtotime($schedule['time_off'])); ?></td>
            <td><?php echo $schedule['dimming_level']; ?>%</td>
            <td><?php echo htmlspecialchars($schedule['days_of_week']); ?></td>
            <td>
              <span class="badge <?php echo $schedule['is_active'] ? 'ok' : 'fail'; ?>">
                <?php echo $schedule['is_active'] ? 'Active' : 'Inactive'; ?>
              </span>
            </td>
            <td>
              <div class="actions-cell">
                <button type="button" class="btn-sm" style="background: #10b981; color: white; border: none;" onclick='openEditModal(<?php echo json_encode($schedule); ?>)'>✏️ Edit</button>
                <button type="button" class="btn-sm danger" style="background: #ef4444; color: white; border: none;" onclick="openDeleteModal(<?php echo $schedule['schedule_id']; ?>, '<?php echo addslashes($schedule['preset_name']); ?>')">🗑️ Delete</button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</main>
</div>

<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:#fef2f2; width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">🗑️</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:#0f172a;">Delete Schedule?</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="deleteModalScheduleName">Loading...</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:24px;">
      Are you sure you want to delete this schedule? This action cannot be undone and will stop any automated lighting for this preset.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="deleteAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="deleteAdminPassword" placeholder="Enter password to confirm deletion" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="deletePasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeDeleteModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <form id="deleteScheduleForm" method="POST" style="margin:0;" onsubmit="event.preventDefault(); confirmDelete();">
        <input type="hidden" name="schedule_id" id="delete_schedule_id">
        <input type="hidden" name="delete_schedule" value="1">
        <button type="submit" id="deleteConfirmBtn" style="padding:10px 22px; border-radius:10px; border:none; background:#ef4444; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.35); transition:all 0.2s;" onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)';">🗑️ Delete Now</button>
      </form>
    </div>
  </div>
</div>

<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 2000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 16px; padding: 32px; width: 100%; max-width: 500px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);">
        <h2 style="margin-top: 0; margin-bottom: 24px; color: #0f172a; font-size: 1.5rem; display: flex; align-items: center; gap: 8px;">
            <span>✏️</span> Edit Schedule
        </h2>
        <form method="POST">
            <input type="hidden" name="schedule_id" id="edit_schedule_id">
            
            <div class="form-grid-2">
                <div class="form-group" style="grid-column: span 2;">
                  <label>Preset Name</label>
                  <input type="text" name="preset_name" id="edit_preset_name" required>
                </div>
                <div class="form-group" style="grid-column: span 2;">
                  <label>Dimming Level (%)</label>
                  <input type="number" name="dimming_level" id="edit_dimming_level" min="0" max="100" required>
                </div>
                <div class="form-group">
                  <label>Turn ON Time</label>
                  <input type="time" name="time_on" id="edit_time_on" required>
                </div>
                <div class="form-group">
                  <label>Turn OFF Time</label>
                  <input type="time" name="time_off" id="edit_time_off" required>
                </div>
            </div>

            <div class="form-days-label" style="margin-top: 15px;">Days of Week</div>
            <div class="days-grid" id="edit_days_grid">
                
            </div>
            
            <div class="form-group" style="margin-top: 15px; margin-bottom: 24px; flex-direction: row; align-items: center; gap: 8px;">
                <input type="checkbox" name="is_active" id="edit_is_active" value="1" style="width: auto;">
                <label for="edit_is_active" style="margin: 0; cursor: pointer; font-size: 0.9rem;">Schedule Active</label>
            </div>

            <div style="margin-bottom: 24px; text-align: left;">
                <label for="editAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="editAdminPassword" placeholder="Enter password to confirm changes" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
                <div id="editPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
            </div>

            <div style="display: flex; gap: 12px; justify-content: flex-end;">
                <button type="button" onclick="closeEditModal()" class="btn-sm" style="padding: 10px 20px; font-size: 0.85rem;">Cancel</button>
                <input type="hidden" name="edit_schedule" value="1">
                <button type="button" id="editConfirmBtn" onclick="confirmEdit()" class="btn-primary">💾 Save Changes</button>
            </div>
        </form>
    </div>
</div>

<div id="createModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.5); z-index:9999; align-items:center; justify-content:center;">
  <div class="modal-spring" style="background:white; border-radius:20px; padding:32px; max-width:400px; width:90%; box-shadow:0 20px 60px rgba(0,0,0,0.25); font-family:'Inter',sans-serif;">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:#f0fdf4; width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">➕</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:#0f172a;">Create Schedule?</div>
        <div style="font-size:0.8rem; color:#64748b; margin-top:2px;" id="createModalTitle">New Preset</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:#475569; line-height:1.6; margin-bottom:24px;">
      You are about to create a new automated schedule. Please provide your administrator password to confirm.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="createAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:#0f172a; margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="createAdminPassword" placeholder="Enter password to confirm creation" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid #cbd5e1; font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='#3b82f6'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='#cbd5e1'; this.style.boxShadow='none'">
        <div id="createPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeCreateModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid #e2e8f0; background:white; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:#64748b; cursor:pointer;" onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background='white'">Cancel</button>
      <button id="createConfirmBtn" onclick="confirmCreate()" style="padding:10px 22px; border-radius:10px; border:none; background:#10b981; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; box-shadow:0 4px 12px rgba(16,185,129,0.35); transition:all 0.2s;" onmouseover="this.style.background='#059669'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#10b981'; this.style.transform='translateY(0)';">💾 Create Now</button>
    </div>
  </div>
</div>

<script>
function openEditModal(schedule) {
    document.getElementById('edit_schedule_id').value = schedule.schedule_id;
    document.getElementById('edit_preset_name').value = schedule.preset_name;
    document.getElementById('edit_dimming_level').value = schedule.dimming_level;
    document.getElementById('edit_time_on').value = schedule.time_on;
    document.getElementById('edit_time_off').value = schedule.time_off;
    document.getElementById('edit_is_active').checked = schedule.is_active == 1;

    const activeDays = schedule.days_of_week ? schedule.days_of_week.split(',') : [];
    const allDays = ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'];
    
    let daysHtml = '';
    allDays.forEach(day => {
        const isChecked = activeDays.includes(day) ? 'checked' : '';
        daysHtml += `
            <label class="day-chip">
                <input type="checkbox" name="days[]" value="${day}" ${isChecked}>
                ${day}
            </label>
        `;
    });
    
    document.getElementById('edit_days_grid').innerHTML = daysHtml;

    document.getElementById('editModal').style.display = 'flex';
}

function validateCheckboxes(formId) {
    const form = document.getElementById(formId);
    const checkboxes = form.querySelectorAll('input[type="checkbox"][name="days[]"]');
    for (let i = 0; i < checkboxes.length; i++) {
        if (checkboxes[i].checked) {
            return true;
        }
    }
    showAppAlert("Please select at least one day for the schedule.", "warning");
    return false;
}

function openCreateModal() {

    if (!validateCheckboxes('createScheduleForm')) {
        return;
    }

    const presetName = document.querySelector('#createScheduleForm input[name="preset_name"]').value;
    document.getElementById('createModalTitle').textContent = `Preset: ${presetName || 'New Schedule'}`;
    
    const modal = document.getElementById('createModal');
    modal.style.display = 'flex';

    const content = modal.querySelector('.modal-spring');
    if (content) {
        content.classList.remove('modal-spring');
        void content.offsetWidth;
        content.classList.add('modal-spring');
    }
}

function closeCreateModal() {
    document.getElementById('createModal').style.display = 'none';
    const pwdInput = document.getElementById('createAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('createPasswordError').style.display = 'none';
        document.getElementById('createConfirmBtn').innerHTML = '💾 Create Now';
        document.getElementById('createConfirmBtn').disabled = false;
    }
}

async function confirmCreate() {
    const pwdInput = document.getElementById('createAdminPassword');
    const pwdError = document.getElementById('createPasswordError');
    const btn = document.getElementById('createConfirmBtn');
    
    if (!pwdInput.value.trim()) {
        pwdError.textContent = 'Password is required';
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    pwdError.style.display = 'none';
    btn.innerHTML = 'Verifying...';
    btn.disabled = true;
    
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'verify_password');
        formData.append('admin_password', pwdInput.value);
        
        const response = await fetch('schedule.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('createModal').style.display = 'none';
            document.getElementById('createScheduleForm').submit();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = '💾 Create Now';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
    const pwdInput = document.getElementById('editAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('editPasswordError').style.display = 'none';
        document.getElementById('editConfirmBtn').innerHTML = '💾 Save Changes';
        document.getElementById('editConfirmBtn').disabled = false;
    }
}

async function confirmEdit() {
    const pwdInput = document.getElementById('editAdminPassword');
    const pwdError = document.getElementById('editPasswordError');
    const btn = document.getElementById('editConfirmBtn');
    
    if (!pwdInput.value.trim()) {
        pwdError.textContent = 'Password is required';
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    pwdError.style.display = 'none';
    btn.innerHTML = 'Verifying...';
    btn.disabled = true;
    
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'verify_password');
        formData.append('admin_password', pwdInput.value);
        
        const response = await fetch('schedule.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {

            document.getElementById('editModal').querySelector('form').submit();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = '💾 Save Changes';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

function openDeleteModal(scheduleId, presetName) {
    document.getElementById('delete_schedule_id').value = scheduleId;
    document.getElementById('deleteModalScheduleName').textContent = `Preset: ${presetName}`;
    
    const modal = document.getElementById('deleteModal');
    modal.style.display = 'flex';

    const content = modal.querySelector('.modal-spring');
    if (content) {
        content.classList.remove('modal-spring');
        void content.offsetWidth; // Force reflow
        content.classList.add('modal-spring');
    }
}

function closeDeleteModal() {
    document.getElementById('deleteModal').style.display = 'none';
    const pwdInput = document.getElementById('deleteAdminPassword');
    if (pwdInput) {
        pwdInput.value = '';
        pwdInput.style.borderColor = '#cbd5e1';
        document.getElementById('deletePasswordError').style.display = 'none';
        document.getElementById('deleteConfirmBtn').innerHTML = '🗑️ Delete Now';
        document.getElementById('deleteConfirmBtn').disabled = false;
    }
}

async function confirmDelete() {
    const pwdInput = document.getElementById('deleteAdminPassword');
    const pwdError = document.getElementById('deletePasswordError');
    const btn = document.getElementById('deleteConfirmBtn');
    
    if (!pwdInput.value.trim()) {
        pwdError.textContent = 'Password is required';
        pwdError.style.display = 'block';
        pwdInput.style.borderColor = '#ef4444';
        pwdInput.focus();
        return;
    }
    
    pwdError.style.display = 'none';
    btn.innerHTML = 'Verifying...';
    btn.disabled = true;
    
    try {
        const formData = new URLSearchParams();
        formData.append('action', 'verify_password');
        formData.append('admin_password', pwdInput.value);
        
        const response = await fetch('schedule.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        
        const data = await response.json();
        if (data.success) {
            document.getElementById('deleteModal').style.display = 'none';
            document.getElementById('deleteScheduleForm').submit();
        } else {
            pwdError.textContent = 'Incorrect password. Try again.';
            pwdError.style.display = 'block';
            pwdInput.style.borderColor = '#ef4444';
            btn.innerHTML = '🗑️ Delete Now';
            btn.disabled = false;
        }
    } catch(err) {
        console.error(err);
        pwdError.textContent = 'Error verifying password. Check connection.';
        pwdError.style.display = 'block';
        btn.disabled = false;
    }
}

window.onclick = function(event) {
    const editModal = document.getElementById('editModal');
    const createModal = document.getElementById('createModal');
    const deleteModal = document.getElementById('deleteModal');
    
    if (event.target == editModal) {
        closeEditModal();
    } else if (event.target == createModal) {
        closeCreateModal();
    } else if (event.target == deleteModal) {
        closeDeleteModal();
    }
}
</script>

</body>
</html>