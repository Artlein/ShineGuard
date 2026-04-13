<?php
require_once 'dbconnect.php';

echo "Starting Reporting Engine Migration (Pillar 7)...\n";

// 1. Create report_archive table
$sql1 = "CREATE TABLE IF NOT EXISTS `report_archive` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `period_range` varchar(100) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_generated` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql1)) {
    echo "✓ Table 'report_archive' ready.\n";
} else {
    echo "✗ Error creating report_archive: " . $conn->error . "\n";
}

// 2. Create report_schedules table
$sql2 = "CREATE TABLE IF NOT EXISTS `report_schedules` (
  `schedule_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_type` varchar(50) NOT NULL,
  `frequency` enum('Daily', 'Weekly', 'Monthly') NOT NULL,
  `recipients` text DEFAULT NULL,
  `is_active` tinyint(1) DEFAULT 1,
  `last_run` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`schedule_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql2)) {
    echo "✓ Table 'report_schedules' ready.\n";
} else {
    echo "✗ Error creating report_schedules: " . $conn->error . "\n";
}

// Seed a demo schedule for City Council
$conn->query("INSERT IGNORE INTO report_schedules (report_type, frequency, recipients) VALUES ('System Audit', 'Weekly', 'mayor@mandaluyong.gov.ph')");

$conn->close();
echo "Migration Complete.\n";
