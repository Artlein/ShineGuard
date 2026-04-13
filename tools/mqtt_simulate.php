<?php

/**
 * SHINEGUARD MQTT SIMULATOR
 * This script mimics a real Smart Pole node.
 * 
 * Usage: php mqtt_simulate.php --node=1
 */

require_once __DIR__ . '/../includes/phpMQTT.php';

$server   = 'broker.emqx.io';
$port     = 1883;
$username = '';
$password = '';

// Get Node ID from CLI
$options = getopt("", ["node:"]);
$node_id = isset($options['node']) ? (int)$options['node'] : 1;

$client_id = "shineguard_sim_node_" . $node_id . "_" . uniqid();
$mqtt = new phpMQTT($server, $port, $client_id);

if (!$mqtt->connect(true, NULL, $username, $password)) {
    exit("Connection failed!");
}

echo "[SIMULATOR] Node #$node_id Active. Sending telemetry every 5 seconds...\n";

while(true) {
    $lux  = rand(20, 100);
    $v    = round(rand(220, 240) / 100, 2); 
    $a    = round(rand(10, 50) / 1000, 2);  
    $temp = rand(28, 35);
    
    $payload = json_encode([
        "lux" => $lux,
        "v"   => $v,
        "a"   => $a,
        "t"   => $temp
    ]);

    $topic = "hulo/sensors/$node_id/telemetry";
    $mqtt->publish($topic, $payload, 0);
    
    echo "[PUSH] Topic: $topic | Payload: $payload\n";
    
    sleep(5);
}

$mqtt->close();
