<?php

/**
 * SHINEGUARD MQTT BRIDGE (Robust Version)
 * This script runs in the background and bridges MQTT messages to the MySQL database.
 */

require_once 'dbconnect.php';
// Autoloaded via Composer in dbconnect.php


$server   = $_ENV['MQTT_SERVER'] ?? 'broker.emqx.io';
$port     = (int)($_ENV['MQTT_PORT'] ?? 1883);
$username = $_ENV['MQTT_USER'] ?? '';
$password = $_ENV['MQTT_PASS'] ?? '';
$client_id = 'shineguard_bridge_' . uniqid();

function log_message($msg) {
    echo $msg . "\n";
    file_put_contents('scratch/mqtt_bridge.log', "[" . date('Y-m-d H:i:s') . "] " . $msg . "\n", FILE_APPEND);
}

while (true) {
    log_message("Attempting to connect to $server...");
    $mqtt = new phpMQTT($server, $port, $client_id);

    if ($mqtt->connect(true, NULL, $username, $password)) {
        log_message("✓ Connected to MQTT Broker.");
        
        $topics['hulo/sensors/#'] = array("qos" => 0, "function" => "onMessage");
        $mqtt->subscribe($topics, 0);

        while($mqtt->proc()) {
            // Keep alive
        }
        
        log_message("⚠ Connection lost. Retrying in 5 seconds...");
        $mqtt->close();
    } else {
        log_message("✗ Connection failed. Retrying in 10 seconds...");
    }
    
    sleep(5);
}

function onMessage($topic, $msg) {
    global $conn;
    log_message("MQTT Update: Topic [$topic] Payload: $msg");

    $parts = explode('/', $topic);
    $node_id = isset($parts[2]) ? (int)$parts[2] : 0;
    if ($node_id === 0) return;

    $data = json_decode($msg, true);
    if (!$data) return;

    $lux   = $data['lux']   ?? 0;
    $v     = $data['v']     ?? 0;
    $a     = $data['a']     ?? 0;
    $temp  = $data['t']     ?? 0;

    // Insert data
    $stmt = $conn->prepare("INSERT INTO sensor_data (light_id, brightness_level, voltage, current_consumption, temperature) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("idddd", $node_id, $lux, $v, $a, $temp);
    $stmt->execute();
    $stmt->close();

    // Update streetlight status
    $upd = $conn->prepare("UPDATE streetlights SET status = 'Active', last_updated = NOW(), communication_protocol = 'MQTT' WHERE light_id = ?");
    $upd->bind_param("i", $node_id);
    $upd->execute();
    $upd->close();

    log_message("✓ Node #$node_id updated in Database.");
}
