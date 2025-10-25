<?php
// Test Authentication System Setup
header('Content-Type: text/html; charset=utf-8');

echo "<h1>🔧 Authentication System Diagnostic Test</h1>";
echo "<style>
    body { font-family: Arial, sans-serif; padding: 20px; background: #f5f5f5; }
    .success { color: #27ae60; background: #d5f4e6; padding: 10px; margin: 10px 0; border-left: 4px solid #27ae60; }
    .error { color: #e74c3c; background: #fadbd8; padding: 10px; margin: 10px 0; border-left: 4px solid #e74c3c; }
    .info { color: #3498db; background: #d6eaf8; padding: 10px; margin: 10px 0; border-left: 4px solid #3498db; }
    table { border-collapse: collapse; width: 100%; margin: 10px 0; background: white; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background: #3498db; color: white; }
    .section { background: white; padding: 20px; margin: 20px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
</style>";

// Test 1: Check if database.php exists
echo "<div class='section'>";
echo "<h2>Test 1: Database Connection File</h2>";
$dbFile = '../includes/database.php';
if (file_exists($dbFile)) {
    echo "<div class='success'>✅ database.php file exists</div>";
} else {
    echo "<div class='error'>❌ database.php file NOT found at: " . realpath('.') . "/$dbFile</div>";
    echo "<div class='info'>Expected location: c:\\xampp\\htdocs\\db\\hospital-dashboard\\includes\\database.php</div>";
}
echo "</div>";

// Test 2: Try to include database.php
echo "<div class='section'>";
echo "<h2>Test 2: Include Database Functions</h2>";
try {
    require_once '../includes/database.php';
    echo "<div class='success'>✅ database.php included successfully</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Error including database.php: " . $e->getMessage() . "</div>";
    die();
}
echo "</div>";

// Test 3: Check database connection
echo "<div class='section'>";
echo "<h2>Test 3: Database Connection</h2>";
try {
    $conn = getDBConnection();
    echo "<div class='success'>✅ Database connection successful</div>";
} catch (Exception $e) {
    echo "<div class='error'>❌ Database connection failed: " . $e->getMessage() . "</div>";
    echo "<div class='info'>💡 Check config.php settings (database name, username, password)</div>";
    die();
}
echo "</div>";

// Test 4: Check if admin_users table exists
echo "<div class='section'>";
echo "<h2>Test 4: Check admin_users Table</h2>";
try {
    $result = executeQuery("SHOW TABLES LIKE 'admin_users'");
    if (count($result) > 0) {
        echo "<div class='success'>✅ admin_users table exists</div>";
    } else {
        echo "<div class='error'>❌ admin_users table NOT found</div>";
        echo "<div class='info'>💡 Run the auth-database.sql script in phpMyAdmin</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking table: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 5: Check if login_logs table exists
echo "<div class='section'>";
echo "<h2>Test 5: Check login_logs Table</h2>";
try {
    $result = executeQuery("SHOW TABLES LIKE 'login_logs'");
    if (count($result) > 0) {
        echo "<div class='success'>✅ login_logs table exists</div>";
    } else {
        echo "<div class='error'>❌ login_logs table NOT found</div>";
        echo "<div class='info'>💡 Run the auth-database.sql script in phpMyAdmin</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error checking table: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 6: Check admin users
echo "<div class='section'>";
echo "<h2>Test 6: Admin Users</h2>";
try {
    $users = executeQuery("SELECT id, username, email, full_name, role, status FROM admin_users");
    if (count($users) > 0) {
        echo "<div class='success'>✅ Found " . count($users) . " admin user(s)</div>";
        echo "<table>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Full Name</th><th>Role</th><th>Status</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>" . $user['id'] . "</td>";
            echo "<td>" . $user['username'] . "</td>";
            echo "<td>" . $user['email'] . "</td>";
            echo "<td>" . $user['full_name'] . "</td>";
            echo "<td>" . $user['role'] . "</td>";
            echo "<td>" . $user['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='error'>❌ No admin users found</div>";
        echo "<div class='info'>💡 Run the auth-database.sql script to insert default users</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error fetching users: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 7: Test password verification
echo "<div class='section'>";
echo "<h2>Test 7: Password Hash Test</h2>";
try {
    $testPassword = 'admin123';
    $storedHash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
    
    if (password_verify($testPassword, $storedHash)) {
        echo "<div class='success'>✅ Password verification works correctly</div>";
        echo "<div class='info'>Test password 'admin123' matches the stored hash</div>";
    } else {
        echo "<div class='error'>❌ Password verification failed</div>";
        echo "<div class='info'>This might indicate a PHP version issue</div>";
    }
} catch (Exception $e) {
    echo "<div class='error'>❌ Error testing password: " . $e->getMessage() . "</div>";
}
echo "</div>";

// Test 8: Check PHP version
echo "<div class='section'>";
echo "<h2>Test 8: PHP Configuration</h2>";
echo "<div class='info'>📋 PHP Version: " . phpversion() . "</div>";
echo "<div class='info'>📋 Session Save Path: " . session_save_path() . "</div>";
echo "<div class='info'>📋 Session Started: " . (session_status() === PHP_SESSION_ACTIVE ? 'Yes' : 'No') . "</div>";
echo "</div>";

// Test 9: Test auth.php endpoint
echo "<div class='section'>";
echo "<h2>Test 9: Test Login API</h2>";
echo "<div class='info'>
    <strong>To test login manually:</strong><br>
    1. Open browser console (F12)<br>
    2. Paste this code:<br>
    <pre style='background: #2c3e50; color: #ecf0f1; padding: 10px; border-radius: 5px; overflow-x: auto;'>
fetch('php/auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        action: 'login',
        username: 'admin',
        password: 'admin123'
    })
})
.then(r => r.text())
.then(t => {
    console.log('Raw response:', t);
    try {
        const data = JSON.parse(t);
        console.log('Parsed:', data);
    } catch(e) {
        console.error('Parse error:', e);
    }
});
    </pre>
    3. Check the console output
</div>";
echo "</div>";

// Summary
echo "<div class='section'>";
echo "<h2>📊 Summary</h2>";
echo "<div class='info'>";
echo "<strong>Next Steps:</strong><br>";
echo "1. If any tests failed, fix those issues first<br>";
echo "2. If all tests pass, try logging in again<br>";
echo "3. Check browser console (F12) for JavaScript errors<br>";
echo "4. Try the manual test in Test 9 above<br>";
echo "</div>";
echo "</div>";

echo "<div class='section'>";
echo "<p><strong>Test completed at:</strong> " . date('Y-m-d H:i:s') . "</p>";
echo "</div>";
?>
