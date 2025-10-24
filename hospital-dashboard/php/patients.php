<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            // Return demo data for now
            $patients = [
                [
                    'id' => 1,
                    'name' => 'John Doe',
                    'age' => 35,
                    'gender' => 'Male',
                    'blood_type' => 'A+',
                    'phone' => '555-0123',
                    'email' => 'john.doe@email.com',
                    'created_at' => '2025-01-15'
                ],
                [
                    'id' => 2,
                    'name' => 'Jane Smith',
                    'age' => 28,
                    'gender' => 'Female',
                    'blood_type' => 'O-',
                    'phone' => '555-0456',
                    'email' => 'jane.smith@email.com',
                    'created_at' => '2025-01-14'
                ],
                [
                    'id' => 3,
                    'name' => 'Robert Johnson',
                    'age' => 45,
                    'gender' => 'Male',
                    'blood_type' => 'B+',
                    'phone' => '555-0789',
                    'email' => 'robert.j@email.com',
                    'created_at' => '2025-01-13'
                ]
            ];
            
            $stats = [
                'total' => count($patients),
                'male' => 2,
                'female' => 1,
                'today' => 1
            ];
            
            echo json_encode([
                'success' => true,
                'patients' => $patients,
                'stats' => $stats,
                'database_status' => 'demo_mode'
            ]);
            break;
            
        case 'create':
        case 'update':
        case 'delete':
            echo json_encode([
                'success' => true,
                'message' => 'Demo mode - operation simulated',
                'database_status' => 'demo_mode'
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