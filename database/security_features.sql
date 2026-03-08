-- ============================================================
-- ShineGuard Security Features Migration
-- Adds: login_attempts (rate limiting) + remember_tokens (secure)
-- Run once against the Hulo database
-- ============================================================

USE Hulo;

-- #1 – Login Rate Limiting: track failed attempts per IP
CREATE TABLE IF NOT EXISTS login_attempts (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ip_address  VARCHAR(45)  NOT NULL,
    username    VARCHAR(100) NOT NULL DEFAULT '',
    attempted_at DATETIME    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip   (ip_address),
    INDEX idx_user (username),
    INDEX idx_time (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- #4 – Secure Remember-Me: store hashed tokens, not raw
CREATE TABLE IF NOT EXISTS remember_tokens (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    token_hash  VARCHAR(64)  NOT NULL UNIQUE,   -- SHA-256 hex of raw token
    expires_at  DATETIME     NOT NULL,
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user    (user_id),
    INDEX idx_expires (expires_at),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: clean up old raw remember_token column from users if it exists
-- ALTER TABLE users DROP COLUMN IF EXISTS remember_token;

-- #5 - Account Freezing (Brute-Force Lockout System)
-- Adds fields to temporarily lock users out after 5 consecutive failed passwords
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS failed_attempts INT DEFAULT 0 AFTER is_active,
ADD COLUMN IF NOT EXISTS last_failed_attempt DATETIME NULL AFTER failed_attempts,
ADD COLUMN IF NOT EXISTS lockout_until DATETIME NULL AFTER last_failed_attempt;
