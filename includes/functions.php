<?php
// Clean user input
function sanitize_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    $data = $conn->real_escape_string($data);
    return $data;
}

// Check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

// Check user role
function has_role($role) {
    return is_logged_in() && $_SESSION['role'] === $role;
}

// Redirect to a different page
function redirect($url) {
    header("Location: " . $url);
    exit();
}

// Set flash message
function set_flash_message($type, $message) {
    if (!isset($_SESSION['flash_messages'])) {
        $_SESSION['flash_messages'] = [];
    }
    
    if (!isset($_SESSION['flash_messages'][$type])) {
        $_SESSION['flash_messages'][$type] = [];
    }
    
    $_SESSION['flash_messages'][$type][] = $message;
}

// Display flash message
function display_flash_message() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        echo "<div class='alert alert-{$type}'>{$message}</div>";
        unset($_SESSION['flash']);
    }
}

// Generate random string
function generate_random_string($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $string;
}

// Upload image
function upload_image($file, $folder = '') {
    $target_dir = UPLOAD_PATH . $folder;
    
    // Create directory if it doesn't exist
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    // Generate unique filename
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = generate_random_string() . '.' . $file_extension;
    $target_file = $target_dir . $unique_filename;
    
    // Check if image file is a actual image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return ['success' => false, 'message' => 'File is not an image.'];
    }
    
    // Check file size (limit to 2MB)
    if ($file["size"] > 2000000) {
        return ['success' => false, 'message' => 'File is too large. Max 2MB allowed.'];
    }
    
    // Allow certain file formats
    if ($file_extension != "jpg" && $file_extension != "png" && $file_extension != "jpeg") {
        return ['success' => false, 'message' => 'Only JPG, JPEG, PNG files are allowed.'];
    }
    
    // Try to upload file
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return [
            'success' => true, 
            'filename' => $unique_filename,
            'path' => $target_file,
            'url' => UPLOAD_URL . $folder . $unique_filename
        ];
    } else {
        return ['success' => false, 'message' => 'Error uploading file.'];
    }
}

// Get current in-season fruits
function get_in_season_fruits($limit = 6) {
    global $conn;
    $current_date = date('Y-m-d');
    
    $sql = "SELECT f.*, c.name as category_name, s.farm_name, AVG(r.rating) as avg_rating 
            FROM fruits f
            JOIN harvest_seasons h ON f.fruit_id = h.fruit_id
            JOIN seller_profiles s ON f.seller_id = s.seller_id
            LEFT JOIN categories c ON f.category_id = c.category_id
            LEFT JOIN reviews r ON f.fruit_id = r.fruit_id
            WHERE h.start_date <= '$current_date' AND h.end_date >= '$current_date'
            AND f.is_available = 1
            GROUP BY f.fruit_id
            ORDER BY avg_rating DESC
            LIMIT $limit";
            
    $result = $conn->query($sql);
    $fruits = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $fruits[] = $row;
        }
    }
    
    return $fruits;
}

// Format price
function format_price($price) {
    return '৳' . number_format($price, 2);
}

// Get user by ID
function get_user_by_id($user_id) {
    global $conn;
    
    $user_id = (int) $user_id;
    $sql = "SELECT * FROM users WHERE user_id = $user_id";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    
    return null;
}

/**
 * Display flash messages to the user
 */
function display_flash_messages() {
    if (isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages'])) {
        foreach ($_SESSION['flash_messages'] as $type => $messages) {
            foreach ($messages as $message) {
                echo '<div class="alert alert-' . $type . '">';
                echo $message;
                echo '</div>';
            }
        }
        // Clear the flash messages after displaying them
        $_SESSION['flash_messages'] = [];
    }
}
?> 