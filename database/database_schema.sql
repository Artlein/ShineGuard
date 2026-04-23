-- Shine Guard Hulo Database Schema
-- IoT Smart Streetlight System with Predictive Maintenance
-- Barangay Hulo Implementation

CREATE DATABASE IF NOT EXISTS Hulo;
USE Hulo;

-- Users Table (Authentication & Authorization)
CREATE TABLE IF NOT EXISTS users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('Admin', 'Operator', 'Maintenance') DEFAULT 'Operator',
    phone VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    is_active BOOLEAN DEFAULT TRUE,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Streetlights Table
CREATE TABLE IF NOT EXISTS streetlights (
    light_id INT PRIMARY KEY AUTO_INCREMENT,
    node_name VARCHAR(50) UNIQUE NOT NULL,
    location VARCHAR(255) NOT NULL,
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    installation_date DATE,
    status ENUM('Active', 'Inactive', 'Maintenance') DEFAULT 'Active',
    power_state ENUM('ON', 'OFF') DEFAULT 'ON',
    dimming_level INT DEFAULT 70,
    last_maintenance DATE,
    INDEX idx_status (status),
    INDEX idx_node_name (node_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sensor Data Table
CREATE TABLE IF NOT EXISTS sensor_data (
    data_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    light_id INT NOT NULL,
    brightness_level DECIMAL(10,2),
    current_consumption DECIMAL(6,3),
    voltage DECIMAL(5,2),
    temperature DECIMAL(4,1),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (light_id) REFERENCES streetlights(light_id) ON DELETE CASCADE,
    INDEX idx_light_timestamp (light_id, timestamp),
    INDEX idx_timestamp (timestamp)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Alerts Table
CREATE TABLE IF NOT EXISTS alerts (
    alert_id INT PRIMARY KEY AUTO_INCREMENT,
    light_id INT NOT NULL,
    alert_type ENUM('Fault', 'Warning', 'Predictive') NOT NULL,
    severity ENUM('Low', 'Medium', 'High') NOT NULL,
    description TEXT NOT NULL,
    status ENUM('Open', 'Acknowledged', 'Resolved') DEFAULT 'Open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    acknowledged_at TIMESTAMP NULL,
    acknowledged_by INT NULL,
    resolved_at TIMESTAMP NULL,
    rul_estimate VARCHAR(50),
    FOREIGN KEY (light_id) REFERENCES streetlights(light_id) ON DELETE CASCADE,
    FOREIGN KEY (acknowledged_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_severity (severity),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Maintenance Logs Table
CREATE TABLE IF NOT EXISTS maintenance_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    light_id INT NOT NULL,
    alert_id INT NULL,
    user_id INT NOT NULL,
    action_taken TEXT NOT NULL,
    notes TEXT,
    parts_replaced TEXT,
    maintenance_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    completion_time INT,
    cost DECIMAL(10,2),
    status ENUM('Scheduled', 'In Progress', 'Completed', 'Cancelled') DEFAULT 'Scheduled',
    FOREIGN KEY (light_id) REFERENCES streetlights(light_id) ON DELETE CASCADE,
    FOREIGN KEY (alert_id) REFERENCES alerts(alert_id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_maintenance_date (maintenance_date),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CCTV Cameras Table
CREATE TABLE IF NOT EXISTS cctv_cameras (
    camera_id INT PRIMARY KEY AUTO_INCREMENT,
    camera_name VARCHAR(100) NOT NULL,
    location VARCHAR(255) NOT NULL,
    stream_url VARCHAR(500),
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    status ENUM('Online', 'Offline', 'Maintenance') DEFAULT 'Online',
    resolution VARCHAR(20),
    fps INT DEFAULT 15,
    installation_date DATE,
    last_checked TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- CCTV Footage Metadata Table
CREATE TABLE IF NOT EXISTS cctv_footage (
    footage_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    camera_id INT NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_size BIGINT,
    start_time TIMESTAMP NOT NULL,
    end_time TIMESTAMP NOT NULL,
    duration INT,
    event_type ENUM('Continuous', 'Motion', 'Alert', 'Manual') DEFAULT 'Continuous',
    cloud_backup_status ENUM('Pending', 'Uploaded', 'Failed') DEFAULT 'Pending',
    uploaded_at TIMESTAMP NULL,
    FOREIGN KEY (camera_id) REFERENCES cctv_cameras(camera_id) ON DELETE CASCADE,
    INDEX idx_camera_time (camera_id, start_time),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Schedule Presets Table
CREATE TABLE IF NOT EXISTS schedule_presets (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    preset_name VARCHAR(100) NOT NULL,
    time_on TIME NOT NULL,
    time_off TIME NOT NULL,
    dimming_level INT DEFAULT 70,
    days_of_week VARCHAR(50) DEFAULT 'Mon,Tue,Wed,Thu,Fri,Sat,Sun',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT NOT NULL,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System Configuration Table
CREATE TABLE IF NOT EXISTS system_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT NOT NULL,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT NOT NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity Logs Table
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user_created (user_id, created_at),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert Default Admin User (password: admin123)
INSERT INTO users (username, password_hash, email, full_name, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@hulo.barangay.ph', 'System Administrator', 'Admin');

-- Insert Sample Streetlights (32 nodes for grid)
INSERT INTO streetlights (node_name, location, latitude, longitude, installation_date, status) VALUES
('SL-001', 'Main Street - North Entry', 14.6507, 121.0494, '2024-01-15', 'Active'),
('SL-002', 'Main Street - Block 1', 14.6508, 121.0495, '2024-01-15', 'Active'),
('SL-003', 'Main Street - Block 2', 14.6509, 121.0496, '2024-01-15', 'Active'),
('SL-004', 'Main Street - Block 3', 14.6510, 121.0497, '2024-01-15', 'Active'),
('SL-005', 'Main Street - Block 4', 14.6511, 121.0498, '2024-01-15', 'Active'),
('SL-006', 'Main Street - Block 5', 14.6512, 121.0499, '2024-01-15', 'Active'),
('SL-007', 'Main Street - Block 6', 14.6513, 121.0500, '2024-01-15', 'Active'),
('SL-008', 'Main Street - South End', 14.6514, 121.0501, '2024-01-15', 'Active'),
('SL-009', 'Rizal Avenue - Corner 1', 14.6507, 121.0502, '2024-01-16', 'Active'),
('SL-010', 'Rizal Avenue - Mid', 14.6508, 121.0503, '2024-01-16', 'Active'),
('SL-011', 'Rizal Avenue - Corner 2', 14.6509, 121.0504, '2024-01-16', 'Active'),
('SL-012', 'Bonifacio Street - Entry', 14.6510, 121.0505, '2024-01-16', 'Active'),
('SL-013', 'Bonifacio Street - Mid', 14.6511, 121.0506, '2024-01-16', 'Active'),
('SL-014', 'Bonifacio Street - Exit', 14.6512, 121.0507, '2024-01-16', 'Active'),
('SL-015', 'Barangay Hall Area', 14.6513, 121.0508, '2024-01-17', 'Active'),
('SL-016', 'Community Center', 14.6514, 121.0509, '2024-01-17', 'Active'),
('SL-017', 'School Zone - Entry', 14.6515, 121.0510, '2024-01-17', 'Active'),
('SL-018', 'School Zone - Main', 14.6516, 121.0511, '2024-01-17', 'Active'),
('SL-019', 'School Zone - Exit', 14.6517, 121.0512, '2024-01-17', 'Active'),
('SL-020', 'Health Center Area', 14.6518, 121.0513, '2024-01-18', 'Active'),
('SL-021', 'Market Area - North', 14.6519, 121.0514, '2024-01-18', 'Active'),
('SL-022', 'Market Area - Center', 14.6520, 121.0515, '2024-01-18', 'Active'),
('SL-023', 'Market Area - South', 14.6521, 121.0516, '2024-01-18', 'Active'),
('SL-024', 'Park Area - Entry', 14.6522, 121.0517, '2024-01-19', 'Active'),
('SL-025', 'Park Area - Center', 14.6523, 121.0518, '2024-01-19', 'Active'),
('SL-026', 'Park Area - Exit', 14.6524, 121.0519, '2024-01-19', 'Active'),
('SL-027', 'Sports Complex - Entry', 14.6525, 121.0520, '2024-01-20', 'Active'),
('SL-028', 'Sports Complex - North', 14.6526, 121.0521, '2024-01-20', 'Active'),
('SL-029', 'Sports Complex - South', 14.6527, 121.0522, '2024-01-20', 'Active'),
('SL-030', 'Chapel Area', 14.6528, 121.0523, '2024-01-20', 'Active'),
('SL-031', 'Terminal Area - Entry', 14.6529, 121.0524, '2024-01-21', 'Active'),
('SL-032', 'Terminal Area - Exit', 14.6530, 121.0525, '2024-01-21', 'Active');

-- Insert Sample CCTV Cameras
INSERT INTO cctv_cameras (camera_name, location, latitude, longitude, status, resolution, installation_date) VALUES
('CAM-01', 'Main Street Entrance', 14.6507, 121.0494, 'Online', '1080p', '2024-01-15'),
('CAM-02', 'Barangay Hall Front', 14.6513, 121.0508, 'Online', '1080p', '2024-01-17'),
('CAM-03', 'Market Area Overview', 14.6520, 121.0515, 'Online', '1080p', '2024-01-18'),
('CAM-04', 'Sports Complex Entrance', 14.6525, 121.0520, 'Online', '1080p', '2024-01-20');

-- Insert Default System Configuration
INSERT INTO system_config (config_key, config_value, description, updated_by) VALUES
('lux_threshold_min', '8', 'Minimum lux level for predictive maintenance alert', 1),
('current_threshold_max', '0.65', 'Maximum current (Amperes) threshold', 1),
('temperature_threshold_max', '50', 'Maximum temperature (Celsius) threshold', 1),
('predictive_days_threshold', '3', 'Number of consecutive days below threshold to trigger predictive alert', 1),
('auto_dim_enabled', '1', 'Enable automatic dimming based on schedule', 1),
('default_dimming_level', '70', 'Default dimming level percentage', 1),
('cloud_backup_enabled', '1', 'Enable Firebase cloud backup', 1),
('alert_email_enabled', '1', 'Enable email notifications for alerts', 1),
('data_retention_days', '90', 'Number of days to retain sensor data', 1),
('footage_retention_days', '30', 'Number of days to retain CCTV footage', 1);

-- Insert Default Schedule Preset
INSERT INTO schedule_presets (preset_name, time_on, time_off, dimming_level, days_of_week, is_active, created_by) VALUES
('Default Night Schedule', '18:00:00', '06:00:00', 70, 'Mon,Tue,Wed,Thu,Fri,Sat,Sun', TRUE, 1);

-- Create Views for Dashboard

-- View: Current Streetlight Status Summary
CREATE OR REPLACE VIEW view_streetlight_summary AS
SELECT 
    status,
    power_state,
    COUNT(*) as count,
    AVG(dimming_level) as avg_dimming
FROM streetlights
GROUP BY status, power_state;

-- View: Recent Alerts
CREATE OR REPLACE VIEW view_recent_alerts AS
SELECT 
    a.alert_id,
    a.alert_type,
    a.severity,
    a.description,
    a.status,
    a.rul_estimate,
    a.created_at,
    s.node_name,
    s.location,
    u.username as acknowledged_by_name
FROM alerts a
INNER JOIN streetlights s ON a.light_id = s.light_id
LEFT JOIN users u ON a.acknowledged_by = u.user_id
WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
ORDER BY a.created_at DESC;

-- View: Maintenance Summary
CREATE OR REPLACE VIEW view_maintenance_summary AS
SELECT 
    ml.log_id,
    ml.maintenance_date,
    ml.action_taken,
    ml.status,
    ml.cost,
    s.node_name,
    s.location,
    u.full_name as technician
FROM maintenance_logs ml
INNER JOIN streetlights s ON ml.light_id = s.light_id
INNER JOIN users u ON ml.user_id = u.user_id
WHERE ml.maintenance_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
ORDER BY ml.maintenance_date DESC;

-- Stored Procedure: Generate Daily Energy Report
DELIMITER $$
CREATE PROCEDURE sp_daily_energy_report(IN report_date DATE)
BEGIN
    SELECT 
        s.node_name,
        s.location,
        AVG(sd.current_consumption) as avg_current,
        AVG(sd.voltage) as avg_voltage,
        AVG(sd.brightness_level) as avg_brightness,
        COUNT(sd.data_id) as reading_count,
        (AVG(sd.current_consumption) * AVG(sd.voltage) * 24 / 1000) as estimated_kwh
    FROM streetlights s
    LEFT JOIN sensor_data sd ON s.light_id = sd.light_id
    WHERE DATE(sd.timestamp) = report_date
    GROUP BY s.light_id, s.node_name, s.location
    ORDER BY s.node_name;
END$$
DELIMITER ;

-- Stored Procedure: Bulk Control Streetlights
DELIMITER $$
CREATE PROCEDURE sp_bulk_control_lights(
    IN action VARCHAR(10),
    IN dimming INT,
    IN user_id INT
)
BEGIN
    DECLARE affected_count INT;
    
    IF action = 'ON' THEN
        UPDATE streetlights SET power_state = 'ON', dimming_level = dimming;
    ELSEIF action = 'OFF' THEN
        UPDATE streetlights SET power_state = 'OFF';
    END IF;
    
    SET affected_count = ROW_COUNT();
    
    INSERT INTO activity_logs (user_id, action, details) 
    VALUES (user_id, CONCAT('Bulk Control: ', action), CONCAT(affected_count, ' streetlights affected'));
END$$
DELIMITER ;

-- Trigger: Auto-create alert when temperature exceeds threshold
DELIMITER $$
CREATE TRIGGER trg_temperature_alert
AFTER INSERT ON sensor_data
FOR EACH ROW
BEGIN
    DECLARE temp_threshold DECIMAL(4,1);
    
    SELECT CAST(config_value AS DECIMAL(4,1)) INTO temp_threshold
    FROM system_config
    WHERE config_key = 'temperature_threshold_max';
    
    IF NEW.temperature > temp_threshold THEN
        INSERT INTO alerts (light_id, alert_type, severity, description, rul_estimate)
        VALUES (NEW.light_id, 'Warning', 'Medium', 
                CONCAT('High temperature detected: ', NEW.temperature, '°C'), 
                '7d');
    END IF;
END$$
DELIMITER ;

-- Grant privileges (adjust as needed for production)
GRANT ALL PRIVILEGES ON Hulo.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
