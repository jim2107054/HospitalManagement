<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../includes/database.php';

// Get the request method and input
$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);
$action = $_GET['action'] ?? $input['action'] ?? 'list';

// Handle different HTTP methods
if ($method === 'POST' && isset($input['action'])) {
    $action = $input['action'];
}

// Initialize SQL query generator for filtering
$lastFilterSQL = '';

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'list':
            $sql = "SELECT * FROM patients ORDER BY name ASC";
            $patients = executeQuery($sql);
            
            // Get statistics
            $stats = [
                'total' => count($patients),
                'male' => count(array_filter($patients, fn($p) => $p['gender'] === 'Male')),
                'female' => count(array_filter($patients, fn($p) => $p['gender'] === 'Female')),
                'today' => count(array_filter($patients, fn($p) => date('Y-m-d', strtotime($p['registered_at'])) === date('Y-m-d')))
            ];
            
            echo json_encode([
                'success' => true,
                'patients' => $patients,
                'stats' => $stats
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            if (!empty($input['gender'])) {
                $where[] = "gender = ?";
                $params[] = $input['gender'];
            }
            
            if (!empty($input['blood_group'])) {
                $where[] = "blood_group = ?";
                $params[] = $input['blood_group'];
            }
            
            if (!empty($input['date_from'])) {
                $where[] = "DATE(registered_at) >= ?";
                $params[] = $input['date_from'];
            }
            
            if (!empty($input['date_to'])) {
                $where[] = "DATE(registered_at) <= ?";
                $params[] = $input['date_to'];
            }
            
            if (!empty($input['search'])) {
                $where[] = "(name LIKE ? OR phone LIKE ? OR email LIKE ?)";
                $searchTerm = "%{$input['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql = "SELECT * FROM patients";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY name ASC";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $patients = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $patients,
                'sql_code' => $lastFilterSQL
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Patient ID is required');
            }
            
            $sql = "SELECT * FROM patients WHERE id = ?";
            $patient = executeSingleQuery($sql, [$id]);
            
            if (!$patient) {
                throw new Exception('Patient not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $patient
            ]);
            break;
            
        case 'create':
            $required = ['name', 'date_of_birth', 'gender'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "INSERT INTO patients (name, date_of_birth, gender, phone, email, address, blood_group, 
                    emergency_contact_name, emergency_contact_phone, insurance_number) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $input['name'],
                $input['date_of_birth'],
                $input['gender'],
                $input['phone'] ?? null,
                $input['email'] ?? null,
                $input['address'] ?? null,
                $input['blood_group'] ?? null,
                $input['emergency_contact_name'] ?? null,
                $input['emergency_contact_phone'] ?? null,
                $input['insurance_number'] ?? null
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient created successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Patient ID is required');
            }
            
            $required = ['name', 'date_of_birth', 'gender'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "UPDATE patients SET name = ?, date_of_birth = ?, gender = ?, phone = ?, 
                    email = ?, address = ?, blood_group = ?, emergency_contact_name = ?, 
                    emergency_contact_phone = ?, insurance_number = ? WHERE id = ?";
            
            $params = [
                $input['name'],
                $input['date_of_birth'],
                $input['gender'],
                $input['phone'] ?? null,
                $input['email'] ?? null,
                $input['address'] ?? null,
                $input['blood_group'] ?? null,
                $input['emergency_contact_name'] ?? null,
                $input['emergency_contact_phone'] ?? null,
                $input['insurance_number'] ?? null,
                $id
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient updated successfully'
            ]);
            break;
            
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Patient ID is required');
            }
            
            $sql = "DELETE FROM patients WHERE id = ?";
            $result = executeModifyQuery($sql, [$id]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            if ($result['rowCount'] === 0) {
                throw new Exception('Patient not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Patient deleted successfully'
            ]);
            break;
            
        case 'get_filter_sql':
            echo json_encode([
                'success' => true,
                'sql_code' => $lastFilterSQL ?: 'No filter applied yet'
            ]);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>