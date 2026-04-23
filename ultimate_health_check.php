<?php
require_once 'dbconnect.php';
require_once 'firebase_config.php';

echo "<style>body{font-family:sans-serif;line-height:1.6;} .ok{color:green;font-weight:bold;} .fail{color:red;font-weight:bold;}</style>";
echo "<h1>🚀 SHINEGUARD ULTIMATE HEALTH AUDIT</h1>";

$errors = 0;

// 1. Database Schema
echo "<h3>1. Database Schema Consistency</h3>";
$columns = $conn->query("SHOW COLUMNS FROM sensor_data");
$fields = [];
while($c = $columns->fetch_assoc()) $fields[] = $c['Field'];

$required = ['light_id', 'brightness_level', 'current_consumption', 'voltage', 'temperature', 'humidity'];
foreach($required as $r) {
    if(in_array($r, $fields)) {
        echo "✅ Field '$r': <span class='ok'>FOUND</span><br>";
    } else {
        echo "❌ Field '$r': <span class='fail'>MISSING</span><br>";
        $errors++;
    }
}

// 2. Node Mapping
echo "<h3>2. IoT Node Mapping</h3>";
$devices = FirebaseConfig::getAllIoTDevices();
foreach($devices as $node => $cfg) {
    $mysqlNode = $cfg['node_id'];
    $check = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $check->bind_param("s", $mysqlNode);
    $check->execute();
    $res = $check->get_result();
    if($res->num_rows > 0) {
        $row = $res->fetch_assoc();
        echo "✅ Node '$node' -> MySQL '$mysqlNode' (ID: {$row['light_id']}): <span class='ok'>ALIGNED</span><br>";
    } else {
        echo "❌ Node '$node' -> MySQL '$mysqlNode': <span class='fail'>NOT FOUND IN streetlights TABLE!</span><br>";
        $errors++;
    }
}

// 3. Firebase Connectivity (Dynamic)
echo "<h3>3. Firebase Connectivity</h3>";
foreach(['SG-NODE2', 'SG-NODE3'] as $node) {
    $url = FirebaseConfig::getUrl('sensor', $node);
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    $res = curl_exec($ch);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if($http === 200) {
        echo "✅ Firebase connection for $node: <span class='ok'>SUCCESS</span><br>";
    } else {
        echo "❌ Firebase connection for $node: <span class='fail'>FAILED (HTTP $http)</span><br>";
        $errors++;
    }
}

echo "<h3>4. Environment Variables</h3>";
$keys = ['FIREBASE_PROJECT_ID', 'FIREBASE_DB_URL', 'FIREBASE_API_KEY'];
foreach($keys as $k) {
    $val = FirebaseConfig::getConstant($k);
    if($val && $val !== '') {
        $masked = substr($val, 0, 5) . "..." . substr($val, -3);
        echo "✅ VARIABLE '$k': <span class='ok'>LOADED ($masked)</span><br>";
    } else {
        echo "❌ VARIABLE '$k': <span class='fail'>EMPTY! Check .env on server</span><br>";
        $errors++;
    }
}

echo "<hr>";
if($errors === 0) {
    echo "<h2 style='color:green'>🏆 ALL SYSTEMS NOMINAL. NO ERRORS DETECTED.</h2>";
} else {
    echo "<h2 style='color:red'>⚠️ AUDIT FAILED. $errors errors found.</h2>";
}
?>
