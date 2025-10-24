<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$action = $_GET['action'] ?? 'list';

if ($action === 'list') {
    echo json_encode([
        'success' => true,
        'data' => [
            ['id' => 1, 'first_name' => 'John', 'last_name' => 'Smith', 'department_name' => 'Cardiology', 'specialization' => 'Heart Surgery', 'experience' => '10 years'],
            ['id' => 2, 'first_name' => 'Sarah', 'last_name' => 'Johnson', 'department_name' => 'Neurology', 'specialization' => 'Brain Surgery', 'experience' => '8 years']
        ],
        'stats' => ['total' => 2, 'available' => 1, 'specializations' => 5, 'departments' => 3, 'avg_experience' => 9]
    ]);
} else {
    echo json_encode(['success' => true, 'message' => 'Demo mode']);
}
?>