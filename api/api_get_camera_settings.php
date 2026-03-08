<?php
require_once '../dbconnect.php';
requireLogin();

header('Content-Type: application/json');

$camera_id = intval($_GET['camera_id'] ?? 0);

if ($camera_id > 0) {
    $stmt = $conn->prepare("SELECT * FROM cameras WHERE camera_id = ?");
    $stmt->bind_param("i", $camera_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($camera = $result->fetch_assoc()) {
        echo json_encode($camera);
    } else {
        echo json_encode(['error' => 'Camera not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid camera ID']);
}

$conn->close();
?>
