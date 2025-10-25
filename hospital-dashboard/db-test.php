<?php
require_once 'includes/database.php';

try {
    $conn = getDBConnection();
    echo "Database connection successful!<br>";
    
    // Test query
    $result = executeQuery("SELECT COUNT(*) as count FROM patients");
    echo "Patients count: " . $result[0]['count'] . "<br>";
    
    $result = executeQuery("SELECT COUNT(*) as count FROM departments");
    echo "Departments count: " . $result[0]['count'] . "<br>";
    
    $result = executeQuery("SELECT COUNT(*) as count FROM doctors");
    echo "Doctors count: " . $result[0]['count'] . "<br>";
    
    $result = executeQuery("SELECT COUNT(*) as count FROM appointments");
    echo "Appointments count: " . $result[0]['count'] . "<br>";
    
    $result = executeQuery("SELECT COUNT(*) as count FROM medical_records");
    echo "Medical records count: " . $result[0]['count'] . "<br>";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>