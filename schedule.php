<?php
require_once 'dbconnect.php';
requireLogin('System Admin');

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
        setAuthorized();
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false]);
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_schedule'])) {
    checkRateLimit('manage_schedule', 10, 1);
    if (!canDo('manage_schedules')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    $preset_name = sanitize($_POST['preset_name']);
    $time_on = $_POST['time_on'];
    $time_off = $_POST['time_off'];
    $dimming_level = intval($_POST['dimming_level']);
    $days_of_week = implode(',', $_POST['days'] ?? []);
    
    // Check for duplicate time
    $check = $conn->prepare("SELECT COUNT(*) FROM schedule_presets WHERE time_on = ? AND time_off = ?");
    $check->bind_param("ss", $time_on, $time_off);
    $check->execute();
    if ($check->get_result()->fetch_row()[0] > 0) {
        header('Location: schedule.php?error=duplicate_time');
        exit();
    }
    
    $stmt = $conn->prepare("INSERT INTO schedule_presets (preset_name, time_on, time_off, dimming_level, days_of_week, created_by) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssisi", $preset_name, $time_on, $time_off, $dimming_level, $days_of_week, $_SESSION['user_id']);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Schedule Created', "Created schedule: $preset_name");
        header('Location: schedule.php?success=schedule_created');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_schedule'])) {
    checkRateLimit('manage_schedule', 10, 1);
    if (!canDo('manage_schedules')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
    $schedule_id = intval($_POST['schedule_id']);
    $preset_name = sanitize($_POST['preset_name']);
    $time_on = $_POST['time_on'];
    $time_off = $_POST['time_off'];
    $dimming_level = intval($_POST['dimming_level']);
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $days_of_week = implode(',', $_POST['days'] ?? []);
    
    // Check for duplicate time (excluding this schedule)
    $check = $conn->prepare("SELECT COUNT(*) FROM schedule_presets WHERE time_on = ? AND time_off = ? AND schedule_id != ?");
    $check->bind_param("ssi", $time_on, $time_off, $schedule_id);
    $check->execute();
    if ($check->get_result()->fetch_row()[0] > 0) {
        header('Location: schedule.php?error=duplicate_time');
        exit();
    }
    
    $stmt = $conn->prepare("UPDATE schedule_presets SET preset_name=?, time_on=?, time_off=?, dimming_level=?, days_of_week=?, is_active=? WHERE schedule_id=?");
    $stmt->bind_param("sssisii", $preset_name, $time_on, $time_off, $dimming_level, $days_of_week, $is_active, $schedule_id);
    
    if ($stmt->execute()) {
        logActivity($conn, $_SESSION['user_id'], 'Schedule Updated', "Updated schedule: $preset_name");
        header('Location: schedule.php?success=edit');
        exit();
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_schedule'])) {
    checkRateLimit('manage_schedule', 5, 1);
    if (!canDo('manage_schedules')) {
        include __DIR__ . '/includes/access_denied_ui.php';
        exit();
    }
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
<link rel="stylesheet" href="assets/style.css">
<?php
if (!isset($theme_color)) {
    $theme_color = '#10b981';
    $tc_result = $conn->query("SELECT config_value FROM system_config WHERE config_key = 'theme_color' LIMIT 1");
    if ($tc_result && $tc_row = $tc_result->fetch_assoc()) {
        $theme_color = $tc_row['config_value'];
    }
}
?>
<style>:root { --theme-color: <?php echo htmlspecialchars($theme_color); ?>; }</style>
<link rel="stylesheet" href="assets/css/schedule.css?v=<?php echo time(); ?>">
<link rel="stylesheet" href="assets/css/schedule_calendar.css?v=<?php echo time(); ?>">
</head>
<body>
<div class="layout">
<?php include 'includes/sidebar.php'; ?>
<?php include 'includes/header.php'; ?>
<main class="main-content">

<?php if(isset($_GET['error']) && $_GET['error'] === 'duplicate_time'): ?>
<div id="errorToast" style="
    position: fixed; top: 24px; right: 24px; z-index: 99999;
    background: #fff1f2; border-radius: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    padding: 18px 24px; display: flex; align-items: center; gap: 16px;
    max-width: 420px; border-left: 4px solid #ef4444;
    border: 1px solid #fecaca;
    animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
    font-family: 'Inter', sans-serif;
">
    <div style="background: rgba(239, 68, 68, 0.1); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">⚠️</div>
    <div style="flex: 1;">
        <div style="font-weight: 800; color: #991b1b; font-size: 0.9rem; margin-bottom: 2px;">Schedule Conflict</div>
        <div style="color: #b91c1c; font-size: 0.8rem;">A schedule with these exact ON/OFF times already exists.</div>
    </div>
    <button onclick="document.getElementById('errorToast').style.display='none'" style="background: none; border: none; cursor: pointer; color: #ef4444; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('errorToast');
        if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
    }, 5000);
</script>
<?php endif; ?>

<?php if(isset($_GET['success']) && $_GET['success'] === 'schedule_created'): ?>
<div id="successToast" style="
    position: fixed; top: 24px; right: 24px; z-index: 99999;
    background: #f0fdf4; border-radius: 16px;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    padding: 18px 24px; display: flex; align-items: center; gap: 16px;
    max-width: 380px; border-left: 4px solid #10b981;
    border: 1px solid #bbf7d0;
    animation: slideInRight 0.4s cubic-bezier(0.34,1.56,0.64,1);
    font-family: 'Inter', sans-serif;
">
    <div style="background: rgba(16, 185, 129, 0.1); width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">✅</div>
    <div style="flex: 1;">
        <div style="font-weight: 800; color: #166534; font-size: 0.9rem; margin-bottom: 2px;">Success!</div>
        <div style="color: #15803d; font-size: 0.8rem;">New schedule preset has been created.</div>
    </div>
    <button onclick="document.getElementById('successToast').style.display='none'" style="background: none; border: none; cursor: pointer; color: #10b981; font-size: 18px; line-height: 1; padding: 0; flex-shrink: 0;">✕</button>
</div>
<script>
    setTimeout(() => {
        const t = document.getElementById('successToast');
        if (t) { t.style.transition = 'opacity 0.4s'; t.style.opacity = '0'; setTimeout(() => t.remove(), 400); }
    }, 4000);
</script>
<?php endif; ?>

  <div class="page-header">
    <h1 style="margin: 0;">⏰ Schedule Presets</h1>
    <p style="margin: 0; color: var(--dim);">Automate streetlight ON/OFF times</p>
  </div>


  <div class="calendar-panel">
    <div class="calendar-header">
        <h2 style="margin:0;">🗓️ Weekly Schedule Visualizer</h2>
        <div style="display:flex; gap:12px; align-items:center; font-size:0.85rem; color:var(--dim);">
            <div style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background:#10b981; border-radius:3px;"></span> Active</div>
            <div style="display:flex; align-items:center; gap:6px;"><span style="width:12px; height:12px; background:#94a3b8; border-radius:3px;"></span> Inactive</div>
        </div>
    </div>
    
    <div class="calendar-grid">
        <div class="calendar-time-col" style="background:var(--muted); border-bottom:1.5px solid var(--border);">Time</div>
        <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
            <div class="calendar-day-header"><?php echo $day; ?></div>
        <?php endforeach; ?>
        
        <div id="calendarContent" class="calendar-content" style="grid-column: span 8; position: relative;">
            <?php for ($h = 0; $h < 24; $h++): ?>
                <div class="time-row">
                    <div class="calendar-time-col">
                        <?php echo $h === 0 ? '12 AM' : ($h === 12 ? '12 PM' : ($h > 12 ? ($h-12) . ' PM' : $h . ' AM')); ?>
                    </div>
                    <?php for ($d = 0; $d < 7; $d++): ?>
                        <div class="calendar-cell"></div>
                    <?php endfor; ?>
                </div>
            <?php endfor; ?>
            <!-- Blocks will be rendered here by JS -->
        </div>
    </div>
  </div>

  <div class="panel panel-list">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <h2 style="margin:0;">📋 Existing Schedules</h2>
        <button type="button" onclick="openCreateFormModal()" class="btn-primary" style="background: #10b981; color: white; border: none; border-radius: 10px; padding: 8px 16px; font-size: 0.85rem; font-weight: 700; box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.4); cursor: pointer; transition: all 0.2s; display: flex; align-items: center; gap: 6px;">
            <span>➕</span> Create New Schedule
        </button>
    </div>
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
                <button type="button" class="btn-sm" style="background: #10b981; color: white; border: none; border-radius: 8px;" onclick='openEditModal(<?php echo json_encode($schedule); ?>)'>✏️ Edit</button>
                <button type="button" class="btn-sm danger" style="background: #ef4444; color: white; border: none; border-radius: 8px;" onclick="openDeleteModal(<?php echo $schedule['schedule_id']; ?>, '<?php echo addslashes($schedule['preset_name']); ?>')">🗑️ Delete</button>
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

<div id="deleteModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
  <div class="modal-spring" style="background:var(--panel); border-radius:24px; padding:32px; max-width:400px; width:90%; box-shadow:var(--shadow-md); font-family:'Inter',sans-serif; border: 1px solid var(--border);">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:rgba(239, 68, 68, 0.1); width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">🗑️</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:var(--text);">Delete Schedule?</div>
        <div style="font-size:0.8rem; color:var(--dim); margin-top:2px;" id="deleteModalScheduleName">Loading...</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:var(--dim); line-height:1.6; margin-bottom:24px;">
      Are you sure you want to delete this schedule? This action cannot be undone and will stop any automated lighting for this preset.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="deleteAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="deleteAdminPassword" placeholder="Enter password to confirm deletion" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text); font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='var(--blue)'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
        <div id="deletePasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeDeleteModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid var(--border); background:var(--panel); font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:var(--dim); cursor:pointer;" onmouseover="this.style.background='var(--muted)'" onmouseout="this.style.background='var(--panel)'">Cancel</button>
      <form id="deleteScheduleForm" method="POST" style="margin:0;" onsubmit="event.preventDefault(); confirmDelete();">
        <input type="hidden" name="schedule_id" id="delete_schedule_id">
        <input type="hidden" name="delete_schedule" value="1">
        <button type="submit" id="deleteConfirmBtn" style="padding:10px 22px; border-radius:10px; border:none; background:#ef4444; font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:700; color:white; cursor:pointer; box-shadow:0 4px 12px rgba(239,68,68,0.35); transition:all 0.2s;" onmouseover="this.style.background='#dc2626'; this.style.transform='translateY(-1px)';" onmouseout="this.style.background='#ef4444'; this.style.transform='translateY(0)';">🗑️ Delete Now</button>
      </form>
    </div>
  </div>
</div>

<div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.7); z-index: 2000; align-items: center; justify-content: center; backdrop-filter: blur(4px);">
    <div style="background: var(--panel); border-radius: 24px; padding: 32px; width: 100%; max-width: 500px; box-shadow: var(--shadow-md); border: 1px solid var(--border);">
        <h2 style="margin-top: 0; margin-bottom: 24px; color: var(--text); font-size: 1.5rem; display: flex; align-items: center; gap: 8px;">
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
                <label for="edit_is_active" style="margin: 0; cursor: pointer; font-size: 0.9rem; color: var(--text);">Schedule Active</label>
            </div>

            <div style="margin-bottom: 24px; text-align: left;">
                <label for="editAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
                <input type="password" id="editAdminPassword" placeholder="Enter password to confirm changes" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text); font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='var(--blue)'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
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

