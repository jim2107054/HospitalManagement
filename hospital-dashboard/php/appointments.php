<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    echo json_encode([
        'success' => true,
        'data' => [
            ['id' => 1, 'patient_name' => 'John Doe', 'doctor_name' => 'Dr. Smith', 'appointment_date' => '2025-10-25', 'appointment_time' => '10:00', 'status' => 'scheduled'],
            ['id' => 2, 'patient_name' => 'Jane Smith', 'doctor_name' => 'Dr. Johnson', 'appointment_date' => '2025-10-25', 'appointment_time' => '14:00', 'status' => 'completed']
        ],
        'stats' => ['total' => 2, 'today' => 1, 'scheduled' => 1, 'completed' => 1, 'upcoming' => 5]
    ]);
} else {
    echo json_encode(['success' => true, 'message' => 'Demo mode']);
}
?>