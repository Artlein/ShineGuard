<?php
require_once '../dbconnect.php';
requireLoginApi();

header('Content-Type: application/json');

$query = "SELECT snapshot_id, camera_id, created_at, filename FROM camera_snapshots ORDER BY created_at DESC";
$result = $conn->query($query);

$snapshots = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $snapshots[] = $row;
    }
}

echo json_encode($snapshots);
exit();
?>