<div id="createFormModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9000; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
  <div class="modal-spring" style="background:var(--panel); border-radius:24px; padding:32px; max-width:600px; width:90%; box-shadow:var(--shadow-md); font-family:'Inter',sans-serif; border: 1px solid var(--border);">
    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:20px;">
        <h2 style="margin:0; font-size:1.5rem; display:flex; align-items:center; gap:8px;"><span>➕</span> Create New Schedule</h2>
        <button onclick="closeCreateFormModal()" style="background:none; border:none; color:var(--dim); font-size:1.5rem; cursor:pointer;">&times;</button>
    </div>
    
    <form id="createScheduleForm" method="POST" onsubmit="event.preventDefault(); openCreateModal();">
      <div class="form-grid-2">
        <div class="form-group" style="margin-bottom:15px;">
          <label style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:6px;">Preset Name</label>
          <input type="text" name="preset_name" required placeholder="e.g., Night Mode" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text);">
        </div>
        <div class="form-group" style="margin-bottom:15px;">
          <label style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:6px;">Dimming Level (%)</label>
          <input type="number" name="dimming_level" value="70" min="0" max="100" required style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text);">
        </div>
        <div class="form-group" style="margin-bottom:15px;">
          <label style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:6px;">Turn ON Time</label>
          <input type="time" name="time_on" value="18:00" required style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text);">
        </div>
        <div class="form-group" style="margin-bottom:15px;">
          <label style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:6px;">Turn OFF Time</label>
          <input type="time" name="time_off" value="06:00" required style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text);">
        </div>
      </div>

      <div class="form-days-label" style="font-weight:600; margin-top:10px; margin-bottom:10px; color:var(--text);">Days of Week</div>
      <div class="days-grid" style="display:flex; flex-wrap:wrap; gap:8px; margin-bottom:24px;">
        <?php foreach(['Mon','Tue','Wed','Thu','Fri','Sat','Sun'] as $day): ?>
        <label class="day-chip">
          <input type="checkbox" name="days[]" value="<?php echo $day; ?>" checked>
          <?php echo $day; ?>
        </label>
        <?php endforeach; ?>
      </div>

      <div style="display:flex; justify-content:flex-end; gap:12px;">
          <button type="button" onclick="closeCreateFormModal()" class="btn-sm" style="padding:10px 20px;">Cancel</button>
          <button type="submit" class="btn-primary" style="padding:10px 24px; background:#10b981; color:white; border:none; border-radius:10px;">💾 Create Schedule</button>
      </div>
      
      <input type="hidden" name="create_schedule" value="1">
    </form>
  </div>
