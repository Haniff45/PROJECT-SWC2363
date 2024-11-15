<?php
session_start();
include 'db.php';

// Redirect to login page if not logged in or if user is not an admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header("Location: login.php");
    exit;
}

$message = '';
$orders = [];

// Handle order actions: add, update, delete, and search
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_order':
                try {
                    // Insert order information into the orders table
                    $stmt = $pdo->prepare("INSERT INTO orders (user_id, order_number, total_amount, created_at) VALUES (?, ?, ?, NOW())");
                    $stmt->execute([$_POST['user_id'], $_POST['order_number'], $_POST['total_amount']]);
                    $orderId = $pdo->lastInsertId();

                    // Insert shipping information
                    $stmt = $pdo->prepare("INSERT INTO shipping_information (order_id, full_name, email, phone_number, address, city, postal_code) VALUES (?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([ 
                        $orderId, $_POST['full_name'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['city'], $_POST['postal_code']
                    ]);

                    $message = 'Order added successfully.';
                } catch (PDOException $e) {
                    $message = 'Error adding order: ' . htmlspecialchars($e->getMessage());
                }
                break;

            case 'update_order':
                try {
                    // Update order information
                    $stmt = $pdo->prepare("UPDATE orders SET order_number = ?, total_amount = ? WHERE order_id = ?");
                    $stmt->execute([$_POST['order_number'], $_POST['total_amount'], $_POST['order_id']]);

                    // Update shipping information
                    $stmt = $pdo->prepare("UPDATE shipping_information SET full_name = ?, email = ?, phone_number = ?, address = ?, city = ?, postal_code = ? WHERE order_id = ?");
                    $stmt->execute([$_POST['full_name'], $_POST['email'], $_POST['phone_number'], $_POST['address'], $_POST['city'], $_POST['postal_code'], $_POST['order_id']]);

                    $message = 'Order updated successfully.';
                } catch (PDOException $e) {
                    $message = 'Error updating order: ' . htmlspecialchars($e->getMessage());
                }
                break;

            case 'delete_order':
                try {
                    // Delete shipping information first due to foreign key constraint
                    $stmt = $pdo->prepare("DELETE FROM shipping_information WHERE order_id = ?");
                    $stmt->execute([$_POST['order_id']]);

                    // Delete order information
                    $stmt = $pdo->prepare("DELETE FROM orders WHERE order_id = ?");
                    $stmt->execute([$_POST['order_id']]);

                    $message = 'Order deleted successfully.';
                } catch (PDOException $e) {
                    $message = 'Error deleting order: ' . htmlspecialchars($e->getMessage());
                }
                break;

            case 'search':
                // Search for orders by user ID
                $stmt = $pdo->prepare("SELECT o.*, s.* FROM orders o LEFT JOIN shipping_information s ON o.order_id = s.order_id WHERE o.user_id = ?");
                $stmt->execute([$_POST['user_id']]);
                $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                break;
        }
    }
}

// Fetch all orders if no search is performed
if (empty($orders)) {
    $stmt = $pdo->prepare("SELECT o.*, s.* FROM orders o LEFT JOIN shipping_information s ON o.order_id = s.order_id ORDER BY o.created_at DESC");
    $stmt->execute();
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch order data for the update form if needed
$orderToEdit = null;
if (isset($_GET['order_id']) && is_numeric($_GET['order_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ?");
    $stmt->execute([$_GET['order_id']]);
    $orderToEdit = $stmt->fetch(PDO::FETCH_ASSOC);

    $stmt = $pdo->prepare("SELECT * FROM shipping_information WHERE order_id = ?");
    $stmt->execute([$_GET['order_id']]);
    $shippingToEdit = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Soopra</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<div class="admin-dashboard">
    <h1>Welcome to the Admin Dashboard</h1>

    <!-- Display message if exists -->
    <?php if ($message): ?>
        <div class="notification"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <!-- Search Form -->
    <form method="POST" class="search-form">
        <input type="text" name="user_id" placeholder="Search by User ID" required>
        <button type="submit" name="action" value="search">Search</button>
    </form>

    <!-- Add Order Form -->
    <div class="order-actions">
        <form method="POST">
            <h2>Add Order</h2>
            <input type="text" name="user_id" placeholder="User ID" required>
            <input type="text" name="order_number" placeholder="Order Number" required>
            <input type="number" step="0.01" name="total_amount" placeholder="Total Amount" required>
            <input type="text" name="full_name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="tel" name="phone_number" placeholder="Phone Number">
            <input type="text" name="address" placeholder="Address" required>
            <input type="text" name="city" placeholder="City" required>
            <input type="text" name="postal_code" placeholder="Postal Code" required>
            <button type="submit" name="action" value="add_order">Add Order</button>
        </form>
    </div>

    <!-- Update Order Form (if editing) -->
    <?php if ($orderToEdit): ?>
        <div class="order-actions">
            <h2>Update Order</h2>
            <form method="POST">
                <input type="hidden" name="order_id" value="<?= htmlspecialchars($orderToEdit['order_id']) ?>">
                <input type="text" name="order_number" value="<?= htmlspecialchars($orderToEdit['order_number']) ?>" required>
                <input type="number" step="0.01" name="total_amount" value="<?= htmlspecialchars($orderToEdit['total_amount']) ?>" required>
                <input type="text" name="full_name" value="<?= htmlspecialchars($shippingToEdit['full_name']) ?>" required>
                <input type="email" name="email" value="<?= htmlspecialchars($shippingToEdit['email']) ?>" required>
                <input type="tel" name="phone_number" value="<?= htmlspecialchars($shippingToEdit['phone_number']) ?>" required>
                <input type="text" name="address" value="<?= htmlspecialchars($shippingToEdit['address']) ?>" required>
                <input type="text" name="city" value="<?= htmlspecialchars($shippingToEdit['city']) ?>" required>
                <input type="text" name="postal_code" value="<?= htmlspecialchars($shippingToEdit['postal_code']) ?>" required>
                <button type="submit" name="action" value="update_order">Update Order</button>
            </form>
        </div>
    <?php endif; ?>

    <!-- Order List Display -->
    <div class="order-list">
        <h2>Order List</h2>
        <?php if (!empty($orders)): ?>
            <table>
                <thead>
                    <tr>
                        <th>Order ID</th>
                        <th>User ID</th>
                        <th>Order Number</th>
                        <th>Total Amount</th>
                        <th>Full Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>City</th>
                        <th>Postal Code</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><?= htmlspecialchars($order['order_id']) ?></td>
                            <td><?= htmlspecialchars($order['user_id']) ?></td>
                            <td><?= htmlspecialchars($order['order_number']) ?></td>
                            <td><?= htmlspecialchars($order['total_amount']) ?></td>
                            <td><?= htmlspecialchars($order['full_name']) ?></td>
                            <td><?= htmlspecialchars($order['email']) ?></td>
                            <td><?= htmlspecialchars($order['phone_number']) ?></td>
                            <td><?= htmlspecialchars($order['address']) ?></td>
                            <td><?= htmlspecialchars($order['city']) ?></td>
                            <td><?= htmlspecialchars($order['postal_code']) ?></td>
                            <td>
                                <!-- Link to edit the order -->
                                <a href="?order_id=<?= $order['order_id'] ?>">Edit</a>
                                <!-- Delete action -->
                                <form method="POST" style="display: inline-block;">
                                    <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['order_id']) ?>">
                                    <button type="submit" name="action" value="delete_order">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p>No orders found.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
