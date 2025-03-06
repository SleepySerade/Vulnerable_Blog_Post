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
    error_log("Headers already sent in logout.php");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the logger
require_once __DIR__ . '/../utils/logger.php';
$apiLogger = new Logger('api_logout');
$apiLogger->info("Logout API endpoint called");

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
    $apiLogger->debug("Started session");
}

// Log user information before logout
if (isset($_SESSION['user_id'])) {
    $apiLogger->info("Logging out user ID: {$_SESSION['user_id']}, username: {$_SESSION['username']}");
} else {
    $apiLogger->warning("Logout called but no user was logged in");
}

// Clear all session variables
$_SESSION = array();
$apiLogger->debug("Cleared session variables");

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    $apiLogger->debug("Destroyed session cookie");
}

// Destroy the session
session_destroy();
$apiLogger->debug("Destroyed session");

// Return success response
echo json_encode([
    'success' => true,
    'message' => 'Logout successful'
]);

$apiLogger->info("Logout API request completed");