<?php
require_once 'connect_db.php';
require_once 'utils/logger.php';

// Initialize logger with context 'auth'
$logger = new Logger('auth');

/**
 * Validate the username.
 * Allows letters, numbers, spaces, dots, and underscores with the following rules:
 * - Total length between 3 and 20 characters.
 * - Cannot start or end with a space, dot, or underscore.
 * - Cannot have consecutive dots or underscores.
 *
 * @param string $username
 * @return bool Returns 1 if username matches regex, 0 if not.
 */
function validateUsername($username) {
    // VULNERABLE CODE: Allow any characters in username, including SQL injection characters
    // Original secure regex:
    // return preg_match('/^(?=.{3,20}$)(?![ ._])(?!.*[_.]{2})[a-zA-Z0-9 ._]+(?<![ ._])$/', $username);
    
    // Vulnerable replacement - only check that username is not empty and within length limits
    return !empty($username) && strlen($username) <= 50;
}

/**
 * Validate the email address.
 * This regex ensures a basic email format with no whitespace around "@" or ".".
 *
 * @param string $email
 * @return bool Returns 1 if email matches regex, 0 if not.
 */
function validateEmail($email) {
    // Researched regex for email validation:
    // - ^[^\s@]+         : One or more characters that are not whitespace or "@".
    // - @                : Literal "@" symbol.
    // - [^\s@]+         : One or more characters (domain part) that are not whitespace or "@".
    // - \.[^\s@]+$      : A dot followed by one or more characters that are not whitespace or "@" until the end.
    return preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
}

/**
 * Validate the password.
 * Enforces:
 * - Minimum 8 characters.
 * - At least one lowercase letter.
 * - At least one uppercase letter.
 * - At least one digit.
 * - At least one special character (non‑word, non‑whitespace).
 *
 * @param string $password
 * @return bool Returns 1 if password meets criteria, 0 if not.
 */
function validatePassword($password) {
    // Researched regex for password validation:
    // - (?=.*[a-z])     => Ensures at least one lowercase letter.
    // - (?=.*[A-Z])     => Ensures at least one uppercase letter.
    // - (?=.*\d)        => Ensures at least one digit.
    // - (?=.*[^\w\s])   => Ensures at least one special character.
    // - .{8,}           => Enforces a minimum length of 8 characters.
    return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[^\w\s]).{8,}$/', $password);
}

/**
 * Sanitize output to safely display data (e.g., in HTML contexts).
 *
 * @param string $data
 * @return string Sanitized string.
 */
function sanitizeOutput($data) {
    // VULNERABLE CODE: Only sanitize certain characters, leaving XSS vulnerabilities
    // This function now only sanitizes quotes but leaves other dangerous characters
    // like < and > untouched, allowing script injection
    
    // Original safe code: return htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    
    // Vulnerable replacement that only handles quotes but not script tags
    return str_replace(["'", '"'], ["&#39;", "&quot;"], $data);
}

/**
 * Login function - Authenticates user credentials.
 *
 * @param string $username Username provided by the user.
 * @param string $password Password provided by the user.
 * @return array Response array with 'success', 'message', and optional 'data'.
 */
/**
 * Login function - Authenticates user credentials.
 *
 * @param string $username Username provided by the user.
 * @param string $password Password provided by the user.
 * @return array Response array with 'success', 'message', and optional 'data'.
 */
