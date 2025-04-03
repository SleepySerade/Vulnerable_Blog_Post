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
    header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
} else {
    error_log("Headers already sent in tags.php");
}

// Include database connection
require_once '../connect_db.php';

// Include tag functions
require_once '../tags.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api', Logger::DEBUG);
$apiLogger->debug("Tags API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'all';
    $apiLogger->debug("Tags API action: " . $action);
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Invalid action',
        'data' => []
    ];
    
    try {
        // Handle different actions
        switch ($action) {
            case 'all':
                // Get all tags
                $result = getAllTags();
                $response = $result;
                break;
                
            case 'post_tags':
                // Get tags for a specific post
                if (!isset($_GET['post_id'])) {
                    throw new Exception('Post ID is required');
                }
                
                $postId = intval($_GET['post_id']);
                $result = getPostTags($postId);
                $response = $result;
                break;
                
            case 'posts_by_tag':
                // Get posts with a specific tag
                if (!isset($_GET['tag_id'])) {
                    throw new Exception('Tag ID is required');
                }
                
                $tagId = intval($_GET['tag_id']);
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
                
                $result = getPostsByTag($tagId, $limit, $offset);
                $response = $result;
                break;
                
            case 'search':
                // Search for tags
                if (!isset($_GET['query'])) {
                    throw new Exception('Search query is required');
                }
                
                $query = $_GET['query'];
                $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
                
                $result = searchTags($query, $limit);
                $response = $result;
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $apiLogger->error("Tags API error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// Handle POST request (for adding tags to posts)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];
    
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in';
        echo json_encode($response);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        // Check if action is provided
        if (!isset($data['action'])) {
            throw new Exception('Action is required');
        }
        
        // Handle different actions
        switch ($data['action']) {
            case 'add_tag':
                // Add a tag to a post
                if (!isset($data['post_id']) || !isset($data['tag_name'])) {
                    throw new Exception('Post ID and tag name are required');
                }
                
                $postId = intval($data['post_id']);
                $tagName = $data['tag_name'];
                
                // Check if user is the author or an admin
                $stmt = $conn->prepare("
                    SELECT author_id FROM blog_posts WHERE post_id = ?
                ");
                $stmt->bind_param('i', $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Post not found');
                }
                
                $post = $result->fetch_assoc();
                
                // Check if user is admin
                $stmt = $conn->prepare("
                    SELECT admin_id FROM admins WHERE user_id = ?
                ");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $is_admin = $stmt->get_result()->num_rows > 0;
                
                if ($post['author_id'] != $user_id && !$is_admin) {
                    throw new Exception('You do not have permission to add tags to this post');
                }
                
                $result = addTagToPost($postId, $tagName);
                $response = $result;
                break;
                
            case 'add_tags':
                // Add multiple tags to a post
                if (!isset($data['post_id']) || !isset($data['tag_names']) || !is_array($data['tag_names'])) {
                    throw new Exception('Post ID and tag names array are required');
                }
                
                $postId = intval($data['post_id']);
                $tagNames = $data['tag_names'];
                
                // Check if user is the author or an admin
                $stmt = $conn->prepare("
                    SELECT author_id FROM blog_posts WHERE post_id = ?
                ");
                $stmt->bind_param('i', $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Post not found');
                }
                
                $post = $result->fetch_assoc();
                
                // Check if user is admin
                $stmt = $conn->prepare("
                    SELECT admin_id FROM admins WHERE user_id = ?
                ");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $is_admin = $stmt->get_result()->num_rows > 0;
                
                if ($post['author_id'] != $user_id && !$is_admin) {
                    throw new Exception('You do not have permission to add tags to this post');
                }
                
                $result = addTagsToPost($postId, $tagNames);
                $response = $result;
                break;
                
            case 'update_tags':
                // Update all tags for a post
                if (!isset($data['post_id']) || !isset($data['tag_names']) || !is_array($data['tag_names'])) {
                    throw new Exception('Post ID and tag names array are required');
                }
                
                $postId = intval($data['post_id']);
                $tagNames = $data['tag_names'];
                
                // Check if user is the author or an admin
                $stmt = $conn->prepare("
                    SELECT author_id FROM blog_posts WHERE post_id = ?
                ");
                $stmt->bind_param('i', $postId);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Post not found');
                }
                
                $post = $result->fetch_assoc();
                
                // Check if user is admin
                $stmt = $conn->prepare("
                    SELECT admin_id FROM admins WHERE user_id = ?
                ");
                $stmt->bind_param('i', $user_id);
                $stmt->execute();
                $is_admin = $stmt->get_result()->num_rows > 0;
                
                if ($post['author_id'] != $user_id && !$is_admin) {
                    throw new Exception('You do not have permission to update tags for this post');
                }
                
                $result = updatePostTags($postId, $tagNames);
                $response = $result;
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $apiLogger->error("Tags API error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// Handle DELETE request (for removing tags from posts)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    // Get query parameters
    $postId = isset($_GET['post_id']) ? intval($_GET['post_id']) : null;
    $tagId = isset($_GET['tag_id']) ? intval($_GET['tag_id']) : null;
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];
    
    // Check if user is logged in
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in';
        echo json_encode($response);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    try {
        if (!$postId || !$tagId) {
            throw new Exception('Post ID and Tag ID are required');
        }
        
        // Check if user is the author or an admin
        $stmt = $conn->prepare("
            SELECT author_id FROM blog_posts WHERE post_id = ?
        ");
        $stmt->bind_param('i', $postId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception('Post not found');
        }
        
        $post = $result->fetch_assoc();
        
        // Check if user is admin
        $stmt = $conn->prepare("
            SELECT admin_id FROM admins WHERE user_id = ?
        ");
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $is_admin = $stmt->get_result()->num_rows > 0;
        
        if ($post['author_id'] != $user_id && !$is_admin) {
            throw new Exception('You do not have permission to remove tags from this post');
        }
        
        $result = removeTagFromPost($postId, $tagId);
        $response = $result;
        
    } catch (Exception $e) {
        $apiLogger->error("Tags API error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// If we get here, it's an unsupported method
$response = [
    'success' => false,
    'message' => 'Unsupported request method'
];

echo json_encode($response);