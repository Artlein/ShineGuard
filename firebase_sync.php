<?php

require_once 'dbconnect.php';
require_once 'firebase_config.php';

function fetchFirebaseData($endpoint) {
    $url = FirebaseConfig::getUrl($endpoint);
    
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

function syncSensorData($conn) {
    $sensorData = fetchFirebaseData('sensor');
    
    if ($sensorData === null) {
        echo "❌ Failed to fetch sensor data from Firebase\n";
        return false;
    }
    
    echo "📡 Sensor data from Firebase:\n";
    print_r($sensorData);

    $mysqlNode = FirebaseConfig::getMySQLNode('SG-NODE2');
    $stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $stmt->bind_param("s", $mysqlNode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo "❌ Node $mysqlNode not found in database\n";
        return false;
    }
    
    $row = $result->fetch_assoc();
    $light_id = $row['light_id'];

    $ldrData = $sensorData['ldrData'] ?? 0;
    $brightness = max(0, 100 - ($ldrData / 40)); 

    $temperature = $sensorData['temperature'] ?? 0;
    $voltage = $sensorData['voltage'] ?? 0;
    $humidity = $sensorData['humidity'] ?? 0;
    $pirMotion = $sensorData['pirMotion'] ?? 0;
    $isNight = $sensorData['isNight'] ?? false;

    $current = $voltage > 0 ? ($voltage / 220) * 0.5 : 0;

    $insertStmt = $conn->prepare("INSERT INTO sensor_data 
        (light_id, brightness_level, current_consumption, voltage, temperature) 
        VALUES (?, ?, ?, ?, ?)");
    $insertStmt->bind_param("idddd", $light_id, $brightness, $current, $voltage, $temperature);
    
    if ($insertStmt->execute()) {
        echo "✅ Sensor data synced to MySQL\n";
        echo "   └─ Brightness: {$brightness} lx (LDR: {$ldrData})\n";
        echo "   └─ Temperature: {$temperature}°C\n";
        echo "   └─ Voltage: {$voltage} V\n";
        echo "   └─ Humidity: {$humidity}%\n";
        echo "   └─ Motion: " . ($pirMotion ? "Detected" : "None") . "\n";
        echo "   └─ Night Mode: " . ($isNight ? "YES" : "NO") . "\n";

        checkThresholds($conn, $light_id, $brightness, $temperature, $current, $voltage, $humidity);
        
        return true;
    } else {
        echo "❌ Failed to insert sensor data: " . $conn->error . "\n";
        return false;
    }
}

function syncActuatorData($conn) {
    $actuatorData = fetchFirebaseData('actuator');
    $controlData = fetchFirebaseData('control');
    
    if ($actuatorData === null) {
        echo "❌ Failed to fetch actuator data from Firebase\n";
        return false;
    }
    
    echo "🎛️ Actuator data from Firebase:\n";
    print_r($actuatorData);
    
    if ($controlData !== null) {
        echo "🎮 Control data from Firebase:\n";
        print_r($controlData);
    }

    $mysqlNode = FirebaseConfig::getMySQLNode('SG-NODE2');

    $lightOn = $actuatorData['lightOn'] ?? false;
    $brightnessPercent = $actuatorData['brightnessPercent'] ?? 100;
    $currentMode = $actuatorData['currentMode'] ?? 0;

    if ($controlData !== null && isset($controlData['mode'])) {
        $currentMode = $controlData['mode'];
    }

    $modeNames = ['AUTO', 'FORCE_ON', 'FORCE_OFF'];
    $modeName = $modeNames[$currentMode] ?? 'UNKNOWN';

    $powerState = $lightOn ? 'ON' : 'OFF';
    $stmt = $conn->prepare("UPDATE streetlights 
        SET power_state = ?, dimming_level = ?, last_updated = NOW() 
        WHERE node_name = ?");
    $stmt->bind_param("sis", $powerState, $brightnessPercent, $mysqlNode);
    
    if ($stmt->execute()) {
        echo "✅ Actuator state synced to MySQL\n";
        echo "   └─ Power: {$powerState}\n";
        echo "   └─ Brightness: {$brightnessPercent}%\n";
        echo "   └─ Mode: {$modeName} ({$currentMode})\n";

        logControlChange($conn, $mysqlNode, $powerState, $brightnessPercent, $modeName);
        
        return true;
    } else {
        echo "❌ Failed to update actuator state: " . $conn->error . "\n";
        return false;
    }
}

function syncHealthData($conn) {
    $healthData = fetchFirebaseData('health');
    
    if ($healthData === null) {
        echo "❌ Failed to fetch health data from Firebase\n";
        return false;
    }
    
    echo "💊 Health data from Firebase:\n";
    print_r($healthData);

    $mysqlNode = FirebaseConfig::getMySQLNode('SG-NODE2');
    $stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $stmt->bind_param("s", $mysqlNode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        return false;
    }
    
    $row = $result->fetch_assoc();
    $light_id = $row['light_id'];

    $lampStatus = $healthData['lampStatus'] ?? 'OK';
    if ($lampStatus !== 'OK') {
        createAlert($conn, $light_id, 'Fault', 'High', 
            "Lamp fault detected on {$mysqlNode}: {$lampStatus}");
    }

    $relayStatus = $healthData['relayStatus'] ?? 'OK';
    if ($relayStatus !== 'OK') {
        createAlert($conn, $light_id, 'Fault', 'High', 
            "Relay fault detected on {$mysqlNode}: {$relayStatus}");
    }

    $envTempStatus = $healthData['envTempStatus'] ?? 'OK';
    if ($envTempStatus !== 'OK') {
        createAlert($conn, $light_id, 'Warning', 'Medium', 
            "Environmental temperature alert on {$mysqlNode}: {$envTempStatus}");
    }

    $envHumidityStatus = $healthData['envHumidityStatus'] ?? 'OK';
    if ($envHumidityStatus !== 'OK') {
        createAlert($conn, $light_id, 'Warning', 'Medium', 
            "Environmental humidity alert on {$mysqlNode}: {$envHumidityStatus}");
    }

    $lampFaultCounter = $healthData['lampFaultCounter'] ?? 0;
    $relayToggleCount = $healthData['relayToggleCount'] ?? 0;
    $highTempCounter = $healthData['highTempCounter'] ?? 0;
    $highHumidityCounter = $healthData['highHumidityCounter'] ?? 0;
    
    echo "✅ Health status checked\n";
    echo "   └─ Lamp: {$lampStatus} (Faults: {$lampFaultCounter})\n";
    echo "   └─ Relay: {$relayStatus} (Toggles: {$relayToggleCount})\n";
    echo "   └─ Env Temp: {$envTempStatus} (High count: {$highTempCounter})\n";
    echo "   └─ Env Humidity: {$envHumidityStatus} (High count: {$highHumidityCounter})\n";

    if ($lampFaultCounter >= 10) {
        createAlert($conn, $light_id, 'Fault', 'High', 
            "Lamp hardware fault limit reached on {$mysqlNode}: {$lampFaultCounter} faults recorded");
    }
    
    if ($highTempCounter >= 10) {
        createAlert($conn, $light_id, 'Fault', 'High', 
            "Temperature sensor fault limit reached on {$mysqlNode}: {$highTempCounter} errors recorded");
    }
    
    if ($highHumidityCounter >= 10) {
        createAlert($conn, $light_id, 'Fault', 'High', 
            "Humidity sensor fault limit reached on {$mysqlNode}: {$highHumidityCounter} errors recorded");
    }

    if ($relayToggleCount > 100) {
        createAlert($conn, $light_id, 'Warning', 'Medium', 
            "High relay toggle count on {$mysqlNode}: {$relayToggleCount} (may indicate instability)");
    }
    
    return true;
}

function checkThresholds($conn, $light_id, $brightness, $temperature, $current, $voltage, $humidity) {
    
    $configQuery = "SELECT config_key, config_value FROM system_config 
        WHERE config_key LIKE '%threshold%'";
    $result = $conn->query($configQuery);
    
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[$row['config_key']] = floatval($row['config_value']);
    }

    $lux_crit = $thresholds['lux_threshold_critical'] ?? 10;
    $lux_warn = $thresholds['lux_threshold_min'] ?? 20;
    if ($brightness < $lux_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "Low brightness detected: {$brightness} lx (threshold: {$lux_crit} lx). Lamp may be aging.");
    } elseif ($brightness < $lux_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "Low brightness detected: {$brightness} lx (threshold: {$lux_warn} lx)");
    }

    $temp_crit = $thresholds['temperature_threshold_critical'] ?? 55;
    $temp_warn = $thresholds['temperature_threshold_max'] ?? 45;
    if ($temperature > $temp_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High temperature detected: {$temperature}°C (threshold: {$temp_crit}°C). Cooling issue suspected.");
    } elseif ($temperature > $temp_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High temperature detected: {$temperature}°C (threshold: {$temp_warn}°C)");
    }

    $cur_crit = $thresholds['current_threshold_critical'] ?? 0.7;
    $cur_warn = $thresholds['current_threshold_max'] ?? 0.5;
    if ($current > $cur_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High current detected: {$current} A (threshold: {$cur_crit} A). Possible overload.");
    } elseif ($current > $cur_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High current detected: {$current} A (threshold: {$cur_warn} A)");
    }

    $volt_crit = $thresholds['voltage_threshold_critical'] ?? 1.5;
    $volt_warn = $thresholds['voltage_threshold_min'] ?? 2.0;
    if ($voltage < $volt_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "Low voltage detected: {$voltage} V (threshold: {$volt_crit} V). Battery may need replacement.");
    } elseif ($voltage < $volt_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "Low voltage detected: {$voltage} V (threshold: {$volt_warn} V)");
    }

    $hum_crit = $thresholds['humidity_threshold_critical'] ?? 90;
    $hum_warn = $thresholds['humidity_threshold_max'] ?? 80;
    if ($humidity > $hum_crit) {
        createAlert($conn, $light_id, 'Fault', 'High', "High humidity detected: {$humidity}% (threshold: {$hum_crit}%). Check environmental sealing.");
    } elseif ($humidity > $hum_warn) {
        createAlert($conn, $light_id, 'Predictive', 'Medium', "High humidity detected: {$humidity}% (threshold: {$hum_warn}%)");
    }
}

