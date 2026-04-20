<?php
require_once '../dbconnect.php';
requireLogin('System Admin');

header('Content-Type: application/json');

$camera_id = intval($_GET['camera_id'] ?? 0);

if ($camera_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cameras WHERE camera_id = ?");
    $stmt->bind_param("i", $camera_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($camera = $result->fetch_assoc()) {
        // ── SECURITY HARDENING: Decryption on Read ──
        $camera['camera_ip'] = \ShineGuard\Services\SecurityService::decrypt($camera['camera_ip'] ?? '');
        $camera['username'] = \ShineGuard\Services\SecurityService::decrypt($camera['username'] ?? '');
        $camera['password'] = \ShineGuard\Services\SecurityService::decrypt($camera['password'] ?? '');
        $camera['stream_url'] = \ShineGuard\Services\SecurityService::decrypt($camera['stream_url'] ?? '');
        
        echo json_encode($camera);
    } else {
        echo json_encode(['error' => 'Camera not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid camera ID']);
}

$conn->close();
?>
