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
            $sql = "SELECT d.*, 
                           doc.name as head_doctor_name,
                           (SELECT COUNT(*) FROM doctors WHERE department_id = d.id) as doctor_count
                    FROM departments d
                    LEFT JOIN doctors doc ON d.head_doctor_id = doc.id
                    ORDER BY d.name ASC";
            
            $departments = executeQuery($sql);
            
            // Get statistics
            $stats = [
                'total' => count($departments),
                'with_head' => count(array_filter($departments, fn($d) => !empty($d['head_doctor_name']))),
                'doctors' => array_sum(array_column($departments, 'doctor_count')),
                'active' => count($departments) // All departments are considered active
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $departments,
                'stats' => $stats
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            if (!empty($input['search'])) {
                $where[] = "(d.name LIKE ? OR d.location LIKE ? OR d.description LIKE ?)";
                $searchTerm = "%{$input['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            if (!empty($input['location'])) {
                $where[] = "d.location LIKE ?";
                $params[] = "%{$input['location']}%";
            }
            
            if (isset($input['has_head']) && $input['has_head'] !== '') {
                if ($input['has_head'] == '1') {
                    $where[] = "d.head_doctor_id IS NOT NULL";
                } else {
                    $where[] = "d.head_doctor_id IS NULL";
                }
            }
            
            $sql = "SELECT d.*, 
                           doc.name as head_doctor_name,
                           (SELECT COUNT(*) FROM doctors WHERE department_id = d.id) as doctor_count
                    FROM departments d
                    LEFT JOIN doctors doc ON d.head_doctor_id = doc.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY d.name ASC";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $departments = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $departments,
                'sql_code' => $lastFilterSQL
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Department ID is required');
            }
            
            $sql = "SELECT d.*, 
                           doc.name as head_doctor_name,
                           (SELECT COUNT(*) FROM doctors WHERE department_id = d.id) as doctor_count
                    FROM departments d
                    LEFT JOIN doctors doc ON d.head_doctor_id = doc.id
                    WHERE d.id = ?";
            
            $department = executeSingleQuery($sql, [$id]);
            
            if (!$department) {
                throw new Exception('Department not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $department
            ]);
            break;
            
        case 'create':
            $required = ['name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "INSERT INTO departments (name, description, contact_number, location, head_doctor_id) 
                    VALUES (?, ?, ?, ?, ?)";
            
            $params = [
                $input['name'],
                $input['description'] ?? null,
                $input['contact_number'] ?? null,
                $input['location'] ?? null,
                !empty($input['head_doctor_id']) ? $input['head_doctor_id'] : null
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Department created successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Department ID is required');
            }
            
            $required = ['name'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "UPDATE departments SET name = ?, description = ?, contact_number = ?, 
                    location = ?, head_doctor_id = ? WHERE id = ?";
            
            $params = [
                $input['name'],
                $input['description'] ?? null,
                $input['contact_number'] ?? null,
                $input['location'] ?? null,
                !empty($input['head_doctor_id']) ? $input['head_doctor_id'] : null,
                $id
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Department updated successfully'
            ]);
            break;
            
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Department ID is required');
            }
            
            // Check if department has doctors
            $checkSql = "SELECT COUNT(*) as doctor_count FROM doctors WHERE department_id = ?";
            $check = executeSingleQuery($checkSql, [$id]);
            
            if ($check['doctor_count'] > 0) {
                throw new Exception('Cannot delete department with assigned doctors. Please reassign doctors first.');
            }
            
            $sql = "DELETE FROM departments WHERE id = ?";
            $result = executeModifyQuery($sql, [$id]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            if ($result['rowCount'] === 0) {
                throw new Exception('Department not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Department deleted successfully'
            ]);
            break;
            
        case 'get_doctors':
            // Get all doctors for dropdown
            $sql = "SELECT id, name FROM doctors ORDER BY name ASC";
            $doctors = executeQuery($sql);
            
            echo json_encode([
                'success' => true,
                'data' => $doctors
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