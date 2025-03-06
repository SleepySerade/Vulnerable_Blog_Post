<?php
/**
 * Logger utility for writing logs to files
 */
class Logger {
    private $logFile;
    private $logLevel;
    
    // Log levels
    const DEBUG = 0;
    const INFO = 1;
    const WARNING = 2;
    const ERROR = 3;
    
    /**
     * Constructor
     * @param string $logName Name of the log file (without extension) - not used anymore
     * @param int $logLevel Minimum log level to record
     */
    public function __construct($logName = 'app', $logLevel = self::INFO) {
        // Create logs directory if it doesn't exist
        // Use absolute path based on /var/www/html
        $logsDir = '/var/www/html/logs';
        
        // Debug output to help diagnose issues
        error_log("Logs directory path: " . $logsDir);
        
        // Create logs directory with more permissive permissions if it doesn't exist
        if (!file_exists($logsDir)) {
            try {
                if (!mkdir($logsDir, 0777, true)) {
                    error_log("Failed to create logs directory: " . $logsDir);
                } else {
                    error_log("Successfully created logs directory: " . $logsDir);
                    // Ensure the directory is writable
                    // Use @ to suppress warnings if chmod fails
                    if (@chmod($logsDir, 0777)) {
                        error_log("Successfully set permissions on logs directory: " . $logsDir);
                    } else {
                        error_log("Failed to set permissions on logs directory: " . $logsDir . " (This is normal on some hosting environments)");
                    }
                }
            } catch (Exception $e) {
                error_log("Exception creating logs directory: " . $e->getMessage());
            }
        } else {
            // Ensure the directory is writable
            // Use @ to suppress warnings if chmod fails
            if (@chmod($logsDir, 0777)) {
                error_log("Successfully set permissions on logs directory: " . $logsDir);
            } else {
                error_log("Failed to set permissions on logs directory: " . $logsDir . " (This is normal on some hosting environments)");
            }
            error_log("Logs directory already exists: " . $logsDir);
        }
        
        // Set log file path to the specific file logs/logs.log
        $this->logFile = $logsDir . '/logs.log';
        $this->logLevel = $logLevel;
        
        // Debug output to help diagnose issues
        error_log("Log file path: " . $this->logFile);
        
        // Test if we can write to the log file
        $testWrite = @file_put_contents($this->logFile, '', FILE_APPEND);
        if ($testWrite === false) {
            error_log("Cannot write to log file: " . $this->logFile);
        } else {
            error_log("Successfully wrote to log file: " . $this->logFile);
        }
    }
    
    /**
     * Write a message to the log file
     * @param string $message Message to log
     * @param int $level Log level
     * @return bool Success or failure
     */
    public function log($message, $level = self::INFO) {
        // Only log if level is at or above the minimum level
        if ($level < $this->logLevel) {
            return true;
        }
        
        // Get level name
        $levelName = $this->getLevelName($level);
        
        // Format log entry
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] [$levelName] $message" . PHP_EOL;
        
        // Try to write to log file
        try {
            // Make sure the logs directory exists
            $logsDir = dirname($this->logFile);
            if (!file_exists($logsDir)) {
                if (!mkdir($logsDir, 0777, true)) {
                    error_log("Failed to create logs directory: " . $logsDir);
                    return false;
                }
                // Use @ to suppress warnings if chmod fails
                if (@chmod($logsDir, 0777)) {
                    error_log("Successfully set permissions on logs directory: " . $logsDir);
                } else {
                    error_log("Failed to set permissions on logs directory: " . $logsDir . " (This is normal on some hosting environments)");
                }
            }
            
            // Write to log file
            $result = @file_put_contents($this->logFile, $logEntry, FILE_APPEND);
            
            // If writing failed, try to diagnose the issue
            if ($result === false) {
                $errorMessage = "Failed to write to log file: " . $this->logFile;
                
                // Check if file exists but is not writable
                if (file_exists($this->logFile) && !is_writable($this->logFile)) {
                    $errorMessage .= " (File exists but is not writable)";
                    // Try to make the file writable
                    // Use @ to suppress warnings if chmod fails (already using @ here, but adding a check)
                    if (@chmod($this->logFile, 0666)) {
                        error_log("Successfully set permissions on log file: " . $this->logFile);
                    } else {
                        error_log("Failed to set permissions on log file: " . $this->logFile . " (This is normal on some hosting environments)");
                    }
                    // Try writing again
                    $result = @file_put_contents($this->logFile, $logEntry, FILE_APPEND);
                }
                
                // If still failed, log to PHP error log
                if ($result === false) {
                    error_log($errorMessage);
                    error_log("Log entry that failed to write: " . $logEntry);
                    return false;
                }
            }
            
            return true;
        } catch (Exception $e) {
            error_log("Exception writing to log file: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Log a debug message
     * @param string $message Message to log
     * @return bool Success or failure
     */
    public function debug($message) {
        return $this->log($message, self::DEBUG);
    }
    
    /**
     * Log an info message
     * @param string $message Message to log
     * @return bool Success or failure
     */
    public function info($message) {
        return $this->log($message, self::INFO);
    }
    
    /**
     * Log a warning message
     * @param string $message Message to log
     * @return bool Success or failure
     */
    public function warning($message) {
        return $this->log($message, self::WARNING);
    }
    
    /**
     * Log an error message
     * @param string $message Message to log
     * @return bool Success or failure
     */
    public function error($message) {
        return $this->log($message, self::ERROR);
    }
    
    /**
     * Get the name of a log level
     * @param int $level Log level
     * @return string Level name
     */
    private function getLevelName($level) {
        switch ($level) {
            case self::DEBUG:
                return 'DEBUG';
            case self::INFO:
                return 'INFO';
            case self::WARNING:
                return 'WARNING';
            case self::ERROR:
                return 'ERROR';
            default:
                return 'UNKNOWN';
        }
    }
}