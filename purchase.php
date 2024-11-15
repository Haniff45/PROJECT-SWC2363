<?php
session_start();
include 'db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Collect form data
    $name = htmlspecialchars($_POST['name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone = htmlspecialchars($_POST['phone']);
    $address = htmlspecialchars($_POST['address']);
    $city = htmlspecialchars($_POST['city']);
    $postalCode = htmlspecialchars($_POST['postalCode']);
    $userId = $_SESSION['user_id'] ?? null;
    $orderNumber = 'SO-' . strtoupper(substr(md5(rand()), 0, 9));
    $totalAmount = 1; // Example amount

    try {
        // Begin transaction
        $pdo->beginTransaction();

        // Insert order
        $stmtOrder = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, created_at) VALUES (?, ?, ?, NOW())");
        $stmtOrder->execute([$userId, $orderNumber, $totalAmount]);
        $orderId = $pdo->lastInsertId();

        // Insert shipping information
        $stmtShipping = $pdo->prepare("INSERT INTO shipping_information (order_id, full_name, email, phone_number, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmtShipping->execute([$orderId, $name, $email, $phone, $address, $city, $postalCode]);

        // Commit transaction
        $pdo->commit();
        
        // Store shipping info and order_id in session
        $_SESSION['shippingInfo'] = [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'address' => $address,
            'city' => $city,
            'postalCode' => $postalCode
        ];
        $_SESSION['order_id'] = $orderId;

        // Success message
        $message = 'Order placed successfully! Redirecting to shipping confirmation...';
        $messageType = 'success';
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $message = 'Failed to place order. Please try again later.';
        $messageType = 'error';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Purchase - SOOPRA</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="purchase.css">
</head>
<body>
    <div class="purchase-container">
        <div class="purchase-box">
            <h2>Complete Your Purchase</h2>
            <form id="purchaseForm" class="purchase-form" method="POST">
                <div class="form-section">
                    <h3>Shipping Information</h3>
                    <input type="text" name="name" placeholder="Full Name" required pattern="[A-Za-z\s]+" title="Please enter a valid name">
                    <input type="email" name="email" placeholder="Email" required>
                    <input type="tel" name="phone" placeholder="Phone Number" required pattern="[0-9\+]+" title="Please enter a valid phone number">
                    <input type="text" name="address" placeholder="Address" required>
                    <input type="text" name="city" placeholder="City" required>
                    <input type="text" name="postalCode" placeholder="Postal Code" required pattern="[0-9]+" title="Please enter a valid postal code">
                </div>
                <div class="form-section">
                    <h3>Payment Details</h3>
                    <input type="text" name="card_number" placeholder="Card Number" required pattern="[0-9]{16}" title="Please enter a valid 16-digit card number">
                    <div class="card-details">
                        <input type="text" name="expiry_date" placeholder="MM/YY" required pattern="(0[1-9]|1[0-2])\/([0-9]{2})" title="Please enter a valid date (MM/YY)">
                        <input type="text" name="cvv" placeholder="CVV" required pattern="[0-9]{3,4}" title="Please enter a valid CVV">
                    </div>
                </div>
                <button type="submit" class="place-order-btn">Place Order</button>
            </form>
        </div>
    </div>

    <?php if ($message): ?>
        <script>
            function showNotification(message, type) {
                const notification = document.createElement('div');
                notification.className = `notification ${type}`;
                notification.innerText = message;
                document.body.appendChild(notification);

                setTimeout(() => {
                    notification.classList.add('fade-out');
                    setTimeout(() => {
                        notification.remove();
                        if (type === 'success') {
                            window.location.href = 'shipping.php';
                        }
                    }, 300);
                }, 2000);
            }

            // Trigger the notification
            showNotification("<?= $message ?>", "<?= $messageType ?>");
        </script>
    <?php endif; ?>
</body>
</html>
