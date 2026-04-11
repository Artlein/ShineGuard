<?php

require_once 'dbconnect.php';
requireLogin(['System Admin', 'Maintenance Operator']);

if (!canDo('manage_firebase')) {
    http_response_code(403);
    die('Access denied');
}

require_once 'firebase_config.php';

function updateFirebase($endpoint, $data) {
    $url = FirebaseConfig::getUrl($endpoint);
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        error_log("Firebase update failed: HTTP $httpCode - $error");
        return false;
    }
    
    return true;
}

function setLightPower($power, $brightness = 100) {
    
    $mode = ($power === 'ON') ? 1 : 2;
    
    $controlData = [
        'mode' => $mode,
        'targetBrightness' => intval($brightness),
        'commandTimestamp' => round(microtime(true) * 1000) 
    ];
    
    if (updateFirebase('control', $controlData)) {
        $modeText = $mode === 1 ? 'FORCE_ON' : 'FORCE_OFF';
        echo json_encode([
            'success' => true, 
            'message' => "Command sent: $power at $brightness%",
            'mode' => $mode,
            'modeText' => $modeText
        ]);

        logControlCommand('power', $power, $brightness);
        
        return true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update Firebase']);
        return false;
    }
}

function setBrightness($brightness) {
    $controlData = [
        'targetBrightness' => intval($brightness),
        'commandTimestamp' => round(microtime(true) * 1000)
    ];
    
    if (updateFirebase('control', $controlData)) {
        echo json_encode(['success' => true, 'message' => "Brightness set to $brightness%"]);

        logControlCommand('brightness', $brightness);
        
        return true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update Firebase']);
        return false;
    }
}

function setControlMode($mode) {
    $mode = intval($mode);
    
    if ($mode < 0 || $mode > 2) {
        echo json_encode(['success' => false, 'message' => 'Invalid mode. Use 0=AUTO, 1=FORCE_ON, 2=FORCE_OFF']);
        return false;
    }
    
    $controlData = [
        'mode' => $mode,
        'commandTimestamp' => round(microtime(true) * 1000)
    ];
    
    if (updateFirebase('control', $controlData)) {
        $modeNames = ['AUTO', 'FORCE_ON', 'FORCE_OFF'];
        echo json_encode([
            'success' => true, 
            'message' => "Control mode set to {$modeNames[$mode]}",
            'mode' => $mode,
            'modeText' => $modeNames[$mode]
        ]);

        logControlCommand('mode', $modeNames[$mode]);
        
        return true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update Firebase']);
        return false;
    }
}

function getFirebaseStatus() {
    try {
        $actuator = @json_decode(file_get_contents(FirebaseConfig::getUrl('actuator')), true);
        $sensor = @json_decode(file_get_contents(FirebaseConfig::getUrl('sensor')), true);
        $health = @json_decode(file_get_contents(FirebaseConfig::getUrl('health')), true);
        $control = @json_decode(file_get_contents(FirebaseConfig::getUrl('control')), true);
        
        return [
            'success' => true,
            'actuator' => $actuator ?? [],
            'sensor' => $sensor ?? [],
            'health' => $health ?? [],
            'control' => $control ?? [],
            'timestamp' => time()
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to fetch Firebase data: ' . $e->getMessage()
        ];
    }
}

function logControlCommand($action, $value1 = null, $value2 = null) {
    global $conn;
    
    try {
        $user = $_SESSION['username'] ?? 'system';
        $nodeId = 'SG-NODE2';
        
        $stmt = $conn->prepare("
            INSERT INTO control_logs (node_id, action, value1, value2, user, timestamp)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt) {
            $stmt->bind_param("sssss", $nodeId, $action, $value1, $value2, $user);
            $stmt->execute();
            $stmt->close();
        }

        // Also log to centralized activity_logs
        $details = "Control Action: $action | Node: $nodeId | Value1: $value1" . ($value2 ? " | Value2: $value2" : "");
        logActivity($conn, $_SESSION['user_id'], 'Streetlight Control', $details);

    } catch (Exception $e) {
        error_log("Failed to log control command: " . $e->getMessage());
    }
}

function emergencyStop() {
    $controlData = [
        'mode' => 2, 
        'targetBrightness' => 0,
        'commandTimestamp' => round(microtime(true) * 1000),
        'emergencyStop' => true
    ];
    
    if (updateFirebase('control', $controlData)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Emergency stop activated - All lights OFF'
        ]);
        
        logControlCommand('emergency_stop', 'ACTIVATED');
        return true;
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to execute emergency stop']);
        return false;
    }
}

