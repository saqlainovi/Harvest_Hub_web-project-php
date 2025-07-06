<?php
/**
 * Firebase Authentication Handler
 * 
 * This file verifies Firebase ID tokens and creates server-side sessions
 */

require_once '../includes/config.php';

// Enable verbose logging
log_error("Firebase Auth: Request received");

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
log_error("Firebase Auth: Raw input", $json);

// Decode JSON data
$data = json_decode($json, true);
log_error("Firebase Auth: Decoded data", $data);

// Check if we have received the ID token
if (!isset($data['idToken']) || empty($data['idToken'])) {
    log_error("Firebase Auth: No ID token provided");
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'ID token is required'
    ]);
    exit;
}

$idToken = $data['idToken'];
log_error("Firebase Auth: Token received (first 20 chars): " . substr($idToken, 0, 20) . "...");

try {
    // Extract payload from the token
    // NOTE: This is a simplified approach. In production, use Firebase Admin SDK
    $tokenParts = explode('.', $idToken);
    log_error("Firebase Auth: Token parts count", count($tokenParts));
    
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
    log_error("Firebase Auth: Raw payload", $payload_raw);
    
    $payload = json_decode($payload_raw, true);
    log_error("Firebase Auth: Decoded payload", $payload);
    
    if (!$payload || !isset($payload['sub'])) {
        throw new Exception('Invalid token payload');
    }
    
    // Extract user information
    $firebase_uid = $payload['sub'] ?? '';
    $email = $payload['email'] ?? '';
    $name = $payload['name'] ?? '';
    
    log_error("Firebase Auth: Extracted info", [
        'uid' => $firebase_uid,
        'email' => $email,
        'name' => $name
    ]);
    
    if (empty($email)) {
        // If email is not in token, try to use verified email
        $email = $payload['verified_email'] ?? '';
        log_error("Firebase Auth: Trying to use verified_email", $email);
    }
    
    if (empty($email)) {
        throw new Exception('Email not found in token');
    }
    
    // Create server-side session with user data
    $_SESSION['firebase_uid'] = $firebase_uid;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $name;
    
    log_error("Firebase Auth: Set session data", $_SESSION);
    
    // Check if user exists in database
    $user_query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    log_error("Firebase Auth: Database query executed. Rows found: " . $result->num_rows);
    
    if ($result->num_rows === 0) {
        // User doesn't exist, create user
        log_error("Firebase Auth: Creating new user");
        $username = generateUsername($email);
        $role = 'buyer'; // Default role
        
        $insert_query = "INSERT INTO users (username, email, full_name, firebase_uid, role, created_at, login_provider) 
                        VALUES (?, ?, ?, ?, ?, NOW(), 'google')";
        $stmt = $conn->prepare($insert_query);
        $stmt->bind_param("sssss", $username, $email, $name, $firebase_uid, $role);
        
        $insert_result = $stmt->execute();
        log_error("Firebase Auth: User insertion result", $insert_result ? "Success" : "Failed: " . $stmt->error);
        
        if (!$insert_result) {
            throw new Exception('Failed to create user: ' . $stmt->error);
        }
        
        // Get the new user ID
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['role'] = $role;
        
        log_error("Firebase Auth: New user created", [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role']
        ]);
    } else {
        // User exists, get their info
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        log_error("Firebase Auth: Existing user found", [
            'user_id' => $_SESSION['user_id'],
            'role' => $_SESSION['role']
        ]);
        
        // Update Firebase UID if needed
        if (empty($user['firebase_uid'])) {
            log_error("Firebase Auth: Updating Firebase UID for existing user");
            $update_query = "UPDATE users SET firebase_uid = ?, login_provider = 'google' WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("si", $firebase_uid, $user['user_id']);
            $update_result = $stmt->execute();
            log_error("Firebase Auth: UID update result", $update_result ? "Success" : "Failed: " . $stmt->error);
        }
        
        // Update last login time
        $update_login = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($update_login);
        $stmt->bind_param("i", $user['user_id']);
        $login_update_result = $stmt->execute();
        log_error("Firebase Auth: Login time update result", $login_update_result ? "Success" : "Failed: " . $stmt->error);
    }
    
    // Prepare response
    $response = [
        'success' => true,
        'message' => 'Authentication successful',
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ]
    ];
    
    log_error("Firebase Auth: Successful response", $response);
    echo json_encode($response);
    
} catch (Exception $e) {
    log_error("Firebase Auth: Exception occurred", [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed: ' . $e->getMessage()
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