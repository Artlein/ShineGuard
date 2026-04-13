<?php
require_once 'dbconnect.php';

echo "Starting DB Migration...\n";

// Add communication_protocol
$sql1 = "ALTER TABLE streetlights ADD COLUMN IF NOT EXISTS communication_protocol ENUM('HTTP', 'MQTT') DEFAULT 'HTTP' AFTER status";
if ($conn->query($sql1)) {
    echo "✓ Added 'communication_protocol' column.\n";
} else {
    echo "✗ Error adding 'communication_protocol': " . $conn->error . "\n";
}

// Ensure last_updated exists
$sql2 = "ALTER TABLE streetlights ADD COLUMN IF NOT EXISTS last_updated TIMESTAMP NULL ON UPDATE CURRENT_TIMESTAMP AFTER communication_protocol";
if ($conn->query($sql2)) {
    echo "✓ Added 'last_updated' column.\n";
} else {
    echo "✗ Error adding 'last_updated': " . $conn->error . "\n";
}

$conn->close();
echo "Migration Complete.\n";
