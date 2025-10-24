<?php
/**
 * Hospital Dashboard Setup Checker
 * This script checks if everything is properly configured
 */

// Set error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<!DOCTYPE html>
<html>
<head>
    <title>Hospital Dashboard - Setup Check</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { color: #27ae60; }
        .error { color: #e74c3c; }
        .warning { color: #f39c12; }
        .step { margin: 20px 0; padding: 15px; border-left: 4px solid #3498db; background: #f8f9fa; }
        h1 { color: #2c3e50; }
        h2 { color: #34495e; }
        .status-box { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .status-success { background: #d4edda; border: 1px solid #c3e6cb; }
        .status-error { background: #f8d7da; border: 1px solid #f5c6cb; }
        .status-warning { background: #fff3cd; border: 1px solid #ffeaa7; }
        pre { background: #f1f2f6; padding: 10px; border-radius: 4px; overflow-x: auto; }
        .btn { background: #3498db; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin: 5px; }
        .btn:hover { background: #2980b9; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🏥 Hospital Management Dashboard - Setup Check</h1>
        <p>This page will verify that your hospital dashboard is properly configured.</p>";

// Check 1: Database connection
echo "<div class='step'>
        <h2>Step 1: Database Connection</h2>";

try {
    require_once 'includes/database.php';
    $conn = getDBConnection();
    
    if ($conn) {
        echo "<div class='status-box status-success'>✅ <strong>Database connection successful!</strong></div>";
        
        // Check 2: Tables exist
        echo "<h3>Checking database tables:</h3>";
        
        $tables = ['departments', 'doctors', 'patients', 'appointments', 'medical_records'];
        $allTablesExist = true;
        
        foreach ($tables as $table) {
            try {
                $stmt = $conn->prepare("SELECT COUNT(*) as count FROM `$table`");
                $stmt->execute();
                $result = $stmt->fetch();
                $count = $result['count'];
                echo "<div class='status-box status-success'>✅ Table '$table': $count records</div>";
            } catch (Exception $e) {
                echo "<div class='status-box status-error'>❌ Table '$table': Missing or error - " . $e->getMessage() . "</div>";
                $allTablesExist = false;
            }
        }
        
        if (!$allTablesExist) {
            echo "<div class='status-box status-warning'>
                    <strong>⚠️ Some tables are missing!</strong><br>
                    Please import the database.sql file using phpMyAdmin:
                    <ol>
                        <li>Open phpMyAdmin (http://localhost/phpmyadmin)</li>
                        <li>Select 'hospital_management' database</li>
                        <li>Click 'Import' tab</li>
                        <li>Choose the 'database.sql' file from your project</li>
                        <li>Click 'Go'</li>
                    </ol>
                  </div>";
        }
        
    } else {
        throw new Exception("Failed to connect to database");
    }
    
} catch (Exception $e) {
    echo "<div class='status-box status-error'>
            ❌ <strong>Database connection failed!</strong><br>
            Error: " . $e->getMessage() . "<br><br>
            <strong>To fix this:</strong>
            <ol>
                <li>Make sure XAMPP is running (Apache & MySQL)</li>
                <li>Create database 'hospital_management' in phpMyAdmin</li>
                <li>Check database credentials in includes/database.php</li>
            </ol>
          </div>";
}

echo "</div>";

// Check 3: PHP Files
echo "<div class='step'>
        <h2>Step 2: PHP API Files</h2>";

$phpFiles = ['overview.php', 'patients.php', 'departments.php', 'doctors.php', 'appointments.php', 'medical-reports.php'];
$allFilesExist = true;

foreach ($phpFiles as $file) {
    $filePath = "php/$file";
    if (file_exists($filePath)) {
        echo "<div class='status-box status-success'>✅ $filePath exists</div>";
    } else {
        echo "<div class='status-box status-error'>❌ $filePath missing</div>";
        $allFilesExist = false;
    }
}

echo "</div>";

// Check 4: Web server access
echo "<div class='step'>
        <h2>Step 3: API Endpoints Test</h2>";

if ($allFilesExist) {
    echo "<p>Testing API endpoints:</p>";
    
    $endpoints = [
        'overview.php' => 'Overview Statistics',
        'patients.php?action=list' => 'Patients List',
        'departments.php?action=list' => 'Departments List',
        'doctors.php?action=list' => 'Doctors List'
    ];
    
    foreach ($endpoints as $endpoint => $description) {
        $url = "php/$endpoint";
        echo "<div class='status-box status-success'>
                📋 <strong>$description</strong><br>
                <a href='$url' target='_blank' class='btn'>Test $endpoint</a>
              </div>";
    }
} else {
    echo "<div class='status-box status-error'>❌ Cannot test endpoints - some PHP files are missing</div>";
}

echo "</div>";

// Check 5: Dashboard access
echo "<div class='step'>
        <h2>Step 4: Dashboard Access</h2>
        <div class='status-box status-success'>
            🎯 <strong>Main Dashboard</strong><br>
            <a href='index.html' target='_blank' class='btn'>Open Hospital Dashboard</a>
        </div>
      </div>";

// Configuration info
echo "<div class='step'>
        <h2>Current Configuration</h2>
        <div class='status-box status-warning'>
            <strong>Database Settings:</strong><br>";

if (file_exists('includes/database.php')) {
    $dbContent = file_get_contents('includes/database.php');
    if (preg_match("/private \\\$host = '([^']+)'/", $dbContent, $matches)) {
        echo "Host: " . $matches[1] . "<br>";
    }
    if (preg_match("/private \\\$db_name = '([^']+)'/", $dbContent, $matches)) {
        echo "Database: " . $matches[1] . "<br>";
    }
    if (preg_match("/private \\\$username = '([^']+)'/", $dbContent, $matches)) {
        echo "Username: " . $matches[1] . "<br>";
    }
}

echo "        </div>
      </div>";

// Next steps
echo "<div class='step'>
        <h2>Next Steps</h2>
        <div class='status-box status-success'>
            <strong>If everything shows ✅ above:</strong>
            <ol>
                <li>Click 'Open Hospital Dashboard' to start using the system</li>
                <li>Test adding patients, doctors, departments</li>
                <li>Try the filtering and search features</li>
                <li>Explore the overview charts and statistics</li>
            </ol>
        </div>
        
        <div class='status-box status-warning'>
            <strong>If you see ❌ errors:</strong>
            <ol>
                <li>Make sure XAMPP Apache and MySQL are running</li>
                <li>Import database.sql into phpMyAdmin</li>
                <li>Check database connection settings</li>
                <li>Refresh this page after fixing issues</li>
            </ol>
        </div>
      </div>";

echo "    </div>
</body>
</html>";
?>