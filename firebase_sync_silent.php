<?php

require_once 'dbconnect.php';

if (php_sapi_name() !== 'cli') {
    requireLogin(['System Admin', 'Maintenance Operator']);
    if (!canDo('manage_firebase')) {
        die('Access denied');
    }
}

require_once 'firebase_config.php';

date_default_timezone_set('Asia/Manila');

function processSchedules($conn) {
    $current_time = date('H:i');
    $current_day_num = date('w');
    $days_map = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
    $current_day = $days_map[$current_day_num];
    $today_date = date('Y-m-d');
    
    $stmt = $conn->prepare("SELECT * FROM schedule_presets WHERE is_active = 1 AND FIND_IN_SET(?, days_of_week) > 0");
    $stmt->bind_param("s", $current_day);
    $stmt->execute();
    $schedules = $stmt->get_result();
    
    while ($schedule = $schedules->fetch_assoc()) {
        $schedule_id = $schedule['schedule_id'];
        $time_on = substr($schedule['time_on'], 0, 5); 
        $time_off = substr($schedule['time_off'], 0, 5);
        
        if ($current_time === $time_on) {
            triggerScheduleAction($conn, $schedule_id, 'ON', $schedule['dimming_level'], $today_date);
        }
        
        if ($current_time === $time_off) {
            triggerScheduleAction($conn, $schedule_id, 'OFF', 0, $today_date);
        }
    }
}

function triggerScheduleAction($conn, $schedule_id, $action, $dimming_level, $today_date) {
    $check = $conn->prepare("SELECT id FROM schedule_executions WHERE schedule_id = ? AND executed_date = ? AND action_type = ?");
    $check->bind_param("iss", $schedule_id, $today_date, $action);
    $check->execute();
    if ($check->get_result()->num_rows > 0) return; 
    $check->close();
    
    $log = $conn->prepare("INSERT INTO schedule_executions (schedule_id, executed_date, action_type) VALUES (?, ?, ?)");
    $log->bind_param("iss", $schedule_id, $today_date, $action);
    $log->execute();
    $log->close();
    
    $power_state = ($action === 'ON') ? 'ON' : 'OFF';
    $dim_level = ($action === 'ON') ? $dimming_level : 0;
    
    $conn->query("UPDATE streetlights SET power_state = '$power_state', dimming_level = $dim_level WHERE status != 'Maintenance'");
    
    $firebaseUpdate = [
        'mode' => ($action === 'ON') ? 1 : 2, 
        'targetBrightness' => $dim_level,
        'commandTimestamp' => round(microtime(true) * 1000)
    ];
    $url = FirebaseConfig::DATABASE_URL . '/SG-NODE2/Control.json';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($firebaseUpdate));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_exec($ch);
    curl_close($ch);
    
}

processSchedules($conn);

header('Content-Type: application/json');

function fetchFirebaseDataSilent($endpoint) {
    $url = FirebaseConfig::getUrl($endpoint);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200 && $response) {
        return json_decode($response, true);
    }
    
    return null;
}

function checkThresholds($conn, $light_id, $brightness, $temperature, $current, $voltage, $humidity) {
    if ($voltage == 0 && $current == 0 && $temperature == 0) {
        return; // Node is likely offline, do not generate false hardware alerts.
    }

    $configQuery = "SELECT config_key, config_value FROM system_config WHERE config_key LIKE '%threshold%'";
    $result = $conn->query($configQuery);
    
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[$row['config_key']] = floatval($row['config_value']);
    }
}

function getThresholds($conn) {
    $query = "SELECT config_key, config_value FROM system_config 
              WHERE config_key LIKE '%threshold%'";
    $result = $conn->query($query);
    
    $thresholds = [];
    while ($row = $result->fetch_assoc()) {
        $thresholds[$row['config_key']] = floatval($row['config_value']);
    }
    
    return $thresholds;
}

function checkThresholdStatus($value, $min, $max, $critical_min, $critical_max, $type = 'high') {
    if ($type === 'high') {
        if ($critical_max && $value > $critical_max) return 'CRITICAL';
        if ($max && $value > $max) return 'WARNING';
        return 'GOOD';
    } else {
        if ($critical_min && $value < $critical_min) return 'CRITICAL';
        if ($min && $value < $min) return 'WARNING';
        return 'GOOD';
    }
}