function createAlert($conn, $light_id, $type, $severity, $description) {
    
    $checkStmt = $conn->prepare("SELECT alert_id FROM alerts 
        WHERE light_id = ? AND description = ? AND status = 'Open' 
        AND timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $checkStmt->bind_param("is", $light_id, $description);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        echo "ℹ️ Recent alert exists: $description\n";
        return; 
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
        echo "ℹ️ No pending MySQL → Firebase commands\n";
        return true;
    }
    
    echo "📤 Processing pending MySQL → Firebase commands...\n";
    
    while ($row = $result->fetch_assoc()) {
        $command_id = $row['command_id'];
        $node_name = $row['node_name'];
        $command_type = $row['command_type'];
        $command_data = json_decode($row['command_data'], true);

        $success = false;
        
        if ($command_type === 'power') {
            $controlData = [
                'mode' => $command_data['power'] === 'ON' ? 1 : 2,
                'targetBrightness' => $command_data['brightness'] ?? 100,
                'commandTimestamp' => round(microtime(true) * 1000)
            ];
            $success = FirebaseConfig::writeData('control', $controlData);
        }

        $status = $success ? 'completed' : 'failed';
        $updateStmt = $conn->prepare("UPDATE pending_commands SET status = ?, executed_at = NOW() WHERE command_id = ?");
        $updateStmt->bind_param("si", $status, $command_id);
        $updateStmt->execute();
        
        echo ($success ? "✅" : "❌") . " Command {$command_id}: {$command_type}\n";
    }
    
    return true;
}

