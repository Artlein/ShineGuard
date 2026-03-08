-- ===================================================================
-- PREDICTIVE MAINTENANCE THRESHOLDS - UPDATED FOR REAL-TIME DATA
-- Based on Firebase IoT Sensor Data from SG-NODE2
-- ===================================================================

-- Update system_config with IoT-based thresholds
UPDATE system_config SET config_value = '20', description = 'Minimum brightness (lux) - WARNING level - turns KPI yellow' 
WHERE config_key = 'lux_threshold_min';

UPDATE system_config SET config_value = '10', description = 'Critical brightness (lux) - CRITICAL level - predictive maintenance needed' 
WHERE config_key = 'lux_threshold_critical';

UPDATE system_config SET config_value = '45', description = 'Maximum temperature (Celsius) - WARNING level - turns KPI yellow' 
WHERE config_key = 'temperature_threshold_max';

UPDATE system_config SET config_value = '55', description = 'Critical temperature (Celsius) - CRITICAL level - immediate maintenance' 
WHERE config_key = 'temperature_threshold_critical';

UPDATE system_config SET config_value = '0.5', description = 'Maximum current (Amperes) - WARNING level' 
WHERE config_key = 'current_threshold_max';

UPDATE system_config SET config_value = '0.7', description = 'Critical current (Amperes) - CRITICAL level' 
WHERE config_key = 'current_threshold_critical';

UPDATE system_config SET config_value = '2.0', description = 'Minimum voltage (V) - WARNING level' 
WHERE config_key = 'voltage_threshold_min';

UPDATE system_config SET config_value = '1.5', description = 'Critical voltage (V) - CRITICAL level - battery replacement needed' 
WHERE config_key = 'voltage_threshold_critical';

UPDATE system_config SET config_value = '80', description = 'Maximum humidity (%) - WARNING level' 
WHERE config_key = 'humidity_threshold_max';

UPDATE system_config SET config_value = '90', description = 'Critical humidity (%) - CRITICAL level - environmental protection needed' 
WHERE config_key = 'humidity_threshold_critical';

-- Add new thresholds if they don't exist
INSERT IGNORE INTO system_config (config_key, config_value, description, updated_by) VALUES
('lux_threshold_critical', '10', 'Critical brightness (lux) - CRITICAL level - predictive maintenance needed', 1),
('temperature_threshold_critical', '55', 'Critical temperature (Celsius) - CRITICAL level - immediate maintenance', 1),
('current_threshold_critical', '0.7', 'Critical current (Amperes) - CRITICAL level', 1),
('voltage_threshold_min', '2.0', 'Minimum voltage (V) - WARNING level', 1),
('voltage_threshold_critical', '1.5', 'Critical voltage (V) - CRITICAL level - battery replacement needed', 1),
('humidity_threshold_max', '80', 'Maximum humidity (%) - WARNING level', 1),
('humidity_threshold_critical', '90', 'Critical humidity (%) - CRITICAL level', 1),
('predictive_window_days', '7', 'Number of days to analyze for predictive maintenance trends', 1),
('predictive_threshold_hits', '3', 'Number of threshold hits in window to trigger predictive alert', 1),
('maintenance_prediction_days', '14', 'Days to predict until maintenance needed', 1);

-- ===================================================================
-- THRESHOLD DEFINITIONS BASED ON FIREBASE DATA ANALYSIS
-- ===================================================================

/*
BASED ON CURRENT FIREBASE DATA (SG-NODE2):
- LDR Data: 2785 (converts to ~30 lux - GOOD)
- Temperature: 30.3°C (GOOD - well below 45°C warning)
- Voltage: 2.366V (GOOD - above 2.0V warning)
- Humidity: 75% (GOOD - below 80% warning)

THRESHOLD LEVELS:
┌─────────────────────────────────────────────────────────────┐
│ Parameter    │ GOOD     │ WARNING   │ CRITICAL │ Action     │
├─────────────────────────────────────────────────────────────┤
│ Brightness   │ >20 lux  │ 10-20 lux │ <10 lux  │ Lamp aging │
│ Temperature  │ <45°C    │ 45-55°C   │ >55°C    │ Cooling    │
│ Current      │ <0.5A    │ 0.5-0.7A  │ >0.7A    │ Overload   │
│ Voltage      │ >2.0V    │ 1.5-2.0V  │ <1.5V    │ Battery    │
│ Humidity     │ <80%     │ 80-90%    │ >90%     │ Sealing    │
└─────────────────────────────────────────────────────────────┘

COLOR CODING:
🟢 GREEN = GOOD (normal operation)
🟡 YELLOW = WARNING (needs monitoring, predictive maintenance soon)
🔴 RED = CRITICAL (immediate action required)

PREDICTIVE MAINTENANCE TRIGGERS:
1. 3+ WARNING threshold hits in 7-day window → Schedule maintenance in 14 days
2. 1+ CRITICAL threshold hit → Create immediate work order
3. Declining trend (getting worse) → Early warning alert
*/

-- ===================================================================
-- VERIFICATION QUERY
-- ===================================================================

SELECT 
    config_key,
    config_value,
    description
FROM system_config
WHERE config_key LIKE '%threshold%'
ORDER BY config_key;
