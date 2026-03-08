-- Update cameras table with NVR configuration fields
ALTER TABLE cameras 
ADD COLUMN IF NOT EXISTS nvr_ip VARCHAR(50),
ADD COLUMN IF NOT EXISTS nvr_port INT DEFAULT 554,
ADD COLUMN IF NOT EXISTS channel INT DEFAULT 1,
ADD COLUMN IF NOT EXISTS username VARCHAR(50) DEFAULT 'admin',
ADD COLUMN IF NOT EXISTS password VARCHAR(100),
ADD COLUMN IF NOT EXISTS stream_type ENUM('main', 'sub') DEFAULT 'main',
ADD COLUMN IF NOT EXISTS protocol ENUM('rtsp', 'http') DEFAULT 'rtsp';

-- Create camera snapshots table
CREATE TABLE IF NOT EXISTS camera_snapshots (
    snapshot_id INT PRIMARY KEY AUTO_INCREMENT,
    camera_id INT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    filepath VARCHAR(500) NOT NULL,
    filesize INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    notes TEXT,
    FOREIGN KEY (camera_id) REFERENCES cameras(camera_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_camera_date (camera_id, created_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Update existing camera records with sample NVR config
UPDATE cameras SET 
    nvr_ip = '192.168.1.64',
    nvr_port = 554,
    channel = camera_id,
    username = 'admin',
    password = 'admin123',
    stream_type = 'main',
    protocol = 'rtsp'
WHERE nvr_ip IS NULL;

-- Insert sample snapshot records
INSERT INTO camera_snapshots (camera_id, filename, filepath, created_at) VALUES
(1, 'snapshot_cam1_20251222120000.jpg', 'snapshots/snapshot_cam1_20251222120000.jpg', NOW() - INTERVAL 2 HOUR),
(2, 'snapshot_cam2_20251222130000.jpg', 'snapshots/snapshot_cam2_20251222130000.jpg', NOW() - INTERVAL 1 HOUR),
(3, 'snapshot_cam3_20251222140000.jpg', 'snapshots/snapshot_cam3_20251222140000.jpg', NOW() - INTERVAL 30 MINUTE);
