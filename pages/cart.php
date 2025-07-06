<?php
$page_title = "Shopping Cart";
require_once '../includes/config.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle "Add to Cart" action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    if (isset($_POST['fruit_id']) && isset($_POST['quantity'])) {
        $result = add_to_cart($_POST['fruit_id'], (int)$_POST['quantity']);
        set_flash_message($result['success'] ? 'success' : 'error', $result['message']);
        redirect($_SERVER['PHP_SELF']);
    }
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $action = $_POST['action'];
        
        // Update quantity
        if ($action === 'update' && isset($_POST['item_id']) && isset($_POST['quantity']) && isset($_POST['item_type'])) {
            $result = update_cart_item($_POST['item_id'], (int)$_POST['quantity'], $_POST['item_type']);
            set_flash_message($result['success'] ? 'success' : 'error', $result['message']);
        }
        
        // Remove item
        elseif ($action === 'remove' && isset($_POST['item_id']) && isset($_POST['item_type'])) {
            $result = remove_from_cart($_POST['item_id'], $_POST['item_type']);
            set_flash_message($result['success'] ? 'success' : 'error', $result['message']);
        }
        
        // Clear cart
        elseif ($action === 'clear') {
            clear_cart();
            set_flash_message('success', 'Cart cleared successfully.');
        }
        
        // Redirect to avoid form resubmission
        redirect($_SERVER['PHP_SELF']);
    }
}

// Get cart contents and calculate totals
$cart = get_cart_contents();
$cart_count = 0;
$cart_total = 0;

foreach ($_SESSION['cart'] as $key => $item) {
    if (is_array($item) && isset($item['quantity']) && isset($item['price'])) {
        $cart_count += $item['quantity'];
        $cart_total += $item['price'] * $item['quantity'];
    }
}

// Store cart count in session for header display
$_SESSION['cart_count'] = $cart_count;

include('../includes/header.php');
?>

<!-- Modern Shopping Cart Page -->
<section class="cart-page" style="padding: 40px 0; background-color: #f9f9f9; min-height: 70vh;">
    <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 20px;">
        <div style="display: flex; align-items: center; margin-bottom: 30px;">
            <h1 style="font-size: 2rem; color: #333; margin: 0;">Your Shopping Cart</h1>
            <span style="margin-left: auto; background-color: #4CAF50; color: white; padding: 5px 12px; border-radius: 20px; font-size: 14px;">
                <?php echo $cart_count; ?> Item<?php echo $cart_count !== 1 ? 's' : ''; ?>
            </span>
        </div>
        
        <?php if (empty($_SESSION['cart'])): ?>
            <!-- Empty Cart State -->
            <div style="text-align: center; padding: 60px 20px; background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05);">
                <div style="font-size: 80px; color: #ddd; margin-bottom: 20px;">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h2 style="font-size: 1.8rem; color: #333; margin-bottom: 15px;">Your cart is empty</h2>
                <p style="color: #666; margin-bottom: 30px; font-size: 16px;">Looks like you haven't added any products to your cart yet.</p>
                <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap;">
                    <a href="<?php echo SITE_URL; ?>/pages/fruits.php" style="display: inline-block; background-color: #4CAF50; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">Browse Fruits</a>
                    <a href="<?php echo SITE_URL; ?>/pages/agricultural_products.php" style="display: inline-block; background-color: rgba(76, 175, 80, 0.1); color: #4CAF50; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; border: 1px solid #4CAF50;">Browse Agriculture Products</a>
                </div>
            </div>
        <?php else: ?>
            <!-- Cart Items -->
            <div style="display: flex; flex-wrap: wrap; gap: 30px;">
                <!-- Cart Items List -->
                <div style="flex: 1; min-width: 60%;">
                    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); overflow: hidden;">
                        <!-- Cart Item Headers -->
                        <div style="display: grid; grid-template-columns: minmax(300px, 3fr) 1fr 1fr 1fr; padding: 15px 20px; background-color: #f5f5f5; border-bottom: 1px solid #eee; font-weight: bold; color: #555;">
                            <div>Product</div>
                            <div style="text-align: center;">Price</div>
                            <div style="text-align: center;">Quantity</div>
                            <div style="text-align: right;">Subtotal</div>
                        </div>
                        
                        <!-- Cart Items -->
                        <?php 
                        $total = 0;
                        foreach ($_SESSION['cart'] as $key => $item): 
                            // Skip if not an array
                            if (!is_array($item)) continue;
                            
                            // Skip if missing required data
                            if (!isset($item['name']) || !isset($item['price']) || !isset($item['quantity'])) continue;
                            
                            $parts = explode('_', $key);
                            $item_type = isset($parts[0]) ? $parts[0] : 'unknown';
                            $item_id = isset($parts[1]) ? $parts[1] : 0;
                            
                            // Calculate subtotal
                            $subtotal = $item['price'] * $item['quantity'];
                            $total += $subtotal;
                            
                            // Determine image path
                            if ($item_type === 'agricultural') {
                                $image_folder = 'agricultural';
                            } else {
                                $image_folder = 'fruits';
                            }
                            
                            // Create a CSS-based fallback for the image
                            $product_name_initial = substr($item['name'], 0, 1);
                            $display_path = !empty($item['image']) 
                                ? SITE_URL . '/img/' . $item['image'] 
                                : SITE_URL . '/img/placeholder.jpg';
                        ?>
                            <!-- Single Cart Item -->
                            <div style="display: grid; grid-template-columns: minmax(300px, 3fr) 1fr 1fr 1fr; padding: 20px; border-bottom: 1px solid #eee; align-items: center;">
                                <!-- Product Info -->
                                <div style="display: flex; align-items: center; gap: 15px;">
                                    <div style="position: relative; width: 80px; height: 80px; border-radius: 8px; overflow: hidden; background-color: #f5f5f5;">
                                        <!-- Fallback text if image fails -->
                                        <div style="position: absolute; width: 100%; height: 100%; display: flex; align-items: center; justify-content: center; font-size: 24px; font-weight: bold; color: #4CAF50;">
                                            <?php echo $product_name_initial; ?>
                                        </div>
                                        <!-- The actual image -->
                                        <img src="<?php echo $display_path; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" style="position: relative; z-index: 1; width: 100%; height: 100%; object-fit: cover;" onerror="this.style.display='none';">
                                    </div>
                                    <div>
                                        <h3 style="margin: 0 0 5px; font-size: 16px; color: #333;"><?php echo htmlspecialchars($item['name']); ?></h3>
                                        <?php if(isset($item['seller'])): ?>
                                            <p style="margin: 0 0 5px; font-size: 14px; color: #666;">Seller: <?php echo htmlspecialchars($item['seller']); ?></p>
                                        <?php endif; ?>
                                        <p style="margin: 0; font-size: 12px; background-color: #f0f8f0; display: inline-block; padding: 3px 8px; border-radius: 12px; color: #4CAF50;">
                                            <?php echo ($item_type === 'agricultural') ? 'Agricultural Product' : 'Fruit'; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <!-- Price -->
                                <div style="text-align: center; color: #333; font-weight: 500;">
                                    <?php echo format_price($item['price']); ?>
                                    <div style="font-size: 12px; color: #999; font-weight: normal;">per kg</div>
                                </div>
                                
                                <!-- Quantity Controls -->
                                <div style="text-align: center;">
                                    <form method="post" id="update-form-<?php echo htmlspecialchars($key); ?>" style="margin: 0;">
                                        <input type="hidden" name="action" value="update">
                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $item_type; ?>">
                                        
                                        <div style="display: flex; align-items: center; justify-content: center; border: 1px solid #ddd; border-radius: 4px; width: fit-content; margin: 0 auto;">
                                            <button type="button" onclick="updateQuantity('<?php echo htmlspecialchars($key); ?>', 'decrease')" style="background: none; border: none; width: 30px; height: 30px; cursor: pointer; font-size: 16px; color: #555;">−</button>
                                            
                                            <input type="number" name="quantity" id="quantity-<?php echo htmlspecialchars($key); ?>" value="<?php echo $item['quantity']; ?>" min="1" max="99" style="width: 40px; border: none; text-align: center; font-size: 14px; -moz-appearance: textfield;" onchange="document.getElementById('update-form-<?php echo htmlspecialchars($key); ?>').submit()">
                                            
                                            <button type="button" onclick="updateQuantity('<?php echo htmlspecialchars($key); ?>', 'increase')" style="background: none; border: none; width: 30px; height: 30px; cursor: pointer; font-size: 16px; color: #555;">+</button>
                                        </div>
                                    </form>
                                </div>
                                
                                <!-- Subtotal and Remove -->
                                <div style="text-align: right; position: relative;">
                                    <div style="font-weight: 600; color: #333; margin-bottom: 10px;">
                                        <?php echo format_price($subtotal); ?>
                                    </div>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="remove">
                                        <input type="hidden" name="item_id" value="<?php echo $item_id; ?>">
                                        <input type="hidden" name="item_type" value="<?php echo $item_type; ?>">
                                        <button type="submit" style="background: none; border: none; color: #ff5252; font-size: 13px; cursor: pointer; display: flex; align-items: center; gap: 5px; padding: 0;">
                                            <i class="fas fa-trash-alt"></i> Remove
                                        </button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div style="flex: 1; min-width: 30%;">
                    <div style="background-color: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); padding: 25px; position: sticky; top: 20px;">
                        <h2 style="margin: 0 0 20px; color: #333; font-size: 1.5rem;">Order Summary</h2>
                        
                        <div style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 15px;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: #666;">Items (<?php echo $cart_count; ?>):</span>
                                <span style="font-weight: 500;"><?php echo format_price($total); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                                <span style="color: #666;">Shipping:</span>
                                <span style="font-weight: 500;">Free</span>
                            </div>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 25px; padding-bottom: 15px; border-bottom: 1px solid #eee;">
                            <span style="font-weight: bold; font-size: 18px;">Total:</span>
                            <span style="font-weight: bold; font-size: 18px; color: #4CAF50;"><?php echo format_price($total); ?></span>
                        </div>
                        
                        <div style="margin-bottom: 20px;">
                            <a href="<?php echo SITE_URL; ?>/pages/checkout.php" style="display: block; background-color: #4CAF50; color: white; padding: 12px; text-align: center; text-decoration: none; border-radius: 5px; font-weight: bold; margin-bottom: 10px;">
                                Proceed to Checkout
                            </a>
                            
                            <a href="<?php echo SITE_URL; ?>/index.php" style="display: block; color: #4CAF50; padding: 12px; text-align: center; text-decoration: none; font-weight: 500; font-size: 14px;">
                                <i class="fas fa-arrow-left" style="margin-right: 5px;"></i> Continue Shopping
                            </a>
                        </div>
                        
                        <!-- Clear Cart Form -->
                        <form method="post" id="clear-cart-form">
                            <input type="hidden" name="action" value="clear">
                            <button type="submit" style="background: none; border: none; color: #ff5252; font-size: 14px; width: 100%; padding: 10px; cursor: pointer; text-align: center; margin-top: 10px;">
                                Clear Cart
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cart Scripts -->
<script>
function updateQuantity(itemKey, action) {
    const quantityInput = document.getElementById('quantity-' + itemKey);
    let currentValue = parseInt(quantityInput.value);
    
    if (action === 'decrease' && currentValue > 1) {
        quantityInput.value = currentValue - 1;
    } else if (action === 'increase' && currentValue < 99) {
        quantityInput.value = currentValue + 1;
    }
    
    // Submit the form to update the cart
    document.getElementById('update-form-' + itemKey).submit();
}

// Confirm before clearing cart
document.getElementById('clear-cart-form').addEventListener('submit', function(e) {
    if (!confirm('Are you sure you want to clear your cart?')) {
        e.preventDefault();
    }
});
</script>

<?php include('../includes/footer.php'); ?> 