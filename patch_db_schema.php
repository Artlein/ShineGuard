<?php
require_once 'dbconnect.php';

echo "<h1>🚀 EMERGENCY DATABASE SCHEMA PATCH</h1>";

// 1. ADD HUMIDITY TO SENSOR_DATA
echo "Checking sensor_data table...<br>";
$checkColumn = $conn->query("SHOW COLUMNS FROM sensor_data LIKE 'humidity'");
if ($checkColumn->num_rows === 0) {
    echo "Adding 'humidity' column...<br>";
    if ($conn->query("ALTER TABLE sensor_data ADD COLUMN humidity decimal(5,2) DEFAULT NULL AFTER temperature")) {
        echo "✅ Humidity added successfully.<br>";
    } else {
        echo "❌ FAILED to add humidity: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Humidity column already exists.<br>";
}

// 2. ADD PREDICTIVE COLUMNS TO STREETLIGHTS
echo "Checking streetlights table...<br>";
$checkHealth = $conn->query("SHOW COLUMNS FROM streetlights LIKE 'lamp_health'");
if ($checkHealth->num_rows === 0) {
    echo "Adding predictive columns...<br>";
    if ($conn->query("ALTER TABLE streetlights ADD COLUMN lamp_health varchar(50) DEFAULT 'STABLE' AFTER runtime_hours, ADD COLUMN maintenance_alert text DEFAULT NULL AFTER lamp_health")) {
        echo "✅ Predictive columns added successfully.<br>";
    } else {
        echo "❌ FAILED to add predictive columns: " . $conn->error . "<br>";
    }
} else {
    echo "✅ Predictive columns already exist.<br>";
}

echo "<h3>🏁 Patching Complete. You can now run the Sync again.</h3>";
?>
