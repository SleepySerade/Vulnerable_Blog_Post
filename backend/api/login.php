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
    error_log("Headers already sent in login.php");
}

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Include the logger
require_once __DIR__ . '/../utils/logger.php';
$apiLogger = new Logger('api_login');
$apiLogger->info("Login API endpoint called");

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
$password = $input['password'] ?? '';

// Validate input
if (empty($username)) {
    $errors[] = "Username is required";
}
if (empty($password)) {
    $errors[] = "Password is required";
}

// If validation errors, return them
if (!empty($errors)) {
    $apiLogger->warning("Validation failed: " . implode(", ", $errors));
    http_response_code(400); // Bad Request
    echo json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]);
    exit;
}

// Attempt login
$apiLogger->info("Validation passed, attempting login for username: $username");
$result = login($username, $password);

// Return the result
if ($result['success']) {
    $apiLogger->info("Login successful for username: $username (ID: {$result['data']['user_id']})");
    
    // Force a new session to be started
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
        $apiLogger->debug("Closed existing session");
    }
    
    // Configure session parameters
    ini_set('session.use_only_cookies', 1);
    ini_set('session.use_strict_mode', 1);
    
    // Set session cookie parameters
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => false,
        'httponly' => true,
        'samesite' => 'Lax'
    ]);
    
    // Start the session
    session_start();
    $apiLogger->debug("Started new session: " . session_id());
    
    // Set session variables
    $_SESSION['user_id'] = $result['data']['user_id'];
    $_SESSION['username'] = $result['data']['username'];
    $_SESSION['logged_in'] = true;
    $_SESSION['login_time'] = time();
    $apiLogger->debug("Set session variables for user ID: {$result['data']['user_id']} in session: " . session_id());
    
    // Force session data to be saved
    session_write_close();
    $apiLogger->debug("Session data saved and closed");
    
    // Set a cookie with the session ID
    setcookie(session_name(), session_id(), 0, '/', '', false, true);
    $apiLogger->debug("Set session cookie: " . session_name() . "=" . session_id());
    
    // Check if user is admin
    $isAdmin = false;
    try {
        $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
        $stmt->bind_param("i", $result['data']['user_id']);
        $stmt->execute();
        $isAdmin = $stmt->get_result()->num_rows > 0;
        $apiLogger->debug("Checked admin status for user ID: {$result['data']['user_id']}, isAdmin: " . ($isAdmin ? 'true' : 'false'));
    } catch (Exception $e) {
        $apiLogger->error("Error checking admin status: " . $e->getMessage());
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Login successful',
        'user' => [
            'user_id' => $result['data']['user_id'],
            'username' => $result['data']['username'],
            'is_admin' => $isAdmin
        ]
    ]);
} else {
    $apiLogger->warning("Login failed: " . $result['message']);
    http_response_code(401); // Unauthorized
    echo json_encode(['success' => false, 'message' => $result['message']]);
}

$apiLogger->info("Login API request completed");