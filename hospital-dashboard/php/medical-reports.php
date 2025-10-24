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
            
            // Handle dropdown filters instead of search
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
            
            if (!empty($input['diagnosis'])) {
                $where[] = "mr.diagnosis = ?";
                $params[] = $input['diagnosis'];
            }
            
            if (!empty($input['symptoms'])) {
                $where[] = "mr.symptoms = ?";
                $params[] = $input['symptoms'];
            }
            
            if (!empty($input['treatment_plan'])) {
                $where[] = "mr.treatment_plan = ?";
                $params[] = $input['treatment_plan'];
            }
            
            if (!empty($input['medication_prescribed'])) {
                $where[] = "mr.medication_prescribed = ?";
                $params[] = $input['medication_prescribed'];
            }
            
            if (!empty($input['visit_date'])) {
                $where[] = "mr.visit_date = ?";
                $params[] = $input['visit_date'];
            }
            
            if (!empty($input['follow_up_date'])) {
                $where[] = "mr.follow_up_date = ?";
                $params[] = $input['follow_up_date'];
            }
            
            if (!empty($input['created_date'])) {
                $where[] = "DATE(mr.created_at) = ?";
                $params[] = $input['created_date'];
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
            
        case 'get_filter_options':
            // Get distinct values for filter dropdowns
            $options = [];
            
            // Get distinct patient names
            $sql = "SELECT DISTINCT p.name FROM medical_records mr 
                    LEFT JOIN patients p ON mr.patient_id = p.id 
                    WHERE p.name IS NOT NULL ORDER BY p.name";
            $options['patient_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct doctor names
            $sql = "SELECT DISTINCT d.name FROM medical_records mr 
                    LEFT JOIN doctors d ON mr.doctor_id = d.id 
                    WHERE d.name IS NOT NULL ORDER BY d.name";
            $options['doctor_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct department names
            $sql = "SELECT DISTINCT dept.name FROM medical_records mr 
                    LEFT JOIN doctors d ON mr.doctor_id = d.id 
                    LEFT JOIN departments dept ON d.department_id = dept.id 
                    WHERE dept.name IS NOT NULL ORDER BY dept.name";
            $options['department_names'] = array_column(executeQuery($sql), 'name');
            
            // Get distinct diagnoses
            $sql = "SELECT DISTINCT diagnosis FROM medical_records WHERE diagnosis IS NOT NULL AND diagnosis != '' ORDER BY diagnosis";
            $options['diagnoses'] = array_column(executeQuery($sql), 'diagnosis');
            
            // Get distinct symptoms
            $sql = "SELECT DISTINCT symptoms FROM medical_records WHERE symptoms IS NOT NULL AND symptoms != '' ORDER BY symptoms";
            $options['symptoms'] = array_column(executeQuery($sql), 'symptoms');
            
            // Get distinct treatment plans
            $sql = "SELECT DISTINCT treatment_plan FROM medical_records WHERE treatment_plan IS NOT NULL AND treatment_plan != '' ORDER BY treatment_plan";
            $options['treatment_plans'] = array_column(executeQuery($sql), 'treatment_plan');
            
            // Get distinct medications prescribed
            $sql = "SELECT DISTINCT medication_prescribed FROM medical_records WHERE medication_prescribed IS NOT NULL AND medication_prescribed != '' ORDER BY medication_prescribed";
            $options['medications_prescribed'] = array_column(executeQuery($sql), 'medication_prescribed');
            
            // Get distinct visit dates
            $sql = "SELECT DISTINCT visit_date FROM medical_records WHERE visit_date IS NOT NULL ORDER BY visit_date DESC";
            $options['visit_dates'] = array_column(executeQuery($sql), 'visit_date');
            
            // Get distinct follow up dates
            $sql = "SELECT DISTINCT follow_up_date FROM medical_records WHERE follow_up_date IS NOT NULL ORDER BY follow_up_date DESC";
            $options['follow_up_dates'] = array_column(executeQuery($sql), 'follow_up_date');
            
            // Get distinct created dates
            $sql = "SELECT DISTINCT DATE(created_at) as created_date FROM medical_records WHERE created_at IS NOT NULL ORDER BY created_date DESC";
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
            
            if (!empty($input['diagnosis'])) {
                $where[] = "mr.diagnosis = ?";
                $params[] = $input['diagnosis'];
            }
            
            if (!empty($input['symptoms'])) {
                $where[] = "mr.symptoms = ?";
                $params[] = $input['symptoms'];
            }
            
            if (!empty($input['treatment_plan'])) {
                $where[] = "mr.treatment_plan = ?";
                $params[] = $input['treatment_plan'];
            }
            
            if (!empty($input['medication_prescribed'])) {
                $where[] = "mr.medication_prescribed = ?";
                $params[] = $input['medication_prescribed'];
            }
            
            if (!empty($input['visit_date'])) {
                $where[] = "mr.visit_date = ?";
                $params[] = $input['visit_date'];
            }
            
            if (!empty($input['follow_up_date'])) {
                $where[] = "mr.follow_up_date = ?";
                $params[] = $input['follow_up_date'];
            }
            
            if (!empty($input['created_date'])) {
                $where[] = "DATE(mr.created_at) = ?";
                $params[] = $input['created_date'];
            }
            
            $sql = "SELECT mr.id,
                           p.name as patient_name,
                           d.name as doctor_name,
                           dept.name as department_name,
                           mr.diagnosis,
                           mr.symptoms,
                           mr.treatment_plan,
                           mr.medication_prescribed,
                           mr.visit_date,
                           mr.follow_up_date,
                           mr.medical_notes,
                           DATE(mr.created_at) as created_date
                    FROM medical_records mr
                    LEFT JOIN patients p ON mr.patient_id = p.id
                    LEFT JOIN doctors d ON mr.doctor_id = d.id
                    LEFT JOIN departments dept ON d.department_id = dept.id";
            
            if (!empty($where)) {
                $sql .= " WHERE " . implode(" AND ", $where);
            }
            $sql .= " ORDER BY mr.visit_date DESC";
            
            $reports = executeQuery($sql, $params);
            
            // Generate CSV content
            $csvContent = "ID,Patient Name,Doctor Name,Department,Diagnosis,Symptoms,Treatment Plan,Medication Prescribed,Visit Date,Follow-up Date,Medical Notes,Created Date\n";
            
            foreach ($reports as $report) {
                $csvContent .= sprintf('"%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s","%s"' . "\n",
                    $report['id'],
                    str_replace('"', '""', $report['patient_name'] ?? ''),
                    str_replace('"', '""', $report['doctor_name'] ?? ''),
                    str_replace('"', '""', $report['department_name'] ?? ''),
                    str_replace('"', '""', $report['diagnosis'] ?? ''),
                    str_replace('"', '""', $report['symptoms'] ?? ''),
                    str_replace('"', '""', $report['treatment_plan'] ?? ''),
                    str_replace('"', '""', $report['medication_prescribed'] ?? ''),
                    $report['visit_date'] ?? '',
                    $report['follow_up_date'] ?? '',
                    str_replace('"', '""', $report['medical_notes'] ?? ''),
                    $report['created_date'] ?? ''
                );
            }
            
            // Set headers for file download
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="medical_reports_' . date('Y-m-d_H-i-s') . '.csv"');
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