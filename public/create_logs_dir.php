<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Get the logs directory path
$logsDir = '/var/www/html/logs';

// Display the path
echo "<h2>Logs Directory Setup</h2>";
echo "<p>Logs directory path: <code>" . htmlspecialchars($logsDir) . "</code></p>";

// Check if logs directory exists
if (file_exists($logsDir)) {
    echo "<p>Logs directory already exists.</p>";
} else {
    echo "<p>Logs directory does not exist. Attempting to create it...</p>";
    
    // Try to create the directory
    if (mkdir($logsDir, 0777, true)) {
        echo "<p style='color:green;'>Successfully created logs directory.</p>";
    } else {
        echo "<p style='color:red;'>Failed to create logs directory.</p>";
    }
}

// Check if logs directory exists now
if (file_exists($logsDir)) {
    // Get current permissions
    $perms = substr(sprintf('%o', fileperms($logsDir)), -4);
    echo "<p>Current permissions: <code>" . $perms . "</code></p>";
    
    // Try to set permissions
    echo "<p>Attempting to set permissions to 0777...</p>";
    if (chmod($logsDir, 0777)) {
        $perms = substr(sprintf('%o', fileperms($logsDir)), -4);
        echo "<p style='color:green;'>Successfully set permissions to: <code>" . $perms . "</code></p>";
    } else {
        echo "<p style='color:red;'>Failed to set permissions.</p>";
    }
    
    // Create the specific logs.log file
    $logFile = $logsDir . '/logs.log';
    echo "<p>Attempting to create the logs.log file: <code>" . htmlspecialchars($logFile) . "</code></p>";
    
    if (file_put_contents($logFile, "Log file initialized on " . date('Y-m-d H:i:s') . "\n", FILE_APPEND)) {
        echo "<p style='color:green;'>Successfully created/updated logs.log file.</p>";
        
        // Set file permissions
        if (chmod($logFile, 0666)) {
            $perms = substr(sprintf('%o', fileperms($logFile)), -4);
            echo "<p style='color:green;'>Successfully set logs.log permissions to: <code>" . $perms . "</code></p>";
        } else {
            echo "<p style='color:red;'>Failed to set logs.log permissions.</p>";
        }
    } else {
        echo "<p style='color:red;'>Failed to create/update logs.log file.</p>";
    }
}

// Display server information
echo "<h2>Server Information</h2>";
echo "<ul>";
echo "<li>PHP Version: " . phpversion() . "</li>";
echo "<li>OS: " . PHP_OS . "</li>";
echo "<li>Server Software: " . ($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown') . "</li>";
echo "<li>Document Root: " . ($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown') . "</li>";
echo "<li>Current Working Directory: " . getcwd() . "</li>";
echo "<li>User: " . get_current_user() . "</li>";
echo "<li>User ID: " . getmyuid() . "</li>";
echo "<li>Group ID: " . getmygid() . "</li>";
echo "</ul>";

echo "<p><a href='test_logging.php'>Go to Logging Test</a></p>";