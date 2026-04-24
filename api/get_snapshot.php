<?php
require_once '../dbconnect.php';

// Diagnostic: Log current user info
if (!isLoggedIn()) {
    error_log("SNAPSHOT AUTH FAIL: User not logged in", 3, "/tmp/shineguard_api.log");
} else {
    error_log("SNAPSHOT AUTH OK: User=" . ($_SESSION['username'] ?? 'unknown') . " Role=" . ($_SESSION['role'] ?? 'none'), 3, "/tmp/shineguard_api.log");
}

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

// ── RESILIENCE: Path Normalization ──
// Ensure paths like "snapshots/..." work correctly regardless of relative pathing
$root_path = dirname(__DIR__) . '/';
$normalized_path = (strpos($filepath, '../') === 0) 
    ? $root_path . substr($filepath, 3) 
    : $root_path . $filepath;

if (!file_exists($normalized_path)) {
    error_log("SNAPSHOT 404: File not found at $normalized_path | Original: $filepath", 3, "/tmp/shineguard_api.log");
    http_response_code(404);
    die('Image file is missing from the server.');
}
error_log("SNAPSHOT SUCCESS: Serving $normalized_path", 3, "/tmp/shineguard_api.log");

$encrypted_data = file_get_contents($normalized_path);

// ── BUFFER CLEARANCE ──
// Remove any accidental whitespace/outputs from included files
if (ob_get_length()) ob_clean();

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
    header('Content-Length: ' . strlen($encrypted_data));
    echo $encrypted_data;
}

exit();
?>
