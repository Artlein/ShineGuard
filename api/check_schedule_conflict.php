<?php
require_once '../dbconnect.php';
requireLoginApi('System Admin');

header('Content-Type: application/json');

$time_on = $_POST['time_on'] ?? '';
$time_off = $_POST['time_off'] ?? '';
$days = $_POST['days'] ?? ''; // Comma-separated string
$exclude_id = isset($_POST['exclude_id']) ? intval($_POST['exclude_id']) : 0;

if (empty($time_on) || empty($time_off) || empty($days)) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields.']);
    exit();
}

$new_days = explode(',', $days);
$conflicts = [];

// Fetch all active schedules (excluding current one if editing)
$sql = "SELECT schedule_id, preset_name, time_on, time_off, days_of_week FROM schedule_presets WHERE is_active = 1";
if ($exclude_id > 0) {
    $sql .= " AND schedule_id != $exclude_id";
}

$result = $conn->query($sql);

while ($row = $result->fetch_assoc()) {
    $existing_days = explode(',', $row['days_of_week']);
    $day_overlap = array_intersect($new_days, $existing_days);
    
    if (!empty($day_overlap)) {
        // Day overlap exists, check for time conflict
        // A conflict exists if time_on or time_off matches exactly OR if intervals overlap
        // The user specifically mentioned "exact same time"
        if ($row['time_on'] === $time_on || $row['time_off'] === $time_off) {
            $conflicts[] = [
                'name' => $row['preset_name'],
                'on' => $row['time_on'],
                'off' => $row['time_off'],
                'days' => $row['days_of_week']
            ];
        }
    }
}

echo json_encode([
    'success' => true,
    'has_conflict' => count($conflicts) > 0,
    'conflicts' => $conflicts
]);
