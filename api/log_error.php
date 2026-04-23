<?php
require_once __DIR__ . '/../dbconnect.php';
$data = json_decode(file_get_contents('php://input'), true);
if ($data) {
    $log = "[" . date('Y-m-d H:i:s') . "] CLIENT ERROR: " . json_encode($data) . "\n";
    file_put_contents(__DIR__ . '/../logs/client_errors.log', $log, FILE_APPEND);
}
