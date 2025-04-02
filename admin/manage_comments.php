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
    header('Location: /public/login');
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
        $logger->warning("Non-admin user (ID: $user_id) attempted to access comment management");
        header('Location: /public/index');
        exit();
    }
} catch (Exception $e) {
    $logger->error("Error checking admin status: " . $e->getMessage());
    // Redirect to home page on error
    header('Location: /public/index');
    exit();
}

// Initialize variables
$comments = [];
$success_message = '';
$error_message = '';
$edit_comment = null;

// Get filter parameters
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$post_filter = isset($_GET['post_id']) && is_numeric($_GET['post_id']) ? (int)$_GET['post_id'] : 0;
$user_filter = isset($_GET['user_id']) && is_numeric($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action from form
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'update_comment':
                // Update comment
                if (isset($_POST['comment_id']) && is_numeric($_POST['comment_id'])) {
                    $comment_id = (int)$_POST['comment_id'];
                    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
                    $is_approved = isset($_POST['is_approved']) ? (int)$_POST['is_approved'] : 0;
                    
                    // Validate content
                    if (empty($content)) {
                        throw new Exception("Comment content is required.");
                    }
                    
                    // Update comment
                    $stmt = $conn->prepare("
                        UPDATE comments
                        SET content = ?, is_approved = ?
                        WHERE comment_id = ?
                    ");
                    $stmt->bind_param("sii", $content, $is_approved, $comment_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                        $success_message = "Comment has been updated successfully.";
                        $logger->info("Admin (ID: $user_id) updated comment (ID: $comment_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
                
            case 'delete_comment':
                // Delete comment
                if (isset($_POST['comment_id']) && is_numeric($_POST['comment_id'])) {
                    $comment_id = (int)$_POST['comment_id'];
                    
                    // Delete comment (and all replies due to ON DELETE CASCADE)
                    $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
                    $stmt->bind_param("i", $comment_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Comment has been deleted successfully.";
                        $logger->info("Admin (ID: $user_id) deleted comment (ID: $comment_id)");
                    } else {
                        $error_message = "Comment could not be deleted.";
                    }
                }
                break;
                
            case 'approve_comment':
                // Approve comment
                if (isset($_POST['comment_id']) && is_numeric($_POST['comment_id'])) {
                    $comment_id = (int)$_POST['comment_id'];
                    
                    // Update comment approval status
                    $stmt = $conn->prepare("UPDATE comments SET is_approved = 1 WHERE comment_id = ?");
                    $stmt->bind_param("i", $comment_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Comment has been approved.";
                        $logger->info("Admin (ID: $user_id) approved comment (ID: $comment_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
                
            case 'unapprove_comment':
                // Unapprove comment
                if (isset($_POST['comment_id']) && is_numeric($_POST['comment_id'])) {
                    $comment_id = (int)$_POST['comment_id'];
                    
                    // Update comment approval status
                    $stmt = $conn->prepare("UPDATE comments SET is_approved = 0 WHERE comment_id = ?");
                    $stmt->bind_param("i", $comment_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Comment has been unapproved.";
                        $logger->info("Admin (ID: $user_id) unapproved comment (ID: $comment_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $logger->error("Error in comment management: " . $e->getMessage());
    }
}

// Handle edit comment request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_comment_id = (int)$_GET['id'];
    
    try {
        // Get comment details
        $stmt = $conn->prepare("
            SELECT c.*, u.username, p.title as post_title
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            JOIN blog_posts p ON c.post_id = p.post_id
            WHERE c.comment_id = ?
        ");
        $stmt->bind_param("i", $edit_comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $edit_comment = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $error_message = "Error fetching comment details: " . $e->getMessage();
        $logger->error("Error fetching comment details: " . $e->getMessage());
    }
}

// Build query based on filters
$query = "
    SELECT c.*, u.username, p.title as post_title
    FROM comments c
    JOIN users u ON c.user_id = u.user_id
    JOIN blog_posts p ON c.post_id = p.post_id
    WHERE 1=1
";

$params = [];
$types = '';

if ($status_filter === 'approved') {
    $query .= " AND c.is_approved = 1";
} elseif ($status_filter === 'unapproved') {
    $query .= " AND c.is_approved = 0";
}

if ($post_filter > 0) {
    $query .= " AND c.post_id = ?";
    $params[] = $post_filter;
    $types .= 'i';
}

if ($user_filter > 0) {
    $query .= " AND c.user_id = ?";
    $params[] = $user_filter;
    $types .= 'i';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $query .= " AND (c.content LIKE ? OR u.username LIKE ? OR p.title LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'sss';
}

$query .= " ORDER BY c.created_at DESC";

// Fetch comments with filters
try {
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $comments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching comments: " . $e->getMessage();
    $logger->error("Error fetching comments: " . $e->getMessage());
}

// Get all posts for filter dropdown
try {
    $stmt = $conn->prepare("SELECT post_id, title FROM blog_posts ORDER BY title");
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $posts = [];
    $logger->error("Error fetching posts for filter: " . $e->getMessage());
}

// Get all users for filter dropdown
try {
    $stmt = $conn->prepare("SELECT user_id, username FROM users ORDER BY username");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $users = [];
    $logger->error("Error fetching users for filter: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Comments - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-chat-left-text"></i> Manage Comments</h1>
            <a href="/admin/dashboard" class="btn btn-outline-primary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if (!empty($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($edit_comment): ?>
            <!-- Edit Comment Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit Comment</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="update_comment">
                        <input type="hidden" name="comment_id" value="<?php echo $edit_comment['comment_id']; ?>">
                        
                        <div class="mb-3">
                            <label class="form-label">Post</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_comment['post_title']); ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_comment['username']); ?>" disabled>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Comment Content</label>
                            <textarea class="form-control" id="content" name="content" rows="4" required><?php echo htmlspecialchars($edit_comment['content']); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label for="is_approved" class="form-label">Status</label>
                            <select class="form-select" id="is_approved" name="is_approved">
                                <option value="1" <?php echo $edit_comment['is_approved'] ? 'selected' : ''; ?>>Approved</option>
                                <option value="0" <?php echo !$edit_comment['is_approved'] ? 'selected' : ''; ?>>Unapproved</option>
                            </select>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/admin/manage_comments" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Cancel
                            </a>
                            
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteCommentModal">
                                    <i class="bi bi-trash"></i> Delete Comment
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Delete Comment Modal -->
            <div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this comment? This action cannot be undone.</p>
                            <p><strong>Comment:</strong> <?php echo htmlspecialchars(substr($edit_comment['content'], 0, 100)) . (strlen($edit_comment['content']) > 100 ? '...' : ''); ?></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post">
                                <input type="hidden" name="action" value="delete_comment">
                                <input type="hidden" name="comment_id" value="<?php echo $edit_comment['comment_id']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Filter Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Filter Comments</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-3">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Comments</option>
                            <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                            <option value="unapproved" <?php echo $status_filter === 'unapproved' ? 'selected' : ''; ?>>Unapproved</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="post_id" class="form-label">Post</label>
                        <select class="form-select" id="post_id" name="post_id">
                            <option value="0">All Posts</option>
                            <?php foreach ($posts as $post): ?>
                                <option value="<?php echo $post['post_id']; ?>" <?php echo $post_filter == $post['post_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(substr($post['title'], 0, 40)) . (strlen($post['title']) > 40 ? '...' : ''); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="user_id" class="form-label">User</label>
                        <select class="form-select" id="user_id" name="user_id">
                            <option value="0">All Users</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?php echo $user['user_id']; ?>" <?php echo $user_filter == $user['user_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($user['username']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search comments...">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                        <a href="/admin/manage_comments" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Comments Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Comments</h5>
                <span class="badge bg-secondary"><?php echo count($comments); ?> comments found</span>
            </div>
            <div class="card-body">
                <?php if (empty($comments)): ?>
                    <p class="text-center text-muted">No comments found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Author</th>
                                    <th>Comment</th>
                                    <th>Post</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($comments as $comment): ?>
                                    <tr>
                                        <td><?php echo $comment['comment_id']; ?></td>
                                        <td><?php echo htmlspecialchars($comment['username']); ?></td>
                                        <td><?php echo htmlspecialchars(substr($comment['content'], 0, 100)) . (strlen($comment['content']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <a href="/public/post?id=<?php echo $comment['post_id']; ?>" target="_blank">
                                                <?php echo htmlspecialchars(substr($comment['post_title'], 0, 30)) . (strlen($comment['post_title']) > 30 ? '...' : ''); ?>
                                            </a>
                                        </td>
                                        <td><?php echo date('M d, Y H:i', strtotime($comment['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $comment['is_approved'] ? 'success' : 'warning'; ?>">
                                                <?php echo $comment['is_approved'] ? 'Approved' : 'Unapproved'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/admin/manage_comments?action=edit&id=<?php echo $comment['comment_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                
                                                <?php if ($comment['is_approved']): ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="unapprove_comment">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-warning">
                                                            <i class="bi bi-x-circle"></i> Unapprove
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <form method="post" class="d-inline">
                                                        <input type="hidden" name="action" value="approve_comment">
                                                        <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-success">
                                                            <i class="bi bi-check-circle"></i> Approve
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this comment?');">
                                                    <input type="hidden" name="action" value="delete_comment">
                                                    <input type="hidden" name="comment_id" value="<?php echo $comment['comment_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </div>
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

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>