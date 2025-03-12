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
        $logger->warning("Non-admin user (ID: $user_id) attempted to access category management");
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
$categories = [];
$success_message = '';
$error_message = '';
$edit_category = null;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action from form
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'add_category':
                // Add new category
                $name = isset($_POST['name']) ? trim($_POST['name']) : '';
                $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                
                // Validate name
                if (empty($name)) {
                    throw new Exception("Category name is required.");
                }
                
                // Check if category already exists
                $stmt = $conn->prepare("SELECT category_id FROM categories WHERE name = ?");
                $stmt->bind_param("s", $name);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception("A category with this name already exists.");
                }
                
                // Insert category
                $stmt = $conn->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $description);
                $stmt->execute();
                
                if ($stmt->affected_rows > 0) {
                    $success_message = "Category has been added successfully.";
                    $logger->info("Admin (ID: $user_id) added new category: $name");
                } else {
                    $error_message = "Failed to add category.";
                }
                break;
                
            case 'update_category':
                // Update category
                if (isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
                    $category_id = (int)$_POST['category_id'];
                    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
                    $description = isset($_POST['description']) ? trim($_POST['description']) : '';
                    
                    // Validate name
                    if (empty($name)) {
                        throw new Exception("Category name is required.");
                    }
                    
                    // Check if category name already exists (excluding current category)
                    $stmt = $conn->prepare("SELECT category_id FROM categories WHERE name = ? AND category_id != ?");
                    $stmt->bind_param("si", $name, $category_id);
                    $stmt->execute();
                    if ($stmt->get_result()->num_rows > 0) {
                        throw new Exception("A category with this name already exists.");
                    }
                    
                    // Update category
                    $stmt = $conn->prepare("UPDATE categories SET name = ?, description = ? WHERE category_id = ?");
                    $stmt->bind_param("ssi", $name, $description, $category_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0 || $stmt->errno === 0) {
                        $success_message = "Category has been updated successfully.";
                        $logger->info("Admin (ID: $user_id) updated category (ID: $category_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
                
            case 'delete_category':
                // Delete category
                if (isset($_POST['category_id']) && is_numeric($_POST['category_id'])) {
                    $category_id = (int)$_POST['category_id'];
                    
                    // Check if category is in use
                    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM blog_posts WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    $count = $stmt->get_result()->fetch_assoc()['count'];
                    
                    if ($count > 0) {
                        throw new Exception("This category cannot be deleted because it is used by $count post(s).");
                    }
                    
                    // Delete category
                    $stmt = $conn->prepare("DELETE FROM categories WHERE category_id = ?");
                    $stmt->bind_param("i", $category_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "Category has been deleted successfully.";
                        $logger->info("Admin (ID: $user_id) deleted category (ID: $category_id)");
                    } else {
                        $error_message = "Category could not be deleted.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $logger->error("Error in category management: " . $e->getMessage());
    }
}

// Handle edit category request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_category_id = (int)$_GET['id'];
    
    try {
        // Get category details
        $stmt = $conn->prepare("SELECT * FROM categories WHERE category_id = ?");
        $stmt->bind_param("i", $edit_category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $edit_category = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $error_message = "Error fetching category details: " . $e->getMessage();
        $logger->error("Error fetching category details: " . $e->getMessage());
    }
}

// Fetch all categories
try {
    $stmt = $conn->prepare("
        SELECT c.*, COUNT(p.post_id) as post_count
        FROM categories c
        LEFT JOIN blog_posts p ON c.category_id = p.category_id
        GROUP BY c.category_id
        ORDER BY c.name
    ");
    $stmt->execute();
    $categories = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching categories: " . $e->getMessage();
    $logger->error("Error fetching categories: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-tag"></i> Manage Categories</h1>
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
        
        <div class="row">
            <div class="col-md-4">
                <!-- Add/Edit Category Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><?php echo $edit_category ? 'Edit Category' : 'Add New Category'; ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="<?php echo $edit_category ? 'update_category' : 'add_category'; ?>">
                            <?php if ($edit_category): ?>
                                <input type="hidden" name="category_id" value="<?php echo $edit_category['category_id']; ?>">
                            <?php endif; ?>
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Category Name</label>
                                <input type="text" class="form-control" id="name" name="name" value="<?php echo htmlspecialchars($edit_category['name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($edit_category['description'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-<?php echo $edit_category ? 'save' : 'plus-circle'; ?>"></i>
                                    <?php echo $edit_category ? 'Update Category' : 'Add Category'; ?>
                                </button>
                                
                                <?php if ($edit_category): ?>
                                    <a href="/admin/manage_categories" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-circle"></i> Cancel
                                    </a>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <!-- Categories Table -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">All Categories</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($categories)): ?>
                            <p class="text-center text-muted">No categories found.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Name</th>
                                            <th>Description</th>
                                            <th>Posts</th>
                                            <th>Created</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($categories as $category): ?>
                                            <tr>
                                                <td><?php echo $category['category_id']; ?></td>
                                                <td><?php echo htmlspecialchars($category['name']); ?></td>
                                                <td>
                                                    <?php if (!empty($category['description'])): ?>
                                                        <?php echo htmlspecialchars(substr($category['description'], 0, 50)) . (strlen($category['description']) > 50 ? '...' : ''); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">No description</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php if ($category['post_count'] > 0): ?>
                                                        <a href="/admin/manage_posts?category=<?php echo $category['category_id']; ?>">
                                                            <?php echo $category['post_count']; ?> post<?php echo $category['post_count'] !== 1 ? 's' : ''; ?>
                                                        </a>
                                                    <?php else: ?>
                                                        0 posts
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="/admin/manage_categories?action=edit&id=<?php echo $category['category_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                            <i class="bi bi-pencil"></i> Edit
                                                        </a>
                                                        
                                                        <?php if ($category['post_count'] === 0): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    onclick="confirmDelete(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')">
                                                                <i class="bi bi-trash"></i> Delete
                                                            </button>
                                                        <?php endif; ?>
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
        </div>
    </div>
    
    <!-- Delete Category Modal -->
    <div class="modal fade" id="deleteCategoryModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this category? This action cannot be undone.</p>
                    <p><strong id="categoryName"></strong></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="post" id="deleteCategoryForm">
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" id="categoryId">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete(categoryId, categoryName) {
            document.getElementById('categoryId').value = categoryId;
            document.getElementById('categoryName').textContent = categoryName;
            
            const modal = new bootstrap.Modal(document.getElementById('deleteCategoryModal'));
            modal.show();
        }
    </script>
</body>
</html>