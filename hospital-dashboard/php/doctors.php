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

// Initialize SQL query generator for filtering
$lastFilterSQL = '';

try {
    $conn = getDBConnection();
    
    switch ($action) {
        case 'list':
            $sql = "SELECT d.*, dept.name as department_name
                    FROM doctors d
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    ORDER BY d.name ASC";
            
            $doctors = executeQuery($sql);
            
            // Get statistics
            $today = date('Y-m-d');
            $stats = [
                'total' => count($doctors),
                'available' => count(array_filter($doctors, fn($d) => !empty($d['available_from']) && !empty($d['available_to']))),
                'specializations' => count(array_unique(array_column($doctors, 'specialization'))),
                'appointments_today' => 0 // We'll get this from appointments table
            ];
            
            // Get today's appointment count
            $appointmentSql = "SELECT COUNT(*) as count FROM appointments WHERE appointment_date = ?";
            $appointmentResult = executeSingleQuery($appointmentSql, [$today]);
            $stats['appointments_today'] = $appointmentResult['count'] ?? 0;
            
            echo json_encode([
                'success' => true,
                'data' => $doctors,
                'stats' => $stats
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            if (!empty($input['department'])) {
                $where[] = "d.department_id = ?";
                $params[] = $input['department'];
            }
            
            if (!empty($input['specialization'])) {
                $where[] = "d.specialization LIKE ?";
                $params[] = "%{$input['specialization']}%";
            }
            
            if (!empty($input['experience'])) {
                $experienceRange = explode('-', $input['experience']);
                if (count($experienceRange) === 2) {
                    $where[] = "d.experience_years >= ? AND d.experience_years <= ?";
                    $params[] = (int)$experienceRange[0];
                    $params[] = (int)$experienceRange[1];
                } elseif ($input['experience'] === '16+') {
                    $where[] = "d.experience_years >= ?";
                    $params[] = 16;
                }
            }
            
            if (!empty($input['availability'])) {
                if ($input['availability'] === 'available') {
                    $where[] = "d.available_from IS NOT NULL AND d.available_to IS NOT NULL";
                } elseif ($input['availability'] === 'busy') {
                    $where[] = "(d.available_from IS NULL OR d.available_to IS NULL)";
                }
            }
            
            if (!empty($input['search'])) {
                $where[] = "(d.name LIKE ? OR d.email LIKE ? OR d.license_number LIKE ?)";
                $searchTerm = "%{$input['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql = "SELECT d.*, dept.name as department_name
                    FROM doctors d
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY d.name ASC";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $doctors = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $doctors,
                'sql_code' => $lastFilterSQL
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Doctor ID is required');
            }
            
            $sql = "SELECT d.*, dept.name as department_name
                    FROM doctors d
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    WHERE d.id = ?";
            
            $doctor = executeSingleQuery($sql, [$id]);
            
            if (!$doctor) {
                throw new Exception('Doctor not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $doctor
            ]);
            break;
            
        case 'create':
            $required = ['name', 'specialization', 'department_id', 'license_number'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "INSERT INTO doctors (name, specialization, department_id, phone, email, 
                    license_number, experience_years, consultation_fee, available_from, available_to) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $input['name'],
                $input['specialization'],
                $input['department_id'],
                $input['phone'] ?? null,
                $input['email'] ?? null,
                $input['license_number'],
                $input['experience_years'] ?? null,
                $input['consultation_fee'] ?? null,
                $input['available_from'] ?? null,
                $input['available_to'] ?? null
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Doctor created successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Doctor ID is required');
            }
            
            $required = ['name', 'specialization', 'department_id', 'license_number'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "UPDATE doctors SET name = ?, specialization = ?, department_id = ?, 
                    phone = ?, email = ?, license_number = ?, experience_years = ?, 
                    consultation_fee = ?, available_from = ?, available_to = ? WHERE id = ?";
            
            $params = [
                $input['name'],
                $input['specialization'],
                $input['department_id'],
                $input['phone'] ?? null,
                $input['email'] ?? null,
                $input['license_number'],
                $input['experience_years'] ?? null,
                $input['consultation_fee'] ?? null,
                $input['available_from'] ?? null,
                $input['available_to'] ?? null,
                $id
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Doctor updated successfully'
            ]);
            break;
            
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Doctor ID is required');
            }
            
            // Check if doctor has appointments
            $checkSql = "SELECT COUNT(*) as appointment_count FROM appointments WHERE doctor_id = ?";
            $check = executeSingleQuery($checkSql, [$id]);
            
            if ($check['appointment_count'] > 0) {
                throw new Exception('Cannot delete doctor with existing appointments. Please handle appointments first.');
            }
            
            $sql = "DELETE FROM doctors WHERE id = ?";
            $result = executeModifyQuery($sql, [$id]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            if ($result['rowCount'] === 0) {
                throw new Exception('Doctor not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Doctor deleted successfully'
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