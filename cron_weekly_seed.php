<?php
/**
 * SHINEGUARD WEEKLY SEED AUTOMATION
 * Purpose: Generates a forensic database seed every week.
 * Integration: Add this to crontab or Task Scheduler.
 * Usage: php cron_weekly_seed.php
 */

// 1. Ensure absolute path for CLI execution
$base_dir = dirname(__FILE__);
require_once $base_dir . '/dbconnect.php';
require_once $base_dir . '/src/Services/MaintenanceService.php';
require_once $base_dir . '/src/Services/AuditService.php';
require_once $base_dir . '/src/Services/IdentityService.php';

use ShineGuard\Services\MaintenanceService;

echo "--- SHINEGUARD AUTOMATED SEED GENERATOR ---\n";
echo "Date: " . date('Y-m-d H:i:s') . "\n";

// 2. Identify System Admin user for the log (usually ID 1)
$admin_id = 1; 

// 3. Execute Seed Generation with SBA Bypass
$notes = "Automated Weekly Forensic Seed (Cron Triggered)";
$result = MaintenanceService::generateForensicSeed($conn, $admin_id, $notes, true);

if ($result['success']) {
    echo "SUCCESS: Seed generated successfully.\n";
    echo "Filename: " . $result['filename'] . "\n";
    echo "Forensic Hash: " . $result['hash'] . "\n";
} else {
    echo "FAILURE: " . ($result['message'] ?? 'Unknown Error') . "\n";
    exit(1);
}

echo "-------------------------------------------\n";
?>
