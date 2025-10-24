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
            $sql = "SELECT mr.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM medical_records mr
                    LEFT JOIN patients p ON mr.patient_id = p.id
                    LEFT JOIN doctors d ON mr.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    ORDER BY mr.visit_date DESC";
            
            $reports = executeQuery($sql);
            
            // Get statistics
            $today = date('Y-m-d');
            $thisWeekStart = date('Y-m-d', strtotime('monday this week'));
            $thisWeekEnd = date('Y-m-d', strtotime('sunday this week'));
            
            $stats = [
                'total' => count($reports),
                'today' => count(array_filter($reports, fn($r) => $r['visit_date'] === $today)),
                'this_week' => count(array_filter($reports, fn($r) => 
                    $r['visit_date'] >= $thisWeekStart && $r['visit_date'] <= $thisWeekEnd
                )),
                'follow_ups' => count(array_filter($reports, fn($r) => 
                    !empty($r['follow_up_date']) && $r['follow_up_date'] >= $today
                )),
                'unique_patients' => count(array_unique(array_column($reports, 'patient_id')))
            ];
            
            echo json_encode([
                'success' => true,
                'data' => $reports,
                'stats' => $stats
            ]);
            break;
            
        case 'filter':
            $where = [];
            $params = [];
            
            if (!empty($input['doctor'])) {
                $where[] = "mr.doctor_id = ?";
                $params[] = $input['doctor'];
            }
            
            if (!empty($input['department'])) {
                $where[] = "d.department_id = ?";
                $params[] = $input['department'];
            }
            
            if (!empty($input['follow_up'])) {
                $today = date('Y-m-d');
                switch ($input['follow_up']) {
                    case 'with_followup':
                        $where[] = "mr.follow_up_date IS NOT NULL";
                        break;
                    case 'without_followup':
                        $where[] = "mr.follow_up_date IS NULL";
                        break;
                    case 'followup_due':
                        $where[] = "mr.follow_up_date = ?";
                        $params[] = $today;
                        break;
                    case 'followup_overdue':
                        $where[] = "mr.follow_up_date < ?";
                        $params[] = $today;
                        break;
                }
            }
            
            if (!empty($input['date_from'])) {
                $where[] = "mr.visit_date >= ?";
                $params[] = $input['date_from'];
            }
            
            if (!empty($input['date_to'])) {
                $where[] = "mr.visit_date <= ?";
                $params[] = $input['date_to'];
            }
            
            if (!empty($input['search'])) {
                $where[] = "(p.name LIKE ? OR mr.diagnosis LIKE ? OR mr.symptoms LIKE ?)";
                $searchTerm = "%{$input['search']}%";
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            $sql = "SELECT mr.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM medical_records mr
                    LEFT JOIN patients p ON mr.patient_id = p.id
                    LEFT JOIN doctors d ON mr.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY mr.visit_date DESC";
            
            // Store the SQL for code display
            $lastFilterSQL = $sql . " -- Parameters: " . json_encode($params);
            
            $reports = executeQuery($sql, $params);
            
            echo json_encode([
                'success' => true,
                'data' => $reports,
                'sql_code' => $lastFilterSQL
            ]);
            break;
            
        case 'get':
            $id = $_GET['id'] ?? null;
            if (!$id) {
                throw new Exception('Medical Record ID is required');
            }
            
            $sql = "SELECT mr.*, 
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name
                    FROM medical_records mr
                    LEFT JOIN patients p ON mr.patient_id = p.id
                    LEFT JOIN doctors d ON mr.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id
                    WHERE mr.id = ?";
            
            $report = executeSingleQuery($sql, [$id]);
            
            if (!$report) {
                throw new Exception('Medical Record not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $report
            ]);
            break;
            
        case 'create':
            $required = ['patient_id', 'doctor_id', 'visit_date', 'diagnosis'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "INSERT INTO medical_records (patient_id, doctor_id, appointment_id, 
                    diagnosis, symptoms, treatment_plan, medication_prescribed, 
                    visit_date, follow_up_date, medical_notes) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $input['patient_id'],
                $input['doctor_id'],
                !empty($input['appointment_id']) ? $input['appointment_id'] : null,
                $input['diagnosis'],
                $input['symptoms'] ?? null,
                $input['treatment_plan'] ?? null,
                $input['medication_prescribed'] ?? null,
                $input['visit_date'],
                !empty($input['follow_up_date']) ? $input['follow_up_date'] : null,
                $input['medical_notes'] ?? null
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Medical Record created successfully',
                'id' => $result['lastInsertId']
            ]);
            break;
            
        case 'edit':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Medical Record ID is required');
            }
            
            $required = ['patient_id', 'doctor_id', 'visit_date', 'diagnosis'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    throw new Exception("Field '$field' is required");
                }
            }
            
            $sql = "UPDATE medical_records SET patient_id = ?, doctor_id = ?, 
                    appointment_id = ?, diagnosis = ?, symptoms = ?, treatment_plan = ?, 
                    medication_prescribed = ?, visit_date = ?, follow_up_date = ?, 
                    medical_notes = ? WHERE id = ?";
            
            $params = [
                $input['patient_id'],
                $input['doctor_id'],
                !empty($input['appointment_id']) ? $input['appointment_id'] : null,
                $input['diagnosis'],
                $input['symptoms'] ?? null,
                $input['treatment_plan'] ?? null,
                $input['medication_prescribed'] ?? null,
                $input['visit_date'],
                !empty($input['follow_up_date']) ? $input['follow_up_date'] : null,
                $input['medical_notes'] ?? null,
                $id
            ];
            
            $result = executeModifyQuery($sql, $params);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Medical Record updated successfully'
            ]);
            break;
            
        case 'delete':
            $id = $input['id'] ?? null;
            if (!$id) {
                throw new Exception('Medical Record ID is required');
            }
            
            $sql = "DELETE FROM medical_records WHERE id = ?";
            $result = executeModifyQuery($sql, [$id]);
            
            if (isset($result['error'])) {
                throw new Exception($result['error']);
            }
            
            if ($result['rowCount'] === 0) {
                throw new Exception('Medical Record not found');
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Medical Record deleted successfully'
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