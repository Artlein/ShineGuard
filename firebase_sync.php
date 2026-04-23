<?php

require_once 'dbconnect.php';

if (php_sapi_name() !== 'cli') {
    requireLogin(['System Admin', 'Maintenance Operator']);
    if (!canDo('manage_firebase')) {
        die('Access denied');
    }
}

require_once 'firebase_config.php';

function fetchFirebaseData($endpoint, $nodeId = 'SG-NODE2') {
    $url = FirebaseConfig::getUrl($endpoint, $nodeId);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return null;
    }
    
    return json_decode($response, true);
}

/**
 * Sync data for a specific node
 */
function syncSpecificNode($conn, $nodeId) {
    echo "🔍 Processing Node: {$nodeId}...\n";
    
    // Attempt all sync operations regardless of individual failures
    $sensorRes = syncSensorData($conn, $nodeId);
    $healthRes = syncHealthData($conn, $nodeId);
    $predRes   = syncPredictiveData($conn, $nodeId);
    
    return ($sensorRes || $healthRes || $predRes);
}

/**
 * Sync sensor data from Firebase to MySQL
 */
function syncSensorData($conn, $nodeId = 'SG-NODE2') {
    $sensorData = fetchFirebaseData('sensor', $nodeId);
    
    if ($sensorData === null) {
        echo "❌ [{$nodeId}] Failed to fetch sensor data\n";
        return false;
    }
    
    // Get MySQL node ID
    $mysqlNode = FirebaseConfig::getMySQLNode($nodeId);
    $stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $stmt->bind_param("s", $mysqlNode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "❌ [{$nodeId}] MySQL Node $mysqlNode not found\n";
        return false;
    }
    
    $row = $result->fetch_assoc();
    $light_id = $row['light_id'];
    
    $ldrData = $sensorData['ldrData'] ?? 0;
    // System-wide update: Store Raw ADC Value (0-4095) instead of percentage
    $brightness = (float)$ldrData; 
    $temperature = $sensorData['temperature'] ?? 0;
    $voltage = $sensorData['voltage'] ?? 0;
    $humidity = $sensorData['humidity'] ?? 0;
    
    // Use actual current if provided, else fall back to calculation
    $current = $sensorData['current'] ?? ($voltage > 0 ? ($voltage / 220) * 0.5 : 0);
    
    $insertStmt = $conn->prepare("INSERT INTO sensor_data 
        (light_id, brightness_level, current_consumption, voltage, temperature, humidity) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $insertStmt->bind_param("iddddd", $light_id, $brightness, $current, $voltage, $temperature, $humidity);
    
    if ($insertStmt->execute()) {
        $new_id = $conn->insert_id;
        echo "✅ [{$nodeId}] Sensor data synced to MySQL (ID: $new_id)\n";
        checkThresholds($conn, $light_id, $brightness, $temperature, $current, $voltage, $humidity);
        return true;
    } else {
        echo "❌ [{$nodeId}] MySQL INSERT FAILED: " . $insertStmt->error . "\n";
    }
    return false;
}

/**
 * Sync actuator/control data from Firebase to MySQL
 */
function syncActuatorData($conn, $nodeId = 'SG-NODE2') {
    $actuatorData = fetchFirebaseData('actuator', $nodeId);
    $controlData = fetchFirebaseData('control', $nodeId);
    
    if ($actuatorData === null) return false;
    
    $mysqlNode = FirebaseConfig::getMySQLNode($nodeId);
    
    // Support multiple hardware iterations (lightOn vs lampStatus)
    $lightOn = $actuatorData['lightOn'] ?? ($actuatorData['lampStatus'] === 'ON' ? true : false);
    $brightnessPercent = $actuatorData['brightnessPercent'] ?? 100;
    $currentMode = $actuatorData['currentMode'] ?? ($controlData['mode'] ?? 0);
    
    $powerState = $lightOn ? 'ON' : 'OFF';
    $stmt = $conn->prepare("UPDATE streetlights 
        SET power_state = ?, dimming_level = ?, last_updated = NOW() 
        WHERE node_name = ?");
    $stmt->bind_param("sis", $powerState, $brightnessPercent, $mysqlNode);
    
    if ($stmt->execute()) {
        echo "✅ [{$nodeId}] Actuator state synced (Mode: {$currentMode})\n";
        return true;
    }
    return false;
}

/**
 * Sync predictive analysis results
 */
function syncPredictiveData($conn, $nodeId = 'SG-NODE2') {
    $predictive = fetchFirebaseData('predictive', $nodeId);
    if ($predictive === null) return false;

    $mysqlNode = FirebaseConfig::getMySQLNode($nodeId);
    $health = $predictive['lampHealth'] ?? 'STABLE';
    $alert = $predictive['maintenanceAlert'] ?? null;

    $stmt = $conn->prepare("UPDATE streetlights SET lamp_health = ?, maintenance_alert = ? WHERE node_name = ?");
    $stmt->bind_param("sss", $health, $alert, $mysqlNode);
    $stmt->execute();
    
    // Create Alert entry if critical maintenance is needed
    if ($alert && $alert !== 'None' && $alert !== 'None (Node Power Stable)') {
        echo "📢 [{$nodeId}] Processing Maintenance Alert: $alert\n";
        // Find light_id for this node
        $idStmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
        $idStmt->bind_param("s", $mysqlNode);
        $idStmt->execute();
        $res = $idStmt->get_result()->fetch_assoc();
        if ($res) {
            $severity = ($health === 'REPLACE') ? 'High' : 'Medium';
            $type = ($health === 'REPLACE') ? 'Fault' : 'Predictive';
            createAlert($conn, $res['light_id'], $type, $severity, "[Hardware Analytics] " . $alert);
        }
    }

    echo "✅ [{$nodeId}] Predictive data synchronized\n";
    return true;
}

/**
 * Sync health status from Firebase to MySQL
 */
function syncHealthData($conn, $nodeId = 'SG-NODE2') {
    $healthData = fetchFirebaseData('health', $nodeId);
    if ($healthData === null) return false;
    
    $mysqlNode = FirebaseConfig::getMySQLNode($nodeId);
    $stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $stmt->bind_param("s", $mysqlNode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) return false;
    $row = $result->fetch_assoc();
    $light_id = $row['light_id'];
    
    // Check various health metrics (Ignoring 'ON'/'OFF' status messages)
    $metrics = [
        'dhtStatus' => 'Sensor integration warning',
        'envTempStatus' => 'Sensors health alert',
        'envHumidityStatus' => 'Environment humidity fault',
        'lampStatus' => 'Lamp hardware fault'
    ];

    foreach ($metrics as $key => $msg) {
        $val = $healthData[$key] ?? 'OK';
        if ($val !== 'OK' && $val !== 'NORMAL' && $val !== 'None') {
            $severity = 'Medium';
            $type = 'Warning';
            
            if (stripos($val, 'CRITICAL') !== false || stripos($val, 'FAULT') !== false || stripos($val, 'FAILURE') !== false) {
                $severity = 'High';
                $type = 'Fault';
            }
            
            createAlert($conn, $light_id, $type, $severity, "[Hardware Health] {$msg}: {$val}");
        }
    }
    
    echo "✅ [{$nodeId}] Health synchronization complete\n";
    return true;
}

/**
 * Check sensor thresholds and create alerts
 */
function checkThresholds($conn, $light_id, $brightness, $temperature, $current, $voltage, $humidity) {
    // If telemetry shows perfect 0s for V, I, and Temp, the node is likely offline/unreachable
    // Do not generate thresholds alerts for an offline node.
    if ($voltage == 0 && $current == 0 && $temperature == 0) {
        return; 
    }

    // Get all thresholds from config
    $configQuery = "SELECT config_key, config_value FROM system_config 
        WHERE config_key LIKE '%threshold%'";
    $result = $conn->query($configQuery);
    
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[$row['config_key']] = floatval($row['config_value']);
    }
    
    // Check brightness threshold (Raw High = Darker)
    $ldr_crit = $thresholds['ldr_threshold_critical'] ?? 4000;
    $ldr_warn = $thresholds['ldr_threshold_warning'] ?? 3500;
    if ($brightness > $ldr_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "Extreme Darkness/High Raw LDR detected: {$brightness} val (threshold: {$ldr_crit}). Check sensors or node status.");
    } elseif ($brightness > $ldr_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High Raw LDR detected (Very Dark): {$brightness} val (threshold: {$ldr_warn})");
    }
    
    // Check temperature threshold (Higher is worse)
    $temp_crit = $thresholds['temperature_threshold_critical'] ?? 55;
    $temp_warn = $thresholds['temperature_threshold_max'] ?? 45;
    if ($temperature > $temp_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High temperature detected: {$temperature}°C (threshold: {$temp_crit}°C). Cooling issue suspected.");
    } elseif ($temperature > $temp_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High temperature detected: {$temperature}°C (threshold: {$temp_warn}°C)");
    }
    
    // Check current threshold 
    $cur_crit = $thresholds['current_threshold_critical'] ?? 0.7;
    $cur_warn = $thresholds['current_threshold_max'] ?? 0.5;
    $cur_min  = $thresholds['current_min_threshold'] ?? 0.015;

    if ($current > $cur_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High current/Overload detected: {$current} A (threshold: {$cur_crit} A).");
    } elseif ($current > $cur_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "Current above expected range: {$current} A (threshold: {$cur_warn} A)");
    } elseif ($current < $cur_min && $brightness > 100) { 
        // Note: $brightness here is actually the LDR Raw value (High=Dark). 
        // We check if it's dark but current is 0 -> Lamp Fault.
        // Actually, we should check Actuator state, but for now we look at current vs min.
        createAlert($conn, $light_id, 'Fault', 'High', "Lamp Failure detected: Current is too low ({$current} A) while light should be active. Possible burned bulb.");
    }
    
    // Check voltage threshold (Lower is worse)
    $volt_crit = $thresholds['voltage_threshold_critical'] ?? 1.5;
    $volt_warn = $thresholds['voltage_threshold_min'] ?? 2.0;
    if ($voltage < $volt_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "Low voltage detected: {$voltage} V (threshold: {$volt_crit} V). Battery may need replacement.");
    } elseif ($voltage < $volt_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "Low voltage detected: {$voltage} V (threshold: {$volt_warn} V)");
    }
    
    // Check humidity threshold (Higher is worse)
    $hum_crit = $thresholds['humidity_threshold_critical'] ?? 90;
    $hum_warn = $thresholds['humidity_threshold_max'] ?? 80;
    if ($humidity > $hum_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High humidity detected: {$humidity}% (threshold: {$hum_crit}%). Check environmental sealing.");
    } elseif ($humidity > $hum_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High humidity detected: {$humidity}% (threshold: {$hum_warn}%)");
    }
}

