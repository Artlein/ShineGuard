<?php
require_once '../dbconnect.php';
requireLoginApi(); 

header('Content-Type: application/json');

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 2) {
    echo json_encode(['results' => []]);
    exit;
}

$like = '%' . $conn->real_escape_string($q) . '%';
$results = [];

$stmt = $conn->query("SELECT light_id, node_name, location, status 
                       FROM streetlights 
                       WHERE node_name LIKE '$like' OR location LIKE '$like'
                       LIMIT 5");
if ($stmt && $stmt->num_rows > 0) {
    while ($row = $stmt->fetch_assoc()) {
        $status = strtolower($row['status']);
        $results[] = [
            'type'  => 'streetlight',
            'icon'  => '💡',
            'title' => $row['node_name'],
            'sub'   => $row['location'] . ' · ' . ucfirst($status),
            'url'   => 'streetlights.php?id=' . $row['light_id'],
            'badge' => $status === 'active' ? 'online' : 'offline'
        ];
    }
}

$stmt = $conn->query("SELECT camera_id, camera_name, location, status 
                       FROM cameras 
                       WHERE camera_name LIKE '$like' OR location LIKE '$like'
                       LIMIT 5");
if ($stmt && $stmt->num_rows > 0) {
    while ($row = $stmt->fetch_assoc()) {
        $status = strtolower($row['status']);
        $results[] = [
            'type'  => 'camera',
            'icon'  => '📹',
            'title' => $row['camera_name'],
            'sub'   => $row['location'] . ' · ' . ucfirst($status),
            'url'   => 'cctv.php?id=' . $row['camera_id'],
            'badge' => $status === 'online' ? 'online' : 'offline'
        ];
    }
}

$stmt = $conn->query("SELECT a.id as alert_id, a.type as alert_type, a.severity, a.status, a.message as description
                       FROM alerts a 
                       WHERE a.type LIKE '$like' OR a.message LIKE '$like'
                       ORDER BY a.created_at DESC LIMIT 5");
if ($stmt && $stmt->num_rows > 0) {
    while ($row = $stmt->fetch_assoc()) {
        $badge = strtolower($row['severity']);
        $results[] = [
            'type'  => 'alert',
            'icon'  => '🚨',
            'title' => $row['alert_type'],
            'sub'   => mb_strimwidth($row['description'], 0, 40, "...") . ' · ' . ucfirst($row['status']),
            'url'   => 'alerts.php?id=' . $row['alert_id'],
            'badge' => $badge
        ];
    }
}

$stmt = $conn->query("SELECT user_id, username, full_name, role 
                       FROM users 
                       WHERE username LIKE '$like' OR full_name LIKE '$like'
                       LIMIT 5");
if ($stmt && $stmt->num_rows > 0) {
    while ($row = $stmt->fetch_assoc()) {
        $results[] = [
            'type'  => 'user',
            'icon'  => '👤',
            'title' => $row['full_name'],
            'sub'   => '@' . $row['username'] . ' · ' . ucfirst($row['role']),
            'url'   => 'settings.php?tab=users&id=' . $row['user_id'],
            'badge' => 'user'
        ];
    }
}

echo json_encode(['results' => $results, 'query' => $q]);
