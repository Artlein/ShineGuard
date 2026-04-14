<?php
/**
 * SHINEGUARD AUDIT LOG REPAIR UTILITY
 * This script rebuilds the cryptographic hash chain for all existing logs.
 */

require_once __DIR__ . "/../dbconnect.php";
use ShineGuard\Services\AuditService;
use ShineGuard\Services\SecurityService;

echo "--- SHINEGUARD AUDIT HEALING --- \n";

// 1. Get all logs ordered by ID
$res = $conn->query("SELECT * FROM activity_logs ORDER BY log_id ASC");
$logs = $res->fetch_all(MYSQLI_ASSOC);

$count = count($logs);
echo "Analyzing {$count} log entries...\n";

$prev_hash = str_repeat('0', 64); // The "Genesis Block" seed
$repaired = 0;

foreach ($logs as $log) {
    $id = $log['log_id'];
    $uid = $log['user_id'] ?? 0;
    $action = $log['action'];
    $details = $log['details'] ?? '';
    $ip = $log['ip_address'] ?? '127.0.0.1';
    
    // Calculate what the signature SHOULD be
    $expected_signature = SecurityService::generateLogSignature($prev_hash, $uid, $action, $details, $ip);
    
    // Update the record
    $stmt = $conn->prepare("UPDATE activity_logs SET log_hash = ? WHERE log_id = ?");
    $stmt->bind_param("si", $expected_signature, $id);
    $stmt->execute();
    $stmt->close();
    
    // Move the chain forward
    $prev_hash = $expected_signature;
    $repaired++;
    
    if ($repaired % 50 === 0) {
        echo "Repaired {$repaired}/{$count} entries...\n";
    }
}

echo "\nSUCCESS: Rebuilt chain for {$repaired} logs.\n";
echo "Audit trail is now cryptographically sealed.\n";
