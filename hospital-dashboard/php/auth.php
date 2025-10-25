<?php
// Start session
session_start();

// Include database connection
require_once '../includes/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? '';

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'login':
            if ($method !== 'POST') {
                throw new Exception('Method not allowed');
            }
            
            $username = trim($input['username'] ?? '');
            $password = $input['password'] ?? '';
            
            if (empty($username) || empty($password)) {
                throw new Exception('Username and password are required');
            }
            
            // Get user from database
            $sql = "SELECT * FROM admin_users WHERE username = ? AND status = 'active'";
            $user = executeSingleQuery($sql, [$username]);
            
            if (!$user) {
                // Log failed attempt
                logLoginAttempt(null, $username, 'failed', 'Invalid username');
                throw new Exception('Invalid username or password');
            }
            
            // Verify password
            if (!password_verify($password, $user['password'])) {
                // Log failed attempt (no restrictions, unlimited attempts allowed)
                logLoginAttempt($user['id'], $username, 'failed', 'Invalid password');
                throw new Exception('Invalid username or password');
            }
            
            // Successful login - Update last login
            executeModifyQuery(
                "UPDATE admin_users SET last_login = NOW() WHERE id = ?",
                [$user['id']]
            );
            
            // Log successful login
            logLoginAttempt($user['id'], $username, 'success', null);
            
            // Set session variables
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            $_SESSION['login_time'] = time();
            
            // Regenerate session ID for security
            session_regenerate_id(true);
            
            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'user' => [
                    'id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ]);
            break;
            
        case 'logout':
            if (isset($_SESSION['user_id'])) {
                // Log logout
                logLogout($_SESSION['user_id'], $_SESSION['username']);
                
                // Destroy session
                session_unset();
                session_destroy();
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Logout successful'
            ]);
            break;
            
        case 'check_session':
            if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
                echo json_encode([
                    'success' => true,
                    'logged_in' => true,
                    'user' => [
                        'id' => $_SESSION['user_id'],
                        'username' => $_SESSION['username'],
                        'email' => $_SESSION['email'],
                        'full_name' => $_SESSION['full_name'],
                        'role' => $_SESSION['role']
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true,
                    'logged_in' => false
                ]);
            }
            break;
            
        case 'change_password':
            // Check if user is logged in
            if (!isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
                throw new Exception('Unauthorized access');
            }
            
            $currentPassword = $input['current_password'] ?? '';
            $newPassword = $input['new_password'] ?? '';
            $confirmPassword = $input['confirm_password'] ?? '';
            
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception('All fields are required');
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception('New passwords do not match');
            }
            
            if (strlen($newPassword) < 6) {
                throw new Exception('Password must be at least 6 characters long');
            }
            
            // Get current user
            $sql = "SELECT password FROM admin_users WHERE id = ?";
            $user = executeSingleQuery($sql, [$_SESSION['user_id']]);
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception('Current password is incorrect');
            }
            
            // Hash new password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            // Update password
            executeModifyQuery(
                "UPDATE admin_users SET password = ? WHERE id = ?",
                [$hashedPassword, $_SESSION['user_id']]
            );
            
            echo json_encode([
                'success' => true,
                'message' => 'Password changed successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// Helper function to log login attempts
function logLoginAttempt($userId, $username, $status, $failureReason = null) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $sql = "INSERT INTO login_logs (user_id, username, ip_address, user_agent, login_time, status, failure_reason) 
            VALUES (?, ?, ?, ?, NOW(), ?, ?)";
    
    executeModifyQuery($sql, [$userId, $username, $ipAddress, $userAgent, $status, $failureReason]);
}

// Helper function to log logout
function logLogout($userId, $username) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    
    $sql = "INSERT INTO login_logs (user_id, username, ip_address, user_agent, login_time, logout_time, status) 
            VALUES (?, ?, ?, ?, NOW(), NOW(), 'logout')";
    
    executeModifyQuery($sql, [$userId, $username, $ipAddress, $userAgent]);
}
?>