</div>

<div id="createModal" style="display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); z-index:9999; align-items:center; justify-content:center; backdrop-filter: blur(4px);">
  <div class="modal-spring" style="background:var(--panel); border-radius:24px; padding:32px; max-width:400px; width:90%; box-shadow:var(--shadow-md); font-family:'Inter',sans-serif; border: 1px solid var(--border);">
    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
      <div style="background:rgba(16, 185, 129, 0.1); width:48px; height:48px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:22px; flex-shrink:0;">➕</div>
      <div>
        <div style="font-size:1.1rem; font-weight:800; color:var(--text);">Create Schedule?</div>
        <div style="font-size:0.8rem; color:var(--dim); margin-top:2px;" id="createModalTitle">New Preset</div>
      </div>
    </div>
    
    <p style="font-size:0.9rem; color:var(--dim); line-height:1.6; margin-bottom:24px;">
      You are about to create a new automated schedule. Please provide your administrator password to confirm.
    </p>

    <div style="margin-bottom: 24px; text-align: left;">
        <label for="createAdminPassword" style="display:block; font-size:0.875rem; font-weight:600; color:var(--text); margin-bottom:8px;">🔐 Administrator Password <span style="color:#ef4444;">*</span></label>
        <input type="password" id="createAdminPassword" placeholder="Enter password to confirm creation" style="width:100%; padding:10px 14px; border-radius:8px; border:1px solid var(--border); background:var(--muted); color:var(--text); font-family:'Inter',sans-serif; font-size:0.875rem; outline:none; transition:all 0.2s;" onfocus="this.style.borderColor='var(--blue)'; this.style.boxShadow='0 0 0 3px rgba(59,130,246,0.1)'" onblur="this.style.borderColor='var(--border)'; this.style.boxShadow='none'">
        <div id="createPasswordError" style="color:#ef4444; font-size:0.75rem; margin-top:6px; display:none;">Password is required</div>
    </div>

    <div style="display:flex; gap:12px; justify-content:flex-end;">
      <button onclick="closeCreateModal()" style="padding:10px 22px; border-radius:10px; border:1.5px solid var(--border); background:var(--panel); font-family:'Inter',sans-serif; font-size:0.875rem; font-weight:600; color:var(--dim); cursor:pointer;" onmouseover="this.style.background='var(--muted)'" onmouseout="this.style.background='var(--panel)'">Cancel</button>
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