function runSync($conn) {
    echo "═══════════════════════════════════════════════════════════\n";
    echo "🔄 Firebase ⇄ MySQL Sync Started\n";
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "Node: SG-NODE2 → SL-001\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
    $success = true;

    echo "1️⃣ Syncing Sensor Data (Firebase → MySQL)...\n";
    if (!syncSensorData($conn)) {
        $success = false;
    }
    echo "\n";

    echo "2️⃣ Syncing Actuator Data (Firebase → MySQL)...\n";
    if (!syncActuatorData($conn)) {
        $success = false;
    }
    echo "\n";

    echo "3️⃣ Syncing Health Data (Firebase → MySQL)...\n";
    if (!syncHealthData($conn)) {
        $success = false;
    }
    echo "\n";

    echo "4️⃣ Checking Pending Commands (MySQL → Firebase)...\n";
    syncMySQLToFirebase($conn);
    echo "\n";
    
    echo "═══════════════════════════════════════════════════════════\n";
    if ($success) {
        echo "✅ Sync completed successfully!\n";
    } else {
        echo "⚠️ Sync completed with some errors\n";
    }
    echo "Time: " . date('Y-m-d H:i:s') . "\n";
    echo "═══════════════════════════════════════════════════════════\n";
    
    return $success;
}

if (php_sapi_name() === 'cli' || !empty($_GET['run'])) {
    runSync($conn);
    $conn->close();
}
?>