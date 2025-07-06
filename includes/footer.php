<?php
// Skip footer for API requests
$is_api_request = (
    strpos($_SERVER['SCRIPT_NAME'], '/pages/firebase_auth.php') !== false ||
    strpos($_SERVER['SCRIPT_NAME'], '/pages/firebase_register.php') !== false
);

// Only output HTML footer if not an API request
if (!$is_api_request): 
?>
    <!-- Footer -->
    <footer>
        <div class="container">
            <div class="footer-grid">
                <div class="footer-about">
                    <h3>About Harvest Hub</h3>
                    <p>Connecting farmers with consumers for fresh, seasonal fruits.</p>
                </div>
                <div class="footer-links">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="<?php echo SITE_URL; ?>/index.php">Home</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/fruits.php">Fruits</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/harvest_calendar.php">Harvest Calendar</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/contact.php">Contact</a></li>
                    </ul>
                </div>
                <div class="footer-contact">
                    <h3>Contact Us</h3>
                    <p><i class="fas fa-envelope"></i> info@harvesthub.com</p>
                    <p><i class="fas fa-phone"></i> +880 1234567890</p>
                    <div class="social-icons">
                        <a href="#"><i class="fab fa-facebook"></i></a>
                        <a href="#"><i class="fab fa-twitter"></i></a>
                        <a href="#"><i class="fab fa-instagram"></i></a>
                    </div>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; <?php echo date('Y'); ?> Harvest Hub. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="<?php echo SITE_URL; ?>/js/script.js"></script>
    <?php if(isset($extra_js)): ?>
        <?php echo $extra_js; ?>
    <?php endif; ?>
</body>
</html>
<?php endif; ?> 