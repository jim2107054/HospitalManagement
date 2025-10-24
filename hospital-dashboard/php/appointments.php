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
            
            if (!empty($input['status'])) {
                $where[] = "a.status = ?";
                $params[] = $input['status'];
            }
            
            if (!empty($input['doctor'])) {
                $where[] = "a.doctor_id = ?";
                $params[] = $input['doctor'];
            }
            
            if (!empty($input['department'])) {
                $where[] = "d.department_id = ?";
                $params[] = $input['department'];
            }
            
            if (!empty($input['date_from'])) {
                $where[] = "a.appointment_date >= ?";
                $params[] = $input['date_from'];
            }
            
            if (!empty($input['date_to'])) {
                $where[] = "a.appointment_date <= ?";
                $params[] = $input['date_to'];
            }
            
            if (!empty($input['search'])) {
                $where[] = "(p.name LIKE ? OR d.name LIKE ? OR a.reason_for_visit LIKE ?)";
                $searchTerm = "%{$input['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
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
            $sql .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
            
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