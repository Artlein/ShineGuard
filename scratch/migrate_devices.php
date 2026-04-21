<?php
require_once __DIR__ . '/../dbconnect.php';

// Disable error suppression for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "Attempting Migration...\n";

$sql = "ALTER TABLE user_devices ADD COLUMN is_acknowledged TINYINT(1) DEFAULT 0 AFTER is_blocked";

if ($conn->query($sql)) {
    echo "SUCCESS: Added is_acknowledged column.\n";
} else {
    echo "NOTICE: " . $conn->error . " (It might already exist)\n";
}

echo "\n--- CURRENT SCHEMA ---\n";
$res = $conn->query("DESCRIBE user_devices");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}\n";
}
?>