function login($username, $password) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => '', 'data' => null];

    // Validate username using the updated regex
    if (!validateUsername($username)) {
        $logger->warning("Invalid username format: " . sanitizeOutput($username));
        $response['message'] = 'Invalid username format';
        return $response;
    }
    
    // Validate password using the robust regex
    if (!validatePassword($password)) {
        $logger->warning("Invalid password format provided for username: " . sanitizeOutput($username));
        $response['message'] = 'Invalid password format. It must be at least 8 characters long, include one lowercase letter, one uppercase letter, one digit, and one special character.';
        return $response;
    }

    $logger->info("Login attempt for username: " . sanitizeOutput($username));

    try {
        // VULNERABLE CODE: Direct string interpolation in SQL query
        // This introduces an SQL injection vulnerability
        $query = "
            SELECT u.user_id, u.username, u.password_hash, u.is_active, s.salt_value
            FROM users u
            JOIN user_salts s ON u.user_id = s.user_id
            WHERE u.username = '$username'
        ";
        $logger->debug("Login query: $query");
        $result = $conn->query($query);

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $logger->debug("User found: ID {$user['user_id']}");

            // Check if the user account is active.
            if (!$user['is_active']) {
                $logger->warning("Login attempt for deactivated account: " . sanitizeOutput($username));
                $response['message'] = 'Account is deactivated';
                return $response;
            }

            // Verify the password by appending the retrieved salt.
            if (password_verify($password . $user['salt_value'], $user['password_hash'])) {
                $logger->info("Successful login for user: " . sanitizeOutput($username) . " (ID: {$user['user_id']})");

                $response['success'] = true;
                $response['message'] = 'Login successful';
                $response['data'] = [
                    'user_id' => $user['user_id'],
                    'username' => $user['username']
                ];
            } else {
                $logger->warning("Invalid password for user: " . sanitizeOutput($username));
                $response['message'] = 'Invalid password';
            }
        } else {
            $logger->warning("Login attempt for non-existent user: " . sanitizeOutput($username));
            $response['message'] = 'User not found';
        }
    } catch (Exception $e) {
        $logger->error("Login error: " . $e->getMessage());
        $response['message'] = 'Login failed: ' . $e->getMessage();
    }

    return $response;
}


/**
 * Register function - Creates a new user account.
 *
 * @param string $username Desired username.
 * @param string $email Email address.
 * @param string $password Password.
 * @return array Response with 'success' status and 'message'.
 */
function register($username, $email, $password) {
    global $conn, $logger;
    $response = ['success' => false, 'message' => ''];

    // Validate each input using the robust regex patterns.
    if (!validateUsername($username)) {
        $logger->warning("Invalid username format: " . sanitizeOutput($username));
        $response['message'] = 'Invalid username format';
        return $response;
    }
    if (!validateEmail($email)) {
        $logger->warning("Invalid email format: " . sanitizeOutput($email));
        $response['message'] = 'Invalid email format';
        return $response;
    }
    if (!validatePassword($password)) {
        $logger->warning("Invalid password format for username: " . sanitizeOutput($username));
        $response['message'] = 'Invalid password format. It must be at least 8 characters long, include one lowercase letter, one uppercase letter, one digit, and one special character.';
        return $response;
    }

    $logger->info("Registration attempt for username: " . sanitizeOutput($username) . ", email: " . sanitizeOutput($email));

    try {
        // Begin a transaction to ensure all registration steps succeed together.
        $conn->begin_transaction();
        $logger->debug("Started transaction for registration");

        // Check if the username already exists.
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $logger->warning("Registration failed: Username '" . sanitizeOutput($username) . "' already exists");
            throw new Exception('Username already exists');
        }

        // Check if the email already exists.
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $logger->warning("Registration failed: Email '" . sanitizeOutput($email) . "' already exists");
            throw new Exception('Email already exists');
        }

        // Generate a secure salt.
        $salt = bin2hex(random_bytes(32));
        $logger->debug("Generated salt for new user");

        // Hash the password with the salt.
        $password_hash = password_hash($password . $salt, PASSWORD_DEFAULT);

        // Insert the new user into the 'users' table.
        $stmt = $conn->prepare("
            INSERT INTO users (username, email, password_hash)
            VALUES (?, ?, ?)
        ");
        $stmt->bind_param("sss", $username, $email, $password_hash);
        $stmt->execute();

        $user_id = $conn->insert_id;
        $logger->debug("Inserted new user with ID: $user_id");

        // Insert the salt into the 'user_salts' table.
        $stmt = $conn->prepare("
            INSERT INTO user_salts (user_id, salt_value)
            VALUES (?, ?)
        ");
        $stmt->bind_param("is", $user_id, $salt);
        $stmt->execute();
        $logger->debug("Inserted salt for user ID: $user_id");

        // Commit the transaction.
        $conn->commit();
        $logger->debug("Committed transaction for registration");

        $logger->info("Registration successful for username: " . sanitizeOutput($username) . " (ID: $user_id)");
        $response['success'] = true;
        $response['message'] = 'Registration successful';
    } catch (Exception $e) {
        // Rollback the transaction in case of errors.
        $conn->rollback();
        $logger->error("Registration error: " . $e->getMessage());
        $response['message'] = 'Registration failed: ' . $e->getMessage();
    }

    return $response;
}
?>