function createPredictiveAlert($conn, $light_id, $parameter, $value, $status, $thresholds) {
    $mysqlNode = 'SL-001';
    
    if ($status === 'GOOD') return;
    
    $severity = ($status === 'CRITICAL') ? 'High' : 'Medium';
    $alert_type = ($status === 'CRITICAL') ? 'Fault' : 'Predictive';
    
    $descriptions = [
        'brightness' => "Low brightness detected: {$value} lux (threshold: {$thresholds['lux_threshold_min']} lux). Lamp may be aging.",
        'temperature' => "High temperature detected: {$value}°C (threshold: {$thresholds['temperature_threshold_max']}°C). Cooling issue suspected.",
        'current' => "High current detected: {$value}A (threshold: {$thresholds['current_threshold_max']}A). Possible overload.",
        'voltage' => "Low voltage detected: {$value}V (threshold: {$thresholds['voltage_threshold_min']}V). Battery may need replacement.",
        'humidity' => "High humidity detected: {$value}% (threshold: {$thresholds['humidity_threshold_max']}%). Check environmental sealing."
    ];
    
    $description = $descriptions[$parameter] ?? "Threshold violation detected";
    
    $checkStmt = $conn->prepare("SELECT alert_id FROM alerts 
                                  WHERE light_id = ? AND description LIKE ? AND status = 'Open' LIMIT 1");
    $likeDesc = '%' . explode(':', $description)[0] . '%';
    $checkStmt->bind_param("is", $light_id, $likeDesc);
    $checkStmt->execute();
    $result = $checkStmt->get_result();
    
    if ($result->num_rows > 0) {
        return; 
    }
    
    $rul_estimate = null;
    if ($alert_type === 'Predictive') {
        $rul_estimate = "14 days";
    }
    
    $stmt = $conn->prepare("INSERT INTO alerts (light_id, alert_type, severity, description, status, rul_estimate, created_at) 
                           VALUES (?, ?, ?, ?, 'Open', ?, NOW())");
    $stmt->bind_param("issss", $light_id, $alert_type, $severity, $description, $rul_estimate);
    $stmt->execute();
    
    if ($status === 'CRITICAL') {
        $conn->query("UPDATE streetlights SET status = 'Maintenance' WHERE light_id = $light_id");
    }
}

function syncSensorDataSilent($conn) {
    $sensorData = fetchFirebaseDataSilent('sensor');
    if (!$sensorData) return ['success' => false, 'message' => 'No sensor data'];
    
    $mysqlNode = 'SL-001';
    
    $stmt = $conn->prepare("SELECT light_id FROM streetlights WHERE node_name = ?");
    $stmt->bind_param("s", $mysqlNode);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) return ['success' => false, 'message' => 'Node not found'];
    
    $row = $result->fetch_assoc();
    $light_id = $row['light_id'];
    
    $thresholds = getThresholds($conn);
    
    $ldrData = floatval($sensorData['ldrData'] ?? 0);
    $brightness = max(0, 100 - ($ldrData / 40));
    $temperature = floatval($sensorData['temperature'] ?? 0);
    $voltage = floatval($sensorData['voltage'] ?? 0);
    $humidity = floatval($sensorData['humidity'] ?? 0);
    $current = ($voltage / 220) * 0.5;

    if ($voltage === 0.0 && $temperature === 0.0 && $humidity === 0.0) {
        return ['success' => true, 'message' => 'Hardware offline. Thresholds bypassed.'];
    }

    $brightness_status = checkThresholdStatus(
        $brightness, 
        $thresholds['lux_threshold_min'] ?? 20,
        null,
        $thresholds['lux_threshold_critical'] ?? 10,
        null,
        'low'
    );
    
    $temp_status = checkThresholdStatus(
        $temperature,
        null,
        $thresholds['temperature_threshold_max'] ?? 45,
        null,
        $thresholds['temperature_threshold_critical'] ?? 55,
        'high'
    );
    
    $voltage_status = checkThresholdStatus(
        $voltage,
        $thresholds['voltage_threshold_min'] ?? 2.0,
        null,
        $thresholds['voltage_threshold_critical'] ?? 1.5,
        null,
        'low'
    );
    
    $humidity_status = checkThresholdStatus(
        $humidity,
        null,
        $thresholds['humidity_threshold_max'] ?? 80,
        null,
        $thresholds['humidity_threshold_critical'] ?? 90,
        'high'
    );
    
    $current_status = checkThresholdStatus(
        $current,
        null,
        $thresholds['current_threshold_max'] ?? 0.5,
        null,
        $thresholds['current_threshold_critical'] ?? 0.7,
        'high'
    );
    
    createPredictiveAlert($conn, $light_id, 'brightness', $brightness, $brightness_status, $thresholds);
    createPredictiveAlert($conn, $light_id, 'temperature', $temperature, $temp_status, $thresholds);
    createPredictiveAlert($conn, $light_id, 'voltage', $voltage, $voltage_status, $thresholds);
    createPredictiveAlert($conn, $light_id, 'humidity', $humidity, $humidity_status, $thresholds);
    createPredictiveAlert($conn, $light_id, 'current', $current, $current_status, $thresholds);
    
    $stmt = $conn->prepare("INSERT INTO sensor_data (light_id, brightness_level, current_consumption, voltage, temperature, timestamp) 
        VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("idddd", $light_id, $brightness, $current, $voltage, $temperature);
    $stmt->execute();
    
    return [
        'success' => true,
        'brightness_status' => $brightness_status,
        'temp_status' => $temp_status,
        'voltage_status' => $voltage_status,
        'humidity_status' => $humidity_status,
        'current_status' => $current_status
    ];
}

function syncActuatorDataSilent($conn) {
    $actuatorData = fetchFirebaseDataSilent('actuator');
    if (!$actuatorData) return ['success' => false, 'message' => 'No actuator data'];
    
    $mysqlNode = 'SL-001';
    
    $lightOn = ($actuatorData['lightOn'] ?? false) ? 'ON' : 'OFF';
    $brightnessPercent = intval($actuatorData['brightnessPercent'] ?? 70);
    
    $stmt = $conn->prepare("UPDATE streetlights SET power_state = ?, dimming_level = ? WHERE node_name = ?");
    $stmt->bind_param("sis", $lightOn, $brightnessPercent, $mysqlNode);
    $stmt->execute();
    
    return ['success' => true];
}

try {
    $sensorSync = syncSensorDataSilent($conn);
    $actuatorSync = syncActuatorDataSilent($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Sync completed with predictive maintenance',
        'sensor' => $sensorSync,
        'actuator' => $actuatorSync,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Sync failed: ' . $e->getMessage()
    ]);
}

$conn->close();
?>
