<?php
require_once 'dbconnect.php';
require_once 'firebase_config.php';

header('Content-Type: application/json');

$node = $_GET['node'] ?? 'SG-NODE2';

// --- WRITE LOGIC (COMMANDS) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $path = $input['path'] ?? '';
    $data = $input['data'] ?? null;
    
    if (!$path) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing path']);
        exit;
    }

    // Direct REST PUT to Firebase from AWS Server
    $baseUrl = rtrim(FirebaseConfig::getConstant('DATABASE_URL'), '/');
    $writeUrl = $baseUrl . '/' . $node . '/' . ltrim($path, '/') . '.json';
    
    $ch = curl_init($writeUrl);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
    // Hardware expects a full JSON object for the Control node
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    
    $res = curl_exec($ch);
    echo $res;
    exit;
}

// --- READ LOGIC (TELEMETRY) ---
$baseUrl = rtrim(FirebaseConfig::getConstant('DATABASE_URL'), '/');
$readUrl = $baseUrl . '/' . $node . '.json';

$ch = curl_init($readUrl);
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
