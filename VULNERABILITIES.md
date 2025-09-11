# Vulnerabilities Introduced for Ethical Hacking Playground

This document outlines the vulnerabilities that have been intentionally introduced into the blog system for ethical hacking practice.

## 1. File Upload Vulnerability
- **Location**: `backend/api/upload.php`
- **Type**: Insecure file validation
- **Description**: The system now only checks file extensions instead of MIME types, allowing attackers to upload PHP files disguised as images (e.g., `malicious.php.jpg`).
- **Potential Impact**: Remote Code Execution (RCE)
- **How to Exploit**: Upload a file with a double extension like `shell.php.jpg` containing PHP code.

## 2. SQL Injection Vulnerability
- **Location**: `backend/auth.php` (login function)
- **Type**: SQL Injection
- **Description**: Changed prepared statements to direct string interpolation in SQL queries and disabled username character validation to allow SQL injection characters. The vulnerability is made worse by the lack of character validation in both the frontend (public/login.php) and backend (backend/api/login.php).
- **Potential Impact**: Authentication bypass, data extraction, database manipulation
- **How to Exploit**: Use payloads like `' OR '1'='1` in the username field to bypass authentication. The validateUsername function has been modified to allow any characters, including those needed for SQL injection, making the vulnerability easily exploitable.

## 3. Cross-Site Scripting (XSS) Vulnerability
- **Location**: `backend/auth.php` (sanitizeOutput function)
- **Type**: Reflected/Stored XSS
- **Description**: Modified sanitization to only handle quotes but not script tags, allowing HTML/JavaScript injection.
- **Potential Impact**: Session hijacking, credential theft, defacement
- **How to Exploit**: Insert script tags like `<script>alert('XSS')</script>` in user input fields.

## 4. CSRF Protection Weaknesses
- **Location**: `backend/utils/csrf.php`
- **Type**: Cross-Site Request Forgery
- **Description**: 
  - Using non-constant time comparison (timing attack vulnerability)
  - Not removing tokens after use (token reuse)
  - Added a backdoor parameter (`skip_csrf=1`) that bypasses CSRF validation
- **Potential Impact**: Unauthorized actions performed on behalf of authenticated users
- **How to Exploit**: Create a malicious page that submits requests to the vulnerable endpoints with `?skip_csrf=1` parameter.

## 5. Admin Authentication Bypass
- **Location**: `backend/admin.php`
- **Type**: Insecure Direct Object Reference (IDOR) / Backdoor
- **Description**: Added a backdoor that grants admin access if a specific cookie is present (`admin_bypass=secret_backdoor_value`).
- **Potential Impact**: Unauthorized administrative access
- **How to Exploit**: Set a cookie named `admin_bypass` with value `secret_backdoor_value` to gain admin privileges.

## 6. Remote Command Execution via SSH Backdoor
- **Location**: `backend/system_check.php`
- **Type**: Remote Command Execution / Backdoor
- **Description**: A hidden backdoor that generates SSH keys and configures SSH access to the www-data user when triggered with a special parameter. Requires admin access to use. Explicitly prevents access to the root user.
- **Potential Impact**: Server compromise with www-data privileges, persistent access to the server, ability to execute commands with web server permissions
- **How to Exploit**:
  1. First gain admin access using one of the other vulnerabilities (e.g., SQL injection in login or the admin backdoor cookie)
  2. Find the hidden link in the footer (tiny "system" text in dark color) or directly access the URL: `http://[server]/backend/system_check.php?action=ssh_setup`
  3. The response will contain a private SSH key
  4. Save the private key to a file (e.g., `private_key.pem`)
  5. Set the correct permissions: `chmod 600 private_key.pem`
  6. Connect via SSH: `ssh -i private_key.pem www-data@[server]`

  **Note**: The backdoor is accessible via a hidden link in the website footer. Look for a tiny "system" text in dark color that's nearly invisible against the background. You must be logged in as an admin user to use this backdoor. The system explicitly prevents creating SSH keys for the root user.

## Disclaimer

These vulnerabilities are intentionally introduced for educational purposes in a controlled environment. Do not implement these vulnerabilities in production systems or use these techniques against systems without explicit permission.