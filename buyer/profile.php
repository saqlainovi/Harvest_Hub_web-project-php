<?php
$page_title = "Buyer Profile";
require_once '../includes/config.php';

// Check if user is logged in and is a buyer
if (!is_logged_in() || !has_role('buyer')) {
    set_flash_message('error', 'You must be logged in as a buyer to view this page.');
    redirect(SITE_URL . '/pages/login.php');
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_sql = "SELECT * FROM users WHERE user_id = $user_id";
$user_result = $conn->query($user_sql);

if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
} else {
    set_flash_message('error', 'User profile not found.');
    redirect(SITE_URL . '/index.php');
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $full_name = sanitize_input($_POST['full_name']);
    $email = sanitize_input($_POST['email']);
    $phone = sanitize_input($_POST['phone']);
    $address = sanitize_input($_POST['address']);
    $password = sanitize_input($_POST['password']);
    $confirm_password = sanitize_input($_POST['confirm_password']);
    
    $errors = [];
    
    // Validate fields
    if (empty($full_name)) {
        $errors[] = 'Full name is required.';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Invalid email format.';
    }
    
    // Check if email already exists (for a different user)
    if ($email !== $user['email']) {
        $check_email_sql = "SELECT * FROM users WHERE email = '$email' AND user_id != $user_id";
        $check_email_result = $conn->query($check_email_sql);
        if ($check_email_result && $check_email_result->num_rows > 0) {
            $errors[] = 'Email already in use by another account.';
        }
    }
    
    // Password validation (only if password is being changed)
    if (!empty($password)) {
        if (strlen($password) < 6) {
            $errors[] = 'Password must be at least 6 characters long.';
        }
        
        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }
    }
    
    // If no errors, update the profile
    if (empty($errors)) {
        // Start with basic query without password
        $update_sql = "UPDATE users SET 
                      full_name = ?, 
                      email = ?, 
                      phone = ?, 
                      address = ?, 
                      updated_at = NOW() 
                      WHERE user_id = ?";
        
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("ssssi", $full_name, $email, $phone, $address, $user_id);
        
        // If password is being updated, use a different query
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $update_sql = "UPDATE users SET 
                          full_name = ?, 
                          email = ?, 
                          phone = ?, 
                          address = ?, 
                          password = ?,
                          updated_at = NOW() 
                          WHERE user_id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("sssssi", $full_name, $email, $phone, $address, $hashed_password, $user_id);
        }
        
        if ($stmt->execute()) {
            set_flash_message('success', 'Profile updated successfully!');
            redirect($_SERVER['PHP_SELF']);
        } else {
            set_flash_message('error', 'Failed to update profile: ' . $conn->error);
        }
    } else {
        // Set error messages
        foreach ($errors as $error) {
            set_flash_message('error', $error);
        }
    }
}

include('../includes/header.php');
?>

<section class="profile-section">
    <div class="container">
        <h2>My Profile</h2>
        
        <div class="profile-navigation">
            <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Back to Dashboard</a>
        </div>
        
        <?php display_flash_messages(); ?>
        
        <div class="profile-card">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <div class="profile-title">
                    <h3><?php echo htmlspecialchars($user['full_name']); ?></h3>
                    <p>Buyer Account</p>
                </div>
            </div>
            
            <div class="profile-form">
                <form method="post" action="<?php echo $_SERVER['PHP_SELF']; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="full_name">Full Name</label>
                            <input type="text" id="full_name" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="text" id="phone" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($user['address'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h4>Change Password (leave blank to keep current password)</h4>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="password">New Password</label>
                            <input type="password" id="password" name="password">
                            <small>At least 6 characters</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password">
                        </div>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<style>
    .profile-section {
        padding: 60px 0;
    }
    
    .profile-section h2 {
        margin-bottom: 30px;
    }
    
    .profile-navigation {
        margin-bottom: 20px;
    }
    
    .back-link {
        display: inline-flex;
        align-items: center;
        color: #4CAF50;
        font-weight: 500;
    }
    
    .back-link i {
        margin-right: 5px;
    }
    
    .profile-card {
        background: white;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 30px;
    }
    
    .profile-header {
        display: flex;
        align-items: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #eee;
    }
    
    .profile-avatar {
        width: 80px;
        height: 80px;
        background: #f1f8e9;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-right: 20px;
    }
    
    .profile-avatar i {
        font-size: 2.5rem;
        color: #4CAF50;
    }
    
    .profile-title h3 {
        margin-bottom: 5px;
        color: #333;
    }
    
    .profile-title p {
        color: #666;
    }
    
    .profile-form {
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-row {
        display: flex;
        gap: 20px;
        margin-bottom: 20px;
    }
    
    .form-group {
        flex: 1;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
        color: #555;
    }
    
    .form-group input {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 1rem;
    }
    
    .form-group small {
        font-size: 0.8rem;
        color: #666;
    }
    
    .profile-form h4 {
        margin: 20px 0 15px;
        color: #333;
        font-size: 1.1rem;
    }
    
    .form-actions {
        margin-top: 30px;
        text-align: right;
    }
    
    .btn {
        padding: 10px 20px;
        font-size: 1rem;
        border: none;
        border-radius: 5px;
        cursor: pointer;
    }
    
    .btn-primary {
        background: #4CAF50;
        color: white;
    }
    
    @media (max-width: 768px) {
        .form-row {
            flex-direction: column;
            gap: 0;
        }
        
        .profile-header {
            flex-direction: column;
            text-align: center;
        }
        
        .profile-avatar {
            margin: 0 auto 15px;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 