<?php
require_once __DIR__ . '/../dbconnect.php';

echo "<h1>AWS Database Repair</h1>";

$sql = "ALTER TABLE user_devices ADD COLUMN is_acknowledged TINYINT(1) DEFAULT 0 AFTER is_blocked";

if ($conn->query($sql)) {
    echo "✅ SUCCESS: Added is_acknowledged column to user_devices.<br>";
} else {
    echo "❌ ERROR: " . $conn->error . "<br>";
    if (strpos($conn->error, 'Duplicate column') !== false) {
        echo "💡 Notice: Column already exists.<br>";
    }
}

echo "<h2>Schema Verification</h2>";
$res = $conn->query("DESC user_devices");
while ($row = $res->fetch_assoc()) {
    echo "{$row['Field']} - {$row['Type']}<br>";
}
?>
