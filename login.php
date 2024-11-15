<?php
session_start();
include 'db.php';

$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $email = filter_var($_POST['login_email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['login_password'];

        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user'] = $user['email'];
                $_SESSION['is_admin'] = $user['is_admin'];
                session_regenerate_id(true);

                if ($user['is_admin'] == 1) {
                    // Admin login
                    $_SESSION['message'] = 'Welcome, Admin!';
                    $_SESSION['messageType'] = 'success';
                    $_SESSION['redirect_url'] = 'admin_dashboard.php';
                } else {
                    // Regular user login
                    $_SESSION['message'] = 'Welcome back!';
                    $_SESSION['messageType'] = 'success';
                    $_SESSION['redirect_url'] = 'profile.php';
                }
            } else {
                $message = 'Invalid login credentials.';
                $messageType = 'error';
            }
        } catch (PDOException $e) {
            $message = 'Login error occurred: ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif (isset($_POST['signup'])) {
        $fullName = htmlspecialchars($_POST['signup_name']);
        $email = filter_var($_POST['signup_email'], FILTER_SANITIZE_EMAIL);
        $password = $_POST['signup_password'];
        $confirmPassword = $_POST['signup_confirm_password'];

        if ($password === $confirmPassword) {
            try {
                $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $existingUser = $stmt->fetch();

                if ($existingUser) {
                    $message = 'Email already registered.';
                    $messageType = 'error';
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

                    $stmt = $pdo->prepare("INSERT INTO users (full_name, email, password, is_admin) VALUES (?, ?, ?, 0)");
                    if ($stmt->execute([$fullName, $email, $hashedPassword])) {
                        $_SESSION['message'] = 'Account created successfully! Please log in.';
                        $_SESSION['messageType'] = 'success';
                    } else {
                        $message = 'Error creating account.';
                        $messageType = 'error';
                    }
                }
            } catch (PDOException $e) {
                $message = 'Registration error occurred: ' . $e->getMessage();
                $messageType = 'error';
            }
        } else {
            $message = 'Passwords do not match.';
            $messageType = 'error';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login/Sign Up - SOOPRA</title>
    <link rel="stylesheet" href="styles.css">
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

        <!-- Login/Signup Section -->
        <div class="auth-container">
            <div class="auth-box">
                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="switchTab('login')">Login</button>
                    <button class="auth-tab" onclick="switchTab('signup')">Sign Up</button>
                </div>

                <!-- Login Form -->
                <form id="loginForm" class="auth-form" method="POST" action="login.php" style="display: flex;">
                    <input type="email" name="login_email" placeholder="Email" required>
                    <input type="password" name="login_password" placeholder="Password" required>
                    <button type="submit" name="login" class="auth-button">Login</button>
                </form>

                <!-- Signup Form -->
                <form id="signupForm" class="auth-form" method="POST" action="login.php" style="display: none;">
                    <input type="text" name="signup_name" placeholder="Full Name" required>
                    <input type="email" name="signup_email" placeholder="Email" required>
                    <input type="password" name="signup_password" placeholder="Password" required>
                    <input type="password" name="signup_confirm_password" placeholder="Confirm Password" required>
                    <button type="submit" name="signup" class="auth-button">Sign Up</button>
                </form>
            </div>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="notification <?= $_SESSION['messageType'] ?>">
                <?= $_SESSION['message'] ?>
            </div>
            <script>
                setTimeout(() => {
                    document.querySelector('.notification').classList.add('fade-out');
                    setTimeout(() => {
                        document.querySelector('.notification').remove();
                        <?php if (isset($_SESSION['redirect_url'])): ?>
                            window.location.href = "<?= $_SESSION['redirect_url'] ?>";
                        <?php endif; ?>
                    }, 300);
                }, 2000);
            </script>
            <?php
            unset($_SESSION['message'], $_SESSION['messageType'], $_SESSION['redirect_url']);
            ?>
        <?php endif; ?>
    </div>

    <script>
        function switchTab(tab) {
            const loginForm = document.getElementById('loginForm');
            const signupForm = document.getElementById('signupForm');
            const tabs = document.getElementsByClassName('auth-tab');

            if (tab === 'login') {
                loginForm.style.display = 'flex';
                signupForm.style.display = 'none';
                tabs[0].classList.add('active');
                tabs[1].classList.remove('active');
            } else {
                loginForm.style.display = 'none';
                signupForm.style.display = 'flex';
                tabs[0].classList.remove('active');
                tabs[1].classList.add('active');
            }
        }
    </script>
</body>
</html>
