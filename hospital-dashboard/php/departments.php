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
            
            // Handle dropdown filters instead of search
            if (!empty($input['name'])) {
                $where[] = "d.name = ?";
                $params[] = $input['name'];
            }
            
            if (!empty($input['location'])) {
                $where[] = "d.location = ?";
                $params[] = $input['location'];
            }
            
            if (!empty($input['contact_number'])) {
                $where[] = "d.contact_number = ?";
                $params[] = $input['contact_number'];
            }
            
            if (!empty($input['head_doctor_name'])) {
                $where[] = "doc.name = ?";
                $params[] = $input['head_doctor_name'];
            }
            
            if (!empty($input['created_date'])) {
                $where[] = "DATE(d.created_at) = ?";
                $params[] = $input['created_date'];
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
            
        case 'get_filter_options':
            // Get distinct values for filter dropdowns
            $options = [];
            
            // Get distinct names
            $sql = "SELECT DISTINCT name FROM departments WHERE name IS NOT NULL AND name != '' ORDER BY name";
            $options['names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct locations
            $sql = "SELECT DISTINCT location FROM departments WHERE location IS NOT NULL AND location != '' ORDER BY location";
            $options['locations'] = array_column(executeQuery($sql), 'location');
            
            // Get distinct contact numbers
            $sql = "SELECT DISTINCT contact_number FROM departments WHERE contact_number IS NOT NULL AND contact_number != '' ORDER BY contact_number";
            $options['contact_numbers'] = array_column(executeQuery($sql), 'contact_number');
            
            // Get distinct head doctor names
            $sql = "SELECT DISTINCT doc.name FROM departments d 
                    LEFT JOIN doctors doc ON d.head_doctor_id = doc.id 
                    WHERE doc.name IS NOT NULL ORDER BY doc.name";
            $options['head_doctor_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct created dates
            $sql = "SELECT DISTINCT DATE(created_at) as created_date FROM departments WHERE created_at IS NOT NULL ORDER BY created_date DESC";
            $options['created_dates'] = array_column(executeQuery($sql), 'created_date');
            
            echo json_encode([
                'success' => true,
                'data' => $options
            ]);
            break;
            
        case 'export_csv':
            // Get filtered data for CSV export
            $where = [];
            $params = [];
            
            // Apply same filters as filter action
            if (!empty($input['name'])) {
                $where[] = "d.name = ?";
                $params[] = $input['name'];
            }
            
            if (!empty($input['location'])) {
                $where[] = "d.location = ?";
                $params[] = $input['location'];
            }
            
            if (!empty($input['contact_number'])) {
                $where[] = "d.contact_number = ?";
                $params[] = $input['contact_number'];
            }
            
            if (!empty($input['head_doctor_name'])) {
                $where[] = "doc.name = ?";
                $params[] = $input['head_doctor_name'];
            }
            
            if (!empty($input['created_date'])) {
                $where[] = "DATE(d.created_at) = ?";
                $params[] = $input['created_date'];
            }
            
            $sql = "SELECT d.id,
                           d.name,
                           d.description,
                           d.contact_number,
                           d.location,
                           doc.name as head_doctor_name,
                           DATE(d.created_at) as created_date,
                           (SELECT COUNT(*) FROM doctors WHERE department_id = d.id) as doctor_count
                    FROM departments d
                    LEFT JOIN doctors doc ON d.head_doctor_id = doc.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY d.name ASC";
            
            $departments = executeQuery($sql, $params);
            
            // Generate CSV content
            $csvContent = "ID,Name,Description,Contact Number,Location,Head Doctor,Created Date,Doctor Count\n";
            
            foreach ($departments as $dept) {
                $csvContent .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $dept['id'],
                    str_replace('"', '""', $dept['name'] ?? ''),
                    str_replace('"', '""', $dept['description'] ?? ''),
                    str_replace('"', '""', $dept['contact_number'] ?? ''),
                    str_replace('"', '""', $dept['location'] ?? ''),
                    str_replace('"', '""', $dept['head_doctor_name'] ?? ''),
                    $dept['created_date'] ?? '',
                    $dept['doctor_count']
                );
            }
            
            // Set headers for file download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="departments_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Content-Length: ' . strlen($csvContent));
            
            echo $csvContent;
            exit;
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