<?php
// Script to convert the first user to an admin

// Include database connection
require_once 'backend/connect_db.php';

// Include admin functions
require_once 'backend/admin.php';

// Initialize logger
$logger = new Logger('convert_admin');
$logger->info("Starting conversion of first user to admin");

// Function to convert the first user to an admin
function convert_first_user_to_admin() {
    global $conn, $logger;
    
    try {
        // Get the first user (lowest user_id)
        $stmt = $conn->prepare("SELECT user_id, username, email FROM users ORDER BY user_id ASC LIMIT 1");
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $logger->error("No users found in the database");
            return [
                'success' => false,
                'message' => 'No users found in the database'
            ];
        }
        
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $username = $user['username'];
        $email = $user['email'];
        
        $logger->info("Found first user: $username (ID: $user_id, Email: $email)");
        
        // Check if user is already an admin
        $stmt = $conn->prepare("SELECT admin_id, role FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            $role = $admin['role'];
            $logger->info("User $username is already an admin with role: $role");
            
            return [
                'success' => true,
                'message' => "User $username (ID: $user_id) is already an admin with role: $role"
            ];
        }
        
        // Convert user to admin with superadmin role
        $role = 'superadmin';
        $add_result = add_admin($user_id, $role);
        
        if ($add_result['success']) {
            $logger->info("Successfully converted user $username (ID: $user_id) to admin with role: $role");
            return [
                'success' => true,
                'message' => "Successfully converted user $username (ID: $user_id) to admin with role: $role"
            ];
        } else {
            $logger->error("Failed to convert user to admin: " . $add_result['message']);
            return [
                'success' => false,
                'message' => "Failed to convert user to admin: " . $add_result['message']
            ];
        }
    } catch (Exception $e) {
        $logger->error("Error converting first user to admin: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

// Execute the conversion
$result = convert_first_user_to_admin();

// Display the result
echo "<html><head><title>Convert First User to Admin</title>";
echo "<style>
    body { font-family: Arial, sans-serif; margin: 40px; line-height: 1.6; }
    .container { max-width: 800px; margin: 0 auto; }
    .success { color: green; background-color: #e8f5e9; padding: 15px; border-radius: 5px; }
    .error { color: red; background-color: #ffebee; padding: 15px; border-radius: 5px; }
    h1 { color: #333; }
</style>";
echo "</head><body><div class='container'>";
echo "<h1>Convert First User to Admin</h1>";

if ($result['success']) {
    echo "<div class='success'>" . $result['message'] . "</div>";
} else {
    echo "<div class='error'>" . $result['message'] . "</div>";
}

echo "<p><a href='index.php'>Return to Home</a></p>";
echo "</div></body></html>";
?>