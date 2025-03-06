<?php
// Set error handling to catch errors and convert them to JSON responses
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors directly
ini_set('log_errors', 1); // Log errors instead

// Custom error handler to convert PHP errors to JSON
function jsonErrorHandler($errno, $errstr, $errfile, $errline) {
    // Log the error
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    
    // Only handle fatal errors that would normally stop execution
    if (in_array($errno, [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        // Clear any output that might have been sent
        ob_clean();
        
        // Send JSON error response
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'Server error occurred',
            'error' => $errstr,
            'file' => basename($errfile),
            'line' => $errline
        ]);
        exit;
    }
    
    // Return false to let PHP handle the error normally
    return false;
}

// Set the custom error handler
set_error_handler('jsonErrorHandler');

// Also handle exceptions
function jsonExceptionHandler($exception) {
    // Log the exception
    error_log("Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine());
    
    // Clear any output that might have been sent
    ob_clean();
    
    // Send JSON error response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Server exception occurred',
        'error' => $exception->getMessage(),
        'file' => basename($exception->getFile()),
        'line' => $exception->getLine()
    ]);
    exit;
}

// Set the exception handler
set_exception_handler('jsonExceptionHandler');

// Start output buffering to catch any unexpected output
ob_start();

// Disable output buffering
ob_end_clean();

// Ensure no output has been sent before headers
if (!headers_sent()) {
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
} else {
    error_log("Headers already sent in profile.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api', Logger::DEBUG);
$apiLogger->debug("Profile API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Check if user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode([
        'success' => false,
        'message' => 'User not logged in'
    ]);
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle GET request (get user profile)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $apiLogger->debug("Profile API GET request for user ID: $user_id");
    
    try {
        // Fetch user data
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
            
            // Remove sensitive data
            unset($user['password_hash']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile retrieved successfully',
                'data' => $user
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'User not found'
            ]);
        }
    } catch (Exception $e) {
        $apiLogger->error("Profile API error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error retrieving profile: ' . $e->getMessage()
        ]);
    }
    
    exit;
}

// Handle POST request (update user profile)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    $apiLogger->debug("Profile API POST request for user ID: $user_id");
    
    try {
        // Validate required fields
        if (!isset($data['email'])) {
            throw new Exception('Email is required');
        }
        
        $email = $data['email'];
        $profile_picture = isset($data['profile_picture']) ? $data['profile_picture'] : null;
        $bio = isset($data['bio']) ? $data['bio'] : null;
        $current_password = isset($data['current_password']) ? $data['current_password'] : null;
        $new_password = isset($data['new_password']) ? $data['new_password'] : null;
        
        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Invalid email format');
        }
        
        // Check if email is already used by another user
        $stmt = $conn->prepare("
            SELECT user_id FROM users
            WHERE email = ? AND user_id != ?
        ");
        $stmt->bind_param("si", $email, $user_id);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email is already in use by another account');
        }
        
        // Start transaction
        $conn->begin_transaction();
        
        // Update user profile
        $stmt = $conn->prepare("
            UPDATE users
            SET email = ?, profile_picture = ?, bio = ?
            WHERE user_id = ?
        ");
        $stmt->bind_param("sssi", $email, $profile_picture, $bio, $user_id);
        $stmt->execute();
        
        // If password change is requested
        if ($current_password && $new_password) {
            // Validate new password
            if (strlen($new_password) < 8) {
                throw new Exception('New password must be at least 8 characters long');
            }
            
            // Get current password hash and salt
            $stmt = $conn->prepare("
                SELECT u.password_hash, s.salt_value
                FROM users u
                JOIN user_salts s ON u.user_id = s.user_id
                WHERE u.user_id = ?
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 1) {
                $row = $result->fetch_assoc();
                $stored_hash = $row['password_hash'];
                $salt = $row['salt_value'];
                
                // Verify current password
                if (!password_verify($current_password . $salt, $stored_hash)) {
                    throw new Exception('Current password is incorrect');
                }
                
                // Generate new password hash
                $new_hash = password_hash($new_password . $salt, PASSWORD_DEFAULT);
                
                // Update password
                $stmt = $conn->prepare("
                    UPDATE users
                    SET password_hash = ?
                    WHERE user_id = ?
                ");
                $stmt->bind_param("si", $new_hash, $user_id);
                $stmt->execute();
            } else {
                throw new Exception('Error retrieving user credentials');
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        // Fetch updated user data
        $stmt = $conn->prepare("
            SELECT user_id, username, email, profile_picture, bio, created_at
            FROM users
            WHERE user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    } catch (Exception $e) {
        // Rollback transaction
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        
        $apiLogger->error("Profile API error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    
    exit;
}

// If we get here, it's an unsupported method
echo json_encode([
    'success' => false,
    'message' => 'Unsupported request method'
]);