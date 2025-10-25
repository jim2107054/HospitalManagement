<?php
/**
 * Quick script to unlock all accounts and reset login attempts
 * Run this once to clear any existing locks
 */

require_once 'includes/database.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unlock All Accounts</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 40px;
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            padding: 40px;
            max-width: 600px;
            width: 100%;
        }
        h1 {
            color: #333;
            margin-bottom: 10px;
        }
        .success {
            background: #4caf50;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .error {
            background: #f44336;
            color: white;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        .info {
            background: #e3f2fd;
            color: #1976d2;
            padding: 15px;
            border-radius: 8px;
            margin: 20px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: #f5f5f5;
            font-weight: 600;
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
        }
        .btn:hover {
            background: #5568d3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔓 Unlock All Accounts</h1>
        
        <?php
        try {
            // Reset all login attempts and locked_until for all users
            $sql = "UPDATE admin_users SET login_attempts = 0, locked_until = NULL";
            $affected = executeModifyQuery($sql);
            
            echo '<div class="success">';
            echo '<strong>✓ Success!</strong><br>';
            echo "Unlocked <strong>{$affected}</strong> admin account(s).<br>";
            echo "All login attempts have been reset to 0.";
            echo '</div>';
            
            // Show current user status
            $users = executeQuery("SELECT username, email, full_name, role, status, login_attempts, locked_until, last_login FROM admin_users");
            
            if (count($users) > 0) {
                echo '<div class="info">';
                echo '<strong>Current Admin Users:</strong>';
                echo '</div>';
                
                echo '<table>';
                echo '<thead><tr><th>Username</th><th>Full Name</th><th>Status</th><th>Last Login</th></tr></thead>';
                echo '<tbody>';
                foreach ($users as $user) {
                    echo '<tr>';
                    echo '<td><strong>' . htmlspecialchars($user['username']) . '</strong></td>';
                    echo '<td>' . htmlspecialchars($user['full_name']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['status']) . '</td>';
                    echo '<td>' . htmlspecialchars($user['last_login'] ?? 'Never') . '</td>';
                    echo '</tr>';
                }
                echo '</tbody></table>';
            }
            
            echo '<div class="info" style="margin-top: 20px;">';
            echo '<strong>📌 Important Notes:</strong><br>';
            echo '• Login attempt restrictions have been <strong>PERMANENTLY REMOVED</strong><br>';
            echo '• Admin users can now attempt login unlimited times<br>';
            echo '• No account lockouts will occur<br>';
            echo '• Default password for all accounts: <strong>admin123</strong>';
            echo '</div>';
            
            echo '<a href="login.html" class="btn">→ Go to Login Page</a>';
            
        } catch (Exception $e) {
            echo '<div class="error">';
            echo '<strong>✗ Error:</strong><br>';
            echo htmlspecialchars($e->getMessage());
            echo '</div>';
        }
        ?>
    </div>
</body>
</html>
