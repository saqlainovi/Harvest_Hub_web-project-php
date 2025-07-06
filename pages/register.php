<?php
$page_title = "Register";
require_once '../includes/config.php';

// If user is already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    if (has_role('admin')) {
        redirect(SITE_URL . '/admin/dashboard.php');
    } elseif (has_role('seller')) {
        redirect(SITE_URL . '/seller/dashboard.php');
    } else {
        redirect(SITE_URL . '/buyer/dashboard.php');
    }
}

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $full_name = sanitize_input($_POST['full_name']);
    $role = sanitize_input($_POST['role']);
    $errors = [];
    
    // Validate inputs
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password) || empty($full_name)) {
        $errors[] = 'Please fill in all required fields';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match';
    }
    
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters long';
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    if (!in_array($role, ['buyer', 'seller'])) {
        $errors[] = 'Invalid user role';
    }
    
    // Check if username or email already exists
    $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$email'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $errors[] = 'Username or email already exists';
    }
    
    // If no errors, create the user
    if (empty($errors)) {
        // In a real app, use password_hash for secure password storage
        $hashed_password = '$2y$10$' . base64_encode($password);
        
        // Insert user into database
        $sql = "INSERT INTO users (username, email, password, full_name, role) 
                VALUES ('$username', '$email', '$hashed_password', '$full_name', '$role')";
        
        if ($conn->query($sql) === TRUE) {
            $user_id = $conn->insert_id;
            
            // If role is seller, create seller profile
            if ($role === 'seller') {
                $farm_name = sanitize_input($_POST['farm_name']);
                $location = sanitize_input($_POST['location']);
                
                if (empty($farm_name)) {
                    $errors[] = 'Farm name is required for sellers';
                } else {
                    $sql = "INSERT INTO seller_profiles (seller_id, user_id, farm_name, location) 
                            VALUES ($user_id, $user_id, '$farm_name', '$location')";
                    
                    if ($conn->query($sql) !== TRUE) {
                        $errors[] = 'Error creating seller profile: ' . $conn->error;
                    }
                }
            }
            
            if (empty($errors)) {
                // Set flash message and redirect to login
                set_flash_message('success', 'Registration successful! You can now log in.');
                redirect(SITE_URL . '/pages/login.php');
            }
        } else {
            $errors[] = 'Error creating user: ' . $conn->error;
        }
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="register-section">
    <div class="container">
        <div class="auth-form">
            <h2>Create an Account</h2>
            
            <?php if (isset($errors) && !empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="" method="POST">
                <div class="form-group">
                    <label for="username">Username <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="full_name">Full Name <span class="required">*</span></label>
                    <input type="text" id="full_name" name="full_name" value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password <span class="required">*</span></label>
                    <input type="password" id="password" name="password" required>
                    <small>Password must be at least 6 characters long</small>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="form-group">
                    <label>I am a: <span class="required">*</span></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="role" value="buyer" <?php echo (!isset($_POST['role']) || $_POST['role'] === 'buyer') ? 'checked' : ''; ?>>
                            Buyer (Consumer)
                        </label>
                        <label>
                            <input type="radio" name="role" value="seller" <?php echo (isset($_POST['role']) && $_POST['role'] === 'seller') ? 'checked' : ''; ?>>
                            Seller (Farmer)
                        </label>
                    </div>
                </div>
                
                <!-- Seller specific fields - initially hidden, shown with JavaScript -->
                <div id="seller-fields" style="<?php echo (isset($_POST['role']) && $_POST['role'] === 'seller') ? 'display: block;' : 'display: none;'; ?>">
                    <div class="form-group">
                        <label for="farm_name">Farm/Orchard Name <span class="required">*</span></label>
                        <input type="text" id="farm_name" name="farm_name" value="<?php echo isset($_POST['farm_name']) ? htmlspecialchars($_POST['farm_name']) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="location">Location</label>
                        <input type="text" id="location" name="location" value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Register</button>
                </div>
                
                <div class="form-footer">
                    <p>Already have an account? <a href="login.php">Login here</a></p>
                </div>
            </form>
        </div>
    </div>
</section>

<style>
    .register-section {
        padding: 80px 0;
    }
    
    .auth-form {
        max-width: 600px;
        margin: 0 auto;
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .auth-form h2 {
        margin-bottom: 30px;
        text-align: center;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .btn-primary {
        width: 100%;
        background: #4CAF50;
        color: white;
        border: none;
        padding: 12px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn-primary:hover {
        background: #388E3C;
    }
    
    .form-footer {
        margin-top: 20px;
        text-align: center;
    }
    
    .alert {
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 5px;
    }
    
    .alert-danger {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    
    .alert-danger ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .radio-group {
        display: flex;
        gap: 20px;
        margin-top: 5px;
    }
    
    .required {
        color: #dc3545;
    }
    
    small {
        color: #6c757d;
        font-size: 0.8rem;
    }
</style>

<script>
    // Show/hide seller fields based on role selection
    document.addEventListener('DOMContentLoaded', function() {
        const roleRadios = document.querySelectorAll('input[name="role"]');
        const sellerFields = document.getElementById('seller-fields');
        
        roleRadios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'seller') {
                    sellerFields.style.display = 'block';
                } else {
                    sellerFields.style.display = 'none';
                }
            });
        });
    });
</script>

<?php include('../includes/footer.php'); ?> 