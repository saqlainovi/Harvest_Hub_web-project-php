<?php
/**
 * Firebase Registration Handler
 * 
 * This file registers users authenticated through Firebase
 */

// Turn off error display - VERY IMPORTANT for JSON endpoints
ini_set('display_errors', 0);
error_reporting(0);

require_once '../includes/config.php';

// Enable verbose logging
log_error("Firebase Register: Request received");

// Set CORS headers to allow requests from any origin
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Set content type header
header('Content-Type: application/json');

// Get input data from the request
$json = file_get_contents('php://input');
log_error("Firebase Register: Raw input", $json);

// Decode JSON data
$data = json_decode($json, true);
log_error("Firebase Register: Decoded data", $data);

// Check if we have received the ID token and user data
if (!isset($data['idToken']) || empty($data['idToken'])) {
    log_error("Firebase Register: No ID token provided");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID token is required'
    ]);
    exit;
}

if (!isset($data['userData']) || empty($data['userData'])) {
    log_error("Firebase Register: No user data provided");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'User data is required'
    ]);
    exit;
}

$idToken = $data['idToken'];
$userData = $data['userData'];
log_error("Firebase Register: Token received (first 20 chars): " . substr($idToken, 0, 20) . "...");
log_error("Firebase Register: User data", $userData);

