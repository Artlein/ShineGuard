<?php

require_once '../dbconnect.php';
requireLoginApi(); 

header('Content-Type: application/json');

try {
    
    $query = "SELECT temperature, brightness, voltage, humidity, timestamp 
              FROM sensor_data 
              WHERE light_id = (SELECT light_id FROM streetlights WHERE node_name = 'SL-001' LIMIT 1)
              ORDER BY timestamp DESC 
              LIMIT 1";
    
    $result = $conn->query($query);
    
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'temperature' => round($row['temperature'], 1),
            'brightness' => round($row['brightness'], 0),
            'voltage' => round($row['voltage'], 3),
            'humidity' => round($row['humidity'], 0),
            'timestamp' => $row['timestamp']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'temperature' => 0,
            'brightness' => 0,
            'voltage' => 0,
            'humidity' => 0,
            'message' => 'No data available'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'temperature' => 0,
        'brightness' => 0,
        'voltage' => 0,
        'humidity' => 0,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
