<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    echo json_encode([
        'success' => true,
        'data' => [
            ['id' => 1, 'name' => 'Cardiology', 'location' => 'Building A', 'head_doctor' => 'Dr. Smith'],
            ['id' => 2, 'name' => 'Neurology', 'location' => 'Building B', 'head_doctor' => 'Dr. Johnson'],
            ['id' => 3, 'name' => 'Pediatrics', 'location' => 'Building C', 'head_doctor' => 'Dr. Williams']
        ],
        'stats' => ['total' => 3, 'with_head' => 3, 'doctors' => 15, 'active' => 3]
    ]);
} else {
    echo json_encode(['success' => true, 'message' => 'Demo mode']);
}
?>