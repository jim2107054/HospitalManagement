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
        
        // Today's appointments
        $todayAppointments = executeQuery("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date = CURDATE()
        ");
        $stats['today_appointments'] = $todayAppointments[0]['count'] ?? 0;
        
        // This week's new patients
        $weekPatients = executeQuery("
            SELECT COUNT(*) as count 
            FROM patients 
            WHERE registered_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ");
        $stats['week_patients'] = $weekPatients[0]['count'] ?? 0;
        
        // Upcoming appointments (next 7 days)
        $upcomingAppointments = executeQuery("
            SELECT COUNT(*) as count 
            FROM appointments 
            WHERE appointment_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
            AND status = 'Scheduled'
        ");
        $stats['upcoming_appointments'] = $upcomingAppointments[0]['count'] ?? 0;
        
        // Recent medical records (last 30 days)
        $recentRecords = executeQuery("
            SELECT COUNT(*) as count 
            FROM medical_records 
            WHERE visit_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
        ");
        $stats['recent_records'] = $recentRecords[0]['count'] ?? 0;
        
        // Active doctors (with appointments this week)
        $activeDoctors = executeQuery("
            SELECT COUNT(DISTINCT doctor_id) as count 
            FROM appointments 
            WHERE appointment_date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ");
        $stats['active_doctors'] = $activeDoctors[0]['count'] ?? 0;
        
        // Average consultation fee
        $avgFee = executeQuery("
            SELECT AVG(consultation_fee) as avg_fee 
            FROM appointments 
            WHERE consultation_fee IS NOT NULL
        ");
        $stats['avg_consultation_fee'] = round($avgFee[0]['avg_fee'] ?? 0, 2);
        
        // Charts data for PIE CHARTS
        $charts = [];
        
        // Appointment status distribution (PIE CHART)
        $appointmentStatus = executeQuery("
            SELECT status, COUNT(*) as count 
            FROM appointments 
            GROUP BY status
        ");
        
        $charts['appointment_status'] = [];
        foreach ($appointmentStatus as $row) {
            $charts['appointment_status'][$row['status']] = (int)$row['count'];
        }
        
        // Blood group distribution (PIE CHART)
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
        
        // Doctors by department (PIE CHART)
        $departmentDoctors = executeQuery("
            SELECT d.name as department_name, COUNT(doc.id) as doctor_count
            FROM departments d
            LEFT JOIN doctors doc ON d.id = doc.department_id
            GROUP BY d.id, d.name
            HAVING doctor_count > 0
            ORDER BY doctor_count DESC
        ");
        
        $charts['departments'] = [];
        foreach ($departmentDoctors as $row) {
            $charts['departments'][$row['department_name']] = (int)$row['doctor_count'];
        }
        
        // Gender distribution (PIE CHART)
        $genderDist = executeQuery("
            SELECT gender, COUNT(*) as count 
            FROM patients 
            GROUP BY gender
        ");
        
        $charts['gender_distribution'] = [];
        foreach ($genderDist as $row) {
            $charts['gender_distribution'][$row['gender']] = (int)$row['count'];
        }
        
        // Age group distribution (PIE CHART)
        $ageGroups = executeQuery("
            SELECT 
                CASE 
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) < 18 THEN 'Under 18'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 18 AND 30 THEN '18-30'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 31 AND 50 THEN '31-50'
                    WHEN TIMESTAMPDIFF(YEAR, date_of_birth, CURDATE()) BETWEEN 51 AND 65 THEN '51-65'
                    ELSE 'Over 65'
                END as age_group,
                COUNT(*) as count
            FROM patients 
            GROUP BY age_group
        ");
        
        $charts['age_groups'] = [];
        foreach ($ageGroups as $row) {
            $charts['age_groups'][$row['age_group']] = (int)$row['count'];
        }
        
        // Monthly revenue (PIE CHART for departments)
        $departmentRevenue = executeQuery("
            SELECT 
                dept.name as department_name,
                SUM(a.consultation_fee) as revenue
            FROM appointments a
            JOIN doctors d ON a.doctor_id = d.id
            JOIN departments dept ON d.department_id = dept.id
            WHERE a.consultation_fee IS NOT NULL
            AND a.appointment_date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
            GROUP BY dept.id, dept.name
            ORDER BY revenue DESC
        ");
        
        $charts['department_revenue'] = [];
        foreach ($departmentRevenue as $row) {
            $charts['department_revenue'][$row['department_name']] = round((float)$row['revenue'], 2);
        }
        
        echo json_encode([
            'success' => true,
            'stats' => $stats,
            'charts' => $charts
        ]);
        
    } else {
        // Database not available, return demo data
        echo json_encode([
            'success' => true,
            'stats' => [
                'total_patients' => 150,
                'total_doctors' => 25,
                'total_appointments' => 320,
                'total_departments' => 8,
                'today_appointments' => 12,
                'week_patients' => 15,
                'upcoming_appointments' => 45,
                'recent_records' => 85,
                'active_doctors' => 18,
                'avg_consultation_fee' => 165.50
            ],
            'charts' => [
                'appointment_status' => [
                    'Scheduled' => 120,
                    'Completed' => 180,
                    'Cancelled' => 15,
                    'No-Show' => 5
                ],
                'blood_groups' => [
                    'O+' => 45,
                    'A+' => 35,
                    'B+' => 25,
                    'AB+' => 15,
                    'O-' => 12,
                    'A-' => 8,
                    'B-' => 6,
                    'AB-' => 4
                ],
                'departments' => [
                    'Cardiology' => 6,
                    'Neurology' => 4,
                    'Pediatrics' => 5,
                    'Orthopedics' => 3,
                    'Emergency' => 4,
                    'Oncology' => 3
                ],
                'gender_distribution' => [
                    'Male' => 78,
                    'Female' => 68,
                    'Other' => 4
                ],
                'age_groups' => [
                    'Under 18' => 25,
                    '18-30' => 45,
                    '31-50' => 55,
                    '51-65' => 20,
                    'Over 65' => 5
                ],
                'department_revenue' => [
                    'Cardiology' => 2500.00,
                    'Neurology' => 1800.00,
                    'Orthopedics' => 1200.00,
                    'Pediatrics' => 950.00,
                    'Emergency' => 800.00,
                    'Oncology' => 2200.00
                ]
            ]
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error loading overview data: ' . $e->getMessage(),
        'stats' => [
            'total_patients' => 0,
            'total_doctors' => 0,
            'total_appointments' => 0,
            'total_departments' => 0
        ],
        'charts' => []
    ]);
}
?>