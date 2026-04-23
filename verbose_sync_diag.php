<?php
// VERBOSE DEBUG SYNC
// Run this to see exactly why the sync is stopping

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'dbconnect.php';
require_once 'firebase_config.php';

echo "<pre>";
echo "🚀 STARTING VERBOSE SYNC DIAGNOSTIC\n";
echo "════════════════════════════════════════\n\n";

$nodeId = 'SG-NODE2';
echo "Step 1: Fetching sensor data for $nodeId...\n";
$sensorData = FirebaseConfig::readData('sensor', $nodeId);

if ($sensorData === null) {
    echo "❌ FAILED: Firebase returned NULL. Check your .env credentials and Internet connection.\n";
    die();
}
echo "✅ SUCCESS: Firebase returned data:\n";
print_r($sensorData);

echo "\nStep 2: Checking MySQL mapping...\n";
$mysqlNode = FirebaseConfig::getMySQLNode($nodeId);
echo "Mapped node in MySQL: $mysqlNode\n";

$stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
$stmt->bind_param("s", $mysqlNode);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();

if (!$row) {
    echo "❌ FAILED: Node '$mysqlNode' not found in MySQL 'streetlights' table.\n";
    die();
}
$light_id = $row['light_id'];
echo "✅ SUCCESS: light_id for $mysqlNode is $light_id\n";

echo "\nStep 3: Attempting Database Insert...\n";
$brightness = 75.0;
$current = $sensorData['current'] ?? 0.0;
$voltage = $sensorData['voltage'] ?? 0.0;
$temp = $sensorData['temperature'] ?? 0.0;
$hum = $sensorData['humidity'] ?? 0.0;

echo "Tracing Values:\n";
echo "- light_id: $light_id\n";
echo "- current: $current\n";
echo "- voltage: $voltage\n";
echo "- temperature: $temp\n";
echo "- humidity: $hum\n";

$insertStmt = $conn->prepare("INSERT INTO sensor_data 
    (light_id, brightness_level, current_consumption, voltage, temperature, humidity) 
    VALUES (?, ?, ?, ?, ?, ?)");

if (!$insertStmt) {
    echo "❌ SQL PREPARE ERROR: " . $conn->error . "\n";
    die();
}

$insertStmt->bind_param("iddddd", $light_id, $brightness, $current, $voltage, $temp, $hum);

if ($insertStmt->execute()) {
    echo "✅ SUCCESS: One row inserted into sensor_data table!\n";
} else {
    echo "❌ SQL EXECUTE ERROR: " . $insertStmt->error . "\n";
}

echo "\n════════════════════════════════════════\n";
echo "🏁 DIAGNOSTIC COMPLETE\n";
?>
