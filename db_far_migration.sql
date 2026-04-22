-- SHINEGUARD FORENSIC REGISTRY MIGRATION
-- Run this on the AWS MySQL instance to enable FAR support.

CREATE TABLE IF NOT EXISTS backup_registry (
    id INT AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    snapshot_hash VARCHAR(64) NOT NULL,
    filesize BIGINT NOT NULL,
    notes TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
