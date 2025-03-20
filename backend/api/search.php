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
        if (ob_get_length()) {
            ob_clean();
        }
        
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
    if (ob_get_length()) {
        ob_clean();
    }
    
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
    header('Access-Control-Allow-Methods: GET, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
} else {
    error_log("Headers already sent in search.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api_search', Logger::DEBUG);
$apiLogger->debug("Search API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Handle GET request (search)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        // Check if query parameter is provided
        if (!isset($_GET['query']) || empty(trim($_GET['query']))) {
            throw new Exception('Search query is required');
        }
        
        // Sanitize and prepare the search query
        $searchQuery = trim($_GET['query']);
        $searchQuery = "%$searchQuery%";
        
        $apiLogger->debug("Searching for: " . $searchQuery);
        
        // Prepare the statement
        $stmt = $conn->prepare("
            SELECT p.post_id, p.title, p.content, p.featured_image, p.created_at, 
                   u.username as author_name, c.name as category_name
            FROM blog_posts p
            JOIN users u ON p.author_id = u.user_id
            LEFT JOIN categories c ON p.category_id = c.category_id
            WHERE (p.title LIKE ? OR p.content LIKE ?)
            AND p.status = 'published'
            ORDER BY p.created_at DESC
        ");
        
        if (!$stmt) {
            throw new Exception("SQL prepare failed: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $searchQuery, $searchQuery);
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Fetch all matching rows
        $search_results = [];
        while ($row = $result->fetch_assoc()) {
            // Process content for excerpt
            $plain_content = strip_tags($row['content']);
            $row['excerpt'] = substr($plain_content, 0, 150) . '...';
            
            // Add to results array
            $search_results[] = $row;
        }
        
        $apiLogger->info("Search completed with " . count($search_results) . " results");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'Search completed successfully',
            'data' => $search_results
        ]);
    } catch (Exception $e) {
        $apiLogger->error("Search error: " . $e->getMessage());
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