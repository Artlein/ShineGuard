<?php
require_once 'dbconnect.php';

echo "<h1>🔍 DATABASE INTEGRITY CHECK</h1>";

// 1. Check Streetlights
echo "<h3>1. Streetlights Check</h3>";
$res = $conn->query("SELECT light_id, node_name, status FROM streetlights");
echo "<table border='1'><tr><th>ID</th><th>Node Name</th><th>Status</th></tr>";
while($r = $res->fetch_assoc()) {
    echo "<tr><td>{$r['light_id']}</td><td>{$r['node_name']}</td><td>{$r['status']}</td></tr>";
}
echo "</table>";

// 2. Check Recent Sensor Data
echo "<h3>2. Recent Sensor Data (Last 10)</h3>";
$res = $conn->query("SELECT * FROM sensor_data ORDER BY timestamp DESC LIMIT 10");
if ($res->num_rows === 0) {
    echo "<p style='color:red'>❌ NO DATA FOUND IN sensor_data TABLE!</p>";
} else {
    echo "<table border='1'><tr><th>ID</th><th>Light ID</th><th>Lux</th><th>Current</th><th>Voltage</th><th>Timestamp</th></tr>";
    while($r = $res->fetch_assoc()) {
        echo "<tr>
            <td>{$r['id']}</td>
            <td>{$r['light_id']}</td>
            <td>{$r['brightness_level']}</td>
            <td>{$r['current_consumption']}</td>
            <td>{$r['voltage']}</td>
            <td>{$r['timestamp']}</td>
        </tr>";
    }
    echo "</table>";
}

// 3. Time Check
$dbTime = $conn->query("SELECT NOW() as now")->fetch_assoc()['now'];
echo "<h3>3. Time Synchronization</h3>";
echo "PHP Time: " . date('Y-m-d H:i:s') . "<br>";
echo "MySQL Time: " . $dbTime . "<br>";

echo "<h3>🏁 Check Complete</h3>";
?>
