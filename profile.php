<?php
// Include database connection file
include 'db.php';

// Assuming session is started and user is logged in
session_start();
$userId = $_SESSION['user_id']; // Assuming the user ID is stored in session after login

// Fetch the current user's data from the database to pre-fill the profile form
$stmt = $pdo->prepare("SELECT full_name, email, profile_picture FROM users WHERE user_id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data
    $fullName = htmlspecialchars($_POST['full_name']);
    $email = htmlspecialchars($_POST['email']);
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    $profilePic = null;

    // Check if profile picture is being uploaded
    if (isset($_FILES['profile_pic']) && $_FILES['profile_pic']['error'] == UPLOAD_ERR_OK) {
        // Save the new profile picture (for example, save in 'uploads' directory)
        $uploadDir = 'uploads/';
        $profilePic = $uploadDir . basename($_FILES['profile_pic']['name']);
        move_uploaded_file($_FILES['profile_pic']['tmp_name'], $profilePic);
    }

    // Password update logic
    if (!empty($newPassword)) {
        if ($newPassword !== $confirmPassword) {
            $error_message = "New passwords do not match!";
        } else {
            // Verify the current password with the database (assuming password is hashed)
            $stmt = $pdo->prepare("SELECT password FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $storedPassword = $stmt->fetchColumn();

            if (!password_verify($currentPassword, $storedPassword)) {
                $error_message = "Current password is incorrect!";
            } else {
                // Update password
                $newPasswordHashed = password_hash($newPassword, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE user_id = ?");
                $stmt->execute([$newPasswordHashed, $userId]);
                $success_message = "Password updated successfully!";
            }
        }
    }

    // Update user profile details in the database
    if (empty($error_message)) {
        $stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, profile_picture = ? WHERE user_id = ?");
        $stmt->execute([$fullName, $email, $profilePic, $userId]);

        $success_message = "Profile updated successfully!";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - SOOPRA</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="min-h-screen">
        <!-- Navigation (same as other pages) -->
        <nav class="border-bottom">
            <div class="nav-container">
                <div class="nav-content">
                    <div class="logo">
                        <a href="index.php" class="logo-link">
                            <img src="logo.png" alt="SOOPRA Logo" class="logo-image">
                        </a>
                    </div>
                    <div class="nav-actions">
                        <a href="profile.php" class="user-icon-link">
                            <div class="user-icon-container">
                                <img src="user.png" alt="User Img" class="user-icon">
                            </div>
                        </a>
                    </div>
                </div>
            </div>
        </nav>
        
        <div class="auth-container">
            <div class="auth-box">
                <h2>My Profile</h2>
                
                <!-- Display success or error messages -->
                <?php if (isset($success_message)): ?>
                    <div class="notification success"><?= $success_message ?></div>
                <?php elseif (isset($error_message)): ?>
                    <div class="notification error"><?= $error_message ?></div>
                <?php endif; ?>

                <!-- Profile Update Form -->
                <form id="profileForm" class="auth-form" method="POST" enctype="multipart/form-data">
                    <div class="profile-image">
                        <img src="<?= $user['profile_picture'] ?: 'user.png' ?>" alt="Profile Picture" id="profilePic">
                        <input type="file" name="profile_pic" id="profilePicInput" accept="image/*">
                    </div>
                    <input type="text" name="full_name" placeholder="Full Name" value="<?= htmlspecialchars($user['full_name']) ?>" required>
                    <input type="email" name="email" placeholder="Email" value="<?= htmlspecialchars($user['email']) ?>" required>
                    <input type="password" name="current_password" placeholder="Current Password">
                    <input type="password" name="new_password" placeholder="New Password">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password">
                    <button type="submit" class="auth-button">Save Changes</button>
                </form>
                <button onclick="logout()" class="auth-button" style="margin-top: 1rem; background-color: #ff4444;">Logout</button>
            </div>
        </div>
    </div>

    <script>
        function showNotification(message, type) {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.classList.add('fade-out');
                setTimeout(() => {
                    document.body.removeChild(notification);
                }, 300);
            }, 2000);
        }

        function logout() {
            showNotification('Logged out successfully!', 'success');
            setTimeout(() => {
                window.location.href = 'index.php';
            }, 1500);
        }
    </script>
</body>
</html>
