<?php
$host = 'broker.emqx.io';
$port = 1883;
echo "Testing connection to $host:$port...\n";
$fp = fsockopen($host, $port, $errno, $errstr, 5);
if (!$fp) {
    echo "✗ Failed: $errstr ($errno)\n";
    
    $host2 = 'test.mosquitto.org';
    echo "Testing connection to $host2:$port...\n";
    $fp2 = fsockopen($host2, $port, $errno, $errstr, 5);
    if (!$fp2) {
        echo "✗ Failed: $errstr ($errno)\n";
    } else {
        echo "✓ Success: Connected to $host2\n";
        fclose($fp2);
    }
} else {
    echo "✓ Success: Connected to $host\n";
    fclose($fp);
}
