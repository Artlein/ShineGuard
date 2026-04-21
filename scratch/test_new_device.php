<?php
require_once 'dbconnect.php';
$token = 'TEST_DEVICE_' . time();
$ua = "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/100.0.0.0 Safari/537.36";
$ip = "127.0.0.1";
$uid = 1; // Admin

$sql = "INSERT INTO user_devices (device_token, user_id, browser_agent, last_ip, is_acknowledged, created_at, last_seen_at) 
        VALUES ('$token', $uid, '$ua', '$ip', 0, NOW(), NOW())";

if ($conn->query($sql)) {
    echo "SUCCESS: Inserted test device $token\n";
} else {
    echo "ERROR: " . $conn->error . "\n";
}
?>
