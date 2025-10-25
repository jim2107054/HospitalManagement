<?php
/**
 * Fix Admin Password - Generate fresh hash and update
 */
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/includes/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fix Admin Password</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            max-width: 700px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
        }
        h1 { color: #333; margin-bottom: 20px; }
        .success { background: #4caf50; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .error { background: #f44336; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .info { background: #2196f3; color: white; padding: 20px; border-radius: 8px; margin: 20px 0; }
        .code {
            background: #263238;
            color: #aed581;
            padding: 15px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 12px;
            overflow-x: auto;
            margin: 10px 0;
            word-break: break-all;
        }
        .btn {
            background: #667eea;
            color: white;
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-size: 16px;
        }
        .btn:hover { background: #5568d3; }
        .highlight { background: yellow; color: #333; padding: 2px 6px; font-weight: 600; border-radius: 3px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Fix Admin Password</h1>
        
        <?php
        try {
            // Generate a FRESH password hash
            $password = 'admin123';
            $freshHash = password_hash($password, PASSWORD_DEFAULT);
            
            echo '<div class="info">';
            echo '<strong>Step 1: Generated fresh password hash</strong><br><br>';
            echo 'Password: <span class="highlight">' . htmlspecialchars($password) . '</span><br>';
            echo 'New Hash:<br>';
            echo '<div class="code">' . htmlspecialchars($freshHash) . '</div>';
            echo '</div>';
            
            // Verify the fresh hash works
            if (password_verify($password, $freshHash)) {
                echo '<div class="success">';
                echo '✅ <strong>Fresh hash verification: SUCCESS</strong><br>';
                echo 'The newly generated hash works correctly!';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ <strong>Fresh hash verification: FAILED</strong><br>';
                echo 'Something is wrong with PHP password functions!';
                echo '</div>';
                exit;
            }
            
            // Update the database
            echo '<div class="info">';
            echo '<strong>Step 2: Updating database...</strong>';
            echo '</div>';
            
            $sql = "UPDATE admin_users SET password = ? WHERE username = 'admin'";
            $affected = executeModifyQuery($sql, [$freshHash]);
            
            if ($affected > 0) {
                echo '<div class="success">';
                echo '✅ <strong>Database updated successfully!</strong><br>';
                echo 'Updated ' . $affected . ' row(s)';
                echo '</div>';
            } else {
                echo '<div class="error">';
                echo '❌ <strong>No rows updated!</strong><br>';
                echo 'The admin user might not exist.';
                echo '</div>';
                exit;
            }
            
            // Verify the update by reading back
            echo '<div class="info">';
            echo '<strong>Step 3: Verifying the update...</strong>';
            echo '</div>';
            
            $admin = executeSingleQuery("SELECT username, password FROM admin_users WHERE username = 'admin'");
            
            if ($admin) {
                $storedHash = $admin['password'];
                
                echo '<div class="info">';
                echo 'Password hash now in database:<br>';
                echo '<div class="code">' . htmlspecialchars($storedHash) . '</div>';
                echo '</div>';
                
                // Test verification with the stored hash
                if (password_verify($password, $storedHash)) {
                    echo '<div class="success">';
                    echo '<h2>✅ SUCCESS!</h2>';
                    echo '<p style="margin-top:10px;">Password verification now works correctly!</p>';
                    echo '<p style="margin-top:10px;">You can now login with:</p>';
                    echo '<ul style="margin-left:20px; margin-top:10px; list-style:none;">';
                    echo '<li>👤 Username: <span class="highlight">admin</span></li>';
                    echo '<li>🔑 Password: <span class="highlight">admin123</span></li>';
                    echo '</ul>';
                    echo '<a href="login.html" class="btn">→ Go to Login Page</a>';
                    echo '</div>';
                } else {
                    echo '<div class="error">';
                    echo '❌ <strong>Still not working!</strong><br><br>';
                    echo 'The password hash was updated but still fails verification.<br>';
                    echo 'This might be a PHP or MySQL character encoding issue.';
                    echo '</div>';
                }
            } else {
                echo '<div class="error">';
                echo '❌ Could not find admin user after update!';
                echo '</div>';
            }
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>❌ Error:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
        
    </div>
</body>
</html>
