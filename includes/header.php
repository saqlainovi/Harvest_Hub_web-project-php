<?php 
require_once 'config.php';
$current_page = basename($_SERVER['PHP_SELF']);

// Get cart count for display
if (!isset($_SESSION['cart_count'])) {
    // If cart_count not set, calculate it
    $cart_count = 0;
    if (isset($_SESSION['cart']) && is_array($_SESSION['cart'])) {
        foreach ($_SESSION['cart'] as $item) {
            if (is_array($item) && isset($item['quantity'])) {
                $cart_count += (int)$item['quantity'];
            }
        }
    }
    $_SESSION['cart_count'] = $cart_count;
} else {
    $cart_count = $_SESSION['cart_count'];
}

// Determine if this is an API request that should output JSON
$is_api_request = (
    strpos($_SERVER['SCRIPT_NAME'], '/pages/firebase_auth.php') !== false ||
    strpos($_SERVER['SCRIPT_NAME'], '/pages/firebase_register.php') !== false
);

// Only continue with HTML output if not an API request
if (!$is_api_request): 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' . SITE_NAME : SITE_NAME; ?></title>
    <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/2153/2153788.png" type="image/png">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/style.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/css/custom.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <?php if(isset($extra_css)): ?>
        <?php echo $extra_css; ?>
    <?php endif; ?>
</head>
<body>
    <!-- Header Section -->
    <header>
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>/index.php">
                    <img src="<?php echo SITE_URL; ?>/img/logo/harvest-hub-logo.svg" alt="Harvest Hub Logo" class="site-logo" onerror="this.src='<?php echo SITE_URL; ?>/img/placeholder.jpg'; this.style.width='40px'; this.style.height='40px';">
                </a>
            </div>
            <nav>
                <ul>
                    <li><a href="<?php echo SITE_URL; ?>/index.php" <?php echo ($current_page == 'index.php') ? 'class="active"' : ''; ?>>Home</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/fruits.php" <?php echo ($current_page == 'fruits.php') ? 'class="active"' : ''; ?>>Fruits</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/agricultural_products.php" <?php echo ($current_page == 'agricultural_products.php') ? 'class="active"' : ''; ?>>Agriculture</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/harvest_calendar.php" <?php echo ($current_page == 'harvest_calendar.php') ? 'class="active"' : ''; ?>>Harvest Calendar</a></li>
                    <li><a href="<?php echo SITE_URL; ?>/pages/contact.php" <?php echo ($current_page == 'contact.php') ? 'class="active"' : ''; ?>>Contact</a></li>
                    
                    <li class="cart-item">
                        <a href="<?php echo SITE_URL; ?>/pages/cart.php" <?php echo ($current_page == 'cart.php') ? 'class="active"' : ''; ?>>
                            <i class="fas fa-shopping-cart"></i>
                            <?php if ($cart_count > 0): ?>
                                <span class="cart-count"><?php echo $cart_count; ?></span>
                            <?php endif; ?>
                        </a>
                    </li>
                    
                    <?php if(is_logged_in()): ?>
                        <?php if(has_role('admin')): ?>
                            <li><a href="<?php echo SITE_URL; ?>/admin/dashboard.php">Admin Panel</a></li>
                        <?php elseif(has_role('seller')): ?>
                            <li><a href="<?php echo SITE_URL; ?>/seller/dashboard.php">Seller Dashboard</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo SITE_URL; ?>/buyer/dashboard.php">My Account</a></li>
                        <?php endif; ?>
                        <li><a href="<?php echo SITE_URL; ?>/pages/logout.php">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo SITE_URL; ?>/pages/login.php" <?php echo ($current_page == 'login.php') ? 'class="active"' : ''; ?>>Login</a></li>
                        <li><a href="<?php echo SITE_URL; ?>/pages/register.php" <?php echo ($current_page == 'register.php') ? 'class="active"' : ''; ?>>Register</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>
    
    <!-- Flash Messages -->
    <div class="container" style="margin-top: 20px;">
        <?php display_flash_messages(); ?>
    </div> 
<?php endif; ?> 