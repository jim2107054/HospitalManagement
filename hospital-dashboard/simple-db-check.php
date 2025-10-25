<?php
/**
 * Simple Database Check - No constants, just direct check
 */
header('Content-Type: text/html; charset=utf-8');

// Include database functions
require_once __DIR__ . '/includes/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simple Database Check</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 30px;
        }
        h1 { color: #333; margin-bottom: 30px; }
        .section {
            margin-bottom: 25px;
            padding: 20px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .success { background: #e8f5e9; border-color: #4caf50; }
        .error { background: #ffebee; border-color: #f44336; }
        .warning { background: #fff3e0; border-color: #ff9800; }
        h2 { color: #333; margin-bottom: 15px; font-size: 18px; }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
            margin-left: 10px;
        }
        .badge-success { background: #4caf50; color: white; }
        .badge-error { background: #f44336; color: white; }
        .badge-warning { background: #ff9800; color: white; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            background: white;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th { background: #f5f5f5; font-weight: 600; }
        .code {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin-top: 10px;
        }
        .highlight { background: yellow; padding: 2px 4px; font-weight: 600; }
        pre { white-space: pre-wrap; word-wrap: break-word; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 Simple Database Check</h1>

<?php
try {
    // Test 1: Check if admin_users table exists
    echo '<div class="section">';
    echo '<h2>1️⃣ Check if admin_users table exists</h2>';
    
    $tables = executeQuery("SHOW TABLES LIKE 'admin_users'");
    
    if (count($tables) > 0) {
        echo '<div class="section success">';
        echo '✅ <strong>Table admin_users EXISTS</strong>';
        echo '</div>';
    } else {
        echo '<div class="section error">';
        echo '❌ <strong>Table admin_users DOES NOT EXIST!</strong><br><br>';
        echo 'You need to run the SQL script. Go to phpMyAdmin and run auth-database.sql';
        echo '</div>';
        echo '</div></div></body></html>';
        exit;
    }
    echo '</div>';
    
    // Test 2: Check admin users
    echo '<div class="section">';
    echo '<h2>2️⃣ List all admin users</h2>';
    
    $users = executeQuery("SELECT id, username, email, full_name, status, password FROM admin_users");
    
    if (count($users) == 0) {
        echo '<div class="section error">';
        echo '❌ <strong>NO USERS FOUND!</strong><br><br>';
        echo 'Run this SQL in phpMyAdmin:<br>';
        echo '<div class="code">';
        echo "INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES<br>";
        echo "('admin', 'admin@hospital.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active');";
        echo '</div>';
        echo '</div>';
        echo '</div></div></body></html>';
        exit;
    }
    
    echo '<div class="section success">';
    echo '✅ <strong>Found ' . count($users) . ' user(s)</strong>';
    echo '</div>';
    
    echo '<table>';
    echo '<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Status</th><th>Password Hash (first 30 chars)</th></tr>';
    foreach ($users as $user) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($user['id']) . '</td>';
        echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
        echo '<td>' . htmlspecialchars($user['email']) . '</td>';
        echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
        echo '<td><span class="badge badge-' . ($user['status'] == 'active' ? 'success' : 'error') . '">' . $user['status'] . '</span></td>';
        echo '<td style="font-family:monospace; font-size:10px;">' . htmlspecialchars(substr($user['password'], 0, 30)) . '...</td>';
        echo '</tr>';
    }
    echo '</table>';
    echo '</div>';
    
    // Test 3: Check admin user specifically
    echo '<div class="section">';
    echo '<h2>3️⃣ Check "admin" user specifically</h2>';
    
    $admin = executeSingleQuery("SELECT username, password, status FROM admin_users WHERE username = 'admin'");
    
    if (!$admin) {
        echo '<div class="section error">';
        echo '❌ <strong>User "admin" NOT FOUND!</strong><br><br>';
        echo 'Run this SQL:<br>';
        echo '<div class="code">';
        echo "INSERT INTO admin_users (username, email, password, full_name, role, status) VALUES<br>";
        echo "('admin', 'admin@hospital.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 'super_admin', 'active');";
        echo '</div>';
        echo '</div>';
        echo '</div></div></body></html>';
        exit;
    }
    
    echo '<div class="section success">';
    echo '✅ <strong>User "admin" found!</strong><br>';
    echo 'Status: <span class="highlight">' . htmlspecialchars($admin['status']) . '</span>';
    echo '</div>';
    echo '</div>';
    
    // Test 4: Password verification
    echo '<div class="section">';
    echo '<h2>4️⃣ Test password verification</h2>';
    
    $testPassword = 'admin123';
    $storedHash = $admin['password'];
    $expectedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    echo '<p><strong>Testing password:</strong> <span class="highlight">' . htmlspecialchars($testPassword) . '</span></p>';
    echo '<p><strong>Stored hash:</strong></p>';
    echo '<pre style="font-size:10px; background:#f5f5f5; padding:10px; border-radius:4px;">' . htmlspecialchars($storedHash) . '</pre>';
    echo '<p><strong>Expected hash:</strong></p>';
    echo '<pre style="font-size:10px; background:#f5f5f5; padding:10px; border-radius:4px;">' . htmlspecialchars($expectedHash) . '</pre>';
    
    // Check if hashes match
    if ($storedHash === $expectedHash) {
        echo '<div class="section success">';
        echo '✅ <strong>Hash matches expected value!</strong>';
        echo '</div>';
    } else {
        echo '<div class="section error">';
        echo '❌ <strong>Hash does NOT match expected value!</strong>';
        echo '</div>';
    }
    
    // Test password_verify
    echo '<p style="margin-top:20px;"><strong>Testing password_verify() function:</strong></p>';
    
    if (password_verify($testPassword, $storedHash)) {
        echo '<div class="section success">';
        echo '✅ <strong>PASSWORD VERIFICATION SUCCESSFUL!</strong><br><br>';
        echo 'Password "' . htmlspecialchars($testPassword) . '" correctly verifies!<br>';
        echo 'Login SHOULD work with username: <span class="highlight">admin</span> and password: <span class="highlight">admin123</span>';
        echo '</div>';
    } else {
        echo '<div class="section error">';
        echo '❌ <strong>PASSWORD VERIFICATION FAILED!</strong><br><br>';
        echo 'Password "' . htmlspecialchars($testPassword) . '" does NOT verify against stored hash!<br><br>';
        echo '<strong>FIX: Run this SQL in phpMyAdmin:</strong><br>';
        echo '<div class="code">';
        echo "UPDATE admin_users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';";
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // Final summary
    echo '<div class="section" style="border-color:#667eea; background:linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));">';
    echo '<h2>📋 Summary</h2>';
    
    if (password_verify($testPassword, $storedHash)) {
        echo '<div class="section success">';
        echo '<h3>✅ Everything looks GOOD!</h3>';
        echo '<p style="margin-top:10px;">You should be able to login with:</p>';
        echo '<ul style="margin-left:20px; margin-top:10px;">';
        echo '<li>Username: <span class="highlight">admin</span></li>';
        echo '<li>Password: <span class="highlight">admin123</span></li>';
        echo '</ul>';
        echo '<p style="margin-top:15px;"><a href="login.html" style="background:#667eea; color:white; padding:10px 20px; text-decoration:none; border-radius:6px; display:inline-block;">→ Try Login Again</a></p>';
        echo '</div>';
    } else {
        echo '<div class="section error">';
        echo '<h3>❌ Problem Found: Password hash is incorrect</h3>';
        echo '<p style="margin-top:10px;"><strong>Action Required:</strong> Copy and run this SQL in phpMyAdmin:</p>';
        echo '<div class="code">';
        echo "UPDATE admin_users SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';";
        echo '</div>';
        echo '</div>';
    }
    
    echo '</div>';
    
} catch (Exception $e) {
    echo '<div class="section error">';
    echo '<h2>❌ ERROR</h2>';
    echo '<p>' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '</div>';
}
?>

    </div>
</body>
</html>
