-- =====================================================
-- Hospital Dashboard - Authentication System
-- Database Changes for Login/Logout Functionality
-- =====================================================

-- Create admin_users table for authentication
CREATE TABLE IF NOT EXISTS `admin_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL UNIQUE,
  `email` varchar(100) NOT NULL UNIQUE,
  `password` varchar(255) NOT NULL,
  `full_name` varchar(100) NOT NULL,
  `role` enum('super_admin','admin','manager') DEFAULT 'admin',
  `status` enum('active','inactive','suspended') DEFAULT 'active',
  `last_login` datetime DEFAULT NULL,
  `login_attempts` int(11) DEFAULT 0,
  `locked_until` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create login_logs table to track login history
CREATE TABLE IF NOT EXISTS `login_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `ip_address` varchar(45) NOT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `login_time` datetime NOT NULL,
  `logout_time` datetime DEFAULT NULL,
  `status` enum('success','failed','logout') NOT NULL,
  `failure_reason` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  KEY `login_time` (`login_time`),
  CONSTRAINT `login_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `admin_users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user
-- Username: admin
-- Password: admin123 (PLEASE CHANGE THIS AFTER FIRST LOGIN!)
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`, `status`) 
VALUES (
  'admin', 
  'admin@hospital.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: admin123
  'System Administrator',
  'super_admin',
  'active'
);

-- Optional: Insert additional test users
INSERT INTO `admin_users` (`username`, `email`, `password`, `full_name`, `role`, `status`) 
VALUES 
(
  'manager', 
  'manager@hospital.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: admin123
  'Hospital Manager',
  'manager',
  'active'
),
(
  'staff', 
  'staff@hospital.com', 
  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- Password: admin123
  'Staff Member',
  'admin',
  'active'
);

-- =====================================================
-- HOW TO RUN THIS FILE:
-- =====================================================
-- Method 1: Using phpMyAdmin
-- 1. Open phpMyAdmin (http://localhost/phpmyadmin)
-- 2. Select your 'hospital_db' database
-- 3. Click on "SQL" tab
-- 4. Copy and paste this entire file content
-- 5. Click "Go" button
--
-- Method 2: Using MySQL Command Line
-- 1. Open Command Prompt/Terminal
-- 2. Navigate to this directory
-- 3. Run: mysql -u root -p hospital_db < auth-database.sql
-- 4. Enter your MySQL password when prompted
--
-- Method 3: Using XAMPP Shell
-- 1. Open XAMPP Control Panel
-- 2. Click "Shell" button
-- 3. Run: mysql -u root hospital_db < auth-database.sql
-- =====================================================

-- =====================================================
-- DEFAULT CREDENTIALS:
-- =====================================================
-- Username: admin
-- Password: admin123
-- 
-- ⚠️ IMPORTANT SECURITY NOTES:
-- 1. Change the default password immediately after first login!
-- 2. The password is hashed using PHP's password_hash() with bcrypt
-- 3. Never store plain text passwords in database
-- 4. Consider implementing 2FA for production environments
-- =====================================================

-- Verify tables were created
SELECT 'Authentication tables created successfully!' as message;
SELECT COUNT(*) as admin_count FROM admin_users;
