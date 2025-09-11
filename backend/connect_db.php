<?php
require_once 'utils/logger.php';

// Initialize logger
$logger = new Logger('database');

// Define possible configuration file locations
$config_locations = [
    '/var/www/private/db-config.ini',  // Production location (secure)
    __DIR__ . '/../config/db-config.ini', // Alternative location
    __DIR__ . '/../db-config.ini'      // Fallback location
];

// Try to load configuration from one of the INI files
$config = false;
$loaded_file = '';

foreach ($config_locations as $location) {
    if (file_exists($location) && is_readable($location)) {
        $config = @parse_ini_file($location);
        if ($config !== false) {
            $loaded_file = $location;
            break;
        }
    }
}

// If no INI file can be read, use default configuration
if ($config === false) {
    $logger->warning("Failed to read database configuration file from any location, using default values");
    
    // Default configuration for development
    $config = [
        'servername' => 'localhost',
        'username' => 'root',
        'password' => '',
        'dbname' => 'blog_db'
    ];
} else {
    $logger->info("Successfully loaded database configuration from: $loaded_file");
    
    // Check if all required configuration values exist
    $required_keys = ['servername', 'username', 'password', 'dbname'];
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
                case 'dbname':
                    $config[$key] = 'blog_db';
                    break;
            }
        }
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
