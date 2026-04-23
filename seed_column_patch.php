<?php
/**
 * SHINEGUARD EMERGENCY MIGRATION PATCH
 * Target: AWS Cloud Database Synchronization
 * Task: Rename 'snapshot_hash' to 'seed_hash' in 'backup_registry' table
 */
require_once 'dbconnect.php';

// Only allow System Admins to run this
if (getUserRole() !== 'System Admin') {
    die("Access Denied: Administrative privileges required for schema migration.");
}

echo "<h2>ShineGuard Forensic Schema Migration</h2>";
echo "<p>Synchronizing data terminology to 'Seed' architecture...</p>";

$sql = "ALTER TABLE backup_registry CHANGE snapshot_hash seed_hash VARCHAR(64) NOT NULL";

try {
    if ($conn->query($sql)) {
        echo "<div style='color: #10b981; font-weight: bold;'>✅ SUCCESS: Column 'snapshot_hash' successfully renamed to 'seed_hash'.</div>";
        echo "<p>Your AWS forensic engine is now fully modernized.</p>";
    } else {
        echo "<div style='color: #ef4444; font-weight: bold;'>❌ ERROR: Migration failed.</div>";
        echo "<p>Detail: " . $conn->error . "</p>";
        echo "<p>Note: This might happen if the migration was already applied.</p>";
    }
} catch (Exception $e) {
    echo "<div style='color: #f59e0b; font-weight: bold;'>⚠️ NOTICE: schema might already be updated.</div>";
    echo "<p>Detail: " . $e->getMessage() . "</p>";
}

echo "<br><hr><p><a href='settings.php'>Return to Settings</a></p>";
?>
