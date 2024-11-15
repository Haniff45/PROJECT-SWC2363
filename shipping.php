<?php
session_start();
include 'db.php';

// Generate a random order confirmation number if needed
$orderNumber = isset($_SESSION['order_number']) ? $_SESSION['order_number'] : 'SO-' . strtoupper(substr(md5(rand()), 0, 9));

// Retrieve order ID from session
$orderId = $_SESSION['order_id'] ?? null;
$shippingInfo = null;

if ($orderId) {
    try {
        // Retrieve shipping information from database using PDO
        $stmt = $pdo->prepare("SELECT * FROM shipping_information WHERE order_id = ?");
        $stmt->execute([$orderId]);
        $shippingInfo = $stmt->fetch();
    } catch (PDOException $e) {
        // Handle any database errors
        error_log("Database error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipping Confirmation - SOOPRA</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="shipping-confirmation-container">
        <div class="confirmation-box">
            <h2>Order Confirmation</h2>
            <div class="confirmation-details">
                <div class="order-status">
                    <img src="checkmark.png" alt="Success" class="success-icon">
                    <h3>Order Successfully Placed!</h3>
                    <p>Your order confirmation number: <span id="orderNumber"><?= htmlspecialchars($orderNumber) ?></span></p>
                </div>
                <div class="shipping-details">
                    <h3>Shipping Details</h3>
                    <?php if ($shippingInfo): ?>
                        <div id="shippingInfo">
                            <p><strong>Name:</strong> <?= htmlspecialchars($shippingInfo['full_name']) ?></p>
                            <p><strong>Address:</strong> <?= htmlspecialchars($shippingInfo['address']) ?></p>
                            <p><strong>City:</strong> <?= htmlspecialchars($shippingInfo['city']) ?></p>
                            <p><strong>Postal Code:</strong> <?= htmlspecialchars($shippingInfo['postal_code']) ?></p>
                            <p><strong>Email:</strong> <?= htmlspecialchars($shippingInfo['email']) ?></p>
                            <p><strong>Phone:</strong> <?= htmlspecialchars($shippingInfo['phone_number']) ?></p>
                        </div>
                    <?php else: ?>
                        <p>No shipping information available.</p>
                    <?php endif; ?>
                </div>
                <div class="estimated-delivery">
                    <h3>Estimated Delivery</h3>
                    <p>Your order will be delivered within 3-5 business days.</p>
                </div>
                <button onclick="window.location.href='index.php'" class="return-home-btn">Return to Home</button>
            </div>
        </div>
    </div>
</body>
</html>
