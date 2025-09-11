<?php
require_once 'utils/logger.php';

// Initialize logger
$logger = new Logger('database');

// Load configuration from INI file - no fallback to root
$config = @parse_ini_file('/var/www/private/db-config.ini');

// If INI file can't be read, throw an error
if ($config === false) {
    $logger->error("Failed to read database configuration file from /var/www/private/db-config.ini");
    throw new Exception("Database configuration file not found or not readable. Please ensure the file exists and has correct permissions.");
}

$logger->info("Successfully loaded database configuration from INI file");

// Check if all required configuration values exist
$required_keys = ['servername', 'username', 'password', 'dbname'];
foreach ($required_keys as $key) {
    if (!array_key_exists($key, $config)) {
        $logger->error("Missing required configuration key: $key in database configuration file");
        throw new Exception("Missing required configuration key: $key in database configuration file");
    }
}

$logger->info("Using database: {$config['dbname']}");

// Create connection
$logger->info("Attempting to connect to database at {$config['servername']}");
try {
    $conn = new mysqli(
        $config['servername'],
        $config['username'],
        $config['password'],
        $config['dbname']
    );

    // Check connection
    if ($conn->connect_error) {
        $logger->error("Database connection failed: " . $conn->connect_error);
        throw new Exception("Connection failed: " . $conn->connect_error);
    }
    
    $logger->info("Successfully connected to database");

    // Set charset to utf8mb4
    if (!$conn->set_charset("utf8")) {
        $logger->error("Error setting charset: " . $conn->error);
        throw new Exception("Error setting charset utf8 " . $conn->error);
    }
    
    $logger->debug("Character set set to: " . $conn->character_set_name());
} catch (Exception $e) {
    $logger->error("Database connection error: " . $e->getMessage());
    throw $e; // Re-throw the exception
}
