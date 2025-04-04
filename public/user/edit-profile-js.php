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
error_log("EditProfileJS.php - Session ID: " . session_id());
error_log("EditProfileJS.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("EditProfileJS.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("EditProfileJS.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];
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
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar'; ?>

    <div class="container my-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Edit Profile</h3>
                    </div>
                    <div class="card-body">
                        <!-- Loading Indicator -->
                        <div id="loadingIndicator" class="text-center my-4">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <p class="mt-2">Loading profile data...</p>
                        </div>
                        
                        <!-- Success Alert -->
                        <div id="successAlert" class="alert alert-success d-none">
                            <i class="bi bi-check-circle-fill"></i> <span id="successMessage">Profile updated successfully.</span>
                        </div>
                        
                        <!-- Error Alert -->
                        <div id="errorAlert" class="alert alert-danger d-none">
                            <i class="bi bi-exclamation-triangle-fill"></i> <span id="errorMessage">An error occurred.</span>
                        </div>
                        
                        <!-- Profile Form -->
                        <form id="profileForm" class="d-none">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" disabled>
                                <div class="form-text">Username cannot be changed.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="profile_picture" class="form-label">Profile Picture URL</label>
                                <input type="url" class="form-control" id="profile_picture" name="profile_picture">
                                <div class="form-text">Enter a URL for your profile picture. Leave empty to use default.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="bio" class="form-label">Bio</label>
                                <textarea class="form-control" id="bio" name="bio" rows="4"></textarea>
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
            const loadingIndicator = document.getElementById('loadingIndicator');
            const successAlert = document.getElementById('successAlert');
            const errorAlert = document.getElementById('errorAlert');
            const successMessage = document.getElementById('successMessage');
            const errorMessage = document.getElementById('errorMessage');
            const profileForm = document.getElementById('profileForm');
            
            const usernameInput = document.getElementById('username');
            const emailInput = document.getElementById('email');
            const profilePictureInput = document.getElementById('profile_picture');
            const bioInput = document.getElementById('bio');
            const currentPasswordInput = document.getElementById('current_password');
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            // Fetch user profile data
            fetch('/backend/api/profile.php')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    // Hide loading indicator
                    loadingIndicator.classList.add('d-none');
                    
                    if (result.success) {
                        // Show form
                        profileForm.classList.remove('d-none');
                        
                        // Populate form with user data
                        const user = result.data;
                        usernameInput.value = user.username;
                        emailInput.value = user.email;
                        
                        if (user.profile_picture) {
                            profilePictureInput.value = user.profile_picture;
                        }
                        
                        if (user.bio) {
                            bioInput.value = user.bio;
                        }
                        
                        // Trigger profile picture preview
                        profilePictureInput.dispatchEvent(new Event('input'));
                    } else {
                        // Show error
                        errorMessage.textContent = result.message || 'Failed to load profile data.';
                        errorAlert.classList.remove('d-none');
                    }
                })
                .catch(error => {
                    // Hide loading indicator
                    loadingIndicator.classList.add('d-none');
                    
                    // Show error
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred while loading profile data.';
                    errorAlert.classList.remove('d-none');
                });
            
            // Handle form submission
            profileForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Validate form
                if (!validateForm()) {
                    return;
                }
                
                // Hide alerts
                successAlert.classList.add('d-none');
                errorAlert.classList.add('d-none');
                
                // Show loading state
                const submitButton = profileForm.querySelector('button[type="submit"]');
                const originalButtonText = submitButton.textContent;
                submitButton.disabled = true;
                submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Saving...';
                
                // Prepare data
                const data = {
                    email: emailInput.value,
                    profile_picture: profilePictureInput.value,
                    bio: bioInput.value
                };
                
                // Add password data if provided
                if (currentPasswordInput.value && newPasswordInput.value) {
                    data.current_password = currentPasswordInput.value;
                    data.new_password = newPasswordInput.value;
                }
                
                // Submit data
                fetch('/backend/api/profile.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin'
                })
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return response.json();
                })
                .then(result => {
                    // Reset loading state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    if (result.success) {
                        // Show success message
                        successMessage.textContent = result.message || 'Profile updated successfully.';
                        successAlert.classList.remove('d-none');
                        
                        // Clear password fields
                        currentPasswordInput.value = '';
                        newPasswordInput.value = '';
                        confirmPasswordInput.value = '';
                        
                        // Scroll to top to show success message
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    } else {
                        // Show error message
                        errorMessage.textContent = result.message || 'Failed to update profile.';
                        errorAlert.classList.remove('d-none');
                        
                        // Scroll to top to show error message
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                    }
                })
                .catch(error => {
                    // Reset loading state
                    submitButton.disabled = false;
                    submitButton.textContent = originalButtonText;
                    
                    // Show error message
                    console.error('Error:', error);
                    errorMessage.textContent = 'An error occurred while updating profile.';
                    errorAlert.classList.remove('d-none');
                    
                    // Scroll to top to show error message
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
            });
            
            // Function to validate form
            function validateForm() {
                let isValid = true;
                
                // Validate email
                if (!emailInput.value) {
                    emailInput.setCustomValidity('Email is required');
                    isValid = false;
                } else if (!isValidEmail(emailInput.value)) {
                    emailInput.setCustomValidity('Invalid email format');
                    isValid = false;
                } else {
                    emailInput.setCustomValidity('');
                }
                
                // Validate password fields
                if (currentPasswordInput.value || newPasswordInput.value || confirmPasswordInput.value) {
                    if (!currentPasswordInput.value) {
                        currentPasswordInput.setCustomValidity('Current password is required to change password');
                        isValid = false;
                    } else {
                        currentPasswordInput.setCustomValidity('');
                    }
                    
                    if (!newPasswordInput.value) {
                        newPasswordInput.setCustomValidity('New password is required');
                        isValid = false;
                    } else if (newPasswordInput.value.length < 8) {
                        newPasswordInput.setCustomValidity('Password must be at least 8 characters long');
                        isValid = false;
                    } else {
                        newPasswordInput.setCustomValidity('');
                    }
                    
                    if (!confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity('Please confirm your new password');
                        isValid = false;
                    } else if (newPasswordInput.value !== confirmPasswordInput.value) {
                        confirmPasswordInput.setCustomValidity('Passwords do not match');
                        isValid = false;
                    } else {
                        confirmPasswordInput.setCustomValidity('');
                    }
                }
                
                // Report validity
                if (!isValid) {
                    profileForm.reportValidity();
                }
                
                return isValid;
            }
            
            // Function to validate email
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Preview profile picture
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
            }
        });
    </script>
</body>
</html>