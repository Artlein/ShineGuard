<?php
/**
 * SHINEGUARD EMERGENCY DIAGNOSTIC PATCH
 * Target: AWS Cloud Database Synchronization
 * Task: Ensure 'diagnostic_logs' table exists
 */
require_once 'dbconnect.php';

// Only allow System Admins to run this
if (getUserRole() !== 'System Admin') {
    die("Access Denied: Administrative privileges required.");
}

echo "<h2>ShineGuard Diagnostic Schema Patch</h2>";

$sql = "CREATE TABLE IF NOT EXISTS diagnostic_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    light_id INT NOT NULL,
    test_type VARCHAR(100) NOT NULL,
    result JSON NOT NULL,
    notes TEXT,
    tested_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (light_id) REFERENCES streetlights(light_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    if ($conn->query($sql)) {
        echo "<div style='color: #10b981; font-weight: bold;'>✅ SUCCESS: 'diagnostic_logs' table is ready.</div>";
    } else {
        echo "<div style='color: #ef4444; font-weight: bold;'>❌ ERROR: Failed to create table.</div>";
        echo "<p>" . $conn->error . "</p>";
    }
} catch (Exception $e) {
    echo "<div style='color: #f59e0b; font-weight: bold;'>⚠️ NOTICE: schema check encountered an issue.</div>";
    echo "<p>" . $e->getMessage() . "</p>";
}

echo "<br><hr><p><a href='streetlights.php'>Return to Dashboard</a></p>";
?>
