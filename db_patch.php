<?php
/**
 * 🛠️ ShineGuard Database Schema Patch (v3.1)
 * This script hardens the MySQL schema on the production AWS server.
 * Specifically fixes column overflows for high-resolution 12-bit ADC data (0-4095).
 */

require_once 'dbconnect.php';

echo "<h2>🔧 Applying Database Schema Patch...</h2>";

$queries = [
    // 1. Expand Brightness Level for 12-bit ADC data
    "ALTER TABLE sensor_data MODIFY brightness_level DECIMAL(10,2);",
];

// 2. Add RUL Estimate to alerts (Programmatic check for MySQL Compatibility)
$check = $conn->query("SHOW COLUMNS FROM alerts LIKE 'rul_estimate'");
if ($check->num_rows === 0) {
    $queries[] = "ALTER TABLE alerts ADD COLUMN rul_estimate VARCHAR(50) DEFAULT 'N/A' AFTER status;";
}

// 3. Optional Indices
$queries[] = "ALTER TABLE sensor_data ADD INDEX IF NOT EXISTS idx_timestamp (timestamp);";

$successCount = 0;
foreach ($queries as $sql) {
    echo "Executing: <code style='background:#eee; padding:2px 5px;'>$sql</code> ... ";
    if ($conn->query($sql)) {
        echo "<span style='color:green;'>SUCCESS</span><br>";
        $successCount++;
    } else {
        echo "<span style='color:red;'>FAILED or ALREADY APPLIED: " . $conn->error . "</span><br>";
    }
}

echo "<hr>";
echo "<p style='font-size:1.2rem; font-weight:bold; color:#10b981;'>✅ Patching complete. $successCount operations attempted.</p>";
echo "<p>Please return to the Dashboard and click <b>'Sync MySQL'</b> again.</p>";

// Self-destruct for security
unlink(__FILE__);
?>
