<?php
// Direct test for patients filtering
header('Content-Type: application/json');

require_once 'includes/database.php';

try {
    echo "<h2>Testing Patients Filtering</h2>";
    
    // Test 1: Get all patients
    echo "<h3>Test 1: All Patients</h3>";
    $allPatients = executeQuery("SELECT * FROM patients ORDER BY name ASC");
    echo "Total patients: " . count($allPatients) . "<br>";
    foreach ($allPatients as $patient) {
        echo "- " . $patient['name'] . " (" . $patient['gender'] . ")<br>";
    }
    
    // Test 2: Filter by Male gender
    echo "<h3>Test 2: Male Patients Only</h3>";
    $malePatients = executeQuery("SELECT * FROM patients WHERE gender = ? ORDER BY name ASC", ['Male']);
    echo "Male patients: " . count($malePatients) . "<br>";
    foreach ($malePatients as $patient) {
        echo "- " . $patient['name'] . " (" . $patient['gender'] . ")<br>";
    }
    
    // Test 3: Simulate filter API call
    echo "<h3>Test 3: Simulate API Filter Call</h3>";
    $input = [
        'name' => '',
        'gender' => 'Male',
        'blood_group' => '',
        'phone' => '',
        'email' => '',
        'birth_year' => '',
        'registered_date' => ''
    ];
    
    $where = [];
    $params = [];
    
    if (!empty($input['gender'])) {
        $where[] = "gender = ?";
        $params[] = $input['gender'];
    }
    
    $sql = "SELECT * FROM patients";
    if (!empty($where)) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }
    $sql .= " ORDER BY name ASC";
    
    echo "Generated SQL: " . $sql . "<br>";
    echo "Parameters: " . json_encode($params) . "<br>";
    
    $filteredPatients = executeQuery($sql, $params);
    echo "Filtered patients: " . count($filteredPatients) . "<br>";
    foreach ($filteredPatients as $patient) {
        echo "- " . $patient['name'] . " (" . $patient['gender'] . ")<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>