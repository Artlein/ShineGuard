<?php
require_once 'dbconnect.php';

echo "<h1>🛠️ FINAL DATABASE ALIGNMENT</h1>";

// 1. ADD MISSING HUMIDITY COLUMN
echo "Step 1: Checking for humidity...<br>";
$check = $conn->query("SHOW COLUMNS FROM sensor_data LIKE 'humidity'");
if ($check->num_rows === 0) {
    echo "Adding 'humidity' column to sensor_data...<br>";
    if ($conn->query("ALTER TABLE sensor_data ADD COLUMN humidity decimal(5,2) DEFAULT 0.00 AFTER temperature")) {
        echo "✅ SUCCESS: Humidity added.<br>";
    } else {
        echo "❌ FAILED: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Humidity already exists.<br>";
}

// 2. CHECK PREDICTIVE COLUMNS
echo "Step 2: Checking predictive columns in streetlights...<br>";
$checkHealth = $conn->query("SHOW COLUMNS FROM streetlights LIKE 'lamp_health'");
if ($checkHealth->num_rows === 0) {
    echo "Adding lamp_health and maintenance_alert...<br>";
    $sq = "ALTER TABLE streetlights 
           ADD COLUMN lamp_health varchar(50) DEFAULT 'STABLE' AFTER runtime_hours, 
           ADD COLUMN maintenance_alert text DEFAULT NULL AFTER lamp_health";
    if ($conn->query($sq)) {
        echo "✅ SUCCESS: Predictive columns added.<br>";
    } else {
        echo "❌ FAILED: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Predictive columns exist.<br>";
}

echo "<h3>🏁 ALIGNMENT COMPLETE!</h3>";
echo "<p>You can now run the Sync script and it will finally work.</p>";
?>
