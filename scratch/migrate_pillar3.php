<?php
require_once 'dbconnect.php';

echo "Starting Security DB Migration (Pillar 3)...\n";

// Add log_hash to activity_logs
$sql1 = "ALTER TABLE activity_logs ADD COLUMN IF NOT EXISTS log_hash CHAR(64) DEFAULT NULL AFTER details";
if ($conn->query($sql1)) {
    echo "✓ Added 'log_hash' column to activity_logs.\n";
} else {
    echo "✗ Error adding 'log_hash': " . $conn->error . "\n";
}

// Ensure unique index for log_hash to prevent double-entries causing chain breaks
// Actually, not unique yet as existing logs are NULL.

$conn->close();
echo "Migration Complete.\n";
