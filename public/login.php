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
error_log("Session ID: " . session_id());
error_log("Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Session variables: " . print_r($_SESSION, true));

// If already logged in, redirect to home
if (isset($_SESSION['user_id'])) {
    // Use absolute path for redirection
    header('Location: /');
    exit();
}

// Initialize variables
$username = '';
$errors = [];

// Check for registration success message
if (isset($_SESSION['registration_success'])) {
    $success_message = "Registration successful! Please login.";
    unset($_SESSION['registration_success']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">

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
            color: rgb(0, 0, 0);
            transition: color 0.3s ease, border-bottom 0.3s ease;
            border-bottom: 2px solid transparent; /* Initially no underline */
        }

        /* Hover effect */
        .back-btn:hover {
            border-bottom: 2px solid rgb(0, 0, 0); /* Underline effect on hover */
        }

        /* Login Button Styles */
        .register-btn {
            font-family: "Courier Prime", monospace;
            font-weight: 300;
            font-size: 1.2rem;
            text-decoration: none;
            color:rgb(0, 0, 0);
            transition: color 0.3s ease, border-bottom 0.3s ease;
            border-bottom: 2px solid transparent; /* Initially no underline */
        }

        /* Hover effect */
        .register-btn:hover {
            border-bottom: 2px solid rgb(0, 0, 0); /* Underline effect on hover */
}

    </style>
</head>
<body>
    <div class="container my-5">
    <div class="auth-container">
        <!-- Button Container for Back and Login -->
        <div class="btn-container">
            <!-- Back Button -->
            <a href="javascript:history.back()" class="back-btn">← Back</a>

            <!-- Login Button -->
            <a href="/public/register" class="register-btn">Register</a>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Login</h3>
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

                        <?php if (isset($success_message)): ?>
                            <div class="alert alert-success">
                                <?php echo htmlspecialchars($success_message); ?>
                            </div>
                        <?php endif; ?>
                        
                        <div id="successAlert" class="alert alert-success d-none">
                            Login successful! Redirecting...
                        </div>
                        
                        <div id="loadingAlert" class="alert alert-info d-none">
                            <div class="d-flex align-items-center">
                                <div class="spinner-border spinner-border-sm me-2" role="status">
                                    <span class="visually-hidden">Loading...</span>
                                </div>
                                <div>Logging in, please wait...</div>
                            </div>
                        </div>

                        <form id="loginForm">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username"
                                       value="<?php echo htmlspecialchars($username); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary" id="loginBtn">Login</button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p class="mb-0">Don't have an account? <a href="/public/register">Register here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const loginForm = document.getElementById('loginForm');
            const errorContainer = document.querySelector('.alert-danger');
            
            loginForm.addEventListener('submit', function(e) {
                // Prevent default form submission
                e.preventDefault();
                
                // Clear previous errors
                if (errorContainer) {
                    errorContainer.classList.add('d-none');
                }
                
                // Get form data
                const formData = new FormData(loginForm);
                const data = {
                    username: formData.get('username'),
                    password: formData.get('password')
                };
                
                // Client-side validation
                const errors = [];
                
                if (!data.username) {
                    errors.push('Username is required');
                }
                
                if (!data.password) {
                    errors.push('Password is required');
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
                console.log('Submitting login to:', '/backend/api/login.php');
                console.log('Data:', data);
                
                fetch('/backend/api/login.php', {
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
                    
                    return response.json();
                })
                .then(result => {
                    console.log('Login result:', result);
                    console.log('Login success:', result.success);
                    console.log('User data:', result.user);
                    
                    // Hide loading indicator
                    if (loadingAlert) {
                        loadingAlert.classList.add('d-none');
                    }
                    
                    if (result.success) {
                        // Show success message
                        const successAlert = document.getElementById('successAlert');
                        if (successAlert) {
                            successAlert.classList.remove('d-none');
                            successAlert.textContent = 'Login successful! Redirecting...';
                        }
                        
                        // Redirect after a short delay to show the success message
                        setTimeout(() => {
                            // Redirect based on user role
                            const redirectUrl = (result.user && result.user.is_admin) ?
                                '/admin/dashboard' : '/';
                            
                            console.log('Redirecting to:', redirectUrl);
                            
                            // Try different redirection methods
                            try {
                                // Method 1: window.location.href
                                window.location.href = redirectUrl;
                                
                                // Method 2: If the above doesn't work, try after a short delay
                                setTimeout(() => {
                                    console.log('Trying alternative redirection method...');
                                    window.location.replace(redirectUrl);
                                }, 500);
                                
                                // Method 3: As a last resort, create and click a link
                                setTimeout(() => {
                                    console.log('Trying final redirection method...');
                                    const link = document.createElement('a');
                                    link.href = redirectUrl;
                                    link.textContent = 'Click here if not redirected automatically';
                                    link.style.display = 'block';
                                    link.style.marginTop = '20px';
                                    link.style.textAlign = 'center';
                                    
                                    // Add the link to the page
                                    const successAlert = document.getElementById('successAlert');
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
                        }, 2000); // Increased delay to 2 seconds for better visibility
                    } else {
                        // Display error message
                        console.error('Login failed:', result.message);
                        
                        // Show more detailed error message
                        let errorMessage = result.message;
                        if (result.errors && Array.isArray(result.errors)) {
                            errorMessage += ': ' + result.errors.join(', ');
                        }
                        
                        displayErrors([errorMessage]);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    
                    // Hide loading indicator
                    if (loadingAlert) {
                        loadingAlert.classList.add('d-none');
                    }
                    
                    // Show more detailed error message
                    let errorMessage = 'An error occurred: ' + error.message;
                    console.error('Detailed error:', errorMessage);
                    
                    displayErrors([errorMessage]);
                    
                    // Log the error to the console with stack trace
                    console.error('Error stack:', error.stack);
                });
            });
            
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
                    loginForm.parentNode.insertBefore(newErrorContainer, loginForm);
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
        });
    </script>
</body>
</html>
