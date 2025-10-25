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
            $sql = "SELECT a.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM appointments a
                    LEFT JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
            $appointments = executeQuery($sql);
            
            // Get statistics
            $today = date('Y-m-d');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            $thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
            
            $stats = [
                'total' => count($appointments),
                'today' => count(array_filter($appointments, fn($a) => $a['appointment_date'] === $today)),
                'scheduled' => count(array_filter($appointments, fn($a) => $a['status'] === 'Scheduled')),
                'completed' => count(array_filter($appointments, fn($a) => $a['status'] === 'Completed')),
                'upcoming' => count(array_filter($appointments, fn($a) => 
                    $a['appointment_date'] >= $thisWeekStart && 
                    $a['appointment_date'] <= $thisWeekEnd && 
                    $a['status'] === 'Scheduled'
                ))
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $appointments,
                'stats' => $stats
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            // Handle dropdown filters
            if (!empty($input['patient_name'])) {
                $where[] = "p.name = ?";
                $params[] = $input['patient_name'];
            }
            
            if (!empty($input['doctor_name'])) {
                $where[] = "d.name = ?";
                $params[] = $input['doctor_name'];
            }
            
            if (!empty($input['department_name'])) {
                $where[] = "dept.name = ?";
                $params[] = $input['department_name'];
            }
            
            // Handle date range
            if (!empty($input['date_from'])) {
                $where[] = "a.appointment_date >= ?";
                $params[] = $input['date_from'];
            }
            
            if (!empty($input['date_to'])) {
                $where[] = "a.appointment_date <= ?";
                $params[] = $input['date_to'];
            }
            
            if (!empty($input['status'])) {
                $where[] = "a.status = ?";
                $params[] = $input['status'];
            }
            
            $sql = "SELECT a.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM appointments a
                    LEFT JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            
            // Handle sorting with whitelist validation
            $sort_by = $input['sort_by'] ?? 'appointment_date';
            $sort_order = strtoupper($input['sort_order'] ?? 'DESC');
            
            // Whitelist for allowed sort columns
            $allowed_sort = ['appointment_date', 'patient_name', 'doctor_name', 'status'];
            if (!in_array($sort_by, $allowed_sort)) {
                $sort_by = 'appointment_date';
            }
            
            // Validate sort order
            if (!in_array($sort_order, ['ASC', 'DESC'])) {
                $sort_order = 'DESC';
            }
            
            // Map sort_by to actual column names if needed
            $sort_column_map = [
                'patient_name' => 'p.name',
                'doctor_name' => 'd.name',
                'appointment_date' => 'a.appointment_date',
                'status' => 'a.status'
            ];
            
            $sort_column = $sort_column_map[$sort_by] ?? 'a.appointment_date';
            $sql .= " ORDER BY {$sort_column} {$sort_order}";
            
            // Add secondary sort for consistency
            if ($sort_by !== 'appointment_date') {
                $sql .= ", a.appointment_date DESC";
            }
            $sql .= ", a.appointment_time DESC";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $appointments = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $appointments,
                'sql_code' => $lastFilterSQL
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Appointment ID is required');
            }
            
            $sql = "SELECT a.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM appointments a
                    LEFT JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    WHERE a.id = ?";
            
            $appointment = executeSingleQuery($sql, [$id]);
            
            if (!$appointment) {
                throw new Exception('Appointment not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $appointment
            ]);
            break;
            
        case 'create':
            $required = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check for conflicts
            $conflictSql = "SELECT COUNT(*) as count FROM appointments 
                           WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                           AND status IN ('Scheduled', 'Completed')";
            $conflict = executeSingleQuery($conflictSql, [
                $input['doctor_id'], 
                $input['appointment_date'], 
                $input['appointment_time']
            ]);
            
            if ($conflict['count'] > 0) {
                throw new Exception('Doctor is not available at this time slot');
            }
            
            $sql = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, 
                    appointment_time, status, reason_for_visit, consultation_fee, notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $input['patient_id'],
                $input['doctor_id'],
                $input['appointment_date'],
                $input['appointment_time'],
                $input['status'] ?? 'Scheduled',
                $input['reason_for_visit'] ?? null,
                $input['consultation_fee'] ?? null,
                $input['notes'] ?? null
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment created successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Appointment ID is required');
            }
            
            $required = ['patient_id', 'doctor_id', 'appointment_date', 'appointment_time'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            // Check for conflicts (excluding current appointment)
            $conflictSql = "SELECT COUNT(*) as count FROM appointments 
                           WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? 
                           AND status IN ('Scheduled', 'Completed') AND id != ?";
            $conflict = executeSingleQuery($conflictSql, [
                $input['doctor_id'], 
                $input['appointment_date'], 
                $input['appointment_time'],
                $id
            ]);
            
            if ($conflict['count'] > 0) {
                throw new Exception('Doctor is not available at this time slot');
            }
            
            $sql = "UPDATE appointments SET patient_id = ?, doctor_id = ?, appointment_date = ?, 
                    appointment_time = ?, status = ?, reason_for_visit = ?, 
                    consultation_fee = ?, notes = ? WHERE id = ?";
            
            $params = [
                $input['patient_id'],
                $input['doctor_id'],
                $input['appointment_date'],
                $input['appointment_time'],
                $input['status'] ?? 'Scheduled',
                $input['reason_for_visit'] ?? null,
                $input['consultation_fee'] ?? null,
                $input['notes'] ?? null,
                $id
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment updated successfully'
            ]);
            break;
            
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Appointment ID is required');
            }
            
            $sql = "DELETE FROM appointments WHERE id = ?";
            $result = executeModifyQuery($sql, [$id]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            if ($result['rowCount'] === 0) {
                throw new Exception('Appointment not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Appointment deleted successfully'
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
            
            // Get distinct patient names
            $sql = "SELECT DISTINCT p.name FROM appointments a 
                    LEFT JOIN patients p ON a.patient_id = p.id 
                    WHERE p.name IS NOT NULL ORDER BY p.name";
            $options['patient_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct doctor names
            $sql = "SELECT DISTINCT d.name FROM appointments a 
                    LEFT JOIN doctors d ON a.doctor_id = d.id 
                    WHERE d.name IS NOT NULL ORDER BY d.name";
            $options['doctor_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct department names
            $sql = "SELECT DISTINCT dept.name FROM appointments a 
                    LEFT JOIN doctors d ON a.doctor_id = d.id 
                    LEFT JOIN departments dept ON d.department_id = dept.id 
                    WHERE dept.name IS NOT NULL ORDER BY dept.name";
            $options['department_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct appointment dates
            $sql = "SELECT DISTINCT appointment_date FROM appointments WHERE appointment_date IS NOT NULL ORDER BY appointment_date DESC";
            $options['appointment_dates'] = array_column(executeQuery($sql), 'appointment_date');
            
            // Get distinct appointment times
            $sql = "SELECT DISTINCT appointment_time FROM appointments WHERE appointment_time IS NOT NULL ORDER BY appointment_time";
            $options['appointment_times'] = array_column(executeQuery($sql), 'appointment_time');
            
            // Get distinct statuses
            $sql = "SELECT DISTINCT status FROM appointments WHERE status IS NOT NULL AND status != '' ORDER BY status";
            $options['statuses'] = array_column(executeQuery($sql), 'status');
            
            // Get distinct reasons for visit
            $sql = "SELECT DISTINCT reason_for_visit FROM appointments WHERE reason_for_visit IS NOT NULL AND reason_for_visit != '' ORDER BY reason_for_visit";
            $options['reasons_for_visit'] = array_column(executeQuery($sql), 'reason_for_visit');
            
            // Get distinct consultation fees
            $sql = "SELECT DISTINCT consultation_fee FROM appointments WHERE consultation_fee IS NOT NULL ORDER BY consultation_fee";
            $options['consultation_fees'] = array_column(executeQuery($sql), 'consultation_fee');
            
            // Get distinct created dates
            $sql = "SELECT DISTINCT DATE(created_at) as created_date FROM appointments WHERE created_at IS NOT NULL ORDER BY created_date DESC";
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
            if (!empty($input['patient_name'])) {
                $where[] = "p.name = ?";
                $params[] = $input['patient_name'];
            }
            
            if (!empty($input['doctor_name'])) {
                $where[] = "d.name = ?";
                $params[] = $input['doctor_name'];
            }
            
            if (!empty($input['department_name'])) {
                $where[] = "dept.name = ?";
                $params[] = $input['department_name'];
            }
            
            if (!empty($input['appointment_date'])) {
                $where[] = "a.appointment_date = ?";
                $params[] = $input['appointment_date'];
            }
            
            if (!empty($input['appointment_time'])) {
                $where[] = "a.appointment_time = ?";
                $params[] = $input['appointment_time'];
            }
            
            if (!empty($input['status'])) {
                $where[] = "a.status = ?";
                $params[] = $input['status'];
            }
            
            if (!empty($input['reason_for_visit'])) {
                $where[] = "a.reason_for_visit = ?";
                $params[] = $input['reason_for_visit'];
            }
            
            if (!empty($input['consultation_fee'])) {
                $where[] = "a.consultation_fee = ?";
                $params[] = $input['consultation_fee'];
            }
            
            if (!empty($input['created_date'])) {
                $where[] = "DATE(a.created_at) = ?";
                $params[] = $input['created_date'];
            }
            
            $sql = "SELECT a.id,
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name,
                           a.appointment_date,
                           a.appointment_time,
                           a.status,
                           a.reason_for_visit,
                           a.consultation_fee,
                           a.notes,
                           DATE(a.created_at) as created_date
                    FROM appointments a
                    LEFT JOIN patients p ON a.patient_id = p.id
                    LEFT JOIN doctors d ON a.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
            $appointments = executeQuery($sql, $params);
            
            // Generate CSV content
            $csvContent = "ID,Patient Name,Doctor Name,Department,Appointment Date,Appointment Time,Status,Reason for Visit,Consultation Fee,Notes,Created Date\n";
            
            foreach ($appointments as $appointment) {
                $csvContent .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $appointment['id'],
                    str_replace('"', '""', $appointment['patient_name'] ?? ''),
                    str_replace('"', '""', $appointment['doctor_name'] ?? ''),
                    str_replace('"', '""', $appointment['department_name'] ?? ''),
                    $appointment['appointment_date'] ?? '',
                    $appointment['appointment_time'] ?? '',
                    str_replace('"', '""', $appointment['status'] ?? ''),
                    str_replace('"', '""', $appointment['reason_for_visit'] ?? ''),
                    $appointment['consultation_fee'] ?? '',
                    str_replace('"', '""', $appointment['notes'] ?? ''),
                    $appointment['created_date'] ?? ''
                );
            }
            
            // Set headers for file download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="appointments_' . date('Y-m-d_H-i-s') . '.csv"');
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