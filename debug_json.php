<?php
// Prevent any HTML error output
ini_set('display_errors', 0);
error_reporting(0);

// Set headers for JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Capture any errors that might occur
try {
    // Create a test data array
    $data = [
        'success' => true,
        'message' => 'JSON test successful',
        'time' => date('Y-m-d H:i:s')
    ];
    
    // Convert to JSON and output
    echo json_encode($data);
} catch (Exception $e) {
    // Return error as JSON
    echo json_encode([
        'success' => false,
        'error' => 'Exception: ' . $e->getMessage()
    ]);
}
?> 