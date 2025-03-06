<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Add Bootstrap Icons CSS
echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">';

// Check if user is logged in and get role
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = false;

if ($isLoggedIn) {
    // Check if user is admin
    require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';
    
    try {
        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $isAdmin = $stmt->get_result()->num_rows > 0;
    } catch (Exception $e) {
        // Log error but don't break the page
        error_log("Error checking admin status: " . $e->getMessage());
        $isAdmin = false;
    }
}
?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <div class="container">
        <a class="navbar-brand" href="/public/index.php"><i class="bi bi-journal-richtext"></i> Blog Website</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/public/index.php"><i class="bi bi-house"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/about.php"><i class="bi bi-info-circle"></i> About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/posts.php"><i class="bi bi-newspaper"></i> Blog Posts</a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/create-post.php"><i class="bi bi-pencil-square"></i> Create Post</a>
                </li>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Admin Navigation -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/admin/dashboard.php">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="/admin/manage_user.php">
                            <i class="bi bi-people"></i> Manage Users
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/manage_posts.php">
                            <i class="bi bi-file-earmark-post"></i> Manage Posts
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/public/log_viewer.php">
                            <i class="bi bi-journal-text"></i> View Logs
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav">
                <?php if ($isLoggedIn): ?>
                    <!-- Logged in user -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i>
                            <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><span class="dropdown-item-text text-muted">Signed in as <strong><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/public/user/profile.php">
                                <i class="bi bi-person"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/public/assets/pages/my-posts.php">
                                <i class="bi bi-file-earmark-text"></i> My Posts
                            </a></li>
                            <li><a class="dropdown-item" href="/public/assets/pages/create-post.php">
                                <i class="bi bi-pencil-square"></i> Create New Post
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/public/logout.php">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Not logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="/public/login.php"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/register.php"><i class="bi bi-person-plus"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>