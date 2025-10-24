<?php
// Simple test file to check overview.php response
try {
    $url = 'http://localhost/db/hospital-dashboard/php/overview.php';
    
    // Use file_get_contents for simplicity
    $context = stream_context_create([
        'http' => [
            'timeout' => 10
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    
    echo "<h2>Overview.php Response:</h2>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Try to decode JSON
    $data = json_decode($response, true);
    if ($data) {
        echo "<h2>Parsed Data:</h2>";
        echo "<pre>" . print_r($data, true) . "</pre>";
    } else {
        echo "<h2>JSON Parse Error:</h2>";
        echo "<pre>" . json_last_error_msg() . "</pre>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error:</h2>";
    echo "<pre>" . $e->getMessage() . "</pre>";
}
?>