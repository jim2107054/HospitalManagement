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
    
    $charts['appointment_status'] = [];
    foreach ($appointmentStatus as $row) {
        $charts['appointment_status'][$row['status']] = (int)$row['count'];
    }
    
    // Blood group distribution
    $bloodGroups = executeQuery("
        SELECT blood_group, COUNT(*) as count 
        FROM patients 
        WHERE blood_group IS NOT NULL 
        GROUP BY blood_group
    ");
    
    $charts['blood_groups'] = [];
    foreach ($bloodGroups as $row) {
        $charts['blood_groups'][$row['blood_group']] = (int)$row['count'];
    }
    
    // Doctors by department
    $departmentDoctors = executeQuery("
        SELECT d.name as department_name, COUNT(doc.id) as doctor_count
        FROM departments d
        LEFT JOIN doctors doc ON d.id = doc.department_id
        GROUP BY d.id, d.name
        ORDER BY doctor_count DESC
    ");
    
    $charts['departments'] = [];
    foreach ($departmentDoctors as $row) {
        $charts['departments'][$row['department_name']] = (int)$row['doctor_count'];
    }
    
    // Additional overview data
    $additionalStats = [];
    
    // Today's appointments
    $todayAppointments = executeQuery("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE appointment_date = CURDATE()
    ");
    $additionalStats['today_appointments'] = $todayAppointments[0]['count'] ?? 0;
    
    // This week's new patients
    $weekPatients = executeQuery("
        SELECT COUNT(*) as count 
        FROM patients 
        WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ");
    $additionalStats['week_patients'] = $weekPatients[0]['count'] ?? 0;
    
    // Upcoming appointments (next 7 days)
    $upcomingAppointments = executeQuery("
        SELECT COUNT(*) as count 
        FROM appointments 
        WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
        AND status = 'Scheduled'
    ");
    $additionalStats['upcoming_appointments'] = $upcomingAppointments[0]['count'] ?? 0;
    
    // Recent medical records (last 30 days)
    $recentRecords = executeQuery("
        SELECT COUNT(*) as count 
        FROM medical_records 
        WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    ");
    $additionalStats['recent_records'] = $recentRecords[0]['count'] ?? 0;
    
    // Gender distribution
    $genderDist = executeQuery("
        SELECT gender, COUNT(*) as count 
        FROM patients 
        GROUP BY gender
    ");
    
    $charts['gender_distribution'] = [];
    foreach ($genderDist as $row) {
        $charts['gender_distribution'][$row['gender']] = (int)$row['count'];
    }
    
    // Monthly appointment trends (last 6 months)
    $monthlyTrends = executeQuery("
        SELECT 
            DATE_FORMAT(appointment_date, '%Y-%m') as month,
            COUNT(*) as count
        FROM appointments 
        WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(appointment_date, '%Y-%m')
        ORDER BY month
    ");
    
    $charts['monthly_trends'] = [
        'labels' => [],
        'data' => []
    ];
    
    foreach ($monthlyTrends as $row) {
        $charts['monthly_trends']['labels'][] = date('M Y', strtotime($row['month'] . '-01'));
        $charts['monthly_trends']['data'][] = (int)$row['count'];
    }
    
    echo json_encode([
        'success' => true,
        'stats' => array_merge($stats, $additionalStats),
        'charts' => $charts
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading overview data: ' . $e->getMessage()
    ]);
}
?>