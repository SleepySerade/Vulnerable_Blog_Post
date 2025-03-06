<?php
require_once 'connect_db.php';
require_once 'utils/logger.php';

// Initialize logger
$logger = new Logger('auth');

/**
 * Login function - Authenticates user credentials
 * @param string $username Username
 * @param string $password Password
 * @return array Response with success status and message
 */
function login($username, $password) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    $logger->info("Login attempt for username: $username");
    
    try {
        // Get user and salt information
        $stmt = $conn->prepare("
            SELECT u.user_id, u.username, u.password_hash, u.is_active, s.salt_value
            FROM users u
            JOIN user_salts s ON u.user_id = s.user_id
            WHERE u.username = ?
        ");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $logger->debug("User found: ID {$user['user_id']}");
            
            // Check if user is active
            if (!$user['is_active']) {
                $logger->warning("Login attempt for deactivated account: $username");
                $response['message'] = 'Account is deactivated';
                return $response;
            }
            
            // Verify password with salt
            if (password_verify($password . $user['salt_value'], $user['password_hash'])) {
                // Don't start session here, let the API endpoint handle it
                $logger->info("Successful login for user: $username (ID: {$user['user_id']})");
                
                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['data'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username']
                ];
            } else {
                $logger->warning("Invalid password for user: $username");
                $response['message'] = 'Invalid password';
            }
        } else {
            $logger->warning("Login attempt for non-existent user: $username");
            $response['message'] = 'User not found';
        }
    } catch (Exception $e) {
        $logger->error("Login error: " . $e->getMessage());
        $response['message'] = 'Login failed: ' . $e->getMessage();
    }
    
    return $response;
}

/**
 * Register function - Creates new user account
 * @param string $username Username
 * @param string $email Email address
 * @param string $password Password
 * @return array Response with success status and message
 */
function register($username, $email, $password) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => ''];
    
    $logger->info("Registration attempt for username: $username, email: $email");
    
    try {
        // Start transaction
        $conn->begin_transaction();
        $logger->debug("Started transaction for registration");
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $logger->warning("Registration failed: Username '$username' already exists");
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $logger->warning("Registration failed: Email '$email' already exists");
            throw new Exception('Email already exists');
        }
        
        // Generate salt
        $salt = bin2hex(random_bytes(32));
        $logger->debug("Generated salt for new user");
        
        // Hash password with salt
        $password_hash = password_hash($password . $salt, PASSWORD_DEFAULT);
        
        // Insert user
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        $stmt->execute();
        
        $user_id = $conn->insert_id;
        $logger->debug("Inserted new user with ID: $user_id");
        
        // Insert salt
        $stmt = $conn->prepare("
            INSERT INTO user_salts (user_id, salt_value)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $user_id, $salt);
        $stmt->execute();
        $logger->debug("Inserted salt for user ID: $user_id");
        
        // Commit transaction
        $conn->commit();
        $logger->debug("Committed transaction for registration");
        
        $logger->info("Registration successful for username: $username (ID: $user_id)");
        
        $response['success'] = true;
        $response['message'] = 'Registration successful';
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $logger->error("Registration error: " . $e->getMessage());
        $response['message'] = 'Registration failed: ' . $e->getMessage();
    }
    
    return $response;
}