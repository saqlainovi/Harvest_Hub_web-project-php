<?php
// Include configuration
require_once 'includes/config.php';

// Check if we have a database connection
if (!$conn) {
    die("Database connection failed. Please check your configuration.");
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to create a seller profile. <a href='pages/login.php'>Log in</a>");
}

$user_id = $_SESSION['user_id'];

// Verify user has seller role
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'seller') {
    die("Error: Only users with seller role can create a seller profile. Your role: " . ($_SESSION['user_role'] ?? 'none'));
}

// Check if seller profile already exists
$check_sql = "SELECT * FROM seller_profiles WHERE user_id = ?";
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    die("You already have a seller profile. <a href='seller/dashboard.php'>Go to Dashboard</a>");
}

// Process form submission
$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $farm_name = isset($_POST['farm_name']) ? trim($_POST['farm_name']) : '';
    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
    $location = isset($_POST['location']) ? trim($_POST['location']) : '';
    
    if (empty($farm_name)) {
        $error = "Farm name is required";
    } else {
        // Get user information for verification
        $user_sql = "SELECT * FROM users WHERE user_id = ?";
        $user_stmt = $conn->prepare($user_sql);
        $user_stmt->bind_param('i', $user_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            $error = "User not found";
        } else {
            $user = $user_result->fetch_assoc();
            
            // Create new seller profile
            $insert_sql = "INSERT INTO seller_profiles (seller_id, user_id, farm_name, description, location, is_verified) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_sql);
            
            // Use user_id as seller_id for simplicity
            $is_verified = 0; // Not verified by default
            $stmt->bind_param('iisssi', $user_id, $user_id, $farm_name, $description, $location, $is_verified);
            
            if ($stmt->execute()) {
                $success = true;
            } else {
                $error = "Database error: " . $conn->error;
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Seller Profile</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
        }
        h1 {
            color: #4CAF50;
        }
        .form-group {
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input[type="text"], textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        textarea {
            height: 100px;
        }
        button {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #45a049;
        }
        .error {
            color: red;
            margin-bottom: 20px;
        }
        .success {
            color: green;
            margin-bottom: 20px;
        }
        .links {
            margin-top: 20px;
        }
        .links a {
            margin-right: 10px;
            color: #4CAF50;
        }
    </style>
</head>
<body>
    <h1>Create Seller Profile</h1>
    
    <?php if ($success): ?>
        <div class="success">
            <p>Your seller profile has been created successfully!</p>
            <p><a href="seller/dashboard.php">Go to Seller Dashboard</a></p>
        </div>
    <?php else: ?>
        <?php if ($error): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="post" action="">
            <div class="form-group">
                <label for="farm_name">Farm Name *</label>
                <input type="text" id="farm_name" name="farm_name" required>
            </div>
            
            <div class="form-group">
                <label for="description">Description</label>
                <textarea id="description" name="description"></textarea>
            </div>
            
            <div class="form-group">
                <label for="location">Location</label>
                <input type="text" id="location" name="location">
            </div>
            
            <button type="submit">Create Profile</button>
        </form>
    <?php endif; ?>
    
    <div class="links">
        <a href="index.php">Home</a>
        <a href="check_seller_profile.php">Check Profile Status</a>
    </div>
</body>
</html> 