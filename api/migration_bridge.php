<?php
require_once '../dbconnect.php';

// ── SECURITY: Migration Secret ──
// This prevents unauthorized use of the bridge
define('MIGRATION_SECRET', 'shineguard_mig_2026_!@#');

$headers = getallheaders();
$auth = $headers['X-Migration-Secret'] ?? '';

if ($auth !== MIGRATION_SECRET) {
    http_response_code(403);
    die(json_encode(['success' => false, 'error' => 'Forbidden: Invalid migration secret']));
}

$action = $_POST['action'] ?? '';

if ($action === 'sync_db') {
    $data = json_decode($_POST['data'], true);
    if (!$data) die(json_encode(['success' => false, 'error' => 'Invalid data format']));

    $stmt = $conn->prepare("INSERT IGNORE INTO camera_snapshots (snapshot_id, camera_id, filename, filepath, created_at, encryption_iv) VALUES (?, ?, ?, ?, ?, ?)");
    
    $count = 0;
    foreach ($data as $row) {
        $stmt->bind_param("iissss", 
            $row['snapshot_id'], 
            $row['camera_id'], 
            $row['filename'], 
            $row['filepath'], 
            $row['created_at'], 
            $row['encryption_iv']
        );
        if ($stmt->execute()) $count++;
    }
    echo json_encode(['success' => true, 'synced_rows' => $count]);
    exit();
}

if ($action === 'upload_file') {
    if (!isset($_FILES['snapshot'])) die(json_encode(['success' => false, 'error' => 'No file']));
    
    $filename = $_POST['filename'];
    $target = "../snapshots/" . $filename;
    
    if (move_uploaded_file($_FILES['snapshot']['tmp_name'], $target)) {
        echo json_encode(['success' => true, 'file' => $filename]);
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to move file']);
    }
    exit();
}
?>
