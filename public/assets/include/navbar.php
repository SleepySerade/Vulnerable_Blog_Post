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

<nav id="navbar" class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="/"><i class="bi bi-journal-richtext"></i> BlogVerse</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link" href="/"><i class="bi bi-house"></i> Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/about"><i class="bi bi-info-circle"></i> About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/posts"><i class="bi bi-newspaper"></i> Blog Posts</a>
                </li>
                
                <?php if ($isLoggedIn): ?>
                <li class="nav-item">
                    <a class="nav-link" href="/public/assets/pages/create-post"><i class="bi bi-pencil-square"></i> Create Post</a>
                </li>
                <?php endif; ?>
                
                <?php if ($isAdmin): ?>
                <!-- Admin Navigation -->
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-shield-lock"></i> Admin Panel
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="/admin/dashboard">
                            <i class="bi bi-speedometer2"></i> Dashboard
                        </a></li>
                        <li><a class="dropdown-item" href="/admin/manage_user">
                            <i class="bi bi-people"></i> Manage Users
                        </a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="/admin/manage_posts">
                            <i class="bi bi-file-earmark-post"></i> Manage Posts
                        </a></li>
                        <li><a class="dropdown-item" href="/admin/manage_comments">
                            <i class="bi bi-chat-left-text"></i> Manage Comments
                        </a></li>
                        <li><a class="dropdown-item" href="/admin/manage_categories">
                            <i class="bi bi-tag"></i> Manage Categories
                        </a></li>
                    </ul>
                </li>
                <?php endif; ?>
                    <!-- Search Bar -->
                <li class="nav-item">
                    <form class="d-flex" action="/public/assets/pages/search" method="GET" id="search-form" onsubmit="return validateSearch()">
                        <input class="form-control me-2 search-input"
                               type="search"
                               name="query"
                               placeholder="Search..."
                               id="search-input"
                               minlength="2"
                               maxlength="50"
                               pattern="[A-Za-z0-9\s\-_.,]+"
                               title="Search term must contain only letters, numbers, spaces, and basic punctuation"
                               required>
                        <button class="btn btn-outline-light search-button" type="submit" id="search-button">🔍</button>
                    </form>
                </li>
                <li class="nav-item">
                    <button id="dark-mode-toggle" class="btn btn-outline-light">
                        🌙 Dark Mode
                    </button>
                </li>
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
                            <li><a class="dropdown-item" href="/public/user/profile">
                                <i class="bi bi-person"></i> My Profile
                            </a></li>
                            <li><a class="dropdown-item" href="/public/assets/pages/my-posts">
                                <i class="bi bi-file-earmark-text"></i> My Posts
                            </a></li>
                            <li><a class="dropdown-item" href="/public/assets/pages/create-post">
                                <i class="bi bi-pencil-square"></i> Create New Post
                            </a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="/public/logout">
                                <i class="bi bi-box-arrow-right"></i> Logout
                            </a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <!-- Not logged in -->
                    <li class="nav-item">
                        <a class="nav-link" href="/public/login"><i class="bi bi-box-arrow-in-right"></i> Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="/public/register"><i class="bi bi-person-plus"></i> Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<script>
    // Search form validation function
    function validateSearch() {
        const searchInput = document.getElementById('search-input');
        const searchQuery = searchInput.value.trim();
        
        // Check if search query is empty
        if (searchQuery === '') {
            alert('Please enter a search term');
            return false;
        }
        
        // Check minimum length
        if (searchQuery.length < 2) {
            alert('Search term must be at least 2 characters long');
            return false;
        }
        
        // Check maximum length
        if (searchQuery.length > 50) {
            alert('Search term must be no more than 50 characters long');
            return false;
        }
        
        // Check for valid characters using regex
        const validPattern = /^[A-Za-z0-9\s\-_.,]+$/;
        if (!validPattern.test(searchQuery)) {
            alert('Search term must contain only letters, numbers, spaces, and basic punctuation');
            return false;
        }
        
        // Sanitize the input (basic client-side sanitization)
        searchInput.value = searchQuery
            .replace(/</g, '')
            .replace(/>/g, '')
            .replace(/&/g, '')
            .replace(/"/g, '')
            .replace(/'/g, '')
            .replace(/\\/g, '')
            .replace(/\//g, '');
        
        return true;
    }
</script>
