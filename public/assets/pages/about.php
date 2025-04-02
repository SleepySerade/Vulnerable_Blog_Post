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
error_log("About.php - Session ID: " . session_id());
error_log("About.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("About.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("About.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <!-- Add AOS CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.css">

</head>
<body class="about-page">
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-1" data-aos="fade-down" data-aos-duration="1000">
            <h1>Meet Our Team</h1>
            <p class="lead">The talented individuals behind our blog platform</p>
            <hr>
        </header>

        <div class="row g-4">
            <!-- Backend Developer -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-database text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">Backend Developer</h3>
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>MySQL database setup</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>API endpoint development</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Database schema management</li>
                        </ul>
                        <p class="card-text">Responsible for building and maintaining the robust backend infrastructure that powers our blog platform.</p>
                    </div>
                </div>
            </div>

            <!-- Frontend Developer (UI/UX) -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="100">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-layout-text-window-reverse text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">UI/UX Developer</h3>
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Responsive design implementation</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>User interface optimization</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Accessibility improvements</li>
                        </ul>
                        <p class="card-text">Creates intuitive and beautiful user interfaces that make our blog a joy to use.</p>
                    </div>
                </div>
            </div>

            <!-- Frontend Developer (JavaScript) -->
            <div class="col-md-6 col-lg-4" data-aos="fade-up" data-aos-delay="200">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-code-square text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">JavaScript Developer</h3>
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Dynamic feature implementation</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Form validation</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>AJAX integration</li>
                        </ul>
                        <p class="card-text">Brings the blog to life with interactive features and smooth user experiences.</p>
                    </div>
                </div>
            </div>

            <!-- Security Developer -->
            <div class="col-md-6 col-lg-4" data-aos="fade-left" data-aos-delay="0">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-shield-lock text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">Security Expert</h3>
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Authentication system</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Security vulnerability prevention</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Access control management</li>
                        </ul>
                        <p class="card-text">Ensures the safety and security of our blog platform and user data.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5 mb-5" data-aos="zoom-in" data-aos-duration="1000">
            <h2>Our Mission</h2>
            <p class="lead">To create a secure, user-friendly, and feature-rich blogging platform that empowers writers and engages readers.</p>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Add AOS JavaScript -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>

    <script>
        // Initialize AOS
        AOS.init({
            duration: 800, // Duration of animation (in milliseconds)
            once: true,    // Whether animation should happen only once
            offset: 100,   // Offset (in pixels) from the original trigger point
            easing: 'ease-in-out', // Easing function
            delay: 0,      // Default delay
            anchorPlacement: 'top-bottom', // Defines which position of the element regarding to window should trigger the animation
        });
    </script>
</body>
</html>

<script>
    // Navbar scroll effect
    document.addEventListener('DOMContentLoaded', function() {
        const navbar = document.getElementById('navbar');
        
        // Initial check for page load
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        }

        // Add scroll event listener
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    });
</script>