<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    echo json_encode([
        'success' => true,
        'data' => [
            ['id' => 1, 'patient_name' => 'John Doe', 'doctor_name' => 'Dr. Smith', 'department_name' => 'Cardiology', 'visit_date' => '2025-10-20', 'diagnosis' => 'Hypertension', 'treatment_plan' => 'Medication and diet changes', 'follow_up_date' => '2025-11-20'],
            ['id' => 2, 'patient_name' => 'Jane Smith', 'doctor_name' => 'Dr. Johnson', 'department_name' => 'Neurology', 'visit_date' => '2025-10-19', 'diagnosis' => 'Migraine', 'treatment_plan' => 'Pain management therapy', 'follow_up_date' => null]
        ],
        'stats' => ['total' => 2, 'today' => 0, 'this_week' => 2, 'follow_ups' => 1, 'unique_patients' => 2]
    ]);
} else {
    echo json_encode(['success' => true, 'message' => 'Demo mode']);
}
?>