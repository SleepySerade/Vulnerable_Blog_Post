<?php
require_once 'connect_db.php';

/**
 * Login function - Authenticates user credentials
 * @param string $username Username
 * @param string $password Password
 * @return array Response with success status and message
 */
function login($username, $password) {
    global $conn;
    $response = ['success' => false, 'message' => '', 'data' => null];
    
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
            
            // Check if user is active
            if (!$user['is_active']) {
                $response['message'] = 'Account is deactivated';
                return $response;
            }
            
            // Verify password with salt
            if (password_verify($password . $user['salt_value'], $user['password_hash'])) {
                // Start session and store user data
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                
                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['data'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username']
                ];
            } else {
                $response['message'] = 'Invalid password';
            }
        } else {
            $response['message'] = 'User not found';
        }
    } catch (Exception $e) {
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
    global $conn;
    $response = ['success' => false, 'message' => ''];
    
    try {
        // Start transaction
        $conn->begin_transaction();
        
        // Check if username exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Username already exists');
        }
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            throw new Exception('Email already exists');
        }
        
        // Generate salt
        $salt = bin2hex(random_bytes(32));
        
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
        
        // Insert salt
        $stmt = $conn->prepare("
            INSERT INTO user_salts (user_id, salt_value) 
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $user_id, $salt);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response['success'] = true;
        $response['message'] = 'Registration successful';
        
    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        $response['message'] = 'Registration failed: ' . $e->getMessage();
    }
    
    return $response;
}