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
        
        // Blood group distribution
        $bloodGroupData = executeQuery("
            SELECT blood_group, COUNT(*) as count 
            FROM patients 
            WHERE blood_group IS NOT NULL AND blood_group != ''
            GROUP BY blood_group
            ORDER BY count DESC
        ");
        
        $charts['bloodGroupDistribution'] = [
            'labels' => array_column($bloodGroupData, 'blood_group'),
            'data' => array_column($bloodGroupData, 'count')
        ];
        
        // Age group distribution
        $ageGroupData = executeQuery("
            SELECT 
                CASE 
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) < 18 THEN 'Under 18'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 31 AND 50 THEN '31-50'
                    WHEN YEAR(CURDATE()) - YEAR(date_of_birth) BETWEEN 51 AND 70 THEN '51-70'
                    ELSE 'Over 70'
                END as age_group,
                COUNT(*) as count
            FROM patients 
            WHERE date_of_birth IS NOT NULL
            GROUP BY age_group
            ORDER BY count DESC
        ");
        
        $charts['ageDistribution'] = [
            'labels' => array_column($ageGroupData, 'age_group'),
            'data' => array_column($ageGroupData, 'count')
        ];
        
        // Gender distribution
        $genderData = executeQuery("
            SELECT gender, COUNT(*) as count 
            FROM patients 
            WHERE gender IS NOT NULL AND gender != ''
            GROUP BY gender
        ");
        
        $charts['genderDistribution'] = [
            'labels' => array_column($genderData, 'gender'),
            'data' => array_column($genderData, 'count')
        ];
        
        // Doctor experience distribution
        $experienceData = executeQuery("
            SELECT 
                CASE 
                    WHEN experience < 5 THEN '0-5 years'
                    WHEN experience BETWEEN 5 AND 10 THEN '5-10 years'
                    WHEN experience BETWEEN 11 AND 20 THEN '11-20 years'
                    ELSE '20+ years'
                END as experience_group,
                COUNT(*) as count
            FROM doctors 
            WHERE experience IS NOT NULL
            GROUP BY experience_group
            ORDER BY count DESC
        ");
        
        $charts['experienceDistribution'] = [
            'labels' => array_column($experienceData, 'experience_group'),
            'data' => array_column($experienceData, 'count')
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
            ],
            'bloodGroupDistribution' => [
                'labels' => ['O+', 'A+', 'B+', 'AB+', 'O-', 'A-', 'B-', 'AB-'],
                'data' => [45, 35, 25, 8, 15, 12, 8, 2]
            ],
            'ageDistribution' => [
                'labels' => ['18-30', '31-50', '51-70', 'Under 18', 'Over 70'],
                'data' => [40, 50, 35, 15, 10]
            ],
            'genderDistribution' => [
                'labels' => ['Male', 'Female'],
                'data' => [75, 75]
            ],
            'experienceDistribution' => [
                'labels' => ['5-10 years', '11-20 years', '0-5 years', '20+ years'],
                'data' => [8, 10, 5, 2]
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