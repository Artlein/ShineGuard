<?php
/**
 * SHINEGUARD EMERGENCY REPAIR: REPORTING INFRASTRUCTURE
 * Target: AWS Production Database
 * Task: Create 'report_archive' table
 */
require_once 'dbconnect.php';

if (getUserRole() !== 'System Admin') {
    die("Access Denied.");
}

echo "<h2>ShineGuard Reporting Infrastructure Patch</h2>";

$sql = "CREATE TABLE IF NOT EXISTS report_archive (
    report_id INT AUTO_INCREMENT PRIMARY KEY,
    report_name VARCHAR(255) NOT NULL,
    report_type VARCHAR(50) NOT NULL,
    period_range VARCHAR(100) NOT NULL,
    generated_by INT NOT NULL,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    filename VARCHAR(255) NOT NULL,
    file_hash VARCHAR(64),
    FOREIGN KEY (generated_by) REFERENCES users(user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";

try {
    if ($conn->query($sql)) {
        echo "<div style='color: #10b981; font-weight: bold;'>✅ SUCCESS: 'report_archive' table is ready.</div>";
    } else {
        echo "<div style='color: #ef4444; font-weight: bold;'>❌ ERROR: " . $conn->error . "</div>";
    }
} catch (Exception $e) {
    echo "<div style='color: #f59e0b; font-weight: bold;'>⚠️ NOTICE: " . $e->getMessage() . "</div>";
}

echo "<br><hr><p><a href='reports.php'>Return to Reports</a></p>";
?>
