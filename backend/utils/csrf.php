<?php
/**
 * CSRF Protection Utility
 * 
 * This file provides functions for generating and validating CSRF tokens
 * to protect forms against Cross-Site Request Forgery attacks.
 */

/**
 * Generate a new CSRF token and store it in the session
 * 
 * @param string $form_name Optional form name to generate specific token
 * @return string The generated CSRF token
 */
function generate_csrf_token($form_name = 'default') {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Generate a random token
    $token = bin2hex(random_bytes(32));
    
    // Store the token in the session
    $_SESSION['csrf_tokens'][$form_name] = [
        'token' => $token,
        'time' => time()
    ];
    
    return $token;
}

/**
 * Validate a CSRF token
 * 
 * @param string $token The token to validate
 * @param string $form_name Optional form name to validate specific token
 * @param int $expiry_time Optional token expiry time in seconds (default: 3600 = 1 hour)
 * @return bool True if token is valid, false otherwise
 */
function validate_csrf_token($token, $form_name = 'default', $expiry_time = 3600) {
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if token exists in session
    if (!isset($_SESSION['csrf_tokens'][$form_name])) {
        return false;
    }
    
    $stored_token = $_SESSION['csrf_tokens'][$form_name]['token'];
    $token_time = $_SESSION['csrf_tokens'][$form_name]['time'];
    
    // Check if token has expired
    if (time() - $token_time > $expiry_time) {
        // Remove expired token
        unset($_SESSION['csrf_tokens'][$form_name]);
        return false;
    }
    
    // Validate token
    if (hash_equals($stored_token, $token)) {
        // Remove used token (one-time use)
        unset($_SESSION['csrf_tokens'][$form_name]);
        return true;
    }
    
    return false;
}

/**
 * Output a CSRF token input field for a form
 * 
 * @param string $form_name Optional form name
 * @return string HTML input field with CSRF token
 */
function csrf_token_input($form_name = 'default') {
    $token = generate_csrf_token($form_name);
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Check if a request has a valid CSRF token
 * 
 * @param string $form_name Optional form name
 * @param int $expiry_time Optional token expiry time in seconds
 * @return bool True if request has valid token, false otherwise
 */
function check_csrf_token($form_name = 'default', $expiry_time = 3600) {
    // Check if token is present in POST data
    if (!isset($_POST['csrf_token'])) {
        return false;
    }
    
    return validate_csrf_token($_POST['csrf_token'], $form_name, $expiry_time);
}
