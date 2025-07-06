<?php
// This file will help identify issues with firebase_auth.php

// First, check for any output buffering
ob_start();

// Set content type as JSON
header('Content-Type: application/json');

// Disable all direct error output
ini_set('display_errors', 0);
error_reporting(0);

// Function to output clean JSON
function output_json($data) {
    // Clean the output buffer to prevent any accidental output
    ob_clean();
    
    // Send the JSON
    echo json_encode($data);
    
    // End execution
    exit;
}

// Track each step - this will help identify the exact point of failure
$debug_log = [
    'steps' => [],
    'errors' => []
];

// Log a step
function log_step($step, $data = null) {
    global $debug_log;
    $debug_log['steps'][] = ['step' => $step, 'data' => $data];
}

// Log an error
function log_error_debug($message, $data = null) {
    global $debug_log;
    $debug_log['errors'][] = ['message' => $message, 'data' => $data];
}

try {
    // Start logging
    log_step('Started processing');
    
    // Try to include config
    try {
        log_step('Including config.php');
        require_once '../includes/config.php';
        log_step('Config included successfully');
    } catch (Exception $e) {
        log_error_debug('Error including config.php', $e->getMessage());
        output_json([
            'success' => false,
            'message' => 'Error including config.php: ' . $e->getMessage(),
            'debug' => $debug_log
        ]);
    }
    
    // Check database connection
    if (isset($conn)) {
        log_step('Database connection exists');
        if ($conn->connect_error) {
            log_error_debug('Database connection error', $conn->connect_error);
            output_json([
                'success' => false,
                'message' => 'Database connection error: ' . $conn->connect_error,
                'debug' => $debug_log
            ]);
        } else {
            log_step('Database connection successful');
        }
    } else {
        log_error_debug('Database connection not established');
        output_json([
            'success' => false,
            'message' => 'Database connection not established',
            'debug' => $debug_log
        ]);
    }
    
    // Get input data
    log_step('Reading input data');
    $input = file_get_contents('php://input');
    
    if (empty($input)) {
        log_error_debug('No input data received');
        output_json([
            'success' => false,
            'message' => 'No input data received',
            'debug' => $debug_log
        ]);
    }
    
    log_step('Parsing JSON input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_error_debug('JSON parse error', json_last_error_msg());
        output_json([
            'success' => false,
            'message' => 'Invalid JSON: ' . json_last_error_msg(),
            'debug' => $debug_log
        ]);
    }
    
    // Check for token
    log_step('Checking for idToken');
    if (!isset($data['idToken']) || empty($data['idToken'])) {
        log_error_debug('No idToken provided');
        output_json([
            'success' => false,
            'message' => 'idToken is required',
            'debug' => $debug_log
        ]);
    }
    
    $idToken = $data['idToken'];
    log_step('Token received');
    
    // Parse token
    log_step('Parsing token');
    $tokenParts = explode('.', $idToken);
    
    if (count($tokenParts) !== 3) {
        log_error_debug('Invalid token format');
        output_json([
            'success' => false,
            'message' => 'Invalid token format',
            'debug' => $debug_log
        ]);
    }
    
    // Decode payload
    log_step('Decoding token payload');
    $base64_payload = $tokenParts[1];
    // Add padding if needed
    $padding = strlen($base64_payload) % 4;
    if ($padding) {
        $base64_payload .= str_repeat('=', 4 - $padding);
    }
    
    $payload_raw = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64_payload));
    if ($payload_raw === false) {
        log_error_debug('Failed to decode base64 payload');
        output_json([
            'success' => false,
            'message' => 'Failed to decode base64 payload',
            'debug' => $debug_log
        ]);
    }
    
    $payload = json_decode($payload_raw, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_error_debug('Failed to parse payload JSON', json_last_error_msg());
        output_json([
            'success' => false,
            'message' => 'Failed to parse payload JSON: ' . json_last_error_msg(),
            'debug' => $debug_log
        ]);
    }
    
    // Extract user info
    log_step('Extracting user info');
    if (!isset($payload['sub'])) {
        log_error_debug('No subject ID in token');
        output_json([
            'success' => false,
            'message' => 'Token is missing subject ID',
            'debug' => $debug_log
        ]);
    }
    
    $firebase_uid = $payload['sub'];
    $email = isset($payload['email']) ? $payload['email'] : '';
    $name = isset($payload['name']) ? $payload['name'] : '';
    
    log_step('User info extracted', [
        'uid' => $firebase_uid,
        'email' => $email,
        'name' => $name
    ]);
    
    // Everything is working! Return success
    output_json([
        'success' => true,
        'message' => 'Authentication test successful',
        'user' => [
            'firebase_uid' => $firebase_uid,
            'email' => $email,
            'name' => $name
        ],
        'debug' => $debug_log
    ]);
    
} catch (Exception $e) {
    // Catch any uncaught exceptions
    log_error_debug('Uncaught exception', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    output_json([
        'success' => false,
        'message' => 'Uncaught exception: ' . $e->getMessage(),
        'debug' => $debug_log
    ]);
}

// This should never be reached, but just in case
$contents = ob_get_clean();
if (!empty($contents)) {
    echo json_encode([
        'success' => false,
        'message' => 'Unexpected output detected',
        'output' => $contents,
        'debug' => $debug_log
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Unknown error - end of script reached',
        'debug' => $debug_log
    ]);
}
?> 