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

// Ensure no output has been sent before headers
if (!headers_sent()) {
    // Set headers for JSON response
    header('Content-Type: application/json');
    header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
    header('Access-Control-Allow-Credentials: true');
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
} else {
    error_log("Headers already sent in comments.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api_comments', Logger::DEBUG);
$apiLogger->debug("Comments API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Start session to get user info
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? $_SESSION['user_id'] : null;

// Check if user is admin
$isAdmin = false;
if ($isLoggedIn) {
    try {
        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $isAdmin = $stmt->get_result()->num_rows > 0;
    } catch (Exception $e) {
        $apiLogger->error("Error checking admin status: " . $e->getMessage());
    }
}

// Get action from query string
$action = isset($_GET['action']) ? $_GET['action'] : '';

// Handle GET requests
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    switch ($action) {
        case 'by_post':
            // Get comments for a specific post
            if (!isset($_GET['post_id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Post ID is required'
                ]);
                exit;
            }
            
            $post_id = intval($_GET['post_id']);
            $apiLogger->debug("Fetching comments for post ID: $post_id");
            
            try {
                // If admin, get all comments including unapproved ones
                if ($isAdmin) {
                    $stmt = $conn->prepare("
                        SELECT c.*, u.username, u.profile_picture
                        FROM comments c
                        JOIN users u ON c.user_id = u.user_id
                        WHERE c.post_id = ? AND c.parent_comment_id IS NULL
                        ORDER BY c.created_at DESC
                    ");
                    $stmt->bind_param("i", $post_id);
                } else {
                    // For regular users, only get approved comments
                    $stmt = $conn->prepare("
                        SELECT c.*, u.username, u.profile_picture
                        FROM comments c
                        JOIN users u ON c.user_id = u.user_id
                        WHERE c.post_id = ? AND c.is_approved = 1 AND c.parent_comment_id IS NULL
                        ORDER BY c.created_at DESC
                    ");
                    $stmt->bind_param("i", $post_id);
                }
                
                $stmt->execute();
                $result = $stmt->get_result();
                
                $comments = [];
                while ($comment = $result->fetch_assoc()) {
                    // Get replies for this comment
                    $replies = [];
                    
                    if ($isAdmin) {
                        $repliesStmt = $conn->prepare("
                            SELECT c.*, u.username, u.profile_picture
                            FROM comments c
                            JOIN users u ON c.user_id = u.user_id
                            WHERE c.parent_comment_id = ?
                            ORDER BY c.created_at ASC
                        ");
                    } else {
                        $repliesStmt = $conn->prepare("
                            SELECT c.*, u.username, u.profile_picture
                            FROM comments c
                            JOIN users u ON c.user_id = u.user_id
                            WHERE c.parent_comment_id = ? AND c.is_approved = 1
                            ORDER BY c.created_at ASC
                        ");
                    }
                    
                    $repliesStmt->bind_param("i", $comment['comment_id']);
                    $repliesStmt->execute();
                    $repliesResult = $repliesStmt->get_result();
                    
                    while ($reply = $repliesResult->fetch_assoc()) {
                        // Remove sensitive data
                        unset($reply['email']);
                        $replies[] = $reply;
                    }
                    
                    // Add replies to comment
                    $comment['replies'] = $replies;
                    
                    // Remove sensitive data
                    unset($comment['email']);
                    
                    $comments[] = $comment;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Comments retrieved successfully',
                    'data' => $comments
                ]);
            } catch (Exception $e) {
                $apiLogger->error("Error fetching comments: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching comments: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'all':
            // Get all comments (admin only)
            if (!$isAdmin) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Unauthorized access'
                ]);
                exit;
            }
            
            $apiLogger->debug("Admin fetching all comments");
            
            try {
                $stmt = $conn->prepare("
                    SELECT c.*, u.username, p.title as post_title
                    FROM comments c
                    JOIN users u ON c.user_id = u.user_id
                    JOIN blog_posts p ON c.post_id = p.post_id
                    ORDER BY c.created_at DESC
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $comments = [];
                while ($comment = $result->fetch_assoc()) {
                    // Remove sensitive data
                    unset($comment['email']);
                    $comments[] = $comment;
                }
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Comments retrieved successfully',
                    'data' => $comments
                ]);
            } catch (Exception $e) {
                $apiLogger->error("Error fetching all comments: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching comments: ' . $e->getMessage()
                ]);
            }
            break;
            
        case 'single':
            // Get a single comment
            if (!isset($_GET['id'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Comment ID is required'
                ]);
                exit;
            }
            
            $comment_id = intval($_GET['id']);
            $apiLogger->debug("Fetching comment ID: $comment_id");
            
            try {
                $stmt = $conn->prepare("
                    SELECT c.*, u.username, u.profile_picture, p.title as post_title
                    FROM comments c
                    JOIN users u ON c.user_id = u.user_id
                    JOIN blog_posts p ON c.post_id = p.post_id
                    WHERE c.comment_id = ?
                ");
                $stmt->bind_param("i", $comment_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Comment not found'
                    ]);
                    exit;
                }
                
                $comment = $result->fetch_assoc();
                
                // Check if user has access to this comment
                if (!$isAdmin && !$comment['is_approved'] && $comment['user_id'] !== $user_id) {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Unauthorized access'
                    ]);
                    exit;
                }
                
                // Remove sensitive data
                unset($comment['email']);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Comment retrieved successfully',
                    'data' => $comment
                ]);
            } catch (Exception $e) {
                $apiLogger->error("Error fetching comment: " . $e->getMessage());
                echo json_encode([
                    'success' => false,
                    'message' => 'Error fetching comment: ' . $e->getMessage()
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Invalid action'
            ]);
            break;
    }
}

// Handle POST requests (create comment)
else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if user is logged in
    if (!$isLoggedIn) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to comment'
        ]);
        exit;
    }
    
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    if (!isset($data['post_id']) || !isset($data['content'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Post ID and content are required'
        ]);
        exit;
    }
    
    $post_id = intval($data['post_id']);
    $content = trim($data['content']);
    $parent_comment_id = isset($data['parent_comment_id']) ? intval($data['parent_comment_id']) : null;
    
    // Validate content
    if (empty($content)) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment content cannot be empty'
        ]);
        exit;
    }
    
    $apiLogger->debug("Creating comment for post ID: $post_id by user ID: $user_id");
    
    try {
        // Check if post exists
        $stmt = $conn->prepare("SELECT post_id FROM blog_posts WHERE post_id = ?");
        $stmt->bind_param("i", $post_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Post not found'
            ]);
            exit;
        }
        
        // If this is a reply, check if parent comment exists
        if ($parent_comment_id) {
            $stmt = $conn->prepare("SELECT comment_id FROM comments WHERE comment_id = ?");
            $stmt->bind_param("i", $parent_comment_id);
            $stmt->execute();
            
            if ($stmt->get_result()->num_rows === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Parent comment not found'
                ]);
                exit;
            }
        }
        
        // Auto-approve comments for admins, otherwise use default (TRUE)
        $is_approved = $isAdmin ? 1 : 1; // Currently approving all comments by default
        
        // Insert comment
        $stmt = $conn->prepare("
            INSERT INTO comments (post_id, user_id, parent_comment_id, content, is_approved)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->bind_param("iiisi", $post_id, $user_id, $parent_comment_id, $content, $is_approved);
        $stmt->execute();
        
        $comment_id = $conn->insert_id;
        
        // Get the newly created comment with user info
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.comment_id = ?
        ");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $comment = $stmt->get_result()->fetch_assoc();
        
        // Remove sensitive data
        unset($comment['email']);
        
        // Add empty replies array for consistency
        $comment['replies'] = [];
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment created successfully',
            'data' => $comment
        ]);
    } catch (Exception $e) {
        $apiLogger->error("Error creating comment: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error creating comment: ' . $e->getMessage()
        ]);
    }
}

// Handle PUT requests (update comment)
else if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    // Check if user is logged in
    if (!$isLoggedIn) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to update a comment'
        ]);
        exit;
    }
    
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!$data) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON data'
        ]);
        exit;
    }
    
    // Validate required fields
    if (!isset($data['comment_id']) || !isset($data['content'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment ID and content are required'
        ]);
        exit;
    }
    
    $comment_id = intval($data['comment_id']);
    $content = trim($data['content']);
    $is_approved = isset($data['is_approved']) ? (bool)$data['is_approved'] : null;
    
    // Validate content
    if (empty($content)) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment content cannot be empty'
        ]);
        exit;
    }
    
    $apiLogger->debug("Updating comment ID: $comment_id by user ID: $user_id");
    
    try {
        // Check if comment exists and belongs to user (or user is admin)
        $stmt = $conn->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Comment not found'
            ]);
            exit;
        }
        
        $comment_user_id = $result->fetch_assoc()['user_id'];
        
        // Check if user has permission to update this comment
        if ($comment_user_id !== $user_id && !$isAdmin) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to update this comment'
            ]);
            exit;
        }
        
        // Update comment
        if ($isAdmin && $is_approved !== null) {
            // Admin can update content and approval status
            $stmt = $conn->prepare("
                UPDATE comments
                SET content = ?, is_approved = ?
                WHERE comment_id = ?
            ");
            $stmt->bind_param("sii", $content, $is_approved, $comment_id);
        } else {
            // Regular users can only update content
            $stmt = $conn->prepare("
                UPDATE comments
                SET content = ?
                WHERE comment_id = ?
            ");
            $stmt->bind_param("si", $content, $comment_id);
        }
        
        $stmt->execute();
        
        // Get the updated comment
        $stmt = $conn->prepare("
            SELECT c.*, u.username, u.profile_picture
            FROM comments c
            JOIN users u ON c.user_id = u.user_id
            WHERE c.comment_id = ?
        ");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $comment = $stmt->get_result()->fetch_assoc();
        
        // Remove sensitive data
        unset($comment['email']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment updated successfully',
            'data' => $comment
        ]);
    } catch (Exception $e) {
        $apiLogger->error("Error updating comment: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error updating comment: ' . $e->getMessage()
        ]);
    }
}

// Handle DELETE requests
else if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Check if user is logged in
    if (!$isLoggedIn) {
        echo json_encode([
            'success' => false,
            'message' => 'You must be logged in to delete a comment'
        ]);
        exit;
    }
    
    // Get comment ID from query string
    if (!isset($_GET['id'])) {
        echo json_encode([
            'success' => false,
            'message' => 'Comment ID is required'
        ]);
        exit;
    }
    
    $comment_id = intval($_GET['id']);
    $apiLogger->debug("Deleting comment ID: $comment_id by user ID: $user_id");
    
    try {
        // Check if comment exists and belongs to user (or user is admin)
        $stmt = $conn->prepare("SELECT user_id FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'Comment not found'
            ]);
            exit;
        }
        
        $comment_user_id = $result->fetch_assoc()['user_id'];
        
        // Check if user has permission to delete this comment
        if ($comment_user_id !== $user_id && !$isAdmin) {
            echo json_encode([
                'success' => false,
                'message' => 'You do not have permission to delete this comment'
            ]);
            exit;
        }
        
        // Delete comment (and all replies due to ON DELETE CASCADE)
        $stmt = $conn->prepare("DELETE FROM comments WHERE comment_id = ?");
        $stmt->bind_param("i", $comment_id);
        $stmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'Comment deleted successfully'
        ]);
    } catch (Exception $e) {
        $apiLogger->error("Error deleting comment: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'message' => 'Error deleting comment: ' . $e->getMessage()
        ]);
    }
}

// Handle unsupported methods
else {
    echo json_encode([
        'success' => false,
        'message' => 'Unsupported request method'
    ]);
}