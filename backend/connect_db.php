<?php
require_once 'utils/logger.php';

// Initialize logger
$logger = new Logger('database');

// Try to load configuration from INI file
$config = @parse_ini_file('/var/www/private/db-config.ini');

// If INI file can't be read, use default configuration
if ($config === false) {
    $logger->warning("Failed to read database configuration file, using default values");
    
    // Default configuration for development
    $config = [
        'servername' => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname' => 'blog_db'
    ];
} else {
    $logger->info("Successfully loaded database configuration from INI file");
    
    // Check if all required configuration values exist
    $required_keys = ['servername', 'username', 'password'];
    foreach ($required_keys as $key) {
        if (!array_key_exists($key, $config)) {
            $logger->warning("Missing required configuration key: $key, using default value");
            
            // If key is missing, use default value
            switch ($key) {
                case 'servername':
                    $config[$key] = 'localhost';
                    break;
                case 'username':
                    $config[$key] = 'root';
                    break;
                case 'password':
                    $config[$key] = '';
                    break;
            }
        }
    }
    
    // Override dbname to ensure correct database is used
    $config['dbname'] = 'blog_db';
    $logger->info("Using database: {$config['dbname']}");
}

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
