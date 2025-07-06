<?php
// Prevent any output before headers
ob_start();

// Set strict error handling
ini_set('display_errors', 0);
error_reporting(0);

// Enable error logging to file
$log_file = dirname(__FILE__) . '/firebase_auth_error.log';
function log_error($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND);
}

// Better debug information for the client if needed
$debug_mode = true; // Set to true for detailed errors
$response = ['success' => false, 'message' => '', 'debug' => []];

try {
    // Set content type
    header('Content-Type: application/json');
    
    // Set CORS headers - Allow from any origin for development
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    // Handle preflight requests
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    // Database settings
    $db_host = "localhost";
    $db_user = "root";
    $db_pass = "";
    $db_name = "fresh_harvest";
    
    // Create database connection
    $conn = @new mysqli($db_host, $db_user, $db_pass, $db_name);
    
    // Check connection
    if ($conn->connect_error) {
        throw new Exception('Database connection failed: ' . $conn->connect_error);
    }
    
    $response['debug'][] = 'Connected to database';
    
    // Start session if needed
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Function to generate username from email
    function generateUsername($email, $conn) {
        $parts = explode('@', $email);
        $base_username = preg_replace('/[^a-zA-Z0-9]/', '', $parts[0]); // Remove special characters
        
        // Make sure username isn't empty
        if (empty($base_username)) {
            $base_username = 'user';
        }
        
        // Check if base username already exists
        $username = $base_username . rand(1000, 9999); // Add random 4-digit number
        
        // Ensure username is unique
        $count = 0;
        $original_username = $username;
        
        while ($count < 5) { // Try up to 5 times with different random numbers
            $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
            if ($stmt) {
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $stmt->bind_result($exists);
                $stmt->fetch();
                $stmt->close();
                
                if (!$exists) {
                    // Username is unique
                    return $username;
                }
                
                // Try with a different random number
                $username = $base_username . rand(1000, 9999);
                $count++;
            } else {
                // If prepared statement fails, just return the original with timestamp
                return $original_username . time();
            }
        }
        
        // If we couldn't find a unique username after 5 tries, use timestamp
        return $base_username . time();
    }
    
    // Get input data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Check if we have received the ID token
    if (!isset($data['idToken']) || empty($data['idToken'])) {
        throw new Exception('ID token is required');
    }
    
    $idToken = $data['idToken'];
    $response['debug'][] = 'Received idToken of length: ' . strlen($idToken);
    
    // Parse the token
    $tokenParts = explode('.', $idToken);
    
    if (count($tokenParts) !== 3) {
        throw new Exception('Invalid token format: Expected 3 parts, got ' . count($tokenParts));
    }
    
    // Decode the payload part
    $base64_payload = $tokenParts[1];
    // Add padding if needed
    $padding = strlen($base64_payload) % 4;
    if ($padding) {
        $base64_payload .= str_repeat('=', 4 - $padding);
    }
    
    // Replace URL-safe characters
    $base64_payload = str_replace(['-', '_'], ['+', '/'], $base64_payload);
    
    // Decode the base64 string
    $payload_raw = base64_decode($base64_payload);
    if ($payload_raw === false) {
        throw new Exception('Failed to decode base64 payload');
    }
    
    $payload = json_decode($payload_raw, true);
    
    if (!$payload) {
        throw new Exception('Invalid token payload: JSON parsing failed');
    }
    
    if (!isset($payload['sub'])) {
        throw new Exception('Invalid token payload: Missing sub field');
    }
    
    // Extract user information
    $firebase_uid = $payload['sub'] ?? '';
    $email = $payload['email'] ?? '';
    $name = $payload['name'] ?? '';
    
    // If name is empty, try to construct it from email
    if (empty($name)) {
        $name = substr($email, 0, strpos($email, '@'));
        $name = ucfirst(str_replace(['.', '_', '-'], ' ', $name));
    }
    
    if (empty($email)) {
        // Try other fields if email is not directly in the token
        if (isset($payload['verified_email'])) {
            $email = $payload['verified_email'];
        } elseif (isset($payload['user_email'])) {
            $email = $payload['user_email'];
        }
    }
    
    if (empty($email)) {
        throw new Exception('Email not found in token');
    }
    
    $response['debug'][] = "User data from token: email=$email, name=$name, uid=$firebase_uid";
    
    // Create server-side session with user data
    $_SESSION['firebase_uid'] = $firebase_uid;
    $_SESSION['email'] = $email;
    $_SESSION['full_name'] = $name;
    
    // Check if user exists in database
    $user_query = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($user_query);
    
    if (!$stmt) {
        throw new Exception('Failed to prepare user query: ' . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $response['debug'][] = "Query executed, checking if user exists";
    
    if ($result->num_rows === 0) {
        // User doesn't exist, create user
        $username = generateUsername($email, $conn);
        $role = 'buyer'; // Default role
        
        $response['debug'][] = "User doesn't exist, creating new user";
        
        // Get full table structure for debugging
        $tableColumns = [];
        $tableInfo = $conn->query("DESCRIBE users");
        while ($column = $tableInfo->fetch_assoc()) {
            $tableColumns[] = $column['Field'];
        }
        $response['debug'][] = "Table columns: " . implode(", ", $tableColumns);
        
        // Modified insert query - simplified to reduce potential errors
        $insert_query = "INSERT INTO users (username, email, full_name, firebase_uid, role) 
                        VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $conn->prepare($insert_query);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare insert statement: ' . $conn->error . ' (Query: ' . $insert_query . ')');
        }
        
        $stmt->bind_param("sssss", $username, $email, $name, $firebase_uid, $role);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to create user: ' . $stmt->error);
        }
        
        // Get the new user ID
        $_SESSION['user_id'] = $conn->insert_id;
        $_SESSION['role'] = $role;
        
        $response['debug'][] = "New user created with ID: " . $_SESSION['user_id'];
    } else {
        // User exists, get their info
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['role'] = $user['role'];
        
        $response['debug'][] = "Existing user found with ID: " . $_SESSION['user_id'];
        
        // Update Firebase UID if needed
        if (empty($user['firebase_uid'])) {
            $update_query = "UPDATE users SET firebase_uid = ?, login_provider = 'google' WHERE user_id = ?";
            $stmt = $conn->prepare($update_query);
            
            if (!$stmt) {
                throw new Exception('Failed to prepare update statement: ' . $conn->error);
            }
            
            $stmt->bind_param("si", $firebase_uid, $user['user_id']);
            $stmt->execute();
            
            $response['debug'][] = "Updated user's Firebase UID";
        }
        
        // Update last login time
        $update_login = "UPDATE users SET last_login = NOW() WHERE user_id = ?";
        $stmt = $conn->prepare($update_login);
        
        if (!$stmt) {
            throw new Exception('Failed to prepare login update statement: ' . $conn->error);
        }
        
        $stmt->bind_param("i", $user['user_id']);
        $stmt->execute();
        
        $response['debug'][] = "Updated last login time";
    }
    
    // Clear the output buffer to ensure no stray HTML
    ob_clean();
    
    // Set success response
    $response['success'] = true;
    $response['message'] = 'Authentication successful';
    $response['user'] = [
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email'],
        'full_name' => $_SESSION['full_name'],
        'role' => $_SESSION['role']
    ];
    
} catch (Exception $e) {
    // Log the error
    log_error($e->getMessage() . "\n" . $e->getTraceAsString());
    
    // Clear the output buffer to ensure no stray HTML
    ob_clean();
    
    // Set error response
    $response['success'] = false;
    $response['message'] = 'Authentication failed: ' . $e->getMessage();
}

// Return final JSON response
if (!$debug_mode) {
    unset($response['debug']);
}

echo json_encode($response);

// Close database connection if it exists
if (isset($conn) && $conn instanceof mysqli) {
    $conn->close();
}
exit;
?> 