/**
 * Create alert in database
 */
function createAlert($conn, $light_id, $type, $severity, $description) {
    // Check if similar alert already exists in the last 5 minutes (for better hardware sync)
    $checkStmt = $conn->prepare("SELECT alert_id FROM alerts 
        WHERE light_id = ? AND description = ? AND status = 'Open' 
        AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)");
    $checkStmt->bind_param("is", $light_id, $description);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "ℹ️ Recent alert exists: $description\n";
        return; // Alert already exists
    }
    
    $stmt = $conn->prepare("INSERT INTO alerts 
        (light_id, alert_type, severity, description, status) 
        VALUES (?, ?, ?, ?, 'Open')");
    $stmt->bind_param("isss", $light_id, $type, $severity, $description);
    
    if ($stmt->execute()) {
        echo "🚨 Alert created: $description\n";
    }
}


function logControlChange($conn, $nodeName, $powerState, $brightness, $mode) {
    try {
        $checkTable = $conn->query("SHOW TABLES LIKE 'control_logs'");
        if ($checkTable->num_rows === 0) {
            return; 
        }
        
        $stmt = $conn->prepare("INSERT INTO control_logs 
            (node_id, action, value1, value2, user, timestamp) 
            VALUES (?, 'sync', ?, ?, 'system', NOW())");
        
        if ($stmt) {
            $value1 = "{$powerState} @ {$brightness}%";
            $value2 = "Mode: {$mode}";
            $stmt->bind_param("sss", $nodeName, $value1, $value2);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
    }
}


function syncMySQLToFirebase($conn) {
    $query = "SELECT * FROM pending_commands WHERE status = 'pending' ORDER BY created_at ASC LIMIT 10";
    $result = $conn->query($query);
    
    if ($result->num_rows === 0) {
        return true;
    }
    
    echo "📤 Processing pending MySQL → Firebase commands...\n";
    
    while ($row = $result->fetch_assoc()) {
        $command_id = $row['command_id'];
        $node_name = $row['node_name']; // Use the specific node name from DB
        $command_type = $row['command_type'];
        $command_data = json_decode($row['command_data'], true);
        
        $success = false;
        if ($command_type === 'power') {
            $controlData = [
                'mode' => $command_data['power'] === 'ON' ? 1 : 2,
                'targetBrightness' => $command_data['brightness'] ?? 100,
                'commandTimestamp' => round(microtime(true) * 1000)
            ];
            $success = FirebaseConfig::writeData('control', $controlData, $node_name);
        }
        
        $status = $success ? 'completed' : 'failed';
        $updateStmt = $conn->prepare("UPDATE pending_commands SET status = ?, executed_at = NOW() WHERE command_id = ?");
        $updateStmt->bind_param("si", $status, $command_id);
        $updateStmt->execute();
        
        echo ($success ? "✅" : "❌") . " Command {$command_id} sent to {$node_name}\n";
    }
    
    return true;
}

/**
 * Main multi-node sync entry point
 */
function runSync($conn) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🔄 Multi-Node Firebase ⇄ MySQL Sync Execution\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $devices = FirebaseConfig::getAllIoTDevices();
    $allNodesSuccess = true;
    
    foreach ($devices as $nodeId => $device) {
        echo "▶️ Synchronizing: {$device['name']} ({$nodeId})...\n";
        if (!syncSpecificNode($conn, $nodeId)) {
            $allNodesSuccess = false;
        }
        echo "\n";
    }
    
    echo "📡 Checking Global Command Buffer...\n";
    syncMySQLToFirebase($conn);
    echo "\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    echo ($allNodesSuccess ? "✅ ALL SYSTEM NODES IN SYNC" : "⚠️ SYNC COMPLETED WITH NODE ERRORS") . "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    return $allNodesSuccess;
}

if (php_sapi_name() === 'cli' || !empty($_GET['run'])) {
    if (!empty($_GET['run'])) {
        logActivity($conn, $_SESSION['user_id'] ?? 0, 'Manual Sync', 'Triggered manual Firebase ⇄ MySQL synchronization');
    }
    runSync($conn);
    $conn->close();
}
?>