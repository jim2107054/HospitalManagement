<?php
// Comprehensive database check for authentication system
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Status Check</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
            font-size: 14px;
        }
        .test-section {
            margin-bottom: 30px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .test-title {
            font-size: 18px;
            font-weight: 600;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .success {
            background: #4caf50;
            color: white;
        }
        .error {
            background: #f44336;
            color: white;
        }
        .warning {
            background: #ff9800;
            color: white;
        }
        .info {
            background: #2196f3;
            color: white;
        }
        .result-box {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 10px;
            border-left: 4px solid #2196f3;
        }
        .error-box {
            background: #ffebee;
            border-left-color: #f44336;
        }
        .success-box {
            background: #e8f5e9;
            border-left-color: #4caf50;
        }
        .code-block {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            font-family: 'Courier New', monospace;
            font-size: 13px;
            overflow-x: auto;
            margin-top: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
            color: #333;
        }
        .password-hash {
            font-family: monospace;
            font-size: 11px;
            word-break: break-all;
            color: #666;
        }
        .action-button {
            background: #667eea;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 14px;
            margin-top: 10px;
            text-decoration: none;
            display: inline-block;
        }
        .action-button:hover {
            background: #5568d3;
        }
        .highlight {
            background: yellow;
            padding: 2px 4px;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Database Authentication Status Check</h1>
        <p class="subtitle">Comprehensive analysis of your authentication system setup</p>

<?php
// Test 1: Check database connection
echo '<div class="test-section">';
echo '<div class="test-title">Test 1: Database Connection';

try {
    require_once __DIR__ . '/includes/database.php';
    echo '<span class="status-badge success">✓ PASSED</span></div>';
    echo '<div class="result-box success-box">';
    echo '<strong>✓ Database connection successful!</strong><br>';
    echo 'Connected to: <strong>' . DB_NAME . '</strong>';
    echo '</div>';
} catch (Exception $e) {
    echo '<span class="status-badge error">✗ FAILED</span></div>';
    echo '<div class="result-box error-box">';
    echo '<strong>✗ Database connection failed!</strong><br>';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
    echo '</div></div></body></html>';
    exit;
}
echo '</div>';

// Test 2: Check if admin_users table exists
echo '<div class="test-section">';
echo '<div class="test-title">Test 2: Admin Users Table';

try {
    $result = executeQuery("SHOW TABLES LIKE 'admin_users'");
    if (count($result) > 0) {
        echo '<span class="status-badge success">✓ EXISTS</span></div>';
        echo '<div class="result-box success-box">';
        echo '<strong>✓ Table `admin_users` exists in database</strong>';
        echo '</div>';
        
        // Get table structure
        $structure = executeQuery("DESCRIBE admin_users");
        echo '<table>';
        echo '<thead><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr></thead>';
        echo '<tbody>';
        foreach ($structure as $col) {
            echo '<tr>';
            echo '<td><strong>' . htmlspecialchars($col['Field']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($col['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($col['Default'] ?? 'NULL') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<span class="status-badge error">✗ NOT FOUND</span></div>';
        echo '<div class="result-box error-box">';
        echo '<strong>✗ Table `admin_users` does NOT exist!</strong><br>';
        echo 'The SQL script may not have been executed properly.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<span class="status-badge error">✗ ERROR</span></div>';
    echo '<div class="result-box error-box">';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 3: Check if login_logs table exists
echo '<div class="test-section">';
echo '<div class="test-title">Test 3: Login Logs Table';

try {
    $result = executeQuery("SHOW TABLES LIKE 'login_logs'");
    if (count($result) > 0) {
        echo '<span class="status-badge success">✓ EXISTS</span></div>';
        echo '<div class="result-box success-box">';
        echo '<strong>✓ Table `login_logs` exists in database</strong>';
        echo '</div>';
    } else {
        echo '<span class="status-badge warning">⚠ NOT FOUND</span></div>';
        echo '<div class="result-box error-box">';
        echo '<strong>⚠ Table `login_logs` does NOT exist!</strong><br>';
        echo 'This table is optional but recommended for audit logging.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<span class="status-badge error">✗ ERROR</span></div>';
    echo '<div class="result-box error-box">';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 4: Check admin users
echo '<div class="test-section">';
echo '<div class="test-title">Test 4: Admin Users in Database';

try {
    $users = executeQuery("SELECT id, username, email, full_name, role, status, login_attempts, locked_until, last_login, password FROM admin_users");
    
    if (count($users) > 0) {
        echo '<span class="status-badge success">✓ FOUND ' . count($users) . ' USER(S)</span></div>';
        echo '<div class="result-box success-box">';
        echo '<strong>✓ Found ' . count($users) . ' admin user(s) in database</strong>';
        echo '</div>';
        
        echo '<table>';
        echo '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th><th>Attempts</th><th>Locked Until</th><th>Password Hash</th></tr></thead>';
        echo '<tbody>';
        foreach ($users as $user) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($user['id']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($user['email']) . '</td>';
            echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
            echo '<td><span class="status-badge info">' . htmlspecialchars($user['role']) . '</span></td>';
            echo '<td><span class="status-badge ' . ($user['status'] == 'active' ? 'success' : 'error') . '">' . htmlspecialchars($user['status']) . '</span></td>';
            echo '<td>' . htmlspecialchars($user['login_attempts']) . '</td>';
            echo '<td>' . htmlspecialchars($user['locked_until'] ?? 'Not locked') . '</td>';
            echo '<td><div class="password-hash">' . htmlspecialchars(substr($user['password'], 0, 30)) . '...</div></td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<span class="status-badge error">✗ NO USERS</span></div>';
        echo '<div class="result-box error-box">';
        echo '<strong>✗ No admin users found in database!</strong><br>';
        echo 'The INSERT statements may not have been executed.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<span class="status-badge error">✗ ERROR</span></div>';
    echo '<div class="result-box error-box">';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 5: Test password verification with admin user
echo '<div class="test-section">';
echo '<div class="test-title">Test 5: Password Verification Test';

try {
    $admin = executeSingleQuery("SELECT username, password, status, login_attempts, locked_until FROM admin_users WHERE username = 'admin'");
    
    if ($admin) {
        echo '<span class="status-badge info">TESTING...</span></div>';
        
        // Check if account is locked
        if ($admin['locked_until'] && strtotime($admin['locked_until']) > time()) {
            echo '<div class="result-box error-box">';
            echo '<strong>⚠ ACCOUNT IS LOCKED!</strong><br>';
            echo 'Locked until: <span class="highlight">' . htmlspecialchars($admin['locked_until']) . '</span><br>';
            echo 'Login attempts: <span class="highlight">' . htmlspecialchars($admin['login_attempts']) . '</span><br><br>';
            echo '<strong>FIX: Run this SQL query to unlock:</strong>';
            echo '<div class="code-block">UPDATE admin_users SET login_attempts = 0, locked_until = NULL WHERE username = \'admin\';</div>';
            echo '</div>';
        } else {
            echo '<div class="result-box success-box">';
            echo '<strong>✓ Account is NOT locked</strong><br>';
            echo 'Status: <span class="highlight">' . htmlspecialchars($admin['status']) . '</span><br>';
            echo 'Login attempts: <span class="highlight">' . htmlspecialchars($admin['login_attempts']) . '</span>';
            echo '</div>';
        }
        
        // Test password verification
        $testPassword = 'admin123';
        $storedHash = $admin['password'];
        
        echo '<div class="result-box">';
        echo '<strong>Password Hash Analysis:</strong><br>';
        echo 'Stored hash: <div class="password-hash">' . htmlspecialchars($storedHash) . '</div>';
        echo 'Expected hash: <div class="password-hash">$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi</div>';
        
        if ($storedHash === '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi') {
            echo '<br><span class="status-badge success">✓ Hash matches expected value</span>';
        } else {
            echo '<br><span class="status-badge error">✗ Hash does NOT match expected value</span>';
        }
        echo '</div>';
        
        // Test password_verify
        echo '<div class="result-box">';
        echo '<strong>Testing password_verify() with "' . htmlspecialchars($testPassword) . '":</strong><br><br>';
        
        if (password_verify($testPassword, $storedHash)) {
            echo '<span class="status-badge success">✓ PASSWORD VERIFICATION SUCCESSFUL!</span><br><br>';
            echo 'The password "<strong>' . htmlspecialchars($testPassword) . '</strong>" correctly verifies against the stored hash.<br>';
            echo 'Login should work if account is active and not locked.';
        } else {
            echo '<span class="status-badge error">✗ PASSWORD VERIFICATION FAILED!</span><br><br>';
            echo 'The password "<strong>' . htmlspecialchars($testPassword) . '</strong>" does NOT verify against the stored hash.<br><br>';
            echo '<strong>🔧 FIX: Reset the password with this SQL:</strong>';
            echo '<div class="code-block">UPDATE admin_users SET password = \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\', login_attempts = 0, locked_until = NULL WHERE username = \'admin\';</div>';
        }
        echo '</div>';
        
    } else {
        echo '<span class="status-badge error">✗ USER NOT FOUND</span></div>';
        echo '<div class="result-box error-box">';
        echo '<strong>✗ User "admin" not found in database!</strong><br>';
        echo 'You need to insert the admin user.';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<span class="status-badge error">✗ ERROR</span></div>';
    echo '<div class="result-box error-box">';
    echo 'Error: ' . htmlspecialchars($e->getMessage());
    echo '</div>';
}
echo '</div>';

// Test 6: Check recent login attempts
echo '<div class="test-section">';
echo '<div class="test-title">Test 6: Recent Login Attempts';

try {
    $logs = executeQuery("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 10");
    
    if (count($logs) > 0) {
        echo '<span class="status-badge info">FOUND ' . count($logs) . ' LOG(S)</span></div>';
        echo '<div class="result-box">';
        echo '<strong>Recent login attempts (last 10):</strong>';
        echo '</div>';
        
        echo '<table>';
        echo '<thead><tr><th>Time</th><th>Username</th><th>IP</th><th>Status</th><th>Reason</th></tr></thead>';
        echo '<tbody>';
        foreach ($logs as $log) {
            $statusClass = $log['status'] == 'success' ? 'success' : 'error';
            echo '<tr>';
            echo '<td>' . htmlspecialchars($log['login_time']) . '</td>';
            echo '<td><strong>' . htmlspecialchars($log['username']) . '</strong></td>';
            echo '<td>' . htmlspecialchars($log['ip_address']) . '</td>';
            echo '<td><span class="status-badge ' . $statusClass . '">' . htmlspecialchars($log['status']) . '</span></td>';
            echo '<td>' . htmlspecialchars($log['failure_reason'] ?? '-') . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    } else {
        echo '<span class="status-badge warning">NO LOGS</span></div>';
        echo '<div class="result-box">';
        echo '<strong>No login attempts recorded yet.</strong>';
        echo '</div>';
    }
} catch (Exception $e) {
    echo '<span class="status-badge warning">TABLE NOT FOUND</span></div>';
    echo '<div class="result-box">';
    echo 'Login logs table does not exist yet (this is optional).';
    echo '</div>';
}
echo '</div>';

// Summary and recommendations
echo '<div class="test-section" style="border-color: #667eea; background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));">';
echo '<div class="test-title" style="color: #667eea;">📋 Summary & Next Steps</div>';
echo '<div class="result-box">';

// Determine the issue
$adminExists = false;
$passwordWorks = false;
$isLocked = false;

try {
    $admin = executeSingleQuery("SELECT username, password, locked_until FROM admin_users WHERE username = 'admin'");
    if ($admin) {
        $adminExists = true;
        $passwordWorks = password_verify('admin123', $admin['password']);
        $isLocked = $admin['locked_until'] && strtotime($admin['locked_until']) > time();
    }
} catch (Exception $e) {
    // Ignore
}

if (!$adminExists) {
    echo '<h3 style="color: #f44336;">❌ Problem: Admin user does not exist</h3>';
    echo '<p><strong>Solution:</strong> Run the INSERT statement from auth-database.sql</p>';
} elseif ($isLocked) {
    echo '<h3 style="color: #ff9800;">⚠️ Problem: Account is LOCKED due to failed login attempts</h3>';
    echo '<p><strong>Solution:</strong> Run this SQL query to unlock:</p>';
    echo '<div class="code-block">UPDATE admin_users SET login_attempts = 0, locked_until = NULL WHERE username = \'admin\';</div>';
    echo '<p style="margin-top: 10px;"><strong>Then try logging in again with username: admin, password: admin123</strong></p>';
} elseif (!$passwordWorks) {
    echo '<h3 style="color: #f44336;">❌ Problem: Password hash is incorrect</h3>';
    echo '<p><strong>Solution:</strong> Reset the password with this SQL query:</p>';
    echo '<div class="code-block">UPDATE admin_users SET password = \'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi\', login_attempts = 0, locked_until = NULL WHERE username = \'admin\';</div>';
} else {
    echo '<h3 style="color: #4caf50;">✅ Everything looks good!</h3>';
    echo '<p><strong>Login should work with:</strong></p>';
    echo '<ul style="margin-left: 20px; margin-top: 10px;">';
    echo '<li>Username: <span class="highlight">admin</span></li>';
    echo '<li>Password: <span class="highlight">admin123</span></li>';
    echo '</ul>';
    echo '<p style="margin-top: 15px;"><a href="login.html" class="action-button">→ Go to Login Page</a></p>';
}

echo '</div>';
echo '</div>';
?>

    </div>
</body>
</html>
