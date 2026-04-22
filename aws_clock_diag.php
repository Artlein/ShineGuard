<?php
header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');
echo json_encode([
    'server_time' => date('Y-m-d H:i:s'),
    'timestamp' => time(),
    'timezone' => date_default_timezone_get()
]);
?>
