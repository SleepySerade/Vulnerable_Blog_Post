<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the logger
require_once 'utils/logger.php';
$setupLogger = new Logger('setup_db');
$setupLogger->info("Database setup script started");

// Include database connection
try {
    require_once 'connect_db.php';
    $setupLogger->info("Database connection established");
} catch (Exception $e) {
    $setupLogger->error("Failed to connect to database: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
    exit;
}

// Function to check if a table exists
function tableExists($conn, $tableName) {
    $result = $conn->query("SHOW TABLES LIKE '$tableName'");
    return $result->num_rows > 0;
}

// Create tables if they don't exist
try {
    $setupLogger->info("Checking and creating database tables");
    
    // Check if users table exists
    if (!tableExists($conn, 'users')) {
        $setupLogger->info("Creating users table");
        echo "Creating users table...<br>";
        $sql = "CREATE TABLE users (
            user_id INT PRIMARY KEY AUTO_INCREMENT,
            username VARCHAR(50) NOT NULL UNIQUE,
            email VARCHAR(100) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            profile_picture VARCHAR(255) NULL,
            bio TEXT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        if ($conn->query($sql) === TRUE) {
            $setupLogger->info("Users table created successfully");
            echo "Users table created successfully<br>";
        } else {
            $setupLogger->error("Error creating users table: " . $conn->error);
            throw new Exception("Error creating users table: " . $conn->error);
        }
    } else {
        $setupLogger->info("Users table already exists");
        echo "Users table already exists<br>";
    }
    
    // Check if user_salts table exists
    if (!tableExists($conn, 'user_salts')) {
        $setupLogger->info("Creating user_salts table");
        echo "Creating user_salts table...<br>";
        $sql = "CREATE TABLE user_salts (
            salt_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL UNIQUE,
            salt_value VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        if ($conn->query($sql) === TRUE) {
            $setupLogger->info("User_salts table created successfully");
            echo "User_salts table created successfully<br>";
        } else {
            $setupLogger->error("Error creating user_salts table: " . $conn->error);
            throw new Exception("Error creating user_salts table: " . $conn->error);
        }
    } else {
        $setupLogger->info("User_salts table already exists");
        echo "User_salts table already exists<br>";
    }
    
    // Check if admins table exists
    if (!tableExists($conn, 'admins')) {
        $setupLogger->info("Creating admins table");
        echo "Creating admins table...<br>";
        $sql = "CREATE TABLE admins (
            admin_id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT UNIQUE,
            role VARCHAR(50) NOT NULL DEFAULT 'editor',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci";
        
        if ($conn->query($sql) === TRUE) {
            $setupLogger->info("Admins table created successfully");
            echo "Admins table created successfully<br>";
        } else {
            $setupLogger->error("Error creating admins table: " . $conn->error);
            throw new Exception("Error creating admins table: " . $conn->error);
        }
    } else {
        $setupLogger->info("Admins table already exists");
        echo "Admins table already exists<br>";
    }
    
    $setupLogger->info("Database setup completed successfully");
    echo "Database setup completed successfully!";
    
} catch (Exception $e) {
    $setupLogger->error("Database setup error: " . $e->getMessage());
    echo "Error: " . $e->getMessage();
}