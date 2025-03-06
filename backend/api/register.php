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
    error_log("Headers already sent in register.php");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the logger
require_once __DIR__ . '/../utils/logger.php';
$apiLogger = new Logger('api_register');
$apiLogger->info("Registration API endpoint called");

// Include the auth file
try {
    require_once '../auth.php';
} catch (Exception $e) {
    $apiLogger->error("Failed to include auth.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $apiLogger->warning("Invalid request method: " . $_SERVER['REQUEST_METHOD']);
    http_response_code(405); // Method Not Allowed
    echo json_encode(['success' => false, 'message' => 'Only POST method is allowed']);
    exit;
}

// Get and validate input
$apiLogger->debug("Parsing request body");
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $apiLogger->warning("JSON parsing failed, falling back to POST data");
    // If JSON parsing fails, try to get data from POST
    $input = $_POST;
}

$errors = [];
$username = trim($input['username'] ?? '');
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$confirm_password = $input['confirm_password'] ?? '';

// Validate input
if (empty($username)) {
    $errors[] = "Username is required";
} elseif (strlen($username) < 3) {
    $errors[] = "Username must be at least 3 characters long";
}

if (empty($email)) {
    $errors[] = "Email is required";
} elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Invalid email format";
}

if (empty($password)) {
    $errors[] = "Password is required";
} elseif (strlen($password) < 8) {
    $errors[] = "Password must be at least 8 characters long";
}

if ($password !== $confirm_password) {
    $errors[] = "Passwords do not match";
}

// If validation errors, return them
if (!empty($errors)) {
    $apiLogger->warning("Validation failed: " . implode(", ", $errors));
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Attempt registration
$apiLogger->info("Validation passed, attempting registration for username: $username");
$result = register($username, $email, $password);

// Return the result
if ($result['success']) {
    $apiLogger->info("Registration successful for username: $username");
    
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
        $apiLogger->debug("Started new session");
    }
    
    // Set session variable for success message on login page
    $_SESSION['registration_success'] = true;
    $apiLogger->debug("Set registration_success session variable");
    
    http_response_code(201); // Created
    echo json_encode(['success' => true, 'message' => 'Registration successful']);
} else {
    $apiLogger->warning("Registration failed: " . $result['message']);
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => $result['message']]);
}

$apiLogger->info("Registration API request completed");