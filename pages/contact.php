<?php
$page_title = "Contact Us";
require_once '../includes/config.php';

$success = false;
$error = '';

// Process contact form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $subject = sanitize_input($_POST['subject']);
    $message = sanitize_input($_POST['message']);
    
    // Validate inputs
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'Please fill in all fields';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address';
    } else {
        // In a real application, you would send the email here
        // For now, we'll just simulate a successful form submission
        $success = true;
    }
}
?>

<?php include('../includes/header.php'); ?>

<section class="contact-section">
    <div class="container">
        <h2>Contact Us</h2>
        <p class="section-desc">Have questions about Fresh Harvest? We're here to help!</p>
        
        <div class="contact-content">
            <div class="contact-info">
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <h3>Address</h3>
                    <p>123 Fruit Street, Dhaka, Bangladesh</p>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <h3>Phone</h3>
                    <p>+880 1234567890</p>
                    <p>+880 9876543210</p>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <h3>Email</h3>
                    <p>info@freshharvest.com</p>
                    <p>support@freshharvest.com</p>
                </div>
                
                <div class="info-item">
                    <i class="fas fa-clock"></i>
                    <h3>Business Hours</h3>
                    <p>Monday - Friday: 9:00 AM - 6:00 PM</p>
                    <p>Saturday: 10:00 AM - 4:00 PM</p>
                    <p>Sunday: Closed</p>
                </div>
                
                <div class="social-links">
                    <h3>Connect With Us</h3>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                        <a href="#"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            
            <div class="contact-form">
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <p>Your message has been sent successfully! We'll get back to you soon.</p>
                    </div>
                <?php else: ?>
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger">
                            <p><?php echo $error; ?></p>
                        </div>
                    <?php endif; ?>
                    
                    <form action="" method="POST">
                        <div class="form-group">
                            <label for="name">Full Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address <span class="required">*</span></label>
                            <input type="email" id="email" name="email" value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">Subject <span class="required">*</span></label>
                            <input type="text" id="subject" name="subject" value="<?php echo isset($_POST['subject']) ? htmlspecialchars($_POST['subject']) : ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">Message <span class="required">*</span></label>
                            <textarea id="message" name="message" rows="6" required><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Map -->
        <div class="map-container">
            <h3>Find Us</h3>
            <div class="map">
                <!-- In a real application, you would embed a Google Map here -->
                <img src="<?php echo SITE_URL; ?>/images/map-placeholder.jpg" alt="Location Map">
            </div>
        </div>
    </div>
</section>

<style>
    .contact-section {
        padding: 60px 0;
    }
    
    .section-desc {
        text-align: center;
        margin-bottom: 40px;
        color: #666;
        font-size: 1.1rem;
    }
    
    .contact-content {
        display: grid;
        grid-template-columns: 1fr 2fr;
        gap: 30px;
        margin-bottom: 50px;
    }
    
    /* Contact Info */
    .contact-info {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .info-item {
        margin-bottom: 25px;
    }
    
    .info-item i {
        font-size: 1.8rem;
        color: #4CAF50;
        margin-bottom: 10px;
    }
    
    .info-item h3 {
        margin-bottom: 10px;
        font-size: 1.2rem;
        color: #333;
    }
    
    .info-item p {
        color: #666;
        margin-bottom: 5px;
    }
    
    .social-links h3 {
        margin-bottom: 15px;
        font-size: 1.2rem;
        color: #333;
    }
    
    .social-icons {
        display: flex;
        gap: 15px;
    }
    
    .social-icons a {
        display: inline-flex;
        justify-content: center;
        align-items: center;
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #f5f5f5;
        color: #333;
        transition: all 0.3s ease;
    }
    
    .social-icons a:hover {
        background: #4CAF50;
        color: white;
    }
    
    /* Contact Form */
    .contact-form {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-group label {
        display: block;
        margin-bottom: 5px;
        font-weight: 500;
    }
    
    .form-group input,
    .form-group textarea {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 16px;
    }
    
    .form-group textarea {
        resize: vertical;
    }
    
    .btn-primary {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 12px 20px;
        font-size: 16px;
        border-radius: 5px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn-primary:hover {
        background: #388E3C;
    }
    
    .required {
        color: #F44336;
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
    
    .alert-success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    
    /* Map */
    .map-container {
        margin-top: 50px;
    }
    
    .map-container h3 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    
    .map {
        height: 400px;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .map img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }
    
    /* Responsive */
    @media (max-width: 992px) {
        .contact-content {
            grid-template-columns: 1fr;
        }
    }
</style>

<?php include('../includes/footer.php'); ?> 