try {
    // Extract payload from the token
    // NOTE: This is a simplified approach. In production, use Firebase Admin SDK
    $tokenParts = explode('.', $idToken);
    log_error("Firebase Register: Token parts count", count($tokenParts));
    
    if (count($tokenParts) !== 3) {
        throw new Exception('Invalid token format');
    }
    
    // Decode the payload part (second part of the token)
    $base64_payload = $tokenParts[1];
    // Add padding if needed
    $padding = strlen($base64_payload) % 4;
    if ($padding) {
        $base64_payload .= str_repeat('=', 4 - $padding);
    }
    
    $payload_raw = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64_payload));
    log_error("Firebase Register: Raw payload", $payload_raw);
    
    $payload = json_decode($payload_raw, true);
    log_error("Firebase Register: Decoded payload", $payload);
    
    if (!$payload || !isset($payload['sub'])) {
        throw new Exception('Invalid token payload');
    }
    
    // Extract user information
    $firebase_uid = $payload['sub'] ?? '';
    $email = $payload['email'] ?? '';
    $name = $payload['name'] ?? '';
    
    log_error("Firebase Register: Extracted info", [
        'uid' => $firebase_uid,
        'email' => $email,
        'name' => $name
    ]);
    
    if (empty($email)) {
        // If email is not in token, try to use verified email
        $email = $payload['verified_email'] ?? '';
        log_error("Firebase Register: Trying to use verified_email", $email);
    }
    
    if (empty($email)) {
        throw new Exception('Email not found in token');
    }
    
    // Check if user already exists
    $check_query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    log_error("Firebase Register: Database query executed. Rows found: " . $result->num_rows);
    
    if ($result->num_rows > 0) {
        // User already exists
        $user = $result->fetch_assoc();
        log_error("Firebase Register: User already exists", $user);
        
        // Update user data with Firebase UID if needed
        if (empty($user['firebase_uid'])) {
            log_error("Firebase Register: Updating Firebase UID for existing user");
            $update_query = "UPDATE users SET firebase_uid = ?, login_provider = 'google' WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $firebase_uid, $user['user_id']);
            $update_result = $stmt->execute();
            log_error("Firebase Register: UID update result", $update_result ? "Success" : "Failed: " . $stmt->error);
        }
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['role'] = $user['role'];
        
        $response = [
            'success' => true,
            'message' => 'User already registered',
            'is_new' => false,
            'user' => [
                'user_id' => $user['user_id'],
                'email' => $user['email'],
                'full_name' => $user['full_name'],
                'role' => $user['role']
            ]
        ];
        
        log_error("Firebase Register: Existing user response", $response);
        echo json_encode($response);
        exit;
    }
    
    // Create new user
    log_error("Firebase Register: Creating new user");
    $username = isset($userData['username']) ? sanitize_input($userData['username']) : generateUsername($email);
    $full_name = isset($userData['full_name']) ? sanitize_input($userData['full_name']) : $name;
    $phone = isset($userData['phone']) ? sanitize_input($userData['phone']) : '';
    $address = isset($userData['address']) ? sanitize_input($userData['address']) : '';
    $role = isset($userData['role']) ? sanitize_input($userData['role']) : 'buyer'; // Default role
    
    // Insert new user
    $insert_query = "INSERT INTO users (username, email, full_name, phone, address, role, firebase_uid, login_provider, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'google', NOW())";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("sssssss", $username, $email, $full_name, $phone, $address, $role, $firebase_uid);
    
    $insert_result = $stmt->execute();
    log_error("Firebase Register: User insertion result", $insert_result ? "Success" : "Failed: " . $stmt->error);
    
    if (!$insert_result) {
        throw new Exception('Failed to insert user: ' . $stmt->error);
    }
    
    $user_id = $conn->insert_id;
    log_error("Firebase Register: New user ID", $user_id);
    
    // Set session variables
    $_SESSION['user_id'] = $user_id;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $full_name;
    $_SESSION['role'] = $role;
    
    // Create entry in buyer table if role is buyer
    if ($role === 'buyer') {
        log_error("Firebase Register: Adding user to buyers table");
        $insert_buyer_query = "INSERT INTO buyers (user_id) VALUES (?)";
        $stmt = $conn->prepare($insert_buyer_query);
        $stmt->bind_param("i", $user_id);
        $buyer_result = $stmt->execute();
        log_error("Firebase Register: Buyer insertion result", $buyer_result ? "Success" : "Failed: " . $stmt->error);
    }
    
    // Create entry in seller table if role is seller
    if ($role === 'seller') {
        log_error("Firebase Register: Adding user to sellers table");
        $farm_name = isset($userData['farm_name']) ? sanitize_input($userData['farm_name']) : $full_name . "'s Farm";
        $description = isset($userData['description']) ? sanitize_input($userData['description']) : '';
        $location = isset($userData['location']) ? sanitize_input($userData['location']) : $address;
        
        $insert_seller_query = "INSERT INTO seller_profiles (user_id, farm_name, description, location, is_verified) VALUES (?, ?, ?, ?, 0)";
        $stmt = $conn->prepare($insert_seller_query);
        $stmt->bind_param("isss", $user_id, $farm_name, $description, $location);
        $seller_result = $stmt->execute();
        log_error("Firebase Register: Seller insertion result", $seller_result ? "Success" : "Failed: " . $stmt->error);
    }
    
    $response = [
        'success' => true,
        'message' => 'User registered successfully',
        'is_new' => true,
        'user' => [
            'user_id' => $user_id,
            'email' => $email,
            'full_name' => $full_name,
            'role' => $role
        ]
    ];
    
    log_error("Firebase Register: New user response", $response);
    echo json_encode($response);
    
} catch (Exception $e) {
    log_error("Firebase Register: Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Registration failed: ' . $e->getMessage()
    ]);
    exit;
}

/**
 * Generate a username from email
 */
function generateUsername($email) {
    $parts = explode('@', $email);
    $username = $parts[0];
    
    // Add random numbers for uniqueness
    $username .= rand(100, 999);
    
    return $username;
}

// Function to output clean JSON error and exit
function output_error($message, $status_code = 400) {
    global $conn;
    
    // Log the error
    log_error("Firebase Register Error: " . $message);
    
    // Close database connection if exists
    if (isset($conn) && $conn instanceof mysqli) {
        $conn->close();
    }
    
    // Send status code
    http_response_code($status_code);
    
    // Output JSON error
    echo json_encode([
        'success' => false,
        'message' => $message
    ]);
    exit;
} 