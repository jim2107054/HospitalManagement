<?php
/**
 * Hospital Management Dashboard Configuration
 * Main configuration file
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_management');
define('DB_USER', 'root');  // Default XAMPP MySQL username
define('DB_PASS', '');      // Default XAMPP MySQL password (empty)
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Hospital Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/New_York');

// Development Settings
define('ENABLE_DEBUG', true); // Set to false for production

// Set timezone
date_default_timezone_set(TIMEZONE);

// Enable error reporting for development
if (ENABLE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

// Blood Groups
define('BLOOD_GROUPS', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']);

// Gender Options
define('GENDER_OPTIONS', ['Male', 'Female', 'Other']);

// Appointment Status Options
define('APPOINTMENT_STATUS', ['Scheduled', 'Completed', 'Cancelled', 'No-Show']);

// Helper Functions
function formatDate($date, $format = 'M d, Y') {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = 'M d, Y g:i A') {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}
?>