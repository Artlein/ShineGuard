<?php
require_once '../dbconnect.php';
requireLogin();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);

$nvr_ip = $data['nvr_ip'] ?? '';
$nvr_port = intval($data['nvr_port'] ?? 554);
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (empty($nvr_ip)) {
    echo json_encode(['success' => false, 'error' => 'NVR IP is required']);
    exit();
}

$ping_result = @exec("ping -n 1 -w 2000 $nvr_ip 2>&1", $output, $return_code);

if ($return_code !== 0) {
    echo json_encode([
        'success' => false,
        'error' => 'NVR is not reachable. Check IP address and network connection.',
        'details' => $output
    ]);
    exit();
}

$socket = @fsockopen($nvr_ip, $nvr_port, $errno, $errstr, 5);
if (!$socket) {
    echo json_encode([
        'success' => false,
        'error' => "Port $nvr_port is not open. Ensure RTSP is enabled on NVR.",
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