function batchControl($commands) {
    $results = [];
    
    foreach ($commands as $cmd) {
        $action = $cmd['action'] ?? '';
        
        switch ($action) {
            case 'power':
                $results[] = setLightPower($cmd['power'], $cmd['brightness'] ?? 100);
                break;
            case 'brightness':
                $results[] = setBrightness($cmd['brightness']);
                break;
            case 'mode':
                $results[] = setControlMode($cmd['mode']);
                break;
        }

        usleep(200000); 
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Batch commands executed',
        'results' => $results
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' || !empty($_GET['action'])) {
    checkRateLimit('firebase_control', 20, 1); 
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'power':
            $power = strtoupper($_POST['power'] ?? $_GET['power'] ?? 'ON');
            $brightness = intval($_POST['brightness'] ?? $_GET['brightness'] ?? 100);

            if (!in_array($power, ['ON', 'OFF'])) {
                echo json_encode(['success' => false, 'message' => 'Power must be ON or OFF']);
                break;
            }
            if ($brightness < 0 || $brightness > 100) {
                echo json_encode(['success' => false, 'message' => 'Brightness must be 0-100']);
                break;
            }
            
            setLightPower($power, $brightness);
            break;
            
        case 'brightness':
            $brightness = intval($_POST['brightness'] ?? $_GET['brightness'] ?? 100);
            
            if ($brightness < 0 || $brightness > 100) {
                echo json_encode(['success' => false, 'message' => 'Brightness must be 0-100']);
                break;
            }
            
            setBrightness($brightness);
            break;
            
        case 'mode':
            $mode = intval($_POST['mode'] ?? $_GET['mode'] ?? 0);
            setControlMode($mode);
            break;
            
        case 'status':
            echo json_encode(getFirebaseStatus());
            break;
            
        case 'emergency_stop':
            emergencyStop();
            break;
            
        case 'batch':
            $commands = json_decode($_POST['commands'] ?? '[]', true);
            if (is_array($commands) && count($commands) > 0) {
                batchControl($commands);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid batch commands']);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action',
                'available_actions' => ['power', 'brightness', 'mode', 'status', 'emergency_stop', 'batch']
            ]);
    }
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Firebase Control API</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1e1e1e; color: #d4d4d4; }
        h1 { color: #4ec9b0; }
        .endpoint { background: #2d2d2d; padding: 15px; margin: 10px 0; border-radius: 5px; }
        .method { color: #dcdcaa; font-weight: bold; }
        .param { color: #9cdcfe; }
        code { color: #ce9178; }
    </style>
</head>
<body>
    <h1>Firebase Control API Documentation</h1>
    
    <div class="endpoint">
        <h3><span class="method">POST</span> /firebase_control.php?action=power</h3>
        <p>Turn light ON or OFF</p>
        <p>Parameters:</p>
        <ul>
            <li><span class="param">power</span>: "ON" or "OFF" (required)</li>
            <li><span class="param">brightness</span>: 0-100 (default: 100)</li>
        </ul>
        <p>Example: <code>?action=power&power=ON&brightness=70</code></p>
    </div>
    
    <div class="endpoint">
        <h3><span class="method">POST</span> /firebase_control.php?action=brightness</h3>
        <p>Set brightness level</p>
        <p>Parameters:</p>
        <ul>
            <li><span class="param">brightness</span>: 0-100 (required)</li>
        </ul>
        <p>Example: <code>?action=brightness&brightness=50</code></p>
    </div>
    
    <div class="endpoint">
        <h3><span class="method">POST</span> /firebase_control.php?action=mode</h3>
        <p>Set control mode</p>
        <p>Parameters:</p>
        <ul>
            <li><span class="param">mode</span>: 0=AUTO, 1=FORCE_ON, 2=FORCE_OFF (required)</li>
        </ul>
        <p>Example: <code>?action=mode&mode=1</code></p>
    </div>
    
    <div class="endpoint">
        <h3><span class="method">GET</span> /firebase_control.php?action=status</h3>
        <p>Get current Firebase status (all data)</p>
        <p>Returns: JSON with actuator, sensor, health, and control data</p>
    </div>
    
    <div class="endpoint">
        <h3><span class="method">POST</span> /firebase_control.php?action=emergency_stop</h3>
        <p>Emergency stop - immediately turn off all lights</p>
        <p>No parameters required</p>
    </div>
</body>
</html>