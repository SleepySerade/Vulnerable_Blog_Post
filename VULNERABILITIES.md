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
- **Description**: Changed prepared statements to direct string interpolation in SQL queries.
- **Potential Impact**: Authentication bypass, data extraction, database manipulation
- **How to Exploit**: Use payloads like `' OR '1'='1` in the username field to bypass authentication.

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

## Disclaimer

These vulnerabilities are intentionally introduced for educational purposes in a controlled environment. Do not implement these vulnerabilities in production systems or use these techniques against systems without explicit permission.