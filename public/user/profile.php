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
error_log("Profile.php - Session ID: " . session_id());
error_log("Profile.php - Session status: " . (session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive'));
error_log("Profile.php - Session variables: " . print_r($_SESSION, true));

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
error_log("Profile.php - User is logged in: " . ($isLoggedIn ? 'yes' : 'no'));

// Redirect to login if not logged in
if (!$isLoggedIn) {
    header('Location: /public/login');
    exit();
}

// Get user ID from session
$user_id = $_SESSION['user_id'];

// Initialize user data
$user = null;
$post_count = 0;
$recent_posts = [];

// Include database connection
require_once $_SERVER['DOCUMENT_ROOT'] . '/backend/connect_db.php';

// Fetch user data
try {
    $stmt = $conn->prepare("
        SELECT u.user_id, u.username, u.email, u.profile_picture, u.bio, u.created_at,
               COUNT(p.post_id) as post_count
        FROM users u
        LEFT JOIN blog_posts p ON u.user_id = p.author_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $post_count = $user['post_count'];
        
        // Fetch recent posts
        $stmt = $conn->prepare("
            SELECT post_id, title, created_at, status, views_count
            FROM blog_posts
            WHERE author_id = ?
            ORDER BY created_at DESC
            LIMIT 5
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) { 
            $recent_posts[] = $row;
        }
    } else {
        // User not found (should not happen since we're using session user_id)
        header('Location: /public/logout');
        exit();
    }
} catch (Exception $e) {
    error_log("Error fetching user data: " . $e->getMessage());
    $error_message = "An error occurred while fetching user data.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php else: ?>
            <div class="row">
                <!-- Profile Information -->
                <div class="col-md-4 mb-4">
                    <div class="card">
                        <div class="card-body text-center">
                            <?php if ($user['profile_picture']): ?>
                                <img src="<?php echo htmlspecialchars($user['profile_picture']); ?>" 
                                     alt="<?php echo htmlspecialchars($user['username']); ?>" 
                                     class="rounded-circle img-fluid mb-3" 
                                     style="max-width: 150px;">
                            <?php else: ?>
                                <div class="bg-light rounded-circle d-inline-flex justify-content-center align-items-center mb-3" 
                                     style="width: 150px; height: 150px;">
                                    <i class="bi bi-person-fill" style="font-size: 5rem;"></i>
                                </div>
                            <?php endif; ?>
                            
                            <h3 class="card-title"><?php echo htmlspecialchars($user['username']); ?></h3>
                            <p class="text-center">
                                Member since <?php echo date('F Y', strtotime($user['created_at'])); ?>
                            </p>
                            
                            <?php if ($user['bio']): ?>
                                <div class="mb-3">
                                    <p><?php echo nl2br(htmlspecialchars($user['bio'])); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <a href="/public/user/edit-profile" class="btn btn-primary">
                                <i class="bi bi-pencil-square"></i> Edit Profile
                            </a>
                        </div>
                        <div class="card-footer">
                            <div class="d-flex justify-content-around">
                                <div class="text-center">
                                    <h5><?php echo $post_count; ?></h5>
                                    <small class="text-center">Posts</small>
                                </div>
                                <div class="text-center">
                                    <h5>0</h5>
                                    <small class="text-center">Comments</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
            <!-- Recent Activity -->
<div class="col-md-8">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="mb-0">Recent Posts</h4>
            <a href="/public/assets/pages/my-posts" class="btn btn-sm btn-outline-primary">
                View All
            </a>
        </div>
        <div class="card-body">
            <?php if (empty($recent_posts)): ?>
                <p class="text-center text-muted">You haven't created any posts yet.</p>
                <div class="text-center">
                    <a href="/public/assets/pages/create-post" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create Your First Post
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-striped table-bordered align-middle">
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Date</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_posts as $post): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($post['title']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo $post['status'] === 'published' ? 'success' : 
                                                 ($post['status'] === 'draft' ? 'warning' : 'secondary'); 
                                        ?>">
                                            <?php echo ucfirst($post['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($post['status'] === 'published'): ?>
                                            <?php echo $post['views_count']; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <!-- Green 'View' button with eye icon -->
                                        <a href="/public/post?id=<?php echo $post['post_id']; ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="bi bi-eye"></i> View
                                        </a>
                                        <!-- Edit button -->
                                        <a href="/public/assets/pages/edit-post?id=<?php echo $post['post_id']; ?>" 
                                           class="btn btn-sm btn-outline-primary">
                                            <i class="bi bi-pencil"></i> Edit
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="/public/assets/pages/create-post" class="btn btn-primary">
                        <i class="bi bi-plus-circle"></i> Create New Post
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>


            </div>
        <?php endif; ?>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
