<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    // Check if database is available
    require_once '../includes/database.php';
    
    // Try to connect to database
    $conn = getDBConnection();
    $databaseAvailable = ($conn !== null);
    
    if ($databaseAvailable) {
        // Database is available, get real data
        $stats = [];
        
        // Total patients
        $result = executeQuery("SELECT COUNT(*) as count FROM patients");
        $stats['total_patients'] = $result[0]['count'] ?? 0;
        
        // Total doctors
        $result = executeQuery("SELECT COUNT(*) as count FROM doctors");
        $stats['total_doctors'] = $result[0]['count'] ?? 0;
        
        // Total appointments
        $result = executeQuery("SELECT COUNT(*) as count FROM appointments");
        $stats['total_appointments'] = $result[0]['count'] ?? 0;
        
        // Total departments
        $result = executeQuery("SELECT COUNT(*) as count FROM departments");
        $stats['total_departments'] = $result[0]['count'] ?? 0;
        
        // Charts data
        $charts = [];
        
        // Appointment status distribution
        $appointmentStatus = executeQuery("
            SELECT status, COUNT(*) as count 
            FROM appointments 
            GROUP BY status
        ");
        
        $charts['appointmentStatus'] = [
            'labels' => array_column($appointmentStatus, 'status'),
            'data' => array_column($appointmentStatus, 'count')
        ];
        
        // Department distribution
        $departmentData = executeQuery("
            SELECT d.name, COUNT(dr.id) as doctor_count 
            FROM departments d 
            LEFT JOIN doctors dr ON d.id = dr.department_id 
            GROUP BY d.id, d.name
        ");
        
        $charts['departmentDistribution'] = [
            'labels' => array_column($departmentData, 'name'),
            'data' => array_column($departmentData, 'doctor_count')
        ];
        
    } else {
        // Database not available, return demo data
        $stats = [
            'total_patients' => 150,
            'total_doctors' => 25,
            'total_appointments' => 85,
            'total_departments' => 8
        ];
        
        $charts = [
            'appointmentStatus' => [
                'labels' => ['Scheduled', 'Completed', 'Cancelled', 'Pending'],
                'data' => [35, 40, 5, 5]
            ],
            'departmentDistribution' => [
                'labels' => ['Cardiology', 'Neurology', 'Pediatrics', 'Emergency', 'Surgery'],
                'data' => [8, 6, 4, 3, 4]
            ]
        ];
    }
    
    echo json_encode([
        'success' => true,
        'stats' => $stats,
        'charts' => $charts,
        'database_status' => $databaseAvailable ? 'connected' : 'demo_mode'
    ]);
    
} catch (Exception $e) {
    // Return demo data on any error
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_patients' => 150,
            'total_doctors' => 25,
            'total_appointments' => 85,
            'total_departments' => 8
        ],
        'charts' => [
            'appointmentStatus' => [
                'labels' => ['Scheduled', 'Completed', 'Cancelled', 'Pending'],
                'data' => [35, 40, 5, 5]
            ],
            'departmentDistribution' => [
                'labels' => ['Cardiology', 'Neurology', 'Pediatrics', 'Emergency', 'Surgery'],
                'data' => [8, 6, 4, 3, 4]
            ]
        ],
        'database_status' => 'demo_mode',
        'error' => $e->getMessage()
    ]);
}
?>