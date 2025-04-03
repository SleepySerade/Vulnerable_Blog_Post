<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Configure session parameters
ini_set('session.use_only_cookies', 1);
ini_set('session.use_strict_mode', 1);

// Set session cookie parameters
session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => false,
    'httponly' => true,
    'samesite' => 'Lax'
]);

// Start the session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Debug session information
error_log("EditProfile.php - Session ID: " . session_id());
error_log("EditProfile.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("EditProfile.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("EditProfile.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize user data
$user = null;
$success_message = '';
$error_message = '';

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT user_id, username, email, profile_picture, bio
        FROM users
        WHERE user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
    } else {
        // User not found (should not happen since we're using session user_id)
        header('Location: /public/logout');
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $error_message = "An error occurred while fetching user data.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $email = isset($_POST['email']) ? trim($_POST['email']) : '';
    $profile_picture = isset($_POST['profile_picture']) ? trim($_POST['profile_picture']) : '';
    $bio = isset($_POST['bio']) ? trim($_POST['bio']) : '';
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    // Validate email
    if (empty($email)) {
        $error_message = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Invalid email format.";
    } else {
        try {
            // Check if email is already used by another user
            $stmt = $conn->prepare("
                SELECT user_id FROM users
                WHERE email = ? AND user_id != ?
            ");
            $stmt->bind_param("si", $email, $user_id);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                $error_message = "Email is already in use by another account.";
            }
        } catch (Exception $e) {
            error_log("Error checking email: " . $e->getMessage());
            $error_message = "An error occurred while checking email.";
        }
    }
    
    // If no errors so far, proceed with update
    if (empty($error_message)) {
        try {
            // Start transaction
            $conn->begin_transaction();
            
            // Update user profile
            $stmt = $conn->prepare("
                UPDATE users
                SET email = ?, profile_picture = ?, bio = ?
                WHERE user_id = ?
            ");
            $stmt->bind_param("sssi", $email, $profile_picture, $bio, $user_id);
            $stmt->execute();
            
            // If password change is requested
            if (!empty($current_password) && !empty($new_password)) {
                // Validate new password
                if (strlen($new_password) < 8) {
                    throw new Exception("New password must be at least 8 characters long.");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("New passwords do not match.");
                }
                
                // Get current password hash and salt
                $stmt = $conn->prepare("
                    SELECT u.password_hash, s.salt_value
                    FROM users u
                    JOIN user_salts s ON u.user_id = s.user_id
                    WHERE u.user_id = ?
                ");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 1) {
                    $row = $result->fetch_assoc();
                    $stored_hash = $row['password_hash'];
                    $salt = $row['salt_value'];
                    
                    // Verify current password
                    if (!password_verify($current_password . $salt, $stored_hash)) {
                        throw new Exception("Current password is incorrect.");
                    }
                    
                    // Generate new password hash
                    $new_hash = password_hash($new_password . $salt, PASSWORD_DEFAULT);
                    
                    // Update password
                    $stmt = $conn->prepare("
                        UPDATE users
                        SET password_hash = ?
                        WHERE user_id = ?
                    ");
                    $stmt->bind_param("si", $new_hash, $user_id);
                    $stmt->execute();
                } else {
                    throw new Exception("Error retrieving user credentials.");
                }
            }
            
            // Commit transaction
            $conn->commit();
            
            // Update user data for display
            $user['email'] = $email;
            $user['profile_picture'] = $profile_picture;
            $user['bio'] = $bio;
            
            $success_message = "Profile updated successfully.";
        } catch (Exception $e) {
            // Rollback transaction
            $conn->rollback();
            
            error_log("Error updating profile: " . $e->getMessage());
            $error_message = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile - BlogVerse</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Edit Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($success_message)): ?>
                            <div class="alert alert-success">
                                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($error_message)): ?>
                            <div class="alert alert-danger">
                                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture URL</label>
                                <input type="url" class="form-control" id="profile_picture" name="profile_picture" value="<?php echo htmlspecialchars($user['profile_picture'] ?? ''); ?>">
                                <div class="form-text">Enter a URL for your profile picture. Leave empty to use default.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"><?php echo htmlspecialchars($user['bio'] ?? ''); ?></textarea>
                                <div class="form-text">Tell us about yourself.</div>
                            </div>
                            
                            <hr class="my-4">
                            
                            <h4>Change Password</h4>
                            <p class="text-muted">Leave these fields empty if you don't want to change your password.</p>
                            
                            <div class="mb-3">
                                <label for="current_password" class="form-label">Current Password</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">New Password</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                                <div class="form-text">Password must be at least 8 characters long.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="/public/user/profile" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">Save Changes</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Preview profile picture
            const profilePictureInput = document.getElementById('profile_picture');
            
            if (profilePictureInput) {
                profilePictureInput.addEventListener('input', function() {
                    const url = this.value.trim();
                    
                    // If URL is not empty, show preview
                    if (url) {
                        // Check if preview already exists
                        let preview = document.getElementById('profile-picture-preview');
                        
                        if (!preview) {
                            // Create preview container
                            preview = document.createElement('div');
                            preview.id = 'profile-picture-preview';
                            preview.className = 'mt-2 text-center';
                            
                            // Create image
                            const img = document.createElement('img');
                            img.className = 'img-thumbnail';
                            img.style.maxHeight = '200px';
                            img.alt = 'Profile Picture Preview';
                            
                            // Add image to preview
                            preview.appendChild(img);
                            
                            // Add preview after input
                            this.parentNode.appendChild(preview);
                        }
                        
                        // Update image source
                        const img = preview.querySelector('img');
                        img.src = url;
                        
                        // Handle image load error
                        img.onerror = function() {
                            preview.innerHTML = '<div class="alert alert-warning">Invalid image URL</div>';
                        };
                    } else {
                        // Remove preview if URL is empty
                        const preview = document.getElementById('profile-picture-preview');
                        if (preview) {
                            preview.remove();
                        }
                    }
                });
                
                // Trigger input event to show preview for existing URL
                profilePictureInput.dispatchEvent(new Event('input'));
            }
            
            // Password validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const currentPasswordInput = document.getElementById('current_password');
            
            if (newPasswordInput && confirmPasswordInput && currentPasswordInput) {
                // Function to check password fields
                function checkPasswordFields() {
                    const newPassword = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    const currentPassword = currentPasswordInput.value;
                    
                    // If any password field is filled, all must be filled
                    if (newPassword || confirmPassword || currentPassword) {
                        if (!currentPassword) {
                            currentPasswordInput.setCustomValidity('Current password is required to change password');
                        } else {
                            currentPasswordInput.setCustomValidity('');
                        }
                        
                        if (!newPassword) {
                            newPasswordInput.setCustomValidity('New password is required');
                        } else if (newPassword.length < 8) {
                            newPasswordInput.setCustomValidity('Password must be at least 8 characters long');
                        } else {
                            newPasswordInput.setCustomValidity('');
                        }
                        
                        if (!confirmPassword) {
                            confirmPasswordInput.setCustomValidity('Please confirm your new password');
                        } else if (newPassword !== confirmPassword) {
                            confirmPasswordInput.setCustomValidity('Passwords do not match');
                        } else {
                            confirmPasswordInput.setCustomValidity('');
                        }
                    } else {
                        // If all fields are empty, that's fine (no password change)
                        currentPasswordInput.setCustomValidity('');
                        newPasswordInput.setCustomValidity('');
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                // Check on input
                newPasswordInput.addEventListener('input', checkPasswordFields);
                confirmPasswordInput.addEventListener('input', checkPasswordFields);
                currentPasswordInput.addEventListener('input', checkPasswordFields);
            }
        });
    </script>
</body>
</html>