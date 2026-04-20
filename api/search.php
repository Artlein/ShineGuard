<?php
require_once '../dbconnect.php';
requireLoginApi(); 

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $q . '%';
$results = [];

// ── SECURITY: Parameterized Streetlights Search ──
$stmt = $conn->prepare("SELECT light_id, node_name, location, location_index, status 
                       FROM streetlights 
                       WHERE node_name LIKE ? OR location_index LIKE ?
                       LIMIT 5");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $status = strtolower($row['status']);
        $display_location = \ShineGuard\Services\SecurityService::decrypt($row['location']);
        $results[] = [
            'type'  => 'streetlight',
            'icon'  => '💡',
            'title' => $row['node_name'],
            'sub'   => $display_location . ' · ' . ucfirst($status),
            'url'   => 'streetlights.php?id=' . $row['light_id'],
            'badge' => $status === 'active' ? 'online' : 'offline'
        ];
    }
}
$stmt->close();

// ── SECURITY: Parameterized Cameras Search ──
$stmt = $conn->prepare("SELECT camera_id, camera_name, location, location_index, status 
                       FROM cameras 
                       WHERE camera_name LIKE ? OR location_index LIKE ?
                       LIMIT 5");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $status = strtolower($row['status']);
        $display_location = \ShineGuard\Services\SecurityService::decrypt($row['location']);
        $results[] = [
            'type'  => 'camera',
            'icon'  => '📹',
            'title' => $row['camera_name'],
            'sub'   => $display_location . ' · ' . ucfirst($status),
            'url'   => 'cctv.php?id=' . $row['camera_id'],
            'badge' => $status === 'online' ? 'online' : 'offline'
        ];
    }
}
$stmt->close();

// ── SECURITY: Parameterized Alerts Search ──
$id_search = null;
if (preg_match('/^#(\d+)$/', $q, $matches)) {
    $id_search = intval($matches[1]);
}

if ($id_search) {
    $stmt = $conn->prepare("SELECT a.alert_id, a.alert_type, a.severity, a.status, a.description
                           FROM alerts a 
                           WHERE a.alert_id = ? OR a.alert_type LIKE ? OR a.description LIKE ?
                           ORDER BY a.created_at DESC LIMIT 5");
    $stmt->bind_param("iss", $id_search, $like, $like);
} else {
    $stmt = $conn->prepare("SELECT a.alert_id, a.alert_type, a.severity, a.status, a.description
                           FROM alerts a 
                           WHERE a.alert_type LIKE ? OR a.description LIKE ?
                           ORDER BY a.created_at DESC LIMIT 5");
    $stmt->bind_param("ss", $like, $like);
}
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $badge = strtolower($row['severity']);
        $results[] = [
            'type'  => 'alert',
            'icon'  => '🚨',
            'title' => '#' . $row['alert_id'] . ' - ' . $row['alert_type'],
            'sub'   => mb_strimwidth($row['description'], 0, 45, "...") . ' · ' . ucfirst($row['status']),
            'url'   => 'alerts.php?id=' . $row['alert_id'],
            'badge' => $badge
        ];
    }
}
$stmt->close();

// ── SECURITY: Parameterized Users Search ──
$stmt = $conn->prepare("SELECT user_id, username, full_name, role 
                       FROM users 
                       WHERE username_blind_index LIKE ? OR email_blind_index LIKE ?
                       LIMIT 5");
$stmt->bind_param("ss", $like, $like);
$stmt->execute();
$res = $stmt->get_result();
if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $dec_name = \ShineGuard\Services\SecurityService::decrypt($row['full_name']);
        $dec_user = \ShineGuard\Services\SecurityService::decrypt($row['username']);
        $dec_role = \ShineGuard\Services\SecurityService::decrypt($row['role']);
        $results[] = [
            'type'  => 'user',
            'icon'  => '👤',
            'title' => $dec_name,
            'sub'   => '@' . $dec_user . ' · ' . ucfirst($dec_role),
            'url'   => 'settings.php?tab=users&id=' . $row['user_id'],
            'badge' => 'user'
        ];
    }
}

echo json_encode(['results' => $results, 'query' => $q]);
