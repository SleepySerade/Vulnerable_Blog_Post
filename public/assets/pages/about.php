<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include 'inlcude/navbar.php'; ?>

    <div class="container my-5">
        <header class="text-center mb-5">
            <h1>Meet Our Team</h1>
            <p class="lead">The talented individuals behind our blog platform</p>
        </header>

        <div class="row g-4">
            <!-- Backend Developer -->
            <div class="col-md-6 col-lg-4">
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
            <div class="col-md-6 col-lg-4">
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
            <div class="col-md-6 col-lg-4">
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
            <div class="col-md-6 col-lg-4">
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

            <!-- Cloud Engineer -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <i class="bi bi-cloud text-primary" style="font-size: 2rem;"></i>
                            <h3 class="mt-3">Cloud Engineer</h3>
                        </div>
                        <ul class="list-unstyled">
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Cloud deployment</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Storage optimization</li>
                            <li><i class="bi bi-check-circle-fill text-success me-2"></i>Infrastructure management</li>
                        </ul>
                        <p class="card-text">Manages our cloud infrastructure to ensure reliable and scalable performance.</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="text-center mt-5">
            <h2>Our Mission</h2>
            <p class="lead">To create a secure, user-friendly, and feature-rich blogging platform that empowers writers and engages readers.</p>
        </div>
    </div>

    <?php include 'inlcude/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>