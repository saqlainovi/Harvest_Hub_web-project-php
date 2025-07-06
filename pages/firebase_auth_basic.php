<?php
// Prevent any output before headers
ob_start();

// Set strict error handling but display errors for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Set content type
header('Content-Type: application/json');

// Set CORS headers
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include config.php to get database configuration
require_once '../includes/config.php';

// Get input data
$json = file_get_contents('php://input');
$data = json_decode($json, true);

// Check if we have received the ID token
if (!isset($data['idToken']) || empty($data['idToken'])) {
    // Clear the output buffer
    if (ob_get_length()) ob_clean();
    
    echo json_encode([
        'success' => false,
        'message' => 'ID token is required'
    ]);
    exit;
}

$idToken = $data['idToken'];

try {
    // Parse the token
    $tokenParts = explode('.', $idToken);
    
    if (count($tokenParts) !== 3) {
        throw new Exception('Invalid token format');
    }
    
    // Decode the payload part
    $base64_payload = $tokenParts[1];
    $padding = strlen($base64_payload) % 4;
    if ($padding) {
        $base64_payload .= str_repeat('=', 4 - $padding);
    }
    
    $payload_raw = base64_decode(str_replace(['-', '_'], ['+', '/'], $base64_payload));
    $payload = json_decode($payload_raw, true);
    
    if (!$payload || !isset($payload['sub'])) {
        throw new Exception('Invalid token payload');
    }
    
    // Extract user information
    $firebase_uid = $payload['sub'] ?? '';
    $email = $payload['email'] ?? '';
    $name = $payload['name'] ?? '';
    
    // If name is empty, use email as name
    if (empty($name)) {
        $name = substr($email, 0, strpos($email, '@'));
        $name = ucfirst(str_replace(['.', '_', '-'], ' ', $name));
    }
    
    if (empty($email)) {
        throw new Exception('Email not found in token');
    }
    
    // Create server-side session with user data
    $_SESSION['firebase_uid'] = $firebase_uid;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $name;
    
    // Check if user exists in database using simple query
    $email_escaped = $conn->real_escape_string($email);
    $result = $conn->query("SELECT * FROM users WHERE email = '$email_escaped'");
    
    if ($result === false) {
        throw new Exception('Database query failed: ' . $conn->error);
    }
    
    if ($result->num_rows === 0) {
        // User doesn't exist, create user using direct SQL (no prepared statement)
        $username = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode('@', $email)[0])) . rand(1000, 9999);
        $name_escaped = $conn->real_escape_string($name);
        $firebase_uid_escaped = $conn->real_escape_string($firebase_uid);
        $username_escaped = $conn->real_escape_string($username);
        
        $insert_sql = "INSERT INTO users (username, email, full_name, firebase_uid, role) 
                      VALUES ('$username_escaped', '$email_escaped', '$name_escaped', '$firebase_uid_escaped', 'buyer')";
        
        if (!$conn->query($insert_sql)) {
            throw new Exception('Failed to create user: ' . $conn->error);
        }
        
        // Get the new user ID
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['role'] = 'buyer';
    } else {
        // User exists, get their info
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        // Update Firebase UID if needed using direct SQL
        if (empty($user['firebase_uid'])) {
            $user_id = (int)$user['user_id'];
            $firebase_uid_escaped = $conn->real_escape_string($firebase_uid);
            
            $conn->query("UPDATE users SET firebase_uid = '$firebase_uid_escaped', login_provider = 'google' WHERE user_id = $user_id");
        }
        
        // Update last login time using direct SQL
        $user_id = (int)$user['user_id'];
        $conn->query("UPDATE users SET last_login = NOW() WHERE user_id = $user_id");
    }
    
    // Clear the output buffer to ensure no stray HTML
    if (ob_get_length()) ob_clean();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Authentication successful',
        'user' => [
            'user_id' => $_SESSION['user_id'],
            'email' => $_SESSION['email'],
            'full_name' => $_SESSION['full_name'],
            'role' => $_SESSION['role']
        ]
    ]);
    
} catch (Exception $e) {
    // Clear the output buffer to ensure no stray HTML
    if (ob_get_length()) ob_clean();
    
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Authentication failed: ' . $e->getMessage()
    ]);
}

// Close database connection
$conn->close();
exit;
?> 