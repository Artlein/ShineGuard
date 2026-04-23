<?php
require_once 'dbconnect.php';
require_once 'firebase_config.php';

header('Content-Type: application/json');

$node = $_GET['node'] ?? 'SG-NODE2';
$url = FirebaseConfig::getUrl('sensor', $node);

// Append .json if not present (though getUrl usually handles it)
if (strpos($url, '.json') === false) {
    // Basic path handling if getUrl returns base
    $url = rtrim($url, '/') . '/' . $node . '.json';
} else {
    // If it already has Sensor.json, we want the whole node for the dashboard
    $url = str_replace('Sensor.json', '.json', $url);
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

if ($httpCode !== 200) {
    http_response_code($httpCode);
    echo json_encode(['error' => 'Firebase Proxy Failed', 'code' => $httpCode]);
    exit;
}

echo $response;
?>
