<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Simple test to check if PHP and database are working
try {
    echo json_encode([
        'success' => true,
        'message' => 'PHP is working!',
        'timestamp' => date('Y-m-d H:i:s'),
        'php_version' => phpversion()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>