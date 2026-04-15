<?php
/**
 * ShineGuard Enterprise Migration Engine
 * --------------------------------------
 * Decoupled from runtime dbconnect.php to reduce overhead.
 * Run this script via CLI: php database/run_migrations.php
 * Or via System Admin SOC interface.
 */

// Define migration mode to prevent dbconnect.php loops if needed
define('SG_MIGRATION_MODE', true);

// Get relative path to dbconnect.php
require_once __DIR__ . '/../dbconnect.php';

// Authorization Check: CLI or System Admin
$is_cli = (php_sapi_name() === 'cli');
$is_admin = (isset($_SESSION['role']) && $_SESSION['role'] === 'System Admin');

if (!$is_cli && !$is_admin) {
    http_response_code(403);
    die("Access Denied: Migration Engine requires Administrative or CLI context.");
}

echo "Starting ShineGuard Database Migrations...<br>\n";

// --- CORE: Activity Logs (Governance Pillar) ---
$conn->query("CREATE TABLE IF NOT EXISTS `activity_logs` (
  `log_id` bigint(20) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `details` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`log_id`),
  KEY `idx_user_created` (`user_id`,`created_at`),
  KEY `idx_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// Patch: Created_at
$col_check = $conn->query("SHOW COLUMNS FROM `activity_logs` LIKE 'created_at'");
if ($col_check && $col_check->num_rows === 0) {
    $conn->query("ALTER TABLE `activity_logs` ADD COLUMN `created_at` timestamp NOT NULL DEFAULT current_timestamp()");
    $conn->query("ALTER TABLE `activity_logs` ADD KEY `idx_created` (`created_at`)");
    $conn->query("ALTER TABLE `activity_logs` ADD KEY `idx_user_created` (`user_id`, `created_at`)");
}

// Patch: log_hash (Tamper-Evident)
$hash_check = $conn->query("SHOW COLUMNS FROM `activity_logs` LIKE 'log_hash'");
if ($hash_check && $hash_check->num_rows === 0) {
    $conn->query("ALTER TABLE `activity_logs` ADD COLUMN `log_hash` varchar(64) DEFAULT NULL");
}

// --- IDENTITY: Users Security Patches ---
$users_cols_res = $conn->query("SHOW COLUMNS FROM `users`")->fetch_all(MYSQLI_ASSOC);
$existing_cols = array_column($users_cols_res, 'Field');

$user_patches = [
    'mfa_enabled' => "ALTER TABLE `users` ADD COLUMN `mfa_enabled` tinyint(1) DEFAULT 0",
    'mfa_secret' => "ALTER TABLE `users` ADD COLUMN `mfa_secret` varchar(32) DEFAULT NULL",
    'failed_attempts' => "ALTER TABLE `users` ADD COLUMN `failed_attempts` int(11) DEFAULT 0",
    'last_failed_attempt' => "ALTER TABLE `users` ADD COLUMN `last_failed_attempt` datetime DEFAULT NULL",
    'lockout_until' => "ALTER TABLE `users` ADD COLUMN `lockout_until` datetime DEFAULT NULL"
];

foreach ($user_patches as $col => $sql) {
    if (!in_array($col, $existing_cols)) {
        $conn->query($sql);
    }
}

// --- INFRASTRUCTURE: Streetlights Maintenance ---
$light_check = $conn->query("SHOW COLUMNS FROM `streetlights` LIKE 'installed_at'");
if ($light_check && $light_check->num_rows === 0) {
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `installed_at` date DEFAULT NULL");
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `runtime_hours` int(11) DEFAULT 0");
    $conn->query("ALTER TABLE `streetlights` ADD COLUMN `hardware_revision` varchar(50) DEFAULT 'v1.0'");
    $conn->query("UPDATE `streetlights` SET installed_at = installation_date WHERE installed_at IS NULL AND installation_date IS NOT NULL");
}

// --- REPORTING: Governance Archive ---
$conn->query("CREATE TABLE IF NOT EXISTS `report_archive` (
  `report_id` int(11) NOT NULL AUTO_INCREMENT,
  `report_name` varchar(255) NOT NULL,
  `report_type` varchar(50) NOT NULL,
  `period_range` varchar(100) NOT NULL,
  `generated_by` int(11) DEFAULT NULL,
  `generated_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `filename` varchar(255) NOT NULL,
  `file_hash` char(64) DEFAULT NULL,
  PRIMARY KEY (`report_id`),
  KEY `idx_generated_at` (`generated_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// --- LOGISTICS: Inventory ---
$conn->query("CREATE TABLE IF NOT EXISTS `inventory_stock` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `category` enum('Lighting','Sensors','Connectivity','Power') NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `part_number` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// --- ANALYTICS: Maintenance Logs ---
$conn->query("CREATE TABLE IF NOT EXISTS `maintenance_logs` (
  `log_id` int(11) NOT NULL AUTO_INCREMENT,
  `light_id` int(11) NOT NULL,
  `alert_id` int(11) DEFAULT NULL,
  `user_id` int(11) NOT NULL,
  `action_taken` text NOT NULL,
  `notes` text DEFAULT NULL,
  `parts_replaced` text DEFAULT NULL,
  `maintenance_date` datetime DEFAULT current_timestamp(),
  `completion_time` int(11) DEFAULT NULL,
  `cost` decimal(10,2) DEFAULT NULL,
  `status` enum('Scheduled','In Progress','Completed','Cancelled') DEFAULT 'Scheduled',
  PRIMARY KEY (`log_id`),
  KEY `idx_light` (`light_id`),
  KEY `idx_user` (`user_id`),
  KEY `idx_maintenance_date` (`maintenance_date`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

// --- RECOVERY: Password Reset Patch ---
try {
    $check = $conn->query("SHOW COLUMNS FROM `password_resets` LIKE 'status'");
    if ($check && $check->num_rows == 0) {
        $conn->query("ALTER TABLE password_resets 
                     ADD COLUMN status ENUM('Pending', 'Fulfilled', 'Dismissed') DEFAULT 'Pending',
                     ADD COLUMN admin_notes TEXT AFTER status");
    }
} catch (Exception $e) {
    echo "Notice: Reset table status patch skipped or already applied.<br>\n";
}

echo "Migrations completed successfully.<br>\n";
?>
