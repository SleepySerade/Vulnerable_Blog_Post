<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if user is logged in and is admin
session_start();
$isLoggedIn = isset($_SESSION['user_id']);
$isAdmin = false;

if ($isLoggedIn) {
    // Check if user is admin
    require_once '../backend/connect_db.php';
    $stmt = $conn->prepare("SELECT admin_id FROM admins WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $isAdmin = $stmt->get_result()->num_rows > 0;
}

// Only allow admins to view logs
if (!$isAdmin) {
    header('Location: login.php');
    exit();
}

// Get logs directory
$logsDir = '/var/www/html/logs';

// Check if logs directory exists
$logsDirExists = file_exists($logsDir);
$logsDirWritable = $logsDirExists && is_writable($logsDir);

// Try to create logs directory if it doesn't exist
if (!$logsDirExists) {
    $createResult = @mkdir($logsDir, 0777, true);
    $logsDirExists = file_exists($logsDir);
    
    if ($createResult) {
        // Set permissions
        @chmod($logsDir, 0777);
        $logsDirWritable = is_writable($logsDir);
    }
} elseif (!$logsDirWritable) {
    // Try to make the directory writable
    @chmod($logsDir, 0777);
    $logsDirWritable = is_writable($logsDir);
}

// Define the specific log file
$specificLogFile = $logsDir . '/logs.log';
$specificLogExists = $logsDirExists && file_exists($specificLogFile);
$specificLogWritable = $specificLogExists && is_writable($specificLogFile);

// For backward compatibility, also get any other log files
$logFiles = $logsDirExists ? glob($logsDir . '/*.log') : [];

// Get selected log file
$selectedLog = isset($_GET['file']) ? $_GET['file'] : '';
$logContent = '';
$validFile = false;

if (!empty($selectedLog)) {
    $fullPath = $logsDir . '/' . basename($selectedLog);
    
    // Validate that the file exists and is within the logs directory
    if (file_exists($fullPath) && is_file($fullPath) && strpos(realpath($fullPath), realpath($logsDir)) === 0) {
        $validFile = true;
        $logContent = file_get_contents($fullPath);
    }
}

// Clear log file if requested
if (isset($_POST['clear']) && $validFile) {
    file_put_contents($fullPath, '');
    $logContent = '';
}

// Delete log file if requested
if (isset($_POST['delete']) && $validFile) {
    unlink($fullPath);
    header('Location: log_viewer.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Viewer - Blog Website</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/styles.css" rel="stylesheet">
    <style>
        pre {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;
            overflow: auto;
        }
        .log-entry {
            margin-bottom: 5px;
        }
        .log-error {
            color: #dc3545;
        }
        .log-warning {
            color: #ffc107;
        }
        .log-info {
            color: #0d6efd;
        }
        .log-debug {
            color: #6c757d;
        }
    </style>
</head>
<body>
    <?php include 'assets/include/navbar.php'; ?>

    <div class="container my-5">
        <div class="row">
            <div class="col-md-3">
                <!-- Status Card -->
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">Logs Status</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Directory Exists
                                <span class="badge <?php echo $logsDirExists ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $logsDirExists ? 'Yes' : 'No'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Directory Writable
                                <span class="badge <?php echo $logsDirWritable ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $logsDirWritable ? 'Yes' : 'No'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Main Log File Exists
                                <span class="badge <?php echo $specificLogExists ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $specificLogExists ? 'Yes' : 'No'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Main Log File Writable
                                <span class="badge <?php echo $specificLogWritable ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                                    <?php echo $specificLogWritable ? 'Yes' : 'No'; ?>
                                </span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Total Log Files
                                <span class="badge bg-primary rounded-pill">
                                    <?php echo count($logFiles); ?>
                                </span>
                            </li>
                        </ul>
                        
                        <?php if (!$logsDirExists || !$logsDirWritable): ?>
                            <div class="mt-3">
                                <a href="create_logs_dir.php" class="btn btn-warning btn-sm w-100">
                                    Fix Logs Directory
                                </a>
                            </div>
                        <?php endif; ?>
                        
                        <div class="mt-2">
                            <a href="test_logging.php" class="btn btn-info btn-sm w-100">
                                Run Logging Test
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Log Files Card -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Log Files</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($logFiles as $logFile): ?>
                                <?php $fileName = basename($logFile); ?>
                                <a href="log_viewer.php?file=<?php echo urlencode($fileName); ?>" 
                                   class="list-group-item list-group-item-action <?php echo ($selectedLog === $fileName) ? 'active' : ''; ?>">
                                    <?php echo htmlspecialchars($fileName); ?>
                                </a>
                            <?php endforeach; ?>
                            
                            <?php if (empty($logFiles)): ?>
                                <div class="list-group-item">No log files found</div>
                                <?php if (!$logsDirExists): ?>
                                    <div class="list-group-item text-danger">Logs directory does not exist!</div>
                                    <a href="create_logs_dir.php" class="list-group-item list-group-item-action list-group-item-primary">
                                        Create Logs Directory
                                    </a>
                                <?php elseif (!$logsDirWritable): ?>
                                    <div class="list-group-item text-danger">Logs directory exists but is not writable!</div>
                                    <a href="create_logs_dir.php" class="list-group-item list-group-item-action list-group-item-warning">
                                        Fix Permissions
                                    </a>
                                <?php endif; ?>
                                <a href="test_logging.php" class="list-group-item list-group-item-action list-group-item-info">
                                    Run Logging Test
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <?php echo $validFile ? htmlspecialchars(basename($fullPath)) : 'Select a log file'; ?>
                        </h5>
                        
                        <?php if ($validFile): ?>
                            <div>
                                <form method="POST" class="d-inline">
                                    <button type="submit" name="clear" class="btn btn-warning btn-sm" 
                                            onclick="return confirm('Are you sure you want to clear this log file?')">
                                        Clear Log
                                    </button>
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" 
                                            onclick="return confirm('Are you sure you want to delete this log file?')">
                                        Delete Log
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if ($validFile): ?>
                            <pre><?php
                                if (empty($logContent)) {
                                    echo "Log file is empty";
                                } else {
                                    $lines = explode("\n", $logContent);
                                    foreach ($lines as $line) {
                                        if (empty(trim($line))) continue;
                                        
                                        $class = 'log-entry';
                                        if (strpos($line, '[ERROR]') !== false) {
                                            $class .= ' log-error';
                                        } elseif (strpos($line, '[WARNING]') !== false) {
                                            $class .= ' log-warning';
                                        } elseif (strpos($line, '[INFO]') !== false) {
                                            $class .= ' log-info';
                                        } elseif (strpos($line, '[DEBUG]') !== false) {
                                            $class .= ' log-debug';
                                        }
                                        
                                        echo '<div class="' . $class . '">' . htmlspecialchars($line) . '</div>';
                                    }
                                }
                            ?></pre>
                        <?php else: ?>
                            <div class="alert alert-info">
                                Please select a log file from the list to view its contents.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include 'assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>