<?php
require_once __DIR__ . '/../firebase_config.php';
require_once __DIR__ . '/../firebase_sync.php';

function checkNode($nodeId) {
    echo "--- Testing $nodeId ---\n";
    $health = fetchFirebaseData('health', $nodeId);
    echo "Health Data: " . json_encode($health, JSON_PRETTY_PRINT) . "\n";
    
    $pred = fetchFirebaseData('predictive', $nodeId);
    echo "Predictive Data: " . json_encode($pred, JSON_PRETTY_PRINT) . "\n";
}

checkNode('SG-NODE2');
checkNode('SG-NODE3');
