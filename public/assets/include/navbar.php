<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in and get role
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = false;

if ($isLoggedIn) {
    // Check if user is admin
    require_once '../../backend/api/connect_db.php';
    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $isAdmin = $stmt->get_result()->num_rows > 0;
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/frontend/index.php">Blog Website</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/frontend/products.php">Blog Posts</a>
                </li>
                
                <?php if ($isAdmin): ?>
                <!-- Admin Navigation -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        Admin Panel
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/frontend/admin/dashboard.php">Dashboard</a></li>
                        <li><a class="dropdown-item" href="/frontend/admin/manage_users.php">Manage Users</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/frontend/admin/manage_posts.php">Manage Posts</a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user -->
                    <li class="nav-item">
                        <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></span>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            Account
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="/frontend/user/profile.php">My Profile</a></li>
                            <li><a class="dropdown-item" href="/frontend/user/my_posts.php">My Posts</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/backend/api/logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Not logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="/frontend/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/frontend/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>