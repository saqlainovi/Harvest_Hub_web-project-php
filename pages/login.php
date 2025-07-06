<?php
$page_title = "Login";
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

// Get redirect URL if any
$redirect = isset($_GET['redirect']) ? $_GET['redirect'] : '';

// Process traditional login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    $error = '';
    
    // Validate inputs
    if (empty($username) || empty($password)) {
        $error = 'Please fill in all fields';
    } else {
        // Check if username exists
        $sql = "SELECT * FROM users WHERE username = '$username' OR email = '$username'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password (in a real app, use password_verify with properly hashed passwords)
            if ($user['password'] === '$2y$10$' . base64_encode($password)) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                // Update last login time
                $update_sql = "UPDATE users SET last_login = NOW() WHERE user_id = " . $user['user_id'];
                $conn->query($update_sql);
                
                // Redirect based on redirect parameter or role
                if (!empty($redirect)) {
                    redirect(SITE_URL . '/' . $redirect);
                } elseif ($user['role'] === 'admin') {
                    redirect(SITE_URL . '/admin/dashboard.php');
                } elseif ($user['role'] === 'seller') {
                    redirect(SITE_URL . '/seller/dashboard.php');
                } else {
                    redirect(SITE_URL . '/buyer/dashboard.php');
                }
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Username or email not found';
        }
    }
}
?>

<?php include('../includes/header.php'); ?>

<!-- Firebase SDK -->
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/9.22.0/firebase-auth-compat.js"></script>

<section class="login-section">
    <div class="container">
        <div class="auth-form">
            <h2>Login to Your Account</h2>
            
            <div id="firebase-error" class="alert alert-danger" style="display: none;"></div>
            
            <?php if (isset($error) && !empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <!-- Traditional Login Form -->
            <form id="traditional-login-form" action="" method="POST">
                <div class="form-group">
                    <label for="username">Username or Email</label>
                    <input type="text" id="username" name="username" value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary">Login</button>
                </div>
            </form>
            
            <!-- Social Login Options -->
            <div class="social-login">
                <p class="divider"><span>Or login with</span></p>
                
                <div class="social-buttons">
                    <button id="google-login" class="btn btn-social btn-google">
                        <i class="fab fa-google"></i> Google
                    </button>
                    
                    <button id="email-login" class="btn btn-social btn-email">
                        <i class="fas fa-envelope"></i> Email
                    </button>
                </div>
            </div>
            
            <!-- Email Login Form (Hidden by default) -->
            <form id="firebase-email-form" style="display: none;">
                <div class="form-group">
                    <label for="firebase-email">Email</label>
                    <input type="email" id="firebase-email" required>
                </div>
                
                <div class="form-group">
                    <label for="firebase-password">Password</label>
                    <input type="password" id="firebase-password" required>
                </div>
                
                <div class="form-actions">
                    <button type="button" id="firebase-login-btn" class="btn btn-primary">Login</button>
                    <button type="button" id="firebase-register-btn" class="btn btn-secondary">Register</button>
                    <button type="button" id="back-to-options" class="btn btn-link">Back</button>
                </div>
            </form>
            
            <div class="form-footer">
                <p>Don't have an account? <a href="register.php<?php echo !empty($redirect) ? '?redirect=' . urlencode($redirect) : ''; ?>">Register here</a></p>
            </div>
        </div>
    </div>
</section>

<style>
    .login-section {
        padding: 80px 0;
    }
    
    .auth-form {
        max-width: 500px;
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
    
    .btn-secondary {
        background: #757575;
        color: white;
    }
    
    .btn-secondary:hover {
        background: #616161;
    }
    
    .btn-link {
        background: none;
        color: #4CAF50;
        text-decoration: underline;
        border: none;
        padding: 0;
        font-size: 14px;
        cursor: pointer;
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
    
    .divider {
        display: flex;
        align-items: center;
        text-align: center;
        margin: 20px 0;
        color: #757575;
    }
    
    .divider::before,
    .divider::after {
        content: '';
        flex: 1;
        border-bottom: 1px solid #ddd;
    }
    
    .divider span {
        padding: 0 10px;
    }
    
    .social-buttons {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
    }
    
    .btn-social {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 10px;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        font-weight: 500;
        transition: opacity 0.3s;
    }
    
    .btn-social:hover {
        opacity: 0.9;
    }
    
    .btn-google {
        background: #DB4437;
        color: white;
    }
    
    .btn-email {
        background: #4285F4;
        color: white;
    }
    
    .form-actions {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
</style>

<script>
    // Initialize Firebase
    firebase.initializeApp({
        apiKey: "AIzaSyAOAa7VEgItWHej6-HFUVNJVpfwzB5hE3A",
        authDomain: "ovisoft-e5377.firebaseapp.com",
        projectId: "ovisoft-e5377",
        storageBucket: "ovisoft-e5377.firebasestorage.app",
        messagingSenderId: "950806320878",
        appId: "1:950806320878:web:3879c1c50a517365e605ff",
        measurementId: "G-SV29JBD062"
    });
    
    const auth = firebase.auth();
    const googleProvider = new firebase.auth.GoogleAuthProvider();
    
    // DOM Elements
    const traditionalForm = document.getElementById('traditional-login-form');
    const firebaseEmailForm = document.getElementById('firebase-email-form');
    const googleLoginBtn = document.getElementById('google-login');
    const emailLoginBtn = document.getElementById('email-login');
    const firebaseLoginBtn = document.getElementById('firebase-login-btn');
    const firebaseRegisterBtn = document.getElementById('firebase-register-btn');
    const backToOptionsBtn = document.getElementById('back-to-options');
    const errorElement = document.getElementById('firebase-error');
    
    // Show Firebase error
    function showError(message) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
    
    // Hide Firebase error
    function hideError() {
        errorElement.style.display = 'none';
    }
    
    // Toggle between login options
    emailLoginBtn.addEventListener('click', () => {
        traditionalForm.style.display = 'none';
        document.querySelector('.social-login').style.display = 'none';
        firebaseEmailForm.style.display = 'block';
    });
    
    backToOptionsBtn.addEventListener('click', () => {
        traditionalForm.style.display = 'block';
        document.querySelector('.social-login').style.display = 'block';
        firebaseEmailForm.style.display = 'none';
        hideError();
    });
    
    // Google Login
    googleLoginBtn.addEventListener('click', async () => {
        try {
            hideError();
            const result = await auth.signInWithPopup(googleProvider);
            const user = result.user;
            const idToken = await user.getIdToken();
            
            // Show waiting message
            showError("Authenticating with server...");
            
            // Send token to server for authentication
            authenticateWithServer(idToken);
        } catch (error) {
            console.error("Google login error:", error);
            showError("Google login failed: " + (error.message || "Unknown error"));
        }
    });
    
    // Firebase Email Login
    firebaseLoginBtn.addEventListener('click', async () => {
        const email = document.getElementById('firebase-email').value;
        const password = document.getElementById('firebase-password').value;
        
        if (!email || !password) {
            showError('Please enter both email and password');
            return;
        }
        
        try {
            hideError();
            const userCredential = await auth.signInWithEmailAndPassword(email, password);
            const user = userCredential.user;
            const idToken = await user.getIdToken();
            
            // Show waiting message
            showError("Authenticating with server...");
            
            // Send token to server for authentication
            authenticateWithServer(idToken);
        } catch (error) {
            console.error("Email login error:", error);
            
            // Display specific error message
            if (error.code === 'auth/invalid-credential') {
                showError('Invalid email or password');
            } else if (error.code === 'auth/user-not-found') {
                showError('No account found with this email');
            } else if (error.code === 'auth/wrong-password') {
                showError('Incorrect password');
            } else {
                showError("Login failed: " + (error.message || "Unknown error"));
            }
        }
    });
    
    // Firebase Email Registration
    firebaseRegisterBtn.addEventListener('click', async () => {
        const email = document.getElementById('firebase-email').value;
        const password = document.getElementById('firebase-password').value;
        
        if (!email || !password) {
            showError('Please enter both email and password');
            return;
        }
        
        if (password.length < 6) {
            showError('Password should be at least 6 characters');
            return;
        }
        
        try {
            hideError();
            const userCredential = await auth.createUserWithEmailAndPassword(email, password);
            const user = userCredential.user;
            const idToken = await user.getIdToken();
            
            // Show waiting message
            showError("Registering with server...");
            
            // Send token to server for registration and authentication
            registerWithServer(idToken, {
                email: email,
                role: 'buyer' // Default role
            });
        } catch (error) {
            console.error("Email registration error:", error);
            
            // Display specific error message
            if (error.code === 'auth/email-already-in-use') {
                showError('An account with this email already exists');
            } else if (error.code === 'auth/invalid-email') {
                showError('Please enter a valid email address');
            } else if (error.code === 'auth/weak-password') {
                showError('Password is too weak');
            } else {
                showError("Registration failed: " + (error.message || "Unknown error"));
            }
        }
    });
    
    // Function to authenticate with the server
    async function authenticateWithServer(idToken) {
        try {
            // Show more detailed status
            showError("Sending request to server...");
            
            const response = await fetch('<?php echo SITE_URL; ?>/pages/firebase_auth_basic.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ idToken }),
                credentials: 'include' // Include cookies for session data
            });
            
            // Get the response as text first
            const responseText = await response.text();
            
            let data;
            try {
                // Try to parse the response as JSON
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error("Failed to parse response:", responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
            
            console.log("Server response data:", data);
            
            if (data.success) {
                // Redirect based on role
                const redirect = '<?php echo !empty($redirect) ? $redirect : ''; ?>';
                if (redirect) {
                    window.location.href = '<?php echo SITE_URL; ?>/' + redirect;
                } else if (data.user.role === 'admin') {
                    window.location.href = '<?php echo SITE_URL; ?>/admin/dashboard.php';
                } else if (data.user.role === 'seller') {
                    window.location.href = '<?php echo SITE_URL; ?>/seller/dashboard.php';
                } else {
                    window.location.href = '<?php echo SITE_URL; ?>/buyer/dashboard.php';
                }
            } else {
                console.error("Server authentication error:", data);
                showError(data.message || 'Server authentication failed');
            }
        } catch (error) {
            console.error("Server authentication error:", error);
            showError(`Server communication error: ${error.message}`);
        }
    }
    
    // Function to register with the server
    async function registerWithServer(idToken, userData) {
        try {
            const response = await fetch('<?php echo SITE_URL; ?>/pages/firebase_register.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ idToken, userData }),
                credentials: 'include' // Include cookies for session data
            });
            
            // Get the response as text first
            const responseText = await response.text();
            
            let data;
            try {
                // Try to parse the response as JSON
                data = JSON.parse(responseText);
            } catch (parseError) {
                console.error("Failed to parse response:", responseText);
                throw new Error(`Invalid JSON response: ${parseError.message}`);
            }
            
            if (data.success) {
                // Redirect based on role
                const redirect = '<?php echo !empty($redirect) ? $redirect : ''; ?>';
                if (redirect) {
                    window.location.href = '<?php echo SITE_URL; ?>/' + redirect;
                } else if (data.user.role === 'admin') {
                    window.location.href = '<?php echo SITE_URL; ?>/admin/dashboard.php';
                } else if (data.user.role === 'seller') {
                    window.location.href = '<?php echo SITE_URL; ?>/seller/dashboard.php';
                } else {
                    window.location.href = '<?php echo SITE_URL; ?>/buyer/dashboard.php';
                }
            } else {
                console.error("Server registration error:", data);
                showError(data.message || 'Registration failed on server');
            }
        } catch (error) {
            console.error("Server registration error:", error);
            showError('Server communication error: ' + error.message);
        }
    }
</script>

<?php include('../includes/footer.php'); ?> 