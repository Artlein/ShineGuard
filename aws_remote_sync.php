<?php
/**
 * 🔒 SHINEGUARD REMOTE SYNC TRIGGER
 * Only to be used for emergency recovery of desynchronized AWS nodes.
 */
session_start();
header('Content-Type: text/plain');

$master_key = "SHINEGUARD_REMOTE_PULL_778899"; // Secret Key
$provided_key = $_GET['key'] ?? '';

if ($provided_key !== $master_key) {
    die("Unauthorized Access Sentinel: Remote Sync Denied.");
}

echo "🛠️ Initiating Remote Forensic Sync...\n";
$output = shell_exec("cd /var/www/html/ShineGuard && sudo git fetch --all && sudo git reset --hard origin/main 2>&1");
echo "OUTPUT:\n$output\n";

$perms = shell_exec("sudo chown -R www-data:www-data /var/www/html/ShineGuard 2>&1");
echo "PERMISSIONS:\n$perms\n";

echo "\n✅ Sync Complete at " . date('H:i:s');
?>
