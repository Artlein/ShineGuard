<?php
require_once '../dbconnect.php';
requireLogin();

header('Content-Type: application/json');

if (!canDo('take_snapshots')) {
    echo json_encode(['success' => false, 'error' => 'Unauthorized: You do not have permission to take snapshots.']);
    exit();
}

$camera_id = intval($_POST['camera_id'] ?? 0);

if ($camera_id > 0 && isset($_FILES['snapshot_image']) && $_FILES['snapshot_image']['error'] === UPLOAD_ERR_OK) {
    $tmp_file = $_FILES['snapshot_image']['tmp_name'];
    $image_binary = file_get_contents($tmp_file);

    if ($image_binary === false) {
        echo json_encode(['success' => false, 'error' => 'Invalid image data']);
        exit();
    }
    
    if (strlen($image_binary) < 3
        || ord($image_binary[0]) !== 0xFF
        || ord($image_binary[1]) !== 0xD8
        || ord($image_binary[2]) !== 0xFF) {
        echo json_encode(['success' => false, 'error' => 'Only JPEG images are accepted']);
        exit();
    }
    
    if (strlen($image_binary) > 5 * 1024 * 1024) {
        echo json_encode(['success' => false, 'error' => 'Image exceeds 5 MB size limit']);
        exit();
    }

    $filename = "snapshot_cam{$camera_id}_" . date('YmdHis') . ".jpg.enc";
    $filepath = "../snapshots/" . $filename;

    if (!file_exists('../snapshots')) {
        mkdir('../snapshots', 0755, true);
    }

    $encryption_key = 'shineguard_secure_snapshot_key_!@#'; 
    $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
    $encrypted_data = openssl_encrypt($image_binary, 'aes-256-cbc', $encryption_key, 0, $iv);

    if (file_put_contents($filepath, $encrypted_data)) {
        
        $iv_hex = bin2hex($iv);

        $stmt = $conn->prepare("INSERT INTO camera_snapshots (camera_id, filename, filepath, created_at, encryption_iv) VALUES (?, ?, ?, NOW(), ?)");
        $stmt->bind_param("isss", $camera_id, $filename, $filepath, $iv_hex);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'filename' => $filename,
                'snapshot_id' => $conn->insert_id
            ]);
            
            logActivity($conn, $_SESSION['user_id'], 'Snapshot', "Saved encrypted snapshot from Camera #$camera_id");
        } else {
            echo json_encode(['success' => false, 'error' => 'Database error']);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Failed to save encrypted image file']);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid data']);
}

$conn->close();
?>
