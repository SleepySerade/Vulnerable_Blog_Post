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
    header('Access-Control-Allow-Methods: POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
} else {
    error_log("Headers already sent in upload.php");
}

// Include database connection
require_once '../connect_db.php';

// Include logger
require_once '../utils/logger.php';

// Include admin functions
require_once '../admin.php';

// Create logger instance
$apiLogger = new Logger('api_upload', Logger::DEBUG);
$apiLogger->debug("Upload API called with method: " . $_SERVER['REQUEST_METHOD']);

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

// Check if user is an admin
$admin_check = is_admin($user_id);
if (!$admin_check['success'] || !$admin_check['data']['is_admin']) {
    $apiLogger->warning("Unauthorized upload attempt by non-admin user ID: $user_id");
    echo json_encode([
        'success' => false,
        'message' => 'Admin access required for file uploads'
    ]);
    exit;
}

// Log the admin access
$apiLogger->info("Admin user (ID: $user_id, Role: {$admin_check['data']['role']}) accessing upload functionality");

// Handle POST request (file upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Check if file was uploaded
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('No file uploaded or upload error: ' . $_FILES['image']['error']);
        }
        
        $file = $_FILES['image'];
        $apiLogger->debug("File upload: " . $file['name'] . " (" . $file['size'] . " bytes)");
        
        // Validate file type - only check file extension instead of actual MIME type
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedExtensions)) {
            throw new Exception('Invalid file extension. Allowed extensions: ' . implode(', ', $allowedExtensions));
        }
        
        // Get file type but don't validate it - this introduces a vulnerability
        $fileType = mime_content_type($file['tmp_name']);
        $apiLogger->debug("File type detected: $fileType (not validated)");
        
        // Validate file size (max 5MB)
        $maxSize = 5 * 1024 * 1024; // 5MB
        if ($file['size'] > $maxSize) {
            throw new Exception('File size exceeds the maximum allowed size (5MB)');
        }
        
        // Create uploads directory if it doesn't exist
        // Use a relative path from the current file
        $uploadsDir = dirname(dirname(dirname(__FILE__))) . '/uploads/images';
        $apiLogger->debug("Upload directory: $uploadsDir");
        
        // Log the current directory and file for debugging
        $apiLogger->debug("Current file: " . __FILE__);
        $apiLogger->debug("Current directory: " . __DIR__);
        $apiLogger->debug("Document root: " . $_SERVER['DOCUMENT_ROOT']);
        
        if (!file_exists($uploadsDir)) {
            if (!mkdir($uploadsDir, 0755, true)) {
                throw new Exception('Failed to create uploads directory: ' . $uploadsDir);
            }
        }
        
        // Check if directory is writable
        if (!is_writable($uploadsDir)) {
            throw new Exception('Uploads directory is not writable: ' . $uploadsDir);
        }
        
        // Generate a unique filename - keep the original extension without sanitizing
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('img_') . '_' . time() . '.' . $extension;
        
        // This is vulnerable because we're not sanitizing the extension
        // An attacker could upload file.php.jpg which passes the extension check
        // but might be executed as PHP depending on server configuration
        $filepath = $uploadsDir . '/' . $filename;
        
        // Move the uploaded file
        $apiLogger->debug("Moving uploaded file from {$file['tmp_name']} to $filepath");
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            $error = error_get_last();
            throw new Exception('Failed to move uploaded file: ' . ($error ? $error['message'] : 'Unknown error'));
        }
        
        // Generate the URL for the uploaded file
        // Use a web-accessible path
        $fileUrl = '/uploads/images/' . $filename;
        $apiLogger->debug("File URL: $fileUrl");
        
        // Log the successful upload
        $apiLogger->info("File uploaded successfully: $fileUrl");
        
        // Return success response
        echo json_encode([
            'success' => true,
            'message' => 'File uploaded successfully',
            'data' => [
                'url' => $fileUrl,
                'filename' => $filename,
                'size' => $file['size'],
                'type' => $fileType
            ]
        ]);
    } catch (Exception $e) {
        $apiLogger->error("Upload error: " . $e->getMessage());
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