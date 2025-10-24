<?php
// Test database connection
require_once 'includes/database.php';

echo "<h2>Database Connection Test</h2>";

try {
    $conn = getDBConnection();
    
    if ($conn) {
        echo "<p style='color: green;'>✓ Database connection successful!</p>";
        
        // Test if tables exist
        $tables = ['patients', 'doctors', 'departments', 'appointments', 'medical_records'];
        
        foreach ($tables as $table) {
            try {
                $result = executeQuery("SELECT COUNT(*) as count FROM $table");
                if (isset($result[0]['count'])) {
                    echo "<p style='color: green;'>✓ Table '$table' exists with {$result[0]['count']} records</p>";
                } else {
                    echo "<p style='color: orange;'>⚠ Table '$table' query returned unexpected result</p>";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>✗ Table '$table' error: " . $e->getMessage() . "</p>";
            }
        }
        
        // Test overview.php directly
        echo "<h3>Testing Overview Data</h3>";
        
        // Test patients count
        $patients = executeQuery("SELECT COUNT(*) as count FROM patients");
        echo "<p>Patients count: " . ($patients[0]['count'] ?? 'Error') . "</p>";
        
        // Test doctors count
        $doctors = executeQuery("SELECT COUNT(*) as count FROM doctors");
        echo "<p>Doctors count: " . ($doctors[0]['count'] ?? 'Error') . "</p>";
        
        // Test appointments count
        $appointments = executeQuery("SELECT COUNT(*) as count FROM appointments");
        echo "<p>Appointments count: " . ($appointments[0]['count'] ?? 'Error') . "</p>";
        
        // Test departments count
        $departments = executeQuery("SELECT COUNT(*) as count FROM departments");
        echo "<p>Departments count: " . ($departments[0]['count'] ?? 'Error') . "</p>";
        
    } else {
        echo "<p style='color: red;'>✗ Failed to connect to database</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>PHP Info</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>PDO MySQL: " . (extension_loaded('pdo_mysql') ? 'Available' : 'Not Available') . "</p>";
?>