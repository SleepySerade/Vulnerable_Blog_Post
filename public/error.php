<?php
// Get the error code from the URL parameter or default to 500
$error_code = isset($_GET['code']) ? intval($_GET['code']) : 500;

// Define error messages for common error codes
$error_messages = [
    400 => 'Bad Request',
    401 => 'Unauthorized',
    403 => 'Forbidden',
    404 => 'Not Found',
    500 => 'Internal Server Error',
    502 => 'Bad Gateway',
    503 => 'Service Unavailable',
    504 => 'Gateway Timeout'
];

// Get the error message or use a default
$error_message = isset($error_messages[$error_code]) ? $error_messages[$error_code] : 'Unknown Error';

// Define error descriptions
$error_descriptions = [
    400 => 'The server could not understand the request due to invalid syntax.',
    401 => 'Authentication is required and has failed or has not yet been provided.',
    403 => 'You don\'t have permission to access this resource.',
    404 => 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.',
    500 => 'The server encountered an unexpected condition that prevented it from fulfilling the request.',
    502 => 'The server received an invalid response from an upstream server.',
    503 => 'The server is currently unavailable. It may be overloaded or down for maintenance.',
    504 => 'The server was acting as a gateway and did not receive a timely response from the upstream server.'
];

// Get the error description or use a default
$error_description = isset($error_descriptions[$error_code]) ? $error_descriptions[$error_code] : 'An unexpected error occurred.';

// Define error images (using free icons from flaticon)
$error_images = [
    400 => 'https://cdn-icons-png.flaticon.com/512/463/463612.png',
    401 => 'https://cdn-icons-png.flaticon.com/512/1680/1680012.png',
    403 => 'https://cdn-icons-png.flaticon.com/512/1680/1680012.png',
    404 => 'https://cdn-icons-png.flaticon.com/512/6195/6195678.png',
    500 => 'https://cdn-icons-png.flaticon.com/512/1138/1138098.png',
    502 => 'https://cdn-icons-png.flaticon.com/512/1138/1138098.png',
    503 => 'https://cdn-icons-png.flaticon.com/512/1138/1138098.png',
    504 => 'https://cdn-icons-png.flaticon.com/512/1138/1138098.png'
];

// Get the error image or use a default
$error_image = isset($error_images[$error_code]) ? $error_images[$error_code] : 'https://cdn-icons-png.flaticon.com/512/1138/1138098.png';

// Define error colors
$error_colors = [
    400 => '#ff9800', // Orange
    401 => '#dc3545', // Red
    403 => '#dc3545', // Red
    404 => '#ff9800', // Orange
    500 => '#dc3545', // Red
    502 => '#dc3545', // Red
    503 => '#dc3545', // Red
    504 => '#dc3545'  // Red
];

// Get the error color or use a default
$error_color = isset($error_colors[$error_code]) ? $error_colors[$error_code] : '#dc3545';

// Set the HTTP response code
http_response_code($error_code);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $error_code; ?> - <?php echo $error_message; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="/public/assets/css/styles.css" rel="stylesheet">
    <style>
        .error-container {
            text-align: center;
            padding: 100px 0;
            min-height: 70vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .error-code {
            font-size: 120px;
            font-weight: bold;
            color: <?php echo $error_color; ?>;
            margin-bottom: 0;
            line-height: 1;
        }
        .error-message {
            font-size: 32px;
            margin-bottom: 30px;
            color: var(--text-color);
        }
        .error-description {
            font-size: 18px;
            margin-bottom: 30px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            color: var(--muted-text-color);
        }
        .error-image {
            max-width: 300px;
            margin: 0 auto 40px;
        }
    </style>
</head>
<body>
    <!-- Include the navbar -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/navbar.php'; ?>

    <div class="container error-container">
        <div class="row">
            <div class="col-md-12">
                <img src="<?php echo $error_image; ?>" alt="Error <?php echo $error_code; ?>" class="error-image">
                <h1 class="error-code"><?php echo $error_code; ?></h1>
                <h2 class="error-message"><?php echo $error_message; ?></h2>
                <p class="error-description">
                    <?php echo $error_description; ?>
                </p>
                <div class="mb-4">
                    <a href="/" class="btn btn-primary btn-lg">Go to Homepage</a>
                    <button onclick="window.history.back();" class="btn btn-outline-secondary btn-lg ms-2">Go Back</button>
                </div>
                
                <?php if ($error_code == 403 && !isset($_SESSION['user_id'])): ?>
                    <p class="mt-3">
                        If you have an account with the necessary permissions, please 
                        <a href="/public/login">log in</a> to access this page.
                    </p>
                <?php endif; ?>
                
                <?php if ($error_code >= 500): ?>
                    <p class="mt-3 text-muted">
                        <small>
                            If this problem persists, please contact the website administrator.
                            <br>
                            Error reference: <?php echo date('YmdHis') . '-' . $error_code; ?>
                        </small>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Include the footer -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/public/assets/include/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>