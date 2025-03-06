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
    error_log("Headers already sent in categories.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Create logger instance
$apiLogger = new Logger('api', Logger::DEBUG);
$apiLogger->debug("Categories API called with method: " . $_SERVER['REQUEST_METHOD']);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    // Just exit with 200 OK status
    http_response_code(200);
    exit;
}

// Handle GET request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $apiLogger->debug("Categories API GET request");
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Failed to retrieve categories',
        'data' => []
    ];
    
    try {
        // Get all categories
        $stmt = $conn->prepare("
            SELECT category_id, name, description
            FROM categories
            ORDER BY name ASC
        ");
        $stmt->execute();
        $result = $stmt->get_result();
        
        $categories = [];
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }
        
        $response = [
            'success' => true,
            'message' => 'Categories retrieved successfully',
            'data' => $categories
        ];
    } catch (Exception $e) {
        $apiLogger->error("Categories API error: " . $e->getMessage());
        $response = [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
    
    // Return JSON response
    echo json_encode($response);
    exit;
}

// Handle POST request (for creating/updating categories)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    // Default response
    $response = [
        'success' => false,
        'message' => 'Invalid request'
    ];
    
    // Check if user is logged in and is an admin
    session_start();
    if (!isset($_SESSION['user_id'])) {
        $response['message'] = 'User not logged in';
        echo json_encode($response);
        exit;
    }
    
    $user_id = $_SESSION['user_id'];
    
    // Check if user is admin
    $stmt = $conn->prepare("
        SELECT admin_id FROM admins WHERE user_id = ?
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $is_admin = $stmt->get_result()->num_rows > 0;
    
    if (!$is_admin) {
        $response['message'] = 'You do not have permission to manage categories';
        echo json_encode($response);
        exit;
    }
    
    try {
        // Check if action is provided
        if (!isset($data['action'])) {
            throw new Exception('Action is required');
        }
        
        // Handle different actions
        switch ($data['action']) {
            case 'create':
                // Create a new category
                if (!isset($data['name'])) {
                    throw new Exception('Category name is required');
                }
                
                $name = $data['name'];
                $description = isset($data['description']) ? $data['description'] : null;
                
                // Check if category already exists
                $stmt = $conn->prepare("
                    SELECT category_id FROM categories WHERE name = ?
                ");
                $stmt->bind_param('s', $name);
                $stmt->execute();
                if ($stmt->get_result()->num_rows > 0) {
                    throw new Exception('Category already exists');
                }
                
                // Insert category
                $stmt = $conn->prepare("
                    INSERT INTO categories (name, description)
                    VALUES (?, ?)
                ");
                $stmt->bind_param('ss', $name, $description);
                $stmt->execute();
                
                $category_id = $conn->insert_id;
                
                $response = [
                    'success' => true,
                    'message' => 'Category created successfully',
                    'data' => [
                        'category_id' => $category_id,
                        'name' => $name,
                        'description' => $description
                    ]
                ];
                break;
                
            case 'update':
                // Update an existing category
                if (!isset($data['category_id']) || !isset($data['name'])) {
                    throw new Exception('Category ID and name are required');
                }
                
                $category_id = intval($data['category_id']);
                $name = $data['name'];
                $description = isset($data['description']) ? $data['description'] : null;
                
                // Check if category exists
                $stmt = $conn->prepare("
                    SELECT category_id FROM categories WHERE category_id = ?
                ");
                $stmt->bind_param('i', $category_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Category not found');
                }
                
                // Update category
                $stmt = $conn->prepare("
                    UPDATE categories
                    SET name = ?, description = ?
                    WHERE category_id = ?
                ");
                $stmt->bind_param('ssi', $name, $description, $category_id);
                $stmt->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Category updated successfully',
                    'data' => [
                        'category_id' => $category_id,
                        'name' => $name,
                        'description' => $description
                    ]
                ];
                break;
                
            case 'delete':
                // Delete a category
                if (!isset($data['category_id'])) {
                    throw new Exception('Category ID is required');
                }
                
                $category_id = intval($data['category_id']);
                
                // Check if category exists
                $stmt = $conn->prepare("
                    SELECT category_id FROM categories WHERE category_id = ?
                ");
                $stmt->bind_param('i', $category_id);
                $stmt->execute();
                if ($stmt->get_result()->num_rows === 0) {
                    throw new Exception('Category not found');
                }
                
                // Delete category
                $stmt = $conn->prepare("
                    DELETE FROM categories
                    WHERE category_id = ?
                ");
                $stmt->bind_param('i', $category_id);
                $stmt->execute();
                
                $response = [
                    'success' => true,
                    'message' => 'Category deleted successfully'
                ];
                break;
                
            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        $apiLogger->error("Categories API error: " . $e->getMessage());
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