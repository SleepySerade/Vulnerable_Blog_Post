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
error_log("Register.php - Session ID: " . session_id());
error_log("Register.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Register.php - Session variables: " . print_r($_SESSION, true));

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    // Use absolute path for redirection
    header('Location: /');
    exit();
}

// Initialize variables
$username = '';
$email = '';
$errors = [];
$success = false;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:ital,wght@0,400;0,700;1,400;1,700&display=swap" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <style>
        /* .auth-container {
            max-width: 400px;
            width: 100%;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            position: relative;
        } */

        .btn-container {
            display: flex;
            justify-content: space-between;
            position: absolute;
            top: 1rem;
            left: 0;
            right: 0;
            padding: 0 1rem;
        }

        .back-btn {
            font-family: "Courier Prime", monospace;
            font-weight: 300;
            top: 1rem;
            font-size: 1.2rem;
            text-decoration: none;
            color: var(--text-color);
            transition: color 0.3s ease, border-bottom 0.3s ease;
            border-bottom: 2px solid transparent; /* Initially no underline */
        }

        /* Hover effect */
        .back-btn:hover {
            border-bottom: 2px solid var(--text-color); /* Underline effect on hover */
        }

        /* Login Button Styles */
        .login-btn {
            font-family: "Courier Prime", monospace;
            font-weight: 300;
            font-size: 1.2rem;
            text-decoration: none;
            color: var(--text-color);
            transition: color 0.3s ease, border-bottom 0.3s ease;
            border-bottom: 2px solid transparent; /* Initially no underline */
        }

        /* Hover effect */
        .login-btn:hover {
            border-bottom: 2px solid var(--text-color); /* Underline effect on hover */
        }

    </style>
</head>
<body>

    <div class="container my-5 auth-container">
        <!-- Button Container for Back and Login -->
        <div class="btn-container">
            <!-- Back Button -->
            <a href="javascript:history.back()" class="back-btn">← Back</a>

            <!-- Login Button -->
            <a href="/public/login" class="login-btn">Log in</a>
        </div>
        
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Create Account</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div id="successAlert" class="alert alert-success d-none">
                            Registration successful! Redirecting to login page...
                        </div>
                        
                        <div id="loadingAlert" class="alert alert-info d-none loading-indicator">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div>Processing registration, please wait...</div>
                            </div>
                        </div>

                        <form id="registerForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo htmlspecialchars($username); ?>" required>
                                <div class="form-text">Username must be 3–20 characters. Allowed: letters, numbers, spaces, dots, and underscores. Must not start or end with a space, dot, or underscore, and cannot contain consecutive dots or underscores</div>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email address</label>
                                <input type="email" class="form-control" id="email" name="email"
                                       value="<?php echo htmlspecialchars($email); ?>" required>
                                       <div class="form-text">Please use a valid email format with no spaces</div>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                                <div class="form-text">Password must be at least 8 characters, including one lowercase letter, one uppercase letter, one number, and one special character</div>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                           
                            
                            <!-- reCAPTCHA Widget -->
                             <div class="mb-3">
                             <div class="g-recaptcha" data-sitekey="6Lc6_vgqAAAAANk9Cwla3pSSE7SAaLGXra8fd0Ci"></div>
                            <div class="form-text">Please verify you're not a robot</div>
                            </div>

                             <!-- Submit button -->
                            <div class="d-grid">
                            <button type="submit" class="btn btn-primary" id="registerBtn" disabled>Register</button>
                             </div>                          
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Already have an account? <a href="/public/login">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>

    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/public/assets/js/script.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const registerForm = document.getElementById('registerForm');
            const errorContainer = document.querySelector('.alert-danger');
            const successAlert = document.getElementById('successAlert');

            const registerBtn = document.getElementById("registerBtn");
            const usernameInput = document.getElementById("username");
            const emailInput = document.getElementById("email");
            const passwordInput = document.getElementById("password");
            const confirmPasswordInput = document.getElementById("confirm_password");
            
            registerForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Clear previous errors
                if (errorContainer) {
                    errorContainer.classList.add('d-none');
                }
                
                // Get form data
                const formData = new FormData(registerForm);
                const data = {
                    username: formData.get('username'),
                    email: formData.get('email'),
                    password: formData.get('password'),
                    confirm_password: formData.get('confirm_password')
                };
                
                // Client-side validation
                const errors = [];
                
                if (!data.username || data.username.length < 3) {
                    errors.push('Username must be at least 3 characters long');
                }
                
                if (!data.email || !isValidEmail(data.email)) {
                    errors.push('Please enter a valid email address');
                }
                
                if (!data.password || data.password.length < 8) {
                    errors.push('Password must be at least 8 characters long');
                }
                
                if (data.password !== data.confirm_password) {
                    errors.push('Passwords do not match');
                }
                
                // Get the reCAPTCHA response
                const recaptchaResponse = grecaptcha.getResponse();
                if (recaptchaResponse.length === 0) {
                    errors.push('Please complete the reCAPTCHA.');
                }
                // Add reCAPTCHA response to data object
                if (errors.length === 0) {
                    data.recaptcha_response = recaptchaResponse;
                }
                // If validation errors, display them
                if (errors.length > 0) {
                    displayErrors(errors);
                    return;
                }
                
                // Show loading indicator
                const loadingAlert = document.getElementById('loadingAlert');
                if (loadingAlert) {
                    loadingAlert.classList.remove('d-none');
                }
                
                // Submit form via AJAX
                console.log('Submitting registration to:', '/backend/api/register');
                console.log('Data:', data);
                
                fetch('/backend/api/register.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify(data),
                    credentials: 'same-origin' // Include cookies in the request
                })
                .then(response => {
                    console.log('Response status:', response.status);
                    console.log('Response headers:', response.headers);
                    
                    // Check if the response is JSON
                    const contentType = response.headers.get('content-type');
                    if (!contentType || !contentType.includes('application/json')) {
                        throw new Error(`Expected JSON response but got ${contentType}`);
                    }
                    
                    // Log the response text for debugging
                    return response.text().then(text => {
                        console.log('Response text:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('JSON parse error:', e);
                            throw new Error('Invalid JSON response: ' + text);
                        }
                    });
                })
                .then(result => {
                    console.log('Registration result:', result);
                    
                    // Hide loading indicator
                    const loadingAlert = document.getElementById('loadingAlert');
                    if (loadingAlert) {
                        loadingAlert.classList.add('d-none');
                    }
                    
                    if (result.success) {
                        // Show success message
                        successAlert.classList.remove('d-none');
                        registerForm.reset();
                        // Redirect to login page after a delay
                        setTimeout(() => {
                            // Use absolute path for redirection
                            console.log('Redirecting to login page...');
                            
                            // Try different redirection methods
                            try {
                                // Method 1: window.location.href
                                window.location.href = '/public/login';
                                
                                // Method 2: If the above doesn't work, try after a short delay
                                setTimeout(() => {
                                    console.log('Trying alternative redirection method...');
                                    window.location.replace('/public/login');
                                }, 500);
                                
                                // Method 3: As a last resort, create and click a link
                                setTimeout(() => {
                                    console.log('Trying final redirection method...');
                                    const link = document.createElement('a');
                                    link.href = '/public/login';
                                    link.textContent = 'Click here if not redirected automatically';
                                    link.style.display = 'block';
                                    link.style.marginTop = '20px';
                                    link.style.textAlign = 'center';
                                    
                                    // Add the link to the page
                                    if (successAlert) {
                                        successAlert.appendChild(link);
                                    } else {
                                        document.body.appendChild(link);
                                    }
                                    
                                    // Try to click it programmatically
                                    link.click();
                                }, 1000);
                            } catch (e) {
                                console.error('Redirection error:', e);
                                alert('Redirection failed. Please click the link to continue.');
                            }
                        }, 2000);
                    } else {
                        // Display error message
                        displayErrors([result.message]);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Hide loading indicator if there is one
                    const loadingIndicator = document.querySelector('.loading-indicator');
                    if (loadingIndicator) {
                        loadingIndicator.classList.add('d-none');
                    }
                    
                    // Show more detailed error message
                    let errorMessage = 'An error occurred: ' + error.message;
                    console.error('Detailed error:', errorMessage);
                    
                    displayErrors([errorMessage]);
                    
                    // Log the error to the console with stack trace
                    console.error('Error stack:', error.stack);
                });
            });
            
            // Helper function to validate email
            function isValidEmail(email) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return emailRegex.test(email);
            }
            
            // Helper function to display errors
            function displayErrors(errors) {
                // Create error container if it doesn't exist
                let errorList;
                
                if (!errorContainer) {
                    const newErrorContainer = document.createElement('div');
                    newErrorContainer.className = 'alert alert-danger';
                    errorList = document.createElement('ul');
                    errorList.className = 'mb-0';
                    newErrorContainer.appendChild(errorList);
                    registerForm.parentNode.insertBefore(newErrorContainer, registerForm);
                } else {
                    errorContainer.classList.remove('d-none');
                    errorList = errorContainer.querySelector('ul');
                    errorList.innerHTML = '';
                }
                
                // Add each error to the list
                errors.forEach(error => {
                    const li = document.createElement('li');
                    li.textContent = error;
                    errorList.appendChild(li);
                });
            }

            // Function to check if all fields are filled
            function checkFormValidity() {
                if (
                    usernameInput.value.trim() !== "" &&
                    emailInput.value.trim() !== "" &&
                    passwordInput.value.trim() !== "" &&
                    confirmPasswordInput.value.trim() !== ""
                ) {
                    registerBtn.disabled = false; // Enable button
                } else {
                    registerBtn.disabled = true; // Disable button
                }
            }

            // Add event listeners to all input fields
            usernameInput.addEventListener("input", checkFormValidity);
            emailInput.addEventListener("input", checkFormValidity);
            passwordInput.addEventListener("input", checkFormValidity);
            confirmPasswordInput.addEventListener("input", checkFormValidity);
        });
    </script>
</body>
</html>