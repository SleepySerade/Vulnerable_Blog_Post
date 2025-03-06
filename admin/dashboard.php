<?php
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

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
if (!$isLoggedIn) {
    // Redirect to login page if not logged in
    header('Location: /public/login.php');
    exit();
}

// Check if user is admin
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/utils/logger.php';

$logger = new Logger('admin');
$isAdmin = false;
$user_id = $_SESSION['user_id'];

try {
    $stmt = $conn->prepare("SELECT admin_id, role FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $admin = $result->fetch_assoc();
        $isAdmin = true;
        $adminRole = $admin['role'];
    } else {
        // Redirect to home page if not admin
        $logger->warning("Non-admin user (ID: $user_id) attempted to access admin dashboard");
        header('Location: /public/index.php');
        exit();
    }
} catch (Exception $e) {
    $logger->error("Error checking admin status: " . $e->getMessage());
    // Redirect to home page on error
    header('Location: /public/index.php');
    exit();
}

// Fetch dashboard statistics
$stats = [
    'total_users' => 0,
    'total_posts' => 0,
    'total_comments' => 0,
    'total_categories' => 0,
    'recent_users' => [],
    'recent_posts' => []
];

try {
    // Get total users
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users");
    $stmt->execute();
    $stats['total_users'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total posts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blog_posts");
    $stmt->execute();
    $stats['total_posts'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total comments
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM comments");
    $stmt->execute();
    $stats['total_comments'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get total categories
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM categories");
    $stmt->execute();
    $stats['total_categories'] = $stmt->get_result()->fetch_assoc()['count'];
    
    // Get recent users (last 5)
    $stmt = $conn->prepare("
        SELECT user_id, username, email, created_at 
        FROM users 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recent_users'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Get recent posts (last 5)
    $stmt = $conn->prepare("
        SELECT p.post_id, p.title, p.status, p.created_at, u.username as author
        FROM blog_posts p
        JOIN users u ON p.author_id = u.user_id
        ORDER BY p.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $stats['recent_posts'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
} catch (Exception $e) {
    $logger->error("Error fetching dashboard statistics: " . $e->getMessage());
    $error_message = "An error occurred while fetching dashboard statistics.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <style>
        .dashboard-card {
            transition: transform 0.3s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
        }
    </style>
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-speedometer2"></i> Admin Dashboard</h1>
            <span class="badge bg-primary">Role: <?php echo ucfirst($adminRole); ?></span>
        </div>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <!-- Statistics Cards -->
        <div class="row mb-5">
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card h-100 bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-people stat-icon"></i>
                        <h2 class="mt-2"><?php echo $stats['total_users']; ?></h2>
                        <p class="card-text">Total Users</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center">
                        <a href="/admin/manage_user.php" class="btn btn-sm btn-light">Manage Users</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card h-100 bg-success text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-file-earmark-post stat-icon"></i>
                        <h2 class="mt-2"><?php echo $stats['total_posts']; ?></h2>
                        <p class="card-text">Total Posts</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center">
                        <a href="/admin/manage_posts.php" class="btn btn-sm btn-light">Manage Posts</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card h-100 bg-info text-white">
                    <div class="card-body text-center">
                        <i class="bi bi-chat-left-text stat-icon"></i>
                        <h2 class="mt-2"><?php echo $stats['total_comments']; ?></h2>
                        <p class="card-text">Total Comments</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center">
                        <a href="#" class="btn btn-sm btn-light">View Comments</a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3 mb-4">
                <div class="card dashboard-card h-100 bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="bi bi-tag stat-icon"></i>
                        <h2 class="mt-2"><?php echo $stats['total_categories']; ?></h2>
                        <p class="card-text">Categories</p>
                    </div>
                    <div class="card-footer bg-transparent border-0 text-center">
                        <a href="/admin/manage_categories.php" class="btn btn-sm btn-dark">Manage Categories</a>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <!-- Recent Users -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-people"></i> Recent Users</h5>
                        <a href="/admin/manage_user.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['recent_users'])): ?>
                            <p class="text-center text-muted">No users found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Email</th>
                                            <th>Joined</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_users'] as $user): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($user['username']); ?></td>
                                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                                <td>
                                                    <a href="/admin/manage_user.php?action=edit&id=<?php echo $user['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Posts -->
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="bi bi-file-earmark-post"></i> Recent Posts</h5>
                        <a href="/admin/manage_posts.php" class="btn btn-sm btn-outline-primary">View All</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($stats['recent_posts'])): ?>
                            <p class="text-center text-muted">No posts found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Title</th>
                                            <th>Author</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats['recent_posts'] as $post): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($post['title']); ?></td>
                                                <td><?php echo htmlspecialchars($post['author']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : ($post['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($post['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                                <td>
                                                    <a href="/admin/manage_posts.php?action=edit&id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>