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
        $logger->warning("Non-admin user (ID: $user_id) attempted to access user management");
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
$users = [];
$success_message = '';
$error_message = '';
$edit_user = null;
$admin_roles = ['editor', 'admin', 'superadmin'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get action from form
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    try {
        switch ($action) {
            case 'toggle_active':
                // Toggle user active status
                if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                    $target_user_id = (int)$_POST['user_id'];
                    $is_active = isset($_POST['is_active']) ? (int)$_POST['is_active'] : 0;
                    
                    // Don't allow deactivating yourself
                    if ($target_user_id === $user_id) {
                        throw new Exception("You cannot deactivate your own account.");
                    }
                    
                    // Update user status
                    $stmt = $conn->prepare("UPDATE users SET is_active = ? WHERE user_id = ?");
                    $stmt->bind_param("ii", $is_active, $target_user_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $status = $is_active ? 'activated' : 'deactivated';
                        $success_message = "User has been $status successfully.";
                        $logger->info("Admin (ID: $user_id) $status user (ID: $target_user_id)");
                    } else {
                        $error_message = "No changes were made.";
                    }
                }
                break;
                
            case 'toggle_admin':
                // Toggle admin status
                if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                    $target_user_id = (int)$_POST['user_id'];
                    $is_admin = isset($_POST['is_admin']) ? (int)$_POST['is_admin'] : 0;
                    $admin_role = isset($_POST['admin_role']) ? $_POST['admin_role'] : 'editor';
                    
                    // Don't allow removing your own admin status
                    if ($target_user_id === $user_id && $is_admin === 0) {
                        throw new Exception("You cannot remove your own admin privileges.");
                    }
                    
                    // Validate admin role
                    if (!in_array($admin_role, $admin_roles)) {
                        $admin_role = 'editor';
                    }
                    
                    if ($is_admin) {
                        // Add admin role
                        $stmt = $conn->prepare("
                            INSERT INTO admins (user_id, role) 
                            VALUES (?, ?) 
                            ON DUPLICATE KEY UPDATE role = ?
                        ");
                        $stmt->bind_param("iss", $target_user_id, $admin_role, $admin_role);
                        $stmt->execute();
                        
                        $success_message = "User has been granted admin privileges with role: $admin_role.";
                        $logger->info("Admin (ID: $user_id) granted admin privileges to user (ID: $target_user_id) with role: $admin_role");
                    } else {
                        // Remove admin role
                        $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
                        $stmt->bind_param("i", $target_user_id);
                        $stmt->execute();
                        
                        $success_message = "Admin privileges have been removed from the user.";
                        $logger->info("Admin (ID: $user_id) removed admin privileges from user (ID: $target_user_id)");
                    }
                }
                break;
                
            case 'delete_user':
                // Delete user
                if (isset($_POST['user_id']) && is_numeric($_POST['user_id'])) {
                    $target_user_id = (int)$_POST['user_id'];
                    
                    // Don't allow deleting yourself
                    if ($target_user_id === $user_id) {
                        throw new Exception("You cannot delete your own account.");
                    }
                    
                    // Delete user (cascade will handle related records)
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $target_user_id);
                    $stmt->execute();
                    
                    if ($stmt->affected_rows > 0) {
                        $success_message = "User has been deleted successfully.";
                        $logger->info("Admin (ID: $user_id) deleted user (ID: $target_user_id)");
                    } else {
                        $error_message = "User could not be deleted.";
                    }
                }
                break;
        }
    } catch (Exception $e) {
        $error_message = $e->getMessage();
        $logger->error("Error in user management: " . $e->getMessage());
    }
}

// Handle edit user request
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $edit_user_id = (int)$_GET['id'];
    
    try {
        // Get user details
        $stmt = $conn->prepare("
            SELECT u.*, IFNULL(a.role, '') as admin_role
            FROM users u
            LEFT JOIN admins a ON u.user_id = a.user_id
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("i", $edit_user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $edit_user = $result->fetch_assoc();
        }
    } catch (Exception $e) {
        $error_message = "Error fetching user details: " . $e->getMessage();
        $logger->error("Error fetching user details: " . $e->getMessage());
    }
}

// Fetch all users
try {
    $stmt = $conn->prepare("
        SELECT u.*, IFNULL(a.role, '') as admin_role
        FROM users u
        LEFT JOIN admins a ON u.user_id = a.user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    $error_message = "Error fetching users: " . $e->getMessage();
    $logger->error("Error fetching users: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-people"></i> Manage Users</h1>
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
        
        <?php if ($edit_user): ?>
            <!-- Edit User Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Edit User: <?php echo htmlspecialchars($edit_user['username']); ?></h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>User Information</h6>
                            <dl class="row">
                                <dt class="col-sm-4">Username:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($edit_user['username']); ?></dd>
                                
                                <dt class="col-sm-4">Email:</dt>
                                <dd class="col-sm-8"><?php echo htmlspecialchars($edit_user['email']); ?></dd>
                                
                                <dt class="col-sm-4">Joined:</dt>
                                <dd class="col-sm-8"><?php echo date('F j, Y', strtotime($edit_user['created_at'])); ?></dd>
                                
                                <dt class="col-sm-4">Status:</dt>
                                <dd class="col-sm-8">
                                    <span class="badge bg-<?php echo $edit_user['is_active'] ? 'success' : 'danger'; ?>">
                                        <?php echo $edit_user['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </dd>
                                
                                <dt class="col-sm-4">Admin Role:</dt>
                                <dd class="col-sm-8">
                                    <?php if ($edit_user['admin_role']): ?>
                                        <span class="badge bg-primary"><?php echo ucfirst($edit_user['admin_role']); ?></span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">None</span>
                                    <?php endif; ?>
                                </dd>
                            </dl>
                        </div>
                        
                        <div class="col-md-6">
                            <h6>Actions</h6>
                            
                            <!-- Toggle Active Status -->
                            <form method="post" class="mb-3">
                                <input type="hidden" name="action" value="toggle_active">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                                <input type="hidden" name="is_active" value="<?php echo $edit_user['is_active'] ? 0 : 1; ?>">
                                
                                <button type="submit" class="btn btn-<?php echo $edit_user['is_active'] ? 'warning' : 'success'; ?> btn-sm" 
                                        <?php echo $edit_user['user_id'] === $user_id ? 'disabled' : ''; ?>>
                                    <i class="bi bi-<?php echo $edit_user['is_active'] ? 'person-dash' : 'person-check'; ?>"></i>
                                    <?php echo $edit_user['is_active'] ? 'Deactivate User' : 'Activate User'; ?>
                                </button>
                            </form>
                            
                            <!-- Toggle Admin Status -->
                            <form method="post" class="mb-3">
                                <input type="hidden" name="action" value="toggle_admin">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                                
                                <?php if ($edit_user['admin_role']): ?>
                                    <input type="hidden" name="is_admin" value="0">
                                    <button type="submit" class="btn btn-warning btn-sm"
                                            <?php echo $edit_user['user_id'] === $user_id ? 'disabled' : ''; ?>>
                                        <i class="bi bi-shield-x"></i> Remove Admin Privileges
                                    </button>
                                <?php else: ?>
                                    <div class="input-group">
                                        <input type="hidden" name="is_admin" value="1">
                                        <select name="admin_role" class="form-select form-select-sm">
                                            <?php foreach ($admin_roles as $role): ?>
                                                <option value="<?php echo $role; ?>"><?php echo ucfirst($role); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-primary btn-sm">
                                            <i class="bi bi-shield-plus"></i> Grant Admin Privileges
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </form>
                            
                            <!-- Delete User -->
                            <form method="post" onsubmit="return confirm('Are you sure you want to delete this user? This action cannot be undone.');">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?php echo $edit_user['user_id']; ?>">
                                
                                <button type="submit" class="btn btn-danger btn-sm"
                                        <?php echo $edit_user['user_id'] === $user_id ? 'disabled' : ''; ?>>
                                    <i class="bi bi-trash"></i> Delete User
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <a href="/admin/manage_user" class="btn btn-secondary">
                        <i class="bi bi-x"></i> Cancel
                    </a>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Users Table -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Users</h5>
            </div>
            <div class="card-body">
                <?php if (empty($users)): ?>
                    <p class="text-center text-muted">No users found.</p>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Joined</th>
                                    <th>Status</th>
                                    <th>Admin Role</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td><?php echo $u['user_id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $u['is_active'] ? 'success' : 'danger'; ?>">
                                                <?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($u['admin_role']): ?>
                                                <span class="badge bg-primary"><?php echo ucfirst($u['admin_role']); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">None</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="/admin/manage_user?action=edit&id=<?php echo $u['user_id']; ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="bi bi-pencil"></i> Edit
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

    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>