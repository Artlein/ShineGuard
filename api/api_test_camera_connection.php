<?php
require_once '../dbconnect.php';
requireLogin('System Admin');

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$camera_ip = $data['camera_ip'] ?? '';
$camera_port = intval($data['camera_port'] ?? 554);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($camera_ip)) {
    echo json_encode(['success' => false, 'error' => 'Camera IP is required']);
    exit();
}

// ── SECURITY: escapeshellarg() prevents Command Injection (RCE) ──
$safe_ip = escapeshellarg($camera_ip);
$ping_result = @exec("ping -n 1 -w 2000 {$safe_ip} 2>&1", $output, $return_code);

if ($return_code !== 0) {
    echo json_encode([
        'success' => false,
        'error' => 'Camera is not reachable. Check IP address and network connection.',
        'details' => $output
    ]);
    exit();
}

$socket = @fsockopen($camera_ip, $camera_port, $errno, $errstr, 5);
if (!$socket) {
    echo json_encode([
        'success' => false,
        'error' => "Port $camera_port is not open. Ensure RTSP is enabled on Camera/Video Gateway.",
        'details' => "$errstr ($errno)"
    ]);
    exit();
}
fclose($socket);

echo json_encode([
    'success' => true,
    'message' => 'Connection successful',
    'ping_time' => 'OK',
    'port_status' => 'OPEN'
]);

$conn->close();
?>
