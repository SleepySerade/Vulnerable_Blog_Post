<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the logger
require_once 'utils/logger.php';
$logger = new Logger('logout');
$logger->info("Logout page accessed");

session_start();

// Log user information before logout
if (isset($_SESSION['user_id'])) {
    $logger->info("Logging out user ID: {$_SESSION['user_id']}, username: {$_SESSION['username']}");
} else {
    $logger->warning("Logout called but no user was logged in");
}

// Clear all session variables
$_SESSION = array();
$logger->debug("Cleared session variables");

// Destroy the session cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 3600, '/');
    $logger->debug("Destroyed session cookie");
}

// Destroy the session
session_destroy();
$logger->debug("Destroyed session");

// Redirect to login page
$logger->info("Redirecting to login page");
header('Location: /public/login.php');
exit();