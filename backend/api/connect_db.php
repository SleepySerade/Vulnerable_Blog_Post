<?php
function connectDB() {
    try {
        // Load configuration from INI file
        $config = parse_ini_file('/var/www/private/db-config.ini');
        if ($config === false) {
            throw new Exception("Failed to read database configuration file");
        }

        // Check if all required configuration values exist
        $required_keys = ['servername', 'username', 'password', 'dbname'];
        foreach ($required_keys as $key) {
            if (!array_key_exists($key, $config)) {
                throw new Exception("Missing required configuration key: $key");
            }
        }

        // Create connection
        $conn = new mysqli(
            $config['servername'],
            $config['username'],
            $config['password'],
            $config['dbname']
        );

        // Check connection
        if ($conn->connect_error) {
            throw new Exception("Connection failed: " . $conn->connect_error);
        }

        // Set charset to utf8mb4
        if (!$conn->set_charset("utf8mb4")) {
            throw new Exception("Error setting charset utf8mb4: " . $conn->error);
        }

        return $conn;
    } catch (Exception $e) {
        // Log error (in production, use proper logging)
        error_log("Database connection error: " . $e->getMessage());
        
        // In production, show generic error
        die("Failed to connect to the database. Please try again later.");
    }
}

// Create database connection
$conn = connectDB();

// Example db-config.ini structure:
/*
[database]
servername = localhost
username = your_username
password = your_password
dbname = blog_db
*/