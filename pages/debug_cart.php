<?php
require_once '../includes/config.php';

// Simple debug page to view cart contents
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart Debug</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        h1 { color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        th { background-color: #f2f2f2; }
        .btn { display: inline-block; padding: 8px 16px; background-color: #4CAF50; color: white; 
               text-decoration: none; border-radius: 4px; margin-top: 20px; }
        .empty { padding: 20px; background-color: #f9f9f9; border-radius: 4px; margin-top: 20px; }
        .debug { background-color: #f8f9fa; border: 1px solid #ddd; padding: 15px; margin-top: 20px; }
        pre { margin: 0; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>Cart Debug</h1>';

// Show login state
echo '<div class="debug">
    <h2>Session Info</h2>
    <p>Is logged in: ' . (is_logged_in() ? 'Yes' : 'No') . '</p>';

if (is_logged_in()) {
    echo '<p>User ID: ' . $_SESSION['user_id'] . '</p>';
    echo '<p>Username: ' . $_SESSION['username'] . '</p>';
    echo '<p>Role: ' . $_SESSION['role'] . '</p>';
}

echo '</div>';

// Show cart contents
echo '<h2>Cart Contents</h2>';

if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
    echo '<div class="empty">Cart is empty</div>';
} else {
    echo '<table>
        <tr>
            <th>Product ID</th>
            <th>Type</th>
            <th>Name</th>
            <th>Price</th>
            <th>Quantity</th>
            <th>Subtotal</th>
        </tr>';
    
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $subtotal = $item['price'] * $item['quantity'];
        $total += $subtotal;
        
        echo '<tr>
            <td>' . $item['product_id'] . '</td>
            <td>' . $item['product_type'] . '</td>
            <td>' . $item['name'] . '</td>
            <td>₹' . number_format($item['price'], 2) . '</td>
            <td>' . $item['quantity'] . '</td>
            <td>₹' . number_format($subtotal, 2) . '</td>
        </tr>';
    }
    
    echo '<tr>
        <td colspan="5" style="text-align: right;"><strong>Total:</strong></td>
        <td>₹' . number_format($total, 2) . '</td>
    </tr>
    </table>';
    
    echo '<div style="margin-top: 20px;">
        <a href="cart.php" class="btn">Go to Cart</a>
        <a href="#" onclick="clearCart(); return false;" class="btn" style="background-color: #f44336;">Clear Cart</a>
    </div>';
}

// Show raw SESSION for debugging
echo '<div class="debug">
    <h2>Raw SESSION data</h2>
    <pre>' . print_r($_SESSION, true) . '</pre>
</div>';

echo '<script>
function clearCart() {
    if (confirm("Are you sure you want to clear your cart?")) {
        fetch("clear_cart.php")
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Cart cleared!");
                    location.reload();
                } else {
                    alert("Error clearing cart: " + data.message);
                }
            })
            .catch(error => {
                alert("Error: " + error);
            });
    }
}
</script>
</body>
</html>';
?> 