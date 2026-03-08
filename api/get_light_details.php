<?php
require_once '../dbconnect.php';
requireLoginApi(); 

header('Content-Type: application/json');

$light_id = intval($_GET['id'] ?? 0);

if ($light_id <= 0) {
    echo json_encode(['error' => 'Invalid light ID']);
    exit();
}

$stmt = $conn->prepare("SELECT * FROM streetlights WHERE light_id = ?");
$stmt->bind_param("i", $light_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Streetlight not found']);
    exit();
}

$light = $result->fetch_assoc();

$light['installation_date'] = $light['installation_date'] ? date('M d, Y', strtotime($light['installation_date'])) : 'N/A';
$light['last_maintenance'] = $light['last_maintenance'] ? date('M d, Y', strtotime($light['last_maintenance'])) : 'N/A';

echo json_encode($light);

$stmt->close();
$conn->close();
?>
