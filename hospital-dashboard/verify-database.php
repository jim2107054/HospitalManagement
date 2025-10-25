<?php
// Quick Database Check
require_once 'includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Database Check</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
        .success { color: #27ae60; background: #d5f4e6; padding: 10px; margin: 10px 0; border-left: 4px solid #27ae60; }
        .error { color: #e74c3c; background: #fadbd8; padding: 10px; margin: 10px 0; border-left: 4px solid #e74c3c; }
        .info { color: #3498db; background: #d6eaf8; padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; background: white; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #3498db; color: white; }
        pre { background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 5px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Database Verification</h1>

<?php
try {
    // Check if admin_users table exists
    echo "<h2>1️⃣ Checking admin_users table...</h2>";
    $tables = executeQuery("SHOW TABLES LIKE 'admin_users'");
    
    if (count($tables) === 0) {
        echo "<div class='error'>❌ Table 'admin_users' does NOT exist!</div>";
        echo "<div class='info'>You need to run the auth-database.sql script in phpMyAdmin</div>";
        die();
    } else {
        echo "<div class='success'>✅ Table 'admin_users' exists</div>";
    }
    
    // Check for admin users
    echo "<h2>2️⃣ Checking admin users...</h2>";
    $users = executeQuery("SELECT id, username, email, full_name, role, status, LEFT(password, 30) as password_preview FROM admin_users");
    
    if (count($users) === 0) {
        echo "<div class='error'>❌ No admin users found!</div>";
        echo "<div class='info'>Run this SQL query in phpMyAdmin to insert admin user:</div>";
        echo "<pre>INSERT INTO admin_users (username, email, password, full_name, role, status) 
VALUES (
  'admin', 
  'admin@hospital.com', 
  '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
  'System Administrator',
  'super_admin',
  'active'
);</pre>";
    } else {
        echo "<div class='success'>✅ Found " . count($users) . " admin user(s)</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th><th>Password Hash (preview)</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($user['id']) . "</td>";
            echo "<td><strong>" . htmlspecialchars($user['username']) . "</strong></td>";
            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td>" . htmlspecialchars($user['role']) . "</td>";
            echo "<td>" . htmlspecialchars($user['status']) . "</td>";
            echo "<td><code>" . htmlspecialchars($user['password_preview']) . "...</code></td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Test password verification
    echo "<h2>3️⃣ Testing password verification...</h2>";
    $testPassword = 'admin123';
    $correctHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    if (password_verify($testPassword, $correctHash)) {
        echo "<div class='success'>✅ Password verification function works correctly</div>";
        echo "<div class='info'>Password 'admin123' matches the expected hash</div>";
    } else {
        echo "<div class='error'>❌ Password verification failed!</div>";
    }
    
    // Test actual user password
    echo "<h2>4️⃣ Testing actual admin user password...</h2>";
    $adminUser = executeSingleQuery("SELECT username, password FROM admin_users WHERE username = 'admin'");
    
    if ($adminUser) {
        echo "<div class='info'>Found admin user: <strong>" . htmlspecialchars($adminUser['username']) . "</strong></div>";
        echo "<div class='info'>Password hash: <code>" . htmlspecialchars($adminUser['password']) . "</code></div>";
        
        if (password_verify('admin123', $adminUser['password'])) {
            echo "<div class='success'>✅✅✅ Password 'admin123' is CORRECT for user 'admin'</div>";
            echo "<div class='info'><strong>You should be able to login with:</strong><br>Username: admin<br>Password: admin123</div>";
        } else {
            echo "<div class='error'>❌ Password 'admin123' does NOT match the stored hash</div>";
            echo "<div class='info'>The password might have been changed or corrupted. Run this SQL to reset:</div>";
            echo "<pre>UPDATE admin_users 
SET password = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' 
WHERE username = 'admin';</pre>";
        }
    } else {
        echo "<div class='error'>❌ No user found with username 'admin'</div>";
        echo "<div class='info'>User might have been deleted or not created. Insert it with the SQL above.</div>";
    }
    
    // Check login_logs table
    echo "<h2>5️⃣ Checking recent login attempts...</h2>";
    $logs = executeQuery("SELECT * FROM login_logs ORDER BY login_time DESC LIMIT 5");
    
    if (count($logs) > 0) {
        echo "<div class='info'>Recent login attempts:</div>";
        echo "<table>";
        echo "<tr><th>Username</th><th>Status</th><th>Time</th><th>IP Address</th><th>Reason</th></tr>";
        foreach ($logs as $log) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($log['username']) . "</td>";
            echo "<td>" . htmlspecialchars($log['status']) . "</td>";
            echo "<td>" . htmlspecialchars($log['login_time']) . "</td>";
            echo "<td>" . htmlspecialchars($log['ip_address']) . "</td>";
            echo "<td>" . htmlspecialchars($log['failure_reason'] ?? '-') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='info'>No login attempts recorded yet</div>";
    }
    
} catch (Exception $e) {
    echo "<div class='error'>❌ Error: " . htmlspecialchars($e->getMessage()) . "</div>";
}
?>

    <h2>📋 Summary</h2>
    <div class='info'>
        <p><strong>If all checks passed, you should be able to login with:</strong></p>
        <ul>
            <li><strong>Username:</strong> admin</li>
            <li><strong>Password:</strong> admin123</li>
        </ul>
        <p>If password verification failed, copy the SQL UPDATE query above and run it in phpMyAdmin.</p>
    </div>

</body>
</html>