let pendingAction = null; // Store data for "Proceed Anyway"

async function openCreateModal() {
    if (!validateCheckboxes('createScheduleForm')) return;

    const form = document.getElementById('createScheduleForm');
    const presetName = form.querySelector('input[name="preset_name"]').value;
    const timeOn = form.querySelector('input[name="time_on"]').value;
    const timeOff = form.querySelector('input[name="time_off"]').value;
    const daysArr = Array.from(form.querySelectorAll('input[name="days[]"]:checked')).map(cb => cb.value);
    
    // Check for conflicts
    const formData = new URLSearchParams();
    formData.append('time_on', timeOn);
    formData.append('time_off', timeOff);
    formData.append('days', daysArr.join(','));
    
    try {
        const response = await fetch('api/check_schedule_conflict.php', {
            method: 'POST',
            body: formData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const data = await response.json();
        
        if (data.success && data.has_conflict) {
            showConflicts(data.conflicts, () => {
                showCreateConfirmation(presetName);
            });
            return;
        }
    } catch (err) { console.error("Conflict check failed:", err); }

    showCreateConfirmation(presetName);
}

function showCreateConfirmation(presetName) {
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

function showConflicts(conflicts, onProceed) {
    const list = document.getElementById('conflictList');
    list.innerHTML = conflicts.map(c => `
        <div style="margin-bottom:10px; padding-bottom:10px; border-bottom:1px solid rgba(0,0,0,0.05); font-size:0.85rem;">
            <strong style="color:var(--text);">${c.name}</strong><br>
            <span style="color:var(--dim);">${c.on} - ${c.off}</span><br>
            <span style="color:#f59e0b; font-weight:600;">Days: ${c.days}</span>
        </div>
    `).join('');
    
    pendingAction = onProceed;
    document.getElementById('conflictModal').style.display = 'flex';
}

function closeConflictModal() {
    document.getElementById('conflictModal').style.display = 'none';
    pendingAction = null;
}

function proceedWithConflict() {
    if (pendingAction) pendingAction();
    closeConflictModal();
}

function openCreateFormModal() {
    document.getElementById('createFormModal').style.display = 'flex';
}

function closeCreateFormModal() {
    document.getElementById('createFormModal').style.display = 'none';
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
            document.getElementById('createFormModal').style.display = 'none';
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
    
    // Check for conflicts before saving edit
    const form = document.getElementById('editModal').querySelector('form');
    const scheduleId = document.getElementById('edit_schedule_id').value;
    const timeOn = document.getElementById('edit_time_on').value;
    const timeOff = document.getElementById('edit_time_off').value;
    const daysArr = Array.from(form.querySelectorAll('input[name="days[]"]:checked')).map(cb => cb.value);
    
    if (daysArr.length === 0) {
        showAppAlert("Please select at least one day.", "warning");
        btn.innerHTML = '💾 Save Changes';
        btn.disabled = false;
        return;
    }

    const conflictData = new URLSearchParams();
    conflictData.append('time_on', timeOn);
    conflictData.append('time_off', timeOff);
    conflictData.append('days', daysArr.join(','));
    conflictData.append('exclude_id', scheduleId);

    try {
        const cResponse = await fetch('api/check_schedule_conflict.php', {
            method: 'POST',
            body: conflictData,
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });
        const cData = await cResponse.json();
        
        if (cData.success && cData.has_conflict) {
            showConflicts(cData.conflicts, () => {
                executeActualEdit(pwdInput.value);
            });
            btn.innerHTML = '💾 Save Changes';
            btn.disabled = false;
            return;
        }
    } catch (err) { console.error("Edit conflict check fail:", err); }

    executeActualEdit(pwdInput.value);
}

async function executeActualEdit(password) {
    const pwdInput = document.getElementById('editAdminPassword');
    const pwdError = document.getElementById('editPasswordError');
    const btn = document.getElementById('editConfirmBtn');
    
    btn.innerHTML = 'Verifying...';
    btn.disabled = true;

    try {
        const formData = new URLSearchParams();
        formData.append('action', 'verify_password');
        formData.append('admin_password', password);
        
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
        pwdError.textContent = 'Error verifying password.';
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
        void content.offsetWidth; 
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
    const createFormModal = document.getElementById('createFormModal');
    
    if (event.target == editModal) {
        closeEditModal();
    } else if (event.target == createModal) {
        closeCreateModal();
    } else if (event.target == deleteModal) {
        closeDeleteModal();
    } else if (event.target == createFormModal) {
        closeCreateFormModal();
    }
}

// Calendar Rendering
const allSchedules = <?php 
    $schedules->data_seek(0);
    $arr = [];
    while($row = $schedules->fetch_assoc()) $arr[] = $row;
    echo json_encode($arr);
?>;

function renderCalendar() {
    const container = document.getElementById('calendarContent');
    
    const dayMap = { 'Mon': 0, 'Tue': 1, 'Wed': 2, 'Thu': 3, 'Fri': 4, 'Sat': 5, 'Sun': 6 };
    
    allSchedules.forEach(s => {
        const days = s.days_of_week.split(',');
        const [onH, onM] = s.time_on.split(':').map(Number);
        const [offH, offM] = s.time_off.split(':').map(Number);
        
        const startMin = onH * 60 + onM;
        const endMin = offH * 60 + offM;
        
        const duration = endMin < startMin ? (1440 - startMin + endMin) : (endMin - startMin);
        
        days.forEach(dayStr => {
            const dIdx = dayMap[dayStr];
            if (dIdx === undefined) return;
            
            // If overnight, split the block
            if (endMin < startMin) {
                // Today part: start to midnight
                addBlock(container, dIdx, startMin, 1440 - startMin, s);
                // Tomorrow part: midnight to end
                const nextDayIdx = (dIdx + 1) % 7;
                addBlock(container, nextDayIdx, 0, endMin, s);
            } else {
                addBlock(container, dIdx, startMin, duration, s);
            }
        });
    });
    
    updateCurrentTimeLine();
    setTimeout(updateCurrentTimeLine, 60000);
}

function addBlock(container, dayIdx, startMin, durationMin, schedule) {
    const block = document.createElement('div');
    block.className = 'schedule-block';
    
    // Calculate position
    // Total height = 24 rows * 45px = 1080px. 
    // Each row is 1 hour (45px). 1 min is 45/60 = 0.75px.
    const top = (startMin * 0.75); 
    const height = (durationMin * 0.75);
    
    // Calculate width & left
    const colWidth = (container.offsetWidth - 80) / 7;
    const left = 80 + (dayIdx * colWidth);
    
    block.style.top = top + 'px';
    block.style.height = height + 'px';
    block.style.left = left + 'px';
    block.style.width = (colWidth - 4) + 'px';
    
    block.style.background = schedule.is_active == 1 ? '#10b981' : '#94a3b8';
    block.style.opacity = schedule.is_active == 1 ? (0.4 + (schedule.dimming_level / 200)) : 0.4;
    
    block.innerHTML = `<div style="font-size: 0.6rem; opacity: 0.8;">${schedule.dimming_level}%</div> ${schedule.preset_name}`;
    block.title = `${schedule.preset_name}\n${schedule.time_on} - ${schedule.time_off}\nDimming: ${schedule.dimming_level}%`;
    
    block.onclick = (e) => {
        e.stopPropagation();
        openEditModal(schedule);
    };
    
    container.appendChild(block);
}

function updateCurrentTimeLine() {
    let line = document.getElementById('currentTimeLine');
    if (!line) {
        line = document.createElement('div');
        line.id = 'currentTimeLine';
        line.className = 'current-time-line';
        document.getElementById('calendarContent').appendChild(line);
    }
    
    const now = new Date();
    const mins = now.getHours() * 60 + now.getMinutes();
    line.style.top = (mins * 0.75) + 'px';
}

document.addEventListener('DOMContentLoaded', renderCalendar);
window.addEventListener('resize', renderCalendar);
</script>
<?php include 'assets/app_alert.php'; ?>
</body>
</html>