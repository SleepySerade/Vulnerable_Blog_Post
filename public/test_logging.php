<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include the logger
require_once '../backend/utils/logger.php';

// Create a test logger
$testLogger = new Logger('test');

// Try to log messages at different levels
$debugResult = $testLogger->debug("This is a debug message");
$infoResult = $testLogger->info("This is an info message");
$warningResult = $testLogger->warning("This is a warning message");
$errorResult = $testLogger->error("This is an error message");

// Get the logs directory path
$logsDir = '/var/www/html/logs';

// Check if logs directory exists
$logsDirExists = file_exists($logsDir);

// Get the specific log file path
$logFile = $logsDir . '/logs.log';

// Check if log file exists
$logFileExists = file_exists($logFile);

// Check if log file is writable
$logFileWritable = $logFileExists ? is_writable($logFile) : false;

// Get the log file content if it exists
$logContent = $logFileExists ? file_get_contents($logFile) : '';

// Get log file size
$logFileSize = $logFileExists ? filesize($logFile) : 0;

// Get server information
$serverInfo = [
    'PHP Version' => phpversion(),
    'OS' => PHP_OS,
    'Server Software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'Document Root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
    'Script Filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
    'Current Working Directory' => getcwd(),
    'User' => get_current_user(),
    'User ID' => getmyuid(),
    'Group ID' => getmygid(),
    'File Permissions' => $logFileExists ? substr(sprintf('%o', fileperms($logFile)), -4) : 'N/A',
    'Directory Permissions' => $logsDirExists ? substr(sprintf('%o', fileperms($logsDir)), -4) : 'N/A'
];

// Get file system information
$diskFreeSpace = disk_free_space('/');
$diskTotalSpace = disk_total_space('/');
$diskUsedSpace = $diskTotalSpace - $diskFreeSpace;
$diskUsagePercent = round(($diskUsedSpace / $diskTotalSpace) * 100, 2);

$fsInfo = [
    'Disk Free Space' => formatBytes($diskFreeSpace),
    'Disk Total Space' => formatBytes($diskTotalSpace),
    'Disk Used Space' => formatBytes($diskUsedSpace),
    'Disk Usage' => $diskUsagePercent . '%'
];

// Function to format bytes to human-readable format
function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, $precision) . ' ' . $units[$pow];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 400px;
            overflow: auto;
        }
        .success {
            color: green;
        }
        .failure {
            color: red;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <h1>Logging Test Results</h1>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Logging Results</h3>
            </div>
            <div class="card-body">
                <p>Debug Log Result: <span class="<?php echo $debugResult ? 'success' : 'failure'; ?>"><?php echo $debugResult ? 'Success' : 'Failed'; ?></span></p>
                <p>Info Log Result: <span class="<?php echo $infoResult ? 'success' : 'failure'; ?>"><?php echo $infoResult ? 'Success' : 'Failed'; ?></span></p>
                <p>Warning Log Result: <span class="<?php echo $warningResult ? 'success' : 'failure'; ?>"><?php echo $warningResult ? 'Success' : 'Failed'; ?></span></p>
                <p>Error Log Result: <span class="<?php echo $errorResult ? 'success' : 'failure'; ?>"><?php echo $errorResult ? 'Success' : 'Failed'; ?></span></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>File System Information</h3>
            </div>
            <div class="card-body">
                <p>Logs Directory Path: <code><?php echo htmlspecialchars($logsDir); ?></code></p>
                <p>Logs Directory Exists: <span class="<?php echo $logsDirExists ? 'success' : 'failure'; ?>"><?php echo $logsDirExists ? 'Yes' : 'No'; ?></span></p>
                <p>Log File Path: <code><?php echo htmlspecialchars($logFile); ?></code></p>
                <p>Log File Exists: <span class="<?php echo $logFileExists ? 'success' : 'failure'; ?>"><?php echo $logFileExists ? 'Yes' : 'No'; ?></span></p>
                <p>Log File Writable: <span class="<?php echo $logFileWritable ? 'success' : 'failure'; ?>"><?php echo $logFileWritable ? 'Yes' : 'No'; ?></span></p>
                <p>Log File Size: <span><?php echo $logFileExists ? formatBytes($logFileSize) : 'N/A'; ?></span></p>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Server Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <?php foreach ($serverInfo as $key => $value): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($key); ?></th>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>Disk Information</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <tbody>
                        <?php foreach ($fsInfo as $key => $value): ?>
                            <tr>
                                <th><?php echo htmlspecialchars($key); ?></th>
                                <td><?php echo htmlspecialchars($value); ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($logFileExists): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h3>Log File Content</h3>
                </div>
                <div class="card-body">
                    <pre><?php echo htmlspecialchars($logContent); ?></pre>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="card mb-4">
            <div class="card-header">
                <h3>PHP Error Log</h3>
            </div>
            <div class="card-body">
                <p>PHP Error Log Path: <code><?php echo htmlspecialchars(ini_get('error_log')); ?></code></p>
                <p>Check your PHP error log for more information about any issues with logging.</p>
            </div>
        </div>
        
        <a href="index.php" class="btn btn-primary">Back to Home</a>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>