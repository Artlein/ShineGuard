<?php
require_once 'dbconnect.php';
$conn->query("UPDATE system_config SET config_value = '3500' WHERE config_key = 'lux_threshold_min'");
$conn->query("UPDATE system_config SET config_value = '4000' WHERE config_key = 'lux_threshold_critical'");
echo "Thresholds updated to Raw ADC values.\n";
?>
