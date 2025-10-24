<?php
/**
 * Hospital Management Dashboard Configuration
 * Copy this file to config.php and update with your settings
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hospital_management');
define('DB_USER', 'root');  // Change to your database username
define('DB_PASS', '');      // Change to your database password
define('DB_CHARSET', 'utf8mb4');

// Application Settings
define('APP_NAME', 'Hospital Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'America/New_York'); // Set your timezone

// Security Settings
define('ENABLE_DEBUG', false); // Set to true for development
define('SESSION_LIFETIME', 3600); // Session timeout in seconds

// Display Settings
define('RECORDS_PER_PAGE', 50);
define('MAX_UPLOAD_SIZE', '5MB');

// Date/Time Formats
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y g:i A');

// Chart Colors (for customization)
define('CHART_COLORS', [
    'primary' => '#3498db',
    'success' => '#27ae60',
    'danger' => '#e74c3c',
    'warning' => '#f39c12',
    'info' => '#1abc9c',
    'purple' => '#9b59b6',
    'dark' => '#34495e',
    'light' => '#95a5a6'
]);

// Error Messages
define('ERROR_MESSAGES', [
    'db_connection' => 'Unable to connect to the database. Please check your configuration.',
    'invalid_request' => 'Invalid request. Please try again.',
    'unauthorized' => 'You are not authorized to perform this action.',
    'not_found' => 'The requested resource was not found.',
    'validation_failed' => 'Please check your input and try again.',
    'server_error' => 'An internal server error occurred. Please try again later.'
]);

// Success Messages
define('SUCCESS_MESSAGES', [
    'created' => ' created successfully.',
    'updated' => ' updated successfully.',
    'deleted' => ' deleted successfully.',
    'saved' => 'Changes saved successfully.'
]);

// Department Defaults (can be customized)
define('DEFAULT_DEPARTMENTS', [
    'Cardiology' => 'Heart and cardiovascular diseases treatment',
    'Neurology' => 'Brain and nervous system disorders',
    'Emergency' => '24/7 emergency medical services',
    'Pediatrics' => 'Medical care for children',
    'Orthopedics' => 'Bone, joint and muscle treatment',
    'Oncology' => 'Cancer treatment and care'
]);

// Blood Groups
define('BLOOD_GROUPS', ['A+', 'A-', 'B+', 'B-', 'O+', 'O-', 'AB+', 'AB-']);

// Gender Options
define('GENDER_OPTIONS', ['Male', 'Female', 'Other']);

// Appointment Status Options
define('APPOINTMENT_STATUS', ['Scheduled', 'Completed', 'Cancelled', 'No-Show']);

// Validation Rules
define('VALIDATION_RULES', [
    'name' => [
        'required' => true,
        'min_length' => 2,
        'max_length' => 100,
        'pattern' => '/^[a-zA-Z\s\-\.]+$/'
    ],
    'email' => [
        'pattern' => '/^[^\s@]+@[^\s@]+\.[^\s@]+$/'
    ],
    'phone' => [
        'pattern' => '/^[\+]?[1-9][\d]{0,15}$/'
    ],
    'license_number' => [
        'required' => true,
        'min_length' => 3,
        'max_length' => 50
    ]
]);

// File Upload Settings
define('UPLOAD_SETTINGS', [
    'allowed_types' => ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'],
    'max_size' => 5 * 1024 * 1024, // 5MB in bytes
    'upload_path' => 'uploads/'
]);

// Pagination Settings
define('PAGINATION', [
    'default_limit' => 20,
    'max_limit' => 100,
    'show_pages' => 5
]);

// Cache Settings
define('CACHE_SETTINGS', [
    'enable' => false,
    'duration' => 3600, // 1 hour
    'prefix' => 'hospital_'
]);

// Logging Settings
define('LOGGING', [
    'enable' => true,
    'level' => 'ERROR', // DEBUG, INFO, WARNING, ERROR
    'file' => 'logs/app.log',
    'max_size' => 10 * 1024 * 1024 // 10MB
]);

// Feature Flags
define('FEATURES', [
    'enable_charts' => true,
    'enable_export' => true,
    'enable_import' => true,
    'enable_notifications' => false,
    'enable_audit_log' => false
]);

// API Settings (for future mobile app integration)
define('API_SETTINGS', [
    'enable' => false,
    'version' => 'v1',
    'rate_limit' => 100, // requests per hour
    'require_auth' => true
]);

// Backup Settings
define('BACKUP_SETTINGS', [
    'auto_backup' => false,
    'backup_path' => 'backups/',
    'retention_days' => 30,
    'include_files' => false
]);

// Email Settings (for notifications)
define('EMAIL_SETTINGS', [
    'enable' => false,
    'smtp_host' => 'smtp.gmail.com',
    'smtp_port' => 587,
    'smtp_username' => '',
    'smtp_password' => '',
    'from_email' => 'noreply@hospital.com',
    'from_name' => 'Hospital Management System'
]);

// Theme Settings
define('THEME_SETTINGS', [
    'primary_color' => '#3498db',
    'secondary_color' => '#2c3e50',
    'success_color' => '#27ae60',
    'danger_color' => '#e74c3c',
    'warning_color' => '#f39c12',
    'info_color' => '#1abc9c',
    'light_color' => '#f8f9fa',
    'dark_color' => '#343a40'
]);

// Development Settings
if (ENABLE_DEBUG) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', 'logs/php_errors.log');
}

// Set timezone
date_default_timezone_set(TIMEZONE);

// Helper Functions
function getConfigValue($key, $default = null) {
    return defined($key) ? constant($key) : $default;
}

function isFeatureEnabled($feature) {
    $features = FEATURES;
    return isset($features[$feature]) ? $features[$feature] : false;
}

function getErrorMessage($key) {
    $messages = ERROR_MESSAGES;
    return isset($messages[$key]) ? $messages[$key] : 'An error occurred.';
}

function getSuccessMessage($key) {
    $messages = SUCCESS_MESSAGES;
    return isset($messages[$key]) ? $messages[$key] : 'Operation completed successfully.';
}

function formatDate($date, $format = DISPLAY_DATE_FORMAT) {
    if (!$date) return '';
    return date($format, strtotime($date));
}

function formatDateTime($datetime, $format = DISPLAY_DATETIME_FORMAT) {
    if (!$datetime) return '';
    return date($format, strtotime($datetime));
}

function sanitizeInput($input) {
    if (is_array($input)) {
        return array_map('sanitizeInput', $input);
    }
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

function validatePhone($phone) {
    $pattern = VALIDATION_RULES['phone']['pattern'];
    return preg_match($pattern, $phone);
}

function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $charactersLength = strlen($characters);
    $randomString = '';
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, $charactersLength - 1)];
    }
    return $randomString;
}

function logError($message, $file = '', $line = '') {
    if (!LOGGING['enable']) return;
    
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] ERROR: $message";
    if ($file) $logMessage .= " in $file";
    if ($line) $logMessage .= " on line $line";
    $logMessage .= PHP_EOL;
    
    $logFile = LOGGING['file'];
    if (!file_exists(dirname($logFile))) {
        mkdir(dirname($logFile), 0755, true);
    }
    
    file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
}

function debugLog($message) {
    if (ENABLE_DEBUG) {
        error_log("[DEBUG] " . $message);
    }
}
?>