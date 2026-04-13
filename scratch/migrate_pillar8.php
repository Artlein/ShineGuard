<?php
require_once 'dbconnect.php';

echo "Starting Asset Lifecycle Migration (Pillar 8)...\n";

// 1. Expand streetlights with lifecycle metadata
$sql1 = "ALTER TABLE streetlights 
        ADD COLUMN IF NOT EXISTS installed_at DATE DEFAULT NULL,
        ADD COLUMN IF NOT EXISTS hardware_revision VARCHAR(50) DEFAULT 'v1.0',
        ADD COLUMN IF NOT EXISTS runtime_hours INT DEFAULT 0 AFTER hardware_revision";

if ($conn->query($sql1)) {
    echo "✓ Expanded 'streetlights' with lifecycle metadata.\n";
} else {
    echo "✗ Error expanding streetlights: " . $conn->error . "\n";
}

// 2. Create inventory_stock table
$sql2 = "CREATE TABLE IF NOT EXISTS `inventory_stock` (
  `item_id` int(11) NOT NULL AUTO_INCREMENT,
  `part_name` varchar(255) NOT NULL,
  `part_number` varchar(100) NOT NULL,
  `quantity` int(11) DEFAULT 0,
  `min_stock_level` int(11) DEFAULT 5,
  `unit_cost` decimal(10,2) DEFAULT 0.00,
  `category` enum('Lighting', 'Sensors', 'Connectivity', 'Power') NOT NULL,
  PRIMARY KEY (`item_id`),
  UNIQUE KEY `idx_part_num` (`part_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";

if ($conn->query($sql2)) {
    echo "✓ Table 'inventory_stock' ready.\n";
} else {
    echo "✗ Error creating inventory_stock: " . $conn->error . "\n";
}

// 3. Seed some inventory and lifecycle data (Simulation)
$conn->query("INSERT IGNORE INTO inventory_stock (part_name, part_number, quantity, category) VALUES 
    ('50W LED Module (Cool White)', 'LED-50W-CW', 25, 'Lighting'),
    ('LoRaWAN Transceiver v3', 'LR-TX-30', 10, 'Connectivity'),
    ('IP67 Voltage Regulator', 'VR-67-24V', 15, 'Power'),
    ('High-Precision Lux Sensor', 'LUX-HP-01', 8, 'Sensors')");

$conn->query("UPDATE streetlights SET installed_at = DATE_SUB(NOW(), INTERVAL 14 MONTH), runtime_hours = 4200 WHERE light_id = 1");

$conn->close();
echo "Migration Complete.\n";
