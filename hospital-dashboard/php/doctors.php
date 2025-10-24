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
            
            // Handle dropdown filters instead of search
            if (!empty($input['name'])) {
                $where[] = "d.name = ?";
                $params[] = $input['name'];
            }
            
            if (!empty($input['specialization'])) {
                $where[] = "d.specialization = ?";
                $params[] = $input['specialization'];
            }
            
            if (!empty($input['department_name'])) {
                $where[] = "dept.name = ?";
                $params[] = $input['department_name'];
            }
            
            if (!empty($input['phone'])) {
                $where[] = "d.phone = ?";
                $params[] = $input['phone'];
            }
            
            if (!empty($input['email'])) {
                $where[] = "d.email = ?";
                $params[] = $input['email'];
            }
            
            if (!empty($input['license_number'])) {
                $where[] = "d.license_number = ?";
                $params[] = $input['license_number'];
            }
            
            if (!empty($input['experience_years'])) {
                $where[] = "d.experience_years = ?";
                $params[] = $input['experience_years'];
            }
            
            if (!empty($input['consultation_fee'])) {
                $where[] = "d.consultation_fee = ?";
                $params[] = $input['consultation_fee'];
            }
            
            if (!empty($input['available_from'])) {
                $where[] = "d.available_from = ?";
                $params[] = $input['available_from'];
            }
            
            if (!empty($input['available_to'])) {
                $where[] = "d.available_to = ?";
                $params[] = $input['available_to'];
            }
            
            if (!empty($input['joined_date'])) {
                $where[] = "DATE(d.joined_at) = ?";
                $params[] = $input['joined_date'];
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
            
        case 'get_filter_options':
            // Get distinct values for filter dropdowns
            $options = [];
            
            // Get distinct names
            $sql = "SELECT DISTINCT name FROM doctors WHERE name IS NOT NULL AND name != '' ORDER BY name";
            $options['names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct specializations
            $sql = "SELECT DISTINCT specialization FROM doctors WHERE specialization IS NOT NULL AND specialization != '' ORDER BY specialization";
            $options['specializations'] = array_column(executeQuery($sql), 'specialization');
            
            // Get distinct department names
            $sql = "SELECT DISTINCT dept.name FROM doctors d 
                    LEFT JOIN departments dept ON d.department_id = dept.id 
                    WHERE dept.name IS NOT NULL ORDER BY dept.name";
            $options['department_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct phones
            $sql = "SELECT DISTINCT phone FROM doctors WHERE phone IS NOT NULL AND phone != '' ORDER BY phone";
            $options['phones'] = array_column(executeQuery($sql), 'phone');
            
            // Get distinct emails
            $sql = "SELECT DISTINCT email FROM doctors WHERE email IS NOT NULL AND email != '' ORDER BY email";
            $options['emails'] = array_column(executeQuery($sql), 'email');
            
            // Get distinct license numbers
            $sql = "SELECT DISTINCT license_number FROM doctors WHERE license_number IS NOT NULL AND license_number != '' ORDER BY license_number";
            $options['license_numbers'] = array_column(executeQuery($sql), 'license_number');
            
            // Get distinct experience years
            $sql = "SELECT DISTINCT experience_years FROM doctors WHERE experience_years IS NOT NULL ORDER BY experience_years";
            $options['experience_years'] = array_column(executeQuery($sql), 'experience_years');
            
            // Get distinct consultation fees
            $sql = "SELECT DISTINCT consultation_fee FROM doctors WHERE consultation_fee IS NOT NULL ORDER BY consultation_fee";
            $options['consultation_fees'] = array_column(executeQuery($sql), 'consultation_fee');
            
            // Get distinct available_from times
            $sql = "SELECT DISTINCT available_from FROM doctors WHERE available_from IS NOT NULL ORDER BY available_from";
            $options['available_from_times'] = array_column(executeQuery($sql), 'available_from');
            
            // Get distinct available_to times
            $sql = "SELECT DISTINCT available_to FROM doctors WHERE available_to IS NOT NULL ORDER BY available_to";
            $options['available_to_times'] = array_column(executeQuery($sql), 'available_to');
            
            // Get distinct joined dates
            $sql = "SELECT DISTINCT DATE(joined_at) as joined_date FROM doctors WHERE joined_at IS NOT NULL ORDER BY joined_date DESC";
            $options['joined_dates'] = array_column(executeQuery($sql), 'joined_date');
            
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
            
            if (!empty($input['specialization'])) {
                $where[] = "d.specialization = ?";
                $params[] = $input['specialization'];
            }
            
            if (!empty($input['department_name'])) {
                $where[] = "dept.name = ?";
                $params[] = $input['department_name'];
            }
            
            if (!empty($input['phone'])) {
                $where[] = "d.phone = ?";
                $params[] = $input['phone'];
            }
            
            if (!empty($input['email'])) {
                $where[] = "d.email = ?";
                $params[] = $input['email'];
            }
            
            if (!empty($input['license_number'])) {
                $where[] = "d.license_number = ?";
                $params[] = $input['license_number'];
            }
            
            if (!empty($input['experience_years'])) {
                $where[] = "d.experience_years = ?";
                $params[] = $input['experience_years'];
            }
            
            if (!empty($input['consultation_fee'])) {
                $where[] = "d.consultation_fee = ?";
                $params[] = $input['consultation_fee'];
            }
            
            if (!empty($input['available_from'])) {
                $where[] = "d.available_from = ?";
                $params[] = $input['available_from'];
            }
            
            if (!empty($input['available_to'])) {
                $where[] = "d.available_to = ?";
                $params[] = $input['available_to'];
            }
            
            if (!empty($input['joined_date'])) {
                $where[] = "DATE(d.joined_at) = ?";
                $params[] = $input['joined_date'];
            }
            
            $sql = "SELECT d.id,
                           d.name,
                           d.specialization,
                           dept.name as department_name,
                           d.phone,
                           d.email,
                           d.license_number,
                           d.experience_years,
                           d.consultation_fee,
                           d.available_from,
                           d.available_to,
                           DATE(d.joined_at) as joined_date
                    FROM doctors d
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY d.name ASC";
            
            $doctors = executeQuery($sql, $params);
            
            // Generate CSV content
            $csvContent = "ID,Name,Specialization,Department,Phone,Email,License Number,Experience Years,Consultation Fee,Available From,Available To,Joined Date\n";
            
            foreach ($doctors as $doctor) {
                $csvContent .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $doctor['id'],
                    str_replace('"', '""', $doctor['name'] ?? ''),
                    str_replace('"', '""', $doctor['specialization'] ?? ''),
                    str_replace('"', '""', $doctor['department_name'] ?? ''),
                    str_replace('"', '""', $doctor['phone'] ?? ''),
                    str_replace('"', '""', $doctor['email'] ?? ''),
                    str_replace('"', '""', $doctor['license_number'] ?? ''),
                    $doctor['experience_years'] ?? '',
                    $doctor['consultation_fee'] ?? '',
                    $doctor['available_from'] ?? '',
                    $doctor['available_to'] ?? '',
                    $doctor['joined_date'] ?? ''
                );
            }
            
            // Set headers for file download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="doctors_' . date('Y-m-d_H-i-s') . '.csv"');
            header('Content-Length: ' . strlen($csvContent));
            
            echo $csvContent;
            exit;
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