# Vulnerabilities Introduced for Ethical Hacking Playground

This document outlines the vulnerabilities that have been intentionally introduced into the blog system for ethical hacking practice.

## 1. File Upload Vulnerability
- **Location**: `backend/api/upload.php`
- **Type**: Insecure file validation
- **Description**: All file type validation has been completely removed from both frontend and backend, allowing attackers to upload any file type including PHP scripts. **Note: This functionality is now restricted to admin users only.**
- **Potential Impact**: Remote Code Execution (RCE)
- **How to Exploit**:
  1. First gain admin access using one of the other vulnerabilities (e.g., SQL injection in login or the admin backdoor cookie)
  2. Upload a file with any extension, including direct PHP files like `shell.php`
  3. The system will accept any file type with no validation in either the frontend or backend
  4. Access the uploaded file to execute the code

## 2. SQL Injection Vulnerability
- **Location**: `backend/auth.php` (login function)
- **Type**: SQL Injection
- **Description**: Changed prepared statements to direct string interpolation in SQL queries and disabled username character validation to allow SQL injection characters. The vulnerability is made worse by the lack of character validation in both the frontend (public/login.php) and backend (backend/api/login.php).
- **Potential Impact**: Authentication bypass, data extraction, database manipulation
- **How to Exploit**:
  - Use the following SQL injection payloads in the username field:
    1. Basic authentication bypass: `' OR 1=1 LIMIT 1 -- `
    2. Target a specific user: `' OR username='admin' -- `
    3. Target users with admin privileges: `' OR EXISTS (SELECT 1 FROM admins WHERE admins.user_id = u.user_id) LIMIT 1 -- `
  
  - **Important Notes**:
    - The `LIMIT 1` is crucial because the login function checks for exactly one row returned (`$result->num_rows === 1`)
    - The login function has been modified to automatically bypass password verification when SQL keywords are detected in the username
    - This means you can login with just the SQL injection in the username field - no password needed!
    - Common SQL keywords that trigger the bypass: 'OR', 'UNION', 'SELECT', 'LIMIT', '--'
    - The validateUsername function has been modified to allow any characters, including those needed for SQL injection

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