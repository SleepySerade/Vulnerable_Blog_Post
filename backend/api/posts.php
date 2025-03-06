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
    error_log("Headers already sent in posts.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api', Logger::DEBUG);
$apiLogger->debug("Posts API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : 'all';
    $apiLogger->debug("Posts API action: " . $action);
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Invalid action',
        'data' => []
    ];
    
    try {
        // Handle different actions
        switch ($action) {
            case 'featured':
                // Get featured posts (status = published, limit to 3)
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.status = 'published'
                    ORDER BY p.created_at DESC
                    LIMIT 3
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Featured posts retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            case 'recent':
                // Get recent posts (status = published, limit to 6)
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.status = 'published'
                    ORDER BY p.created_at DESC
                    LIMIT 6
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Recent posts retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            case 'all':
                // Get all posts (status = published)
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.status = 'published'
                    ORDER BY p.created_at DESC
                ");
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'All posts retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            case 'single':
                // Get a single post by ID
                if (!isset($_GET['id'])) {
                    throw new Exception('Post ID is required');
                }
                
                $post_id = intval($_GET['id']);
                
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.post_id = ? AND p.status = 'published'
                ");
                $stmt->bind_param('i', $post_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    throw new Exception('Post not found');
                }
                
                $post = $result->fetch_assoc();
                
                // Increment views count
                $stmt = $conn->prepare("
                    UPDATE blog_posts
                    SET views_count = views_count + 1
                    WHERE post_id = ?
                ");
                $stmt->bind_param('i', $post_id);
                $stmt->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Post retrieved successfully',
                    'data' => $post
                ];
                break;
                
            case 'by_category':
                // Get posts by category
                if (!isset($_GET['category_id'])) {
                    throw new Exception('Category ID is required');
                }
                
                $category_id = intval($_GET['category_id']);
                
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.category_id = ? AND p.status = 'published'
                    ORDER BY p.created_at DESC
                ");
                $stmt->bind_param('i', $category_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Posts by category retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            case 'by_author':
                // Get posts by author
                if (!isset($_GET['author_id'])) {
                    throw new Exception('Author ID is required');
                }
                
                $author_id = intval($_GET['author_id']);
                
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE p.author_id = ? AND p.status = 'published'
                    ORDER BY p.created_at DESC
                ");
                $stmt->bind_param('i', $author_id);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Posts by author retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            case 'search':
                // Search posts
                if (!isset($_GET['query'])) {
                    throw new Exception('Search query is required');
                }
                
                $query = '%' . $_GET['query'] . '%';
                
                $stmt = $conn->prepare("
                    SELECT p.*, u.username as author_name, c.name as category_name
                    FROM blog_posts p
                    JOIN users u ON p.author_id = u.user_id
                    LEFT JOIN categories c ON p.category_id = c.category_id
                    WHERE (p.title LIKE ? OR p.content LIKE ?) AND p.status = 'published'
                    ORDER BY p.created_at DESC
                ");
                $stmt->bind_param('ss', $query, $query);
                $stmt->execute();
                $result = $stmt->get_result();
                
                $posts = [];
                while ($row = $result->fetch_assoc()) {
                    $posts[] = $row;
                }
                
                $response = [
                    'success' => true,
                    'message' => 'Search results retrieved successfully',
                    'data' => $posts
                ];
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $apiLogger->error("Posts API error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// Handle POST request (for creating/updating posts)
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
            case 'create':
                // Create a new post
                if (!isset($data['title']) || !isset($data['content'])) {
                    throw new Exception('Title and content are required');
                }
                
                $title = $data['title'];
                $content = $data['content'];
                $category_id = isset($data['category_id']) ? intval($data['category_id']) : null;
                $featured_image = isset($data['featured_image']) ? $data['featured_image'] : null;
                $status = isset($data['status']) ? $data['status'] : 'draft';
                
                // Validate status
                if (!in_array($status, ['draft', 'published', 'archived'])) {
                    throw new Exception('Invalid status');
                }
                
                // Insert post
                $stmt = $conn->prepare("
                    INSERT INTO blog_posts (title, content, author_id, category_id, featured_image, status)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->bind_param('ssiiss', $title, $content, $user_id, $category_id, $featured_image, $status);
                $stmt->execute();
                
                $post_id = $conn->insert_id;
                
                $response = [
                    'success' => true,
                    'message' => 'Post created successfully',
                    'data' => [
                        'post_id' => $post_id
                    ]
                ];
                break;
                
            case 'update':
                // Update an existing post
                if (!isset($data['post_id'])) {
                    throw new Exception('Post ID is required');
                }
                
                $post_id = intval($data['post_id']);
                
                // Check if user is the author or an admin
                $stmt = $conn->prepare("
                    SELECT author_id FROM blog_posts WHERE post_id = ?
                ");
                $stmt->bind_param('i', $post_id);
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
                
                if ($post['author_id'] !== $user_id && !$is_admin) {
                    throw new Exception('You do not have permission to update this post');
                }
                
                // Build update query
                $updates = [];
                $params = [];
                $types = '';
                
                if (isset($data['title'])) {
                    $updates[] = 'title = ?';
                    $params[] = $data['title'];
                    $types .= 's';
                }
                
                if (isset($data['content'])) {
                    $updates[] = 'content = ?';
                    $params[] = $data['content'];
                    $types .= 's';
                }
                
                if (isset($data['category_id'])) {
                    $updates[] = 'category_id = ?';
                    $params[] = intval($data['category_id']);
                    $types .= 'i';
                }
                
                if (isset($data['featured_image'])) {
                    $updates[] = 'featured_image = ?';
                    $params[] = $data['featured_image'];
                    $types .= 's';
                }
                
                if (isset($data['status'])) {
                    // Validate status
                    if (!in_array($data['status'], ['draft', 'published', 'archived'])) {
                        throw new Exception('Invalid status');
                    }
                    
                    $updates[] = 'status = ?';
                    $params[] = $data['status'];
                    $types .= 's';
                }
                
                if (empty($updates)) {
                    throw new Exception('No fields to update');
                }
                
                // Add post_id to params
                $params[] = $post_id;
                $types .= 'i';
                
                // Update post
                $stmt = $conn->prepare("
                    UPDATE blog_posts
                    SET " . implode(', ', $updates) . "
                    WHERE post_id = ?
                ");
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Post updated successfully'
                ];
                break;
                
            case 'delete':
                // Delete a post
                if (!isset($data['post_id'])) {
                    throw new Exception('Post ID is required');
                }
                
                $post_id = intval($data['post_id']);
                
                // Check if user is the author or an admin
                $stmt = $conn->prepare("
                    SELECT author_id FROM blog_posts WHERE post_id = ?
                ");
                $stmt->bind_param('i', $post_id);
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
                
                if ($post['author_id'] !== $user_id && !$is_admin) {
                    throw new Exception('You do not have permission to delete this post');
                }
                
                // Delete post
                $stmt = $conn->prepare("
                    DELETE FROM blog_posts
                    WHERE post_id = ?
                ");
                $stmt->bind_param('i', $post_id);
                $stmt->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Post deleted successfully'
                ];
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $apiLogger->error("Posts API error: " . $e->getMessage());
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