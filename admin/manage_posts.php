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
        $logger->warning("Non-admin user (ID: $user_id) attempted to access post management");
        header('Location: /index');
        exit();
    }
} catch (Exception $e) {
    $logger->error("Error checking admin status: " . $e->getMessage());
    // Redirect to home page on error
    header('Location: /index');
    exit();
}

// Initialize variables
$posts = [];
$categories = [];
$success_message = '';
$error_message = '';
$edit_post = null;
$post_statuses = ['draft', 'published', 'archived'];

// Get all categories for dropdown
try {
    $stmt = $conn->prepare("SELECT category_id, name FROM categories ORDER BY name");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $logger->error("Error fetching categories: " . $e->getMessage());
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action from form
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'update_post':
                // Update post
                if (isset($_POST['post_id']) && is_numeric($_POST['post_id'])) {
                    $post_id = (int)$_POST['post_id'];
                    $title = isset($_POST['title']) ? trim($_POST['title']) : '';
                    $content = isset($_POST['content']) ? trim($_POST['content']) : '';
                    $category_id = isset($_POST['category_id']) && is_numeric($_POST['category_id']) ? (int)$_POST['category_id'] : null;
                    $featured_image = isset($_POST['featured_image']) ? trim($_POST['featured_image']) : null;
                    $status = isset($_POST['status']) ? $_POST['status'] : 'draft';
                    
                    // Validate required fields
                    if (empty($title)) {
                        throw new Exception("Title is required.");
                    }
                    
                    if (empty($content)) {
                        throw new Exception("Content is required.");
                    }
                    
                    // Validate status
                    if (!in_array($status, $post_statuses)) {
                        $status = 'draft';
                    }
                    
                    // Update post
                    $stmt = $conn->prepare("
                        UPDATE blog_posts
                        SET title = ?, content = ?, category_id = ?, featured_image = ?, status = ?
                        WHERE post_id = ?
                    ");
                    $stmt->bind_param("ssissi", $title, $content, $category_id, $featured_image, $status, $post_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                        $success_message = "Post has been updated successfully.";
                        $logger->info("Admin (ID: $user_id) updated post (ID: $post_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
                
            case 'delete_post':
                // Delete post
                if (isset($_POST['post_id']) && is_numeric($_POST['post_id'])) {
                    $post_id = (int)$_POST['post_id'];
                    
                    // Delete post (cascade will handle related records)
                    $stmt = $conn->prepare("DELETE FROM blog_posts WHERE post_id = ?");
                    $stmt->bind_param("i", $post_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Post has been deleted successfully.";
                        $logger->info("Admin (ID: $user_id) deleted post (ID: $post_id)");
                    } else {
                        $error_message = "Post could not be deleted.";
                    }
                }
                break;
                
            case 'change_status':
                // Change post status
                if (isset($_POST['post_id']) && is_numeric($_POST['post_id']) && isset($_POST['status'])) {
                    $post_id = (int)$_POST['post_id'];
                    $status = $_POST['status'];
                    
                    // Validate status
                    if (!in_array($status, $post_statuses)) {
                        throw new Exception("Invalid status.");
                    }
                    
                    // Update post status
                    $stmt = $conn->prepare("UPDATE blog_posts SET status = ? WHERE post_id = ?");
                    $stmt->bind_param("si", $status, $post_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Post status has been changed to " . ucfirst($status) . ".";
                        $logger->info("Admin (ID: $user_id) changed post (ID: $post_id) status to $status");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $logger->error("Error in post management: " . $e->getMessage());
    }
}

// Handle edit post request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_post_id = (int)$_GET['id'];
    
    try {
        // Get post details
        $stmt = $conn->prepare("
            SELECT p.*, u.username as author_name, c.name as category_name
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE p.post_id = ?
        ");
        $stmt->bind_param("i", $edit_post_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $edit_post = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $error_message = "Error fetching post details: " . $e->getMessage();
        $logger->error("Error fetching post details: " . $e->getMessage());
    }
}

// Get filter parameters
$status_filter = isset($_GET['status']) && in_array($_GET['status'], $post_statuses) ? $_GET['status'] : '';
$category_filter = isset($_GET['category']) && is_numeric($_GET['category']) ? (int)$_GET['category'] : 0;
$search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build query based on filters
$query = "
    SELECT p.*, u.username as author_name, c.name as category_name
    FROM blog_posts p
    JOIN users u ON p.author_id = u.user_id
    LEFT JOIN categories c ON p.category_id = c.category_id
    WHERE 1=1
";

$params = [];
$types = '';

if (!empty($status_filter)) {
    $query .= " AND p.status = ?";
    $params[] = $status_filter;
    $types .= 's';
}

if ($category_filter > 0) {
    $query .= " AND p.category_id = ?";
    $params[] = $category_filter;
    $types .= 'i';
}

if (!empty($search_query)) {
    $search_param = "%$search_query%";
    $query .= " AND (p.title LIKE ? OR p.content LIKE ?)";
    $params[] = $search_param;
    $params[] = $search_param;
    $types .= 'ss';
}

$query .= " ORDER BY p.created_at DESC";

// Fetch posts with filters
try {
    $stmt = $conn->prepare($query);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $posts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching posts: " . $e->getMessage();
    $logger->error("Error fetching posts: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Posts - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-file-earmark-post"></i> Manage Posts</h1>
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
        
        <?php if ($edit_post): ?>
            <!-- Edit Post Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit Post: <?php echo htmlspecialchars($edit_post['title']); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="update_post">
                        <input type="hidden" name="post_id" value="<?php echo $edit_post['post_id']; ?>">
                        
                        <div class="mb-3">
                            <label for="title" class="form-label">Title</label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($edit_post['title']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="content" class="form-label">Content</label>
                            <textarea class="form-control" id="content" name="content" rows="10" required><?php echo htmlspecialchars($edit_post['content']); ?></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id">
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php echo $edit_post['category_id'] == $category['category_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <?php foreach ($post_statuses as $status): ?>
                                        <option value="<?php echo $status; ?>" <?php echo $edit_post['status'] === $status ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($status); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="featured_image" class="form-label">Featured Image</label>
                            
                            <!-- Image Source Toggle -->
                            <div class="btn-group mb-3 w-100" role="group" aria-label="Image source options">
                                <input type="radio" class="btn-check" name="imageSourceOption" id="uploadOption" autocomplete="off" checked>
                                <label class="btn btn-outline-primary" for="uploadOption">Upload Image</label>
                                
                                <input type="radio" class="btn-check" name="imageSourceOption" id="urlOption" autocomplete="off">
                                <label class="btn btn-outline-primary" for="urlOption">Image URL</label>
                            </div>
                            
                            <!-- Upload Image Option -->
                            <div id="uploadImageSection">
                                <div class="input-group mb-3">
                                    <input type="file" class="form-control" id="imageUpload" accept="image/*">
                                    <button class="btn btn-outline-secondary" type="button" id="uploadButton">Upload</button>
                                </div>
                                <div class="form-text">Upload an image for your post (max 5MB, JPEG, PNG, GIF, or WebP).</div>
                                <div id="uploadProgress" class="progress mt-2 d-none">
                                    <div class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width: 0%"></div>
                                </div>
                            </div>
                            
                            <!-- Image URL Option -->
                            <div id="imageUrlSection" class="d-none">
                                <div class="input-group mb-3">
                                    <input type="url" class="form-control" id="imageUrl" placeholder="https://example.com/image.jpg">
                                    <button class="btn btn-outline-secondary" type="button" id="previewUrlButton">Preview</button>
                                </div>
                                <div class="form-text">Enter a direct URL to an image (JPEG, PNG, GIF, or WebP).</div>
                            </div>
                            
                            <input type="hidden" id="featured_image" name="featured_image" value="<?php echo htmlspecialchars($edit_post['featured_image'] ?? ''); ?>">
                            
                            <!-- Image Preview (shared between both options) -->
                            <div id="imagePreview" class="mt-2 <?php echo empty($edit_post['featured_image']) ? 'd-none' : ''; ?>">
                                <img src="<?php echo htmlspecialchars($edit_post['featured_image'] ?? ''); ?>" alt="Featured Image" class="img-thumbnail" style="max-height: 200px;">
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Author</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($edit_post['author_name']); ?>" disabled>
                        </div>
                        
                        <div class="d-flex justify-content-between">
                            <a href="/admin/manage_posts" class="btn btn-secondary">
                                <i class="bi bi-x"></i> Cancel
                            </a>
                            
                            <div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-save"></i> Save Changes
                                </button>
                                
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deletePostModal">
                                    <i class="bi bi-trash"></i> Delete Post
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Delete Post Modal -->
            <div class="modal fade" id="deletePostModal" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Confirm Delete</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Are you sure you want to delete this post? This action cannot be undone.</p>
                            <p><strong><?php echo htmlspecialchars($edit_post['title']); ?></strong></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <form method="post">
                                <input type="hidden" name="action" value="delete_post">
                                <input type="hidden" name="post_id" value="<?php echo $edit_post['post_id']; ?>">
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
                <h5 class="mb-0">Filter Posts</h5>
            </div>
            <div class="card-body">
                <form method="get" class="row g-3">
                    <div class="col-md-4">
                        <label for="status" class="form-label">Status</label>
                        <select class="form-select" id="status" name="status">
                            <option value="">All Statuses</option>
                            <?php foreach ($post_statuses as $status): ?>
                                <option value="<?php echo $status; ?>" <?php echo $status_filter === $status ? 'selected' : ''; ?>>
                                    <?php echo ucfirst($status); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="category" class="form-label">Category</label>
                        <select class="form-select" id="category" name="category">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['category_id']; ?>" <?php echo $category_filter == $category['category_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="search" class="form-label">Search</label>
                        <input type="text" class="form-control" id="search" name="search" value="<?php echo htmlspecialchars($search_query); ?>" placeholder="Search title or content...">
                    </div>
                    
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-filter"></i> Apply Filters
                        </button>
                        <a href="/admin/manage_posts" class="btn btn-outline-secondary">
                            <i class="bi bi-x-circle"></i> Clear Filters
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Posts Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">All Posts</h5>
                <span class="badge bg-secondary"><?php echo count($posts); ?> posts found</span>
            </div>
            <div class="card-body">
                <?php if (empty($posts)): ?>
                    <p class="text-center text-muted">No posts found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Title</th>
                                    <th>Author</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Views</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($posts as $post): ?>
                                    <tr>
                                        <td><?php echo $post['post_id']; ?></td>
                                        <td><?php echo htmlspecialchars($post['title']); ?></td>
                                        <td><?php echo htmlspecialchars($post['author_name']); ?></td>
                                        <td><?php echo htmlspecialchars($post['category_name'] ?? 'Uncategorized'); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $post['status'] === 'published' ? 'success' : ($post['status'] === 'draft' ? 'warning' : 'secondary'); ?>">
                                                <?php echo ucfirst($post['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($post['created_at'])); ?></td>
                                        <td><?php echo $post['views_count']; ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="/admin/manage_posts?action=edit&id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-primary dropdown-toggle dropdown-toggle-split" data-bs-toggle="dropdown">
                                                    <span class="visually-hidden">Toggle Dropdown</span>
                                                </button>
                                                <ul class="dropdown-menu">
                                                    <li>
                                                        <a class="dropdown-item" href="/public/post?id=<?php echo $post['post_id']; ?>" target="_blank">
                                                            <i class="bi bi-eye"></i> View
                                                        </a>
                                                    </li>
                                                    <?php if ($post['status'] !== 'published'): ?>
                                                        <li>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                                <input type="hidden" name="status" value="published">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-check-circle"></i> Publish
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($post['status'] !== 'draft'): ?>
                                                        <li>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                                <input type="hidden" name="status" value="draft">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-file"></i> Set as Draft
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <?php if ($post['status'] !== 'archived'): ?>
                                                        <li>
                                                            <form method="post" class="d-inline">
                                                                <input type="hidden" name="action" value="change_status">
                                                                <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                                <input type="hidden" name="status" value="archived">
                                                                <button type="submit" class="dropdown-item">
                                                                    <i class="bi bi-archive"></i> Archive
                                                                </button>
                                                            </form>
                                                        </li>
                                                    <?php endif; ?>
                                                    <li><hr class="dropdown-divider"></li>
                                                    <li>
                                                        <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                                            <input type="hidden" name="action" value="delete_post">
                                                            <input type="hidden" name="post_id" value="<?php echo $post['post_id']; ?>">
                                                            <button type="submit" class="dropdown-item text-danger">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        </form>
                                                    </li>
                                                </ul>
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
    
    <?php if ($edit_post): ?>
    <script>
        // Image source toggle functionality
        const uploadOption = document.getElementById('uploadOption');
        const urlOption = document.getElementById('urlOption');
        const uploadImageSection = document.getElementById('uploadImageSection');
        const imageUrlSection = document.getElementById('imageUrlSection');
        
        // Elements for image upload
        const imageUpload = document.getElementById('imageUpload');
        const uploadButton = document.getElementById('uploadButton');
        const uploadProgress = document.getElementById('uploadProgress');
        const progressBar = uploadProgress.querySelector('.progress-bar');
        
        // Elements for image URL
        const imageUrl = document.getElementById('imageUrl');
        const previewUrlButton = document.getElementById('previewUrlButton');
        
        // Shared elements
        const featuredImageInput = document.getElementById('featured_image');
        const imagePreview = document.getElementById('imagePreview');
        const previewImage = imagePreview.querySelector('img');
        
        // Toggle between upload and URL options
        uploadOption.addEventListener('change', function() {
            if (this.checked) {
                uploadImageSection.classList.remove('d-none');
                imageUrlSection.classList.add('d-none');
            }
        });
        
        urlOption.addEventListener('change', function() {
            if (this.checked) {
                uploadImageSection.classList.add('d-none');
                imageUrlSection.classList.remove('d-none');
            }
        });
        
        // Check if the current image is an external URL
        if (featuredImageInput.value && featuredImageInput.value.match(/^https?:\/\//i) && !featuredImageInput.value.includes('/uploads/')) {
            // Select URL option
            urlOption.checked = true;
            uploadOption.checked = false;
            uploadImageSection.classList.add('d-none');
            imageUrlSection.classList.remove('d-none');
            
            // Set the URL in the input field
            imageUrl.value = featuredImageInput.value;
        }
        
        // Handle image upload
        uploadButton.addEventListener('click', function() {
            if (!imageUpload.files.length) {
                alert('Please select an image to upload');
                return;
            }
            
            const file = imageUpload.files[0];
            
            // Check file size (max 5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size exceeds the maximum allowed size (5MB)');
                return;
            }
            
            // Check file type
            const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!allowedTypes.includes(file.type)) {
                alert('Invalid file type. Allowed types: JPEG, PNG, GIF, WebP');
                return;
            }
            
            // Create form data
            const formData = new FormData();
            formData.append('image', file);
            
            // Show progress bar
            uploadProgress.classList.remove('d-none');
            progressBar.style.width = '0%';
            
            // Upload file
            const xhr = new XMLHttpRequest();
            
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    progressBar.style.width = percentComplete + '%';
                }
            });
            
            xhr.addEventListener('load', function() {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        
                        if (response.success) {
                            // Set featured image URL
                            featuredImageInput.value = response.data.url;
                            
                            // Show image preview
                            previewImage.src = response.data.url;
                            imagePreview.classList.remove('d-none');
                            
                            // Hide progress bar after a delay
                            setTimeout(() => {
                                uploadProgress.classList.add('d-none');
                            }, 1000);
                        } else {
                            alert('Upload failed: ' + response.message);
                            uploadProgress.classList.add('d-none');
                        }
                    } catch (error) {
                        alert('Error parsing response: ' + error.message);
                        uploadProgress.classList.add('d-none');
                    }
                } else {
                    alert('Upload failed with status: ' + xhr.status);
                    uploadProgress.classList.add('d-none');
                }
            });
            
            xhr.addEventListener('error', function() {
                alert('Upload failed. Please try again.');
                uploadProgress.classList.add('d-none');
            });
            
            xhr.open('POST', '/backend/api/upload.php', true);
            xhr.send(formData);
        });
        
        // Handle image URL preview
        previewUrlButton.addEventListener('click', function() {
            const url = imageUrl.value.trim();
            
            if (!url) {
                alert('Please enter an image URL');
                return;
            }
            
            // Basic URL validation
            if (!url.match(/^https?:\/\/.+\.(jpg|jpeg|png|gif|webp)(\?.*)?$/i)) {
                alert('Please enter a valid image URL (must end with .jpg, .jpeg, .png, .gif, or .webp)');
                return;
            }
            
            // Set the URL to the hidden input
            featuredImageInput.value = url;
            
            // Show image preview
            previewImage.src = url;
            previewImage.onerror = function() {
                alert('Failed to load image from the provided URL. Please check the URL and try again.');
                imagePreview.classList.add('d-none');
                featuredImageInput.value = '';
            };
            previewImage.onload = function() {
                imagePreview.classList.remove('d-none');
            };
        });
    </script>
    <?php endif; ?>
</body>
</html>