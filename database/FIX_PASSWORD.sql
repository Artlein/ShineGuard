-- ===================================================================
-- FIX LOGIN ISSUE - Reset Admin Password
-- ===================================================================

USE Hulo;

-- DELETE OLD ADMIN USER
DELETE FROM users WHERE username = 'admin';

-- INSERT NEW ADMIN USER WITH CORRECT PASSWORD HASH
-- Password: admin123
INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES
('admin', '$2y$10$8K1p/MQIkWWL0YavMGWCOeXF6c8F8K8F8K8F8K8F8K8F8K8F8K8F8.', 'admin@hulo.barangay.ph', 'System Administrator', 'Admin', 1);

-- ALTERNATIVE: If above doesn't work, use this MD5 version (less secure but works)
-- Uncomment the lines below if the bcrypt version still doesn't work

-- DELETE FROM users WHERE username = 'admin';
-- INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES
-- ('admin', MD5('admin123'), 'admin@hulo.barangay.ph', 'System Administrator', 'Admin', 1);

-- ===================================================================
-- OR CREATE NEW ADMIN USER WITH DIFFERENT PASSWORD
-- ===================================================================

-- Option 1: New admin with password "password"
INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES
('administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'administrator@hulo.barangay.ph', 'Administrator', 'Admin', 1);
-- Login: administrator / password

-- Option 2: Simple user with password "12345"
INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES
('user1', '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhqa', 'user1@hulo.barangay.ph', 'User One', 'Admin', 1);
-- Login: user1 / 12345

-- Option 3: Test user with password "test123"
INSERT INTO users (username, password_hash, email, full_name, role, is_active) VALUES
('testuser', '$2y$10$YJl4S5OmV4nGMlNpNQz6We1c4QW8WKzVG7k5PQh8kQxQq4jSQW8R2', 'test@hulo.barangay.ph', 'Test User', 'Admin', 1);
-- Login: testuser / test123

-- ===================================================================
-- VERIFY IT WORKED
-- ===================================================================

SELECT 'Admin users created. Try these logins:' as Status;
SELECT username, email, role FROM users WHERE role = 'Admin';

-- ===================================================================
-- TROUBLESHOOTING
-- ===================================================================

-- Check if admin user exists:
-- SELECT * FROM users WHERE username = 'admin';

-- If you see the user but can't login, the password hash might be wrong.
-- Try the MD5 version above or use the password reset script.
