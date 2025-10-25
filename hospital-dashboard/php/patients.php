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
            // Get sorting parameters
            $sort_by = $_GET['sort_by'] ?? 'name';
            $sort_order = $_GET['sort_order'] ?? 'ASC';
            
            // Validate sort_by to prevent SQL injection
            $allowed_sort_fields = ['id', 'name', 'date_of_birth', 'gender', 'blood_group', 'phone', 'email', 'registered_at'];
            if (!in_array($sort_by, $allowed_sort_fields)) {
                $sort_by = 'name';
            }
            
            // Validate sort_order
            $sort_order = strtoupper($sort_order);
            if (!in_array($sort_order, ['ASC', 'DESC'])) {
                $sort_order = 'ASC';
            }
            
            $sql = "SELECT * FROM patients ORDER BY {$sort_by} {$sort_order}";
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
                'stats' => $stats,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            if (!empty($input['name'])) {
                $where[] = "name = ?";
                $params[] = $input['name'];
            }
            
            if (!empty($input['gender'])) {
                $where[] = "gender = ?";
                $params[] = $input['gender'];
            }
            
            if (!empty($input['blood_group'])) {
                $where[] = "blood_group = ?";
                $params[] = $input['blood_group'];
            }
            
            if (!empty($input['phone'])) {
                $where[] = "phone = ?";
                $params[] = $input['phone'];
            }
            
            if (!empty($input['email'])) {
                $where[] = "email = ?";
                $params[] = $input['email'];
            }
            
            if (!empty($input['birth_year'])) {
                $where[] = "YEAR(date_of_birth) = ?";
                $params[] = $input['birth_year'];
            }
            
            if (!empty($input['registered_date'])) {
                $where[] = "DATE(registered_at) = ?";
                $params[] = $input['registered_date'];
            }
            
            // Get sorting parameters
            $sort_by = $input['sort_by'] ?? 'name';
            $sort_order = $input['sort_order'] ?? 'ASC';
            
            // Validate sort_by to prevent SQL injection
            $allowed_sort_fields = ['id', 'name', 'date_of_birth', 'gender', 'blood_group', 'phone', 'email', 'registered_at'];
            if (!in_array($sort_by, $allowed_sort_fields)) {
                $sort_by = 'name';
            }
            
            // Validate sort_order
            $sort_order = strtoupper($sort_order);
            if (!in_array($sort_order, ['ASC', 'DESC'])) {
                $sort_order = 'ASC';
            }
            
            $sql = "SELECT * FROM patients";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY {$sort_by} {$sort_order}";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $patients = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $patients,
                'sql_code' => $lastFilterSQL,
                'sort_by' => $sort_by,
                'sort_order' => $sort_order
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
            
        case 'get_filter_options':
            // Get all unique values for dropdown filters
            $options = [];
            
            // Names
            $names = executeQuery("SELECT DISTINCT name FROM patients WHERE name IS NOT NULL ORDER BY name");
            $options['names'] = array_column($names, 'name');
            
            // Phone numbers
            $phones = executeQuery("SELECT DISTINCT phone FROM patients WHERE phone IS NOT NULL ORDER BY phone");
            $options['phones'] = array_column($phones, 'phone');
            
            // Emails
            $emails = executeQuery("SELECT DISTINCT email FROM patients WHERE email IS NOT NULL ORDER BY email");
            $options['emails'] = array_column($emails, 'email');
            
            // Birth years
            $birthYears = executeQuery("SELECT DISTINCT YEAR(date_of_birth) as birth_year FROM patients ORDER BY birth_year DESC");
            $options['birth_years'] = array_column($birthYears, 'birth_year');
            
            // Registration dates
            $regDates = executeQuery("SELECT DISTINCT DATE(registered_at) as reg_date FROM patients ORDER BY reg_date DESC");
            $options['registered_dates'] = array_column($regDates, 'reg_date');
            
            echo json_encode([
                'success' => true,
                'options' => $options
            ]);
            break;
            
        case 'export_csv':
            // Get all patients or filtered patients
            $where = [];
            $params = [];
            
            // Apply same filters as filter action
            if (!empty($input['name'])) {
                $where[] = "name = ?";
                $params[] = $input['name'];
            }
            if (!empty($input['gender'])) {
                $where[] = "gender = ?";
                $params[] = $input['gender'];
            }
            if (!empty($input['blood_group'])) {
                $where[] = "blood_group = ?";
                $params[] = $input['blood_group'];
            }
            if (!empty($input['phone'])) {
                $where[] = "phone = ?";
                $params[] = $input['phone'];
            }
            if (!empty($input['email'])) {
                $where[] = "email = ?";
                $params[] = $input['email'];
            }
            if (!empty($input['birth_year'])) {
                $where[] = "YEAR(date_of_birth) = ?";
                $params[] = $input['birth_year'];
            }
            if (!empty($input['registered_date'])) {
                $where[] = "DATE(registered_at) = ?";
                $params[] = $input['registered_date'];
            }
            
            $sql = "SELECT id, name, date_of_birth, gender, phone, email, address, blood_group, 
                           emergency_contact_name, emergency_contact_phone, insurance_number, registered_at 
                    FROM patients";
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY name ASC";
            
            $patients = executeQuery($sql, $params);
            
            // Generate CSV content
            $csvContent = "ID,Name,Date of Birth,Gender,Phone,Email,Address,Blood Group,Emergency Contact Name,Emergency Contact Phone,Insurance Number,Registered Date\n";
            
            foreach ($patients as $patient) {
                $csvContent .= sprintf(
                    "%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s,%s\n",
                    $patient['id'],
                    '"' . str_replace('"', '""', $patient['name']) . '"',
                    $patient['date_of_birth'],
                    $patient['gender'],
                    '"' . str_replace('"', '""', $patient['phone'] ?? '') . '"',
                    '"' . str_replace('"', '""', $patient['email'] ?? '') . '"',
                    '"' . str_replace('"', '""', $patient['address'] ?? '') . '"',
                    $patient['blood_group'] ?? '',
                    '"' . str_replace('"', '""', $patient['emergency_contact_name'] ?? '') . '"',
                    '"' . str_replace('"', '""', $patient['emergency_contact_phone'] ?? '') . '"',
                    '"' . str_replace('"', '""', $patient['insurance_number'] ?? '') . '"',
                    $patient['registered_at']
                );
            }
            
            // Set headers for CSV download
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="patients_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Content-Length: ' . strlen($csvContent));
            
            echo $csvContent;
            exit;
            
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