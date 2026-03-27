<?php
require_once '../dbconnect.php';
requireLogin(['System Admin', 'Maintenance Operator']);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    die('Invalid snapshot ID.');
}

$snapshot_id = intval($_GET['id']);

$stmt = $conn->prepare("SELECT filepath, encryption_iv FROM camera_snapshots WHERE snapshot_id = ?");
$stmt->bind_param("i", $snapshot_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    http_response_code(404);
    die('Snapshot not found.');
}

$snapshot = $result->fetch_assoc();
$filepath = $snapshot['filepath'];
$iv_hex = $snapshot['encryption_iv'];

if (!file_exists($filepath)) {
    http_response_code(404);
    die('Image file is missing from the server.');
}

$encrypted_data = file_get_contents($filepath);

if (!empty($iv_hex)) {
    $encryption_key = 'shineguard_secure_snapshot_key_!@#'; 
    $iv = hex2bin($iv_hex);
    
    $decrypted_data = openssl_decrypt($encrypted_data, 'aes-256-cbc', $encryption_key, 0, $iv);
    
    if ($decrypted_data === false) {
        http_response_code(500);
        die('Failed to decrypt image.');
    }

    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($decrypted_data));
    echo $decrypted_data;
} else {
    
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . filesize($filepath));
    readfile($filepath);
}

exit();
?>
