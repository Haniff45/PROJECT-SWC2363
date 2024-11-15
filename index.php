<?php
session_start();
include 'db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SOOPRA</title>
    <link rel="stylesheet" href="styles.css">
    
    <!-- JavaScript function for notifications -->
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
                }, 300);
            }, 2000);
        }
    </script>
</head>
<body>
    <div class="min-h-screen">
        <!-- Navigation -->
        <nav class="border-bottom">
            <div class="nav-container">
                <div class="nav-content">
                    <div class="logo">
                        <a href="index.php" class="logo-link">
                            <img src="logo.png" alt="SOOPRA Logo" class="logo-image">
                        </a>
                    </div>
                    <div class="nav-actions">
                        <a href="login.php" class="user-icon-link">
                            <div class="user-icon-container">
                                <img src="user.png" alt="User Img" class="user-icon">
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Hero Section -->
        <div class="hero-section">
            <div class="hero-image">
                <img src="banner.png" alt="Hero sneaker">
            </div>
            <div class="hero-overlay"></div>
            <div class="hero-content">
                <div class="hero-text">
                    <!-- Dynamic hero text or promotions can be added here with PHP -->
                </div>
            </div>
        </div>

        <!-- Featured Products -->
        <div class="featured-section">
            <h2>FEATURED</h2>
            <div class="product-grid">
                <?php
                // Example array of products. Replace this with database data if available.
                $products = [
                    ["image" => "shoe1.png", "alt_image" => "shoe1.1.png", "name" => "Tiffany & Co. Nike Air Force 1 Low", "size" => "11 UK", "price" => "RM2200", "link" => "purchase.php"],
                    ["image" => "shoe2.png", "alt_image" => "shoe2.1.png", "name" => "Nike Air Max 90 'Snakeskin'", "size" => "10 UK", "price" => "RM2550", "link" => "purchase.php"],
                    ["image" => "shoe3.png", "alt_image" => "shoe3.1.png", "name" => "adidas x Pharrell Williams Adizero Adios Pro Evo 1 'Earth'", "size" => "11 UK", "price" => "RM2400", "link" => "purchase.php"]
                ];

                foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-content">
                            <img src="<?= $product['image'] ?>" alt="<?= $product['name'] ?>" class="product-image"
                                 onmouseover="this.src='<?= $product['alt_image'] ?>'"
                                 onmouseout="this.src='<?= $product['image'] ?>'">
                            <div class="product-info">
                                <h3><?= $product['name'] ?></h3>
                                <p class="colors">Size <?= $product['size'] ?></p>
                                <p class="price"><?= $product['price'] ?></p>
                                <button class="shop-now-btn" onclick="window.location.href='<?= $product['link'] ?>'">Buy</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Passion Section -->
        <div class="passion-section">
            <div class="passion-container">
                <div class="passion-content">
                    <div class="passion-text">
                        <h2>ABOUT US</h2>
                        <p>Dedicated to crafting the perfect footwear for every people's journey.<br>Contact Number : 01867534609</p>
                    </div>
                    <div class="passion-image">
                        <img src="lifestyle.png" alt="Lifestyle">
                    </div>
                </div>
            </div>
        </div>

        <!-- Newsletter -->
        <div class="newsletter-section">
            <div class="newsletter-content">
                <h2>Subscribe for Exclusive Offers</h2>
                <form method="POST" class="newsletter-form">
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" class="subscribe-btn">Subscribe</button>
                </form>

                <?php
                // Handle subscription form submission
                if ($_SERVER['REQUEST_METHOD'] == 'POST' && !empty($_POST['email'])) {
                    include 'db.php';
                    
                    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
                    $message = '';
                    $messageType = '';

                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        try {
                            // Check if email already exists
                            $stmt = $pdo->prepare("SELECT id FROM newsletter_subscribers WHERE email = ?");
                            $stmt->execute([$email]);
                            
                            if ($stmt->rowCount() == 0) {
                                // Insert the email into the newsletter_subscribers table
                                $stmt = $pdo->prepare("INSERT INTO newsletter_subscribers (email, subscribed_at) VALUES (?, NOW())");
                                
                                if ($stmt->execute([$email])) {
                                    $message = 'Thank you for subscribing! We will inform you of new offers a week early!';
                                    $messageType = 'success';
                                } else {
                                    $message = 'Subscription failed. Please try again.';
                                    $messageType = 'error';
                                }
                            } else {
                                $message = 'You are already subscribed to our newsletter.';
                                $messageType = 'info';
                            }
                        } catch (PDOException $e) {
                            $message = 'Subscription failed. Please try again.';
                            $messageType = 'error';
                            error_log("Newsletter subscription error: " . $e->getMessage());
                        }
                    } else {
                        $message = 'Please enter a valid email.';
                        $messageType = 'error';
                    }

                    // JavaScript to trigger notification with PHP message
                    echo "<script>showNotification('$message', '$messageType');</script>";
                }
                ?>
            </div>
        </div>
    </div>
</body>
</html>
