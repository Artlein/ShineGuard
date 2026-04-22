<?php
session_start();
header('Content-Type: text/plain');

echo "DIAGNOSTIC LOG DUMP\n";
echo "===================\n";
echo "Current Session User ID: " . ($_SESSION['user_id'] ?? 'NULL') . "\n";
echo "PHP Error Log Path: " . ini_get('error_log') . "\n\n";

$logFile = ini_get('error_log');
if ($logFile && file_exists($logFile)) {
    // Show last 50 lines of logs related to FAR
    $logs = shell_exec("grep 'FAR AUTH' " . escapeshellarg($logFile) . " | tail -n 50");
    echo "RECENT AUTH LOGS:\n";
    echo $logs ? $logs : "No FAR logs found in system log file.\n";
} else {
    echo "Cannot access system log file directly.\n";
}
?>
