# INF1005 Web Systems & Technologies - Group Project

## Introduction

BlogVerse represents a significant achievement in web development, offering a comprehensive platform for content creation, sharing, and community engagement. This project demonstrates the successful implementation of modern web technologies, robust security practices, and user-centered design principles to create a seamless blogging experience.

## Vision and Purpose
BlogVerse was conceived as more than just a blogging platform—it's a digital ecosystem where individuals can express themselves, share knowledge, and build communities around shared interests. The name "BlogVerse" itself captures the essence of the project: a universe of diverse voices and perspectives coming together in one digital space.

## Key Features and Functionality
The platform offers a rich set of features designed to enhance both the content creation and consumption experience:

- User Authentication System: Secure registration and login processes with proper password hashing and salt mechanisms
- Content Management: Intuitive interfaces for creating, editing, and managing blog posts
- Categorization and Tagging: Organizational tools that help users discover relevant content
- Interactive Elements: Comment sections, reaction systems (likes/dislikes), and social sharing capabilities
- Responsive Design: Mobile-friendly interface that adapts to various screen sizes
- Dark Mode: Enhanced reading experience with light and dark theme options
- Admin Dashboard: Comprehensive tools for content moderation and user management
- Profile Management: Personalized user profiles with customization options

## Technical Architecture
BlogVerse demonstrates technical excellence through its well-structured architecture:

- Frontend: HTML5, CSS3, Bootstrap 5, and JavaScript create a responsive and interactive user interface
- Backend: PHP powers the server-side logic with a clean separation of concerns
- Database: MySQL database with a thoughtfully designed schema supporting complex relationships between users, posts, comments, categories, and tags
- API Layer: RESTful API endpoints facilitate smooth communication between frontend and backend
- Security Layer: Comprehensive protection against common web vulnerabilities

## Development Approach
The project followed a structured development approach with clear role assignments:

Backend development focused on database design, API endpoints, and core functionality.Frontend development emphasized user experience, responsive design, and accessibility. Security implementation ensured data protection and user privacy
The team maintained a collaborative workflow with interdependent tasks and milestones.

## 📁 Project File Structure

```
/
│── .gitignore          # Git ignore file
│── .htaccess           # Main Apache configuration file
│── database.sql        # Database schema
│── README.md           # Project documentation
│── admin/              # Admin panel (restricted access)
│   ├── .htaccess       # Admin directory protection
│   ├── dashboard.php   # Admin dashboard
│   ├── manage_categories.php # Manage blog categories
│   ├── manage_posts.php # Manage blog posts
│   ├── manage_user.php # Manage users
│── backend/            # Backend logic (not web-accessible)
│   ├── .htaccess       # Backend directory protection
│   ├── admin.php       # Admin-related functions
│   ├── auth.php        # Authentication logic
│   ├── connect_db.php  # Database connection
│   ├── logout.php      # Logout functionality
│   ├── posts.php       # Post-related functions
│   ├── products.php    # Product-related functions
│   ├── setup_db.php    # Database setup
│   ├── tags.php        # Tag-related functions
│   ├── users.php       # User-related functions
│   ├── api/            # API endpoints
│   │   ├── categories.php # Categories API
│   │   ├── login.php   # Login API
│   │   ├── logout.php  # Logout API
│   │   ├── posts.php   # Posts API
│   │   ├── profile.php # Profile API
│   │   ├── register.php # Registration API
│   ├── utils/          # Utility functions
│   │   ├── logger.php  # Logging utility
│── logs/               # Server logs
│   ├── .htaccess       # Logs directory protection
│   ├── logs.log        # Log file
│── public/             # Public-facing website
│   ├── .htaccess       # Public directory configuration
│   ├── 403.php         # Forbidden error page
│   ├── 404.php         # Not found error page
│   ├── create_logs_dir.php # Create logs directory
│   ├── create-post.php # Create post page
│   ├── error.php       # General error handling page
│   ├── index.php       # Main entry point
│   ├── log_viewer.php  # View logs
│   ├── login.php       # Login page
│   ├── logout.php      # Logout page
│   ├── post.php        # View post page
│   ├── profile.php     # Profile page
│   ├── register.php    # Registration page
│   ├── test_logging.php # Test logging
│   ├── trigger-error.php # Test error pages
│   ├── assets/         # Static files
│   │   ├── css/        # CSS stylesheets
│   │   │   ├── styles.css # Main stylesheet
│   │   ├── images/     # Image files
│   │   ├── include/    # Reusable components
│   │   │   ├── footer.php # Footer component
│   │   │   ├── navbar.php # Navigation bar
│   │   ├── js/         # JavaScript files
│   │   │   ├── back-to-top.js # Back to top button functionality
│   │   │   ├── dark-mode-check.js # Dark mode preference check
│   │   │   ├── dropdown-fix.js # Dropdown menu enhancement
│   │   │   ├── lazyload.js # Lazy loading for images
│   │   │   ├── reading-time.js # Reading time estimator
│   │   │   ├── script.js # Main JavaScript functionality
│   │   │   ├── social-share.js # Social sharing buttons
│   │   ├── pages/      # Public pages
│   │   │   ├── about.php # About page
│   │   │   ├── create-post.php # Create post page
│   │   │   ├── edit-post.php # Edit post page
│   │   │   ├── my-posts.php # My posts page
│   │   │   ├── posts.php # Posts listing page
│   ├── user/           # User profile management
│   │   ├── edit-profile.php # Edit profile page
│   │   ├── edit-profile-js.php # JS-enhanced profile editing
│   │   ├── profile.php # User profile page
│── uploads/            # User uploads
│   ├── .htaccess       # Uploads directory protection
```

## 📋 File Purpose Documentation

| File/Directory | Purpose |
|----------------|---------|
| **Root Directory** | |
| `.gitignore` | Specifies files and directories to be ignored by Git version control |
| `.htaccess` | Main Apache configuration file with URL rewriting and error handling |
| `database.sql` | Contains SQL schema for creating and setting up the database |
| `README.md` | Project documentation with team roles and work order |
| `index.php` | Main entry point and homepage |
| **admin/** | Admin panel with restricted access |
| `admin/.htaccess` | Access control for admin directory, restricts unauthorized access |
| `admin/dashboard.php` | Main admin dashboard showing site statistics and overview |
| `admin/manage_categories.php` | Interface for managing blog categories (CRUD operations) |
| `admin/manage_posts.php` | Interface for managing all blog posts (edit, delete, publish) |
| `admin/manage_user.php` | Interface for managing users (activate/deactivate, grant admin) |
| **backend/** | Backend logic (not directly web-accessible) |
| `backend/.htaccess` | Protects backend files from direct web access, allows API endpoints |
| `backend/admin.php` | Functions for admin operations and privilege management |
| `backend/auth.php` | Authentication logic (login, register, password hashing) |
| `backend/connect_db.php` | Database connection and configuration |
| `backend/logout.php` | Handles user logout functionality |
| `backend/posts.php` | Functions for blog post operations |
| `backend/products.php` | Functions for product-related operations |
| `backend/setup_db.php` | Database setup and initialization |
| `backend/tags.php` | Functions for tag management and operations |
| `backend/users.php` | User management functions |
| **backend/api/** | API endpoints for frontend-backend communication |
| `backend/api/categories.php` | API for category operations |
| `backend/api/login.php` | API endpoint for user login |
| `backend/api/logout.php` | API endpoint for user logout |
| `backend/api/posts.php` | API for blog post operations |
| `backend/api/profile.php` | API for user profile operations |
| `backend/api/register.php` | API endpoint for user registration |
| **backend/utils/** | Utility functions and helpers |
| `backend/utils/logger.php` | Logging utility for application events |
| **logs/** | Server logs directory |
| `logs/.htaccess` | Prevents direct access to log files for security |
| `logs/logs.log` | Application log file |
| **public/** | Public-facing website files |
| `public/.htaccess` | URL rewriting, error handling, and security for public directory |
| `public/403.php` | Custom 403 Forbidden error page |
| `public/404.php` | Custom 404 Not Found error page |
| `public/create_logs_dir.php` | Utility to create logs directory |
| `public/create-post.php` | Page for creating new blog posts |
| `public/error.php` | Dynamic error handling page for all HTTP error codes |
| `public/log_viewer.php` | Interface for viewing application logs |
| `public/login.php` | User login page |
| `public/logout.php` | User logout page |
| `public/preview-draft.php`| Preview Draft Post |
| `public/post.php` | Page for viewing individual blog posts |
| `public/profile.php` | Redirect to user profile page |
| `public/register.php` | User registration page |
| `public/test_logging.php` | Utility for testing logging functionality |
| `public/trigger-error.php` | Utility for testing error pages with different error codes |
| **public/assets/** | Static assets for the public website |
| `public/assets/css/styles.css` | Main CSS stylesheet |
| `public/assets/images/` | Directory for image files |
| `public/assets/include/` | Reusable PHP components |
| `public/assets/include/footer.php` | Footer component included on all pages |
| `public/assets/include/navbar.php` | Navigation bar component included on all pages |
| **public/assets/js/** | JavaScript files for client-side functionality |
| `public/assets/js/back-to-top.js` | Implements a back-to-top button for long pages |
| `public/assets/js/dark-mode-check.js` | Checks and applies dark mode preference based on user settings |
| `public/assets/js/dropdown-fix.js` | Enhances dropdown menu functionality for better user experience |
| `public/assets/js/lazyload.js` | Implements lazy loading for images to improve page load performance |
| `public/assets/js/reading-time.js` | Calculates and displays estimated reading time for blog posts |
| `public/assets/js/script.js` | Main JavaScript file for site-wide functionality including dark mode toggle |
| `public/assets/js/social-share.js` | Adds social sharing buttons (Facebook, Twitter, LinkedIn, WhatsApp, Email) to blog posts |
| **public/assets/pages/** | Public content pages |
| `public/assets/pages/about.php` | About page with team information |
| `public/assets/pages/create-post.php` | Interface for creating blog posts |
| `public/assets/pages/edit-post.php` | Interface for editing blog posts |
| `public/assets/pages/my-posts.php` | Page showing current user's posts |
| `public/assets/pages/posts.php` | Page listing all blog posts |
| **public/user/** | User profile management |
| `public/user/edit-profile.php` | Page for editing user profile |
| `public/user/edit-profile-js.php` | JavaScript-enhanced profile editing page |
| `public/user/profile.php` | User profile page |
| **uploads/** | Directory for user-uploaded files |
| `uploads/.htaccess` | Prevents execution of uploaded scripts for security |

## 📊 Use Case Diagrams

### Public User (Non-Logged In) Use Cases
```
+---------------------------------------------+
|                                             |
|  +-------------+        +-----------------+ |
|  | Public User |        | BlogVerse      | |
|  +-------------+        | System         | |
|        |                +-----------------+ |
|        |                        |           |
|        |----> View Homepage --->|           |
|        |                        |           |
|        |----> Browse Posts ---->|           |
|        |                        |           |
|        |----> View Single Post >|           |
|        |                        |           |
|        |----> Search Content -->|           |
|        |                        |           |
|        |----> Register -------->|           |
|        |                        |           |
|        |----> Login ----------->|           |
|        |                        |           |
+---------------------------------------------+
```

### Registered User Use Cases
```
+---------------------------------------------+
|                                             |
|  +----------------+     +-----------------+ |
|  | Registered User|     | BlogVerse      | |
|  +----------------+     | System         | |
|        |                +-----------------+ |
|        |                        |           |
|        |----> Login ----------->|           |
|        |                        |           |
|        |----> Create Post ----->|           |
|        |                        |           |
|        |----> Edit Own Post --->|           |
|        |                        |           |
|        |----> Delete Own Post ->|           |
|        |                        |           |
|        |----> Comment --------->|           |
|        |                        |           |
|        |----> Like/React ------>|           |
|        |                        |           |
|        |----> Edit Profile ---->|           |
|        |                        |           |
|        |----> Logout ---------->|           |
|        |                        |           |
+---------------------------------------------+
```

### Admin User Use Cases
```
+---------------------------------------------+
|                                             |
|  +-------------+        +-----------------+ |
|  | Admin User  |        | BlogVerse      | |
|  +-------------+        | System         | |
|        |                +-----------------+ |
|        |                        |           |
|        |----> Login ----------->|           |
|        |                        |           |
|        |----> View Dashboard -->|           |
|        |                        |           |
|        |----> Manage Users ---->|           |
|        |                        |           |
|        |----> Manage Posts ---->|           |
|        |                        |           |
|        |----> Manage Comments ->|           |
|        |                        |           |
|        |----> Manage Categories>|           |
|        |                        |           |
|        |----> View Logs ------->|           |
|        |                        |           |
|        |----> Logout ---------->|           |
|        |                        |           |
+---------------------------------------------+
```

### User Flow Diagram - Public User
```
+-------+     +--------+     +------------+     +-----------+
| Start |---->| Browse |---->| View Post  |---->| Register/ |
+-------+     | Posts  |     | Details    |     | Login     |
                |  ^          |                 +-----+-----+
                |  |          |                       |
                v  |          v                       v
              +---+----------+----------------+     +--------+
              | Search/Filter| Comment (if    |     | Become |
              | Posts        | logged in)     |     | Member |
              +--------------+----------------+     +--------+
```

### User Flow Diagram - Admin User
```
+-------+     +----------+     +----------------+
| Login |---->| Admin    |---->| Manage Users   |
+-------+     | Dashboard|     +----------------+
                |               |
                |               v
                |             +----------------+
                |------------>| Manage Posts   |
                |             +----------------+
                |               |
                |               v
                |             +----------------+
                |------------>| Manage Comments|
                |             +----------------+
                |               |
                |               v
                |             +----------------+
                +------------>| Manage         |
                              | Categories     |
                              +----------------+
```

## 👥 Team Roles and Responsibilities  

### **1️⃣ Backend Developer**  (Zachary Phoon)
- Set up MySQL database on Google Cloud SQL  
- Develop API endpoints (User authentication, Products, CRUD operations)  
- Manage database schema and ensure proper data flow  

### **2️⃣ Frontend Developer (UI/UX Focus)**  (Zong Yang)
- Design and implement the user interface using HTML5, Bootstrap, and CSS  
- Ensure responsive and mobile-friendly design  
- Optimize layout for usability and accessibility  

### **3️⃣ Frontend Developer (JavaScript & Dynamic Features)**  (Ayesha & Saad)
- Implement client-side dynamic features using JavaScript  
- Manage user interactions and form validation  
- Handle AJAX calls for smooth frontend-backend communication  

### **4️⃣ Authentication & Security Developer** (Nicholas)   
- Implement user authentication and session management  
- Secure the system against SQL injection, XSS, and other vulnerabilities  
- Ensure password hashing and proper access control  


## 🛠️ Work Order & Dependencies  

| Step | Task | Primary Role | Dependency |
|------|------|-------------|------------|
| 1️⃣ | Set up database schema & API endpoints | Backend Developer | None (Start First) |
| 2️⃣ | Set up Google Cloud services | Cloud Engineer | None (Parallel to Backend) |
| 3️⃣ | Implement authentication system | Authentication & Security Developer | Needs Backend API Ready |
| 4️⃣ | Design UI & Page Layouts | Frontend (UI/UX) | Can start early, but needs Backend for final data |
| 5️⃣ | Implement JavaScript functionality & API integration | Frontend (JavaScript) | Needs Backend API & UI Ready |
| 6️⃣ | Perform Testing & Debugging | Everyone | Needs Full System Ready |
| 7️⃣ | Final Deployment & Documentation | Cloud Engineer + Everyone | All Development Must Be Complete |

## 📌 Summary
- **Backend and Cloud setup must be done first** before frontend development.
- **Authentication system requires a working backend** before implementation.
- **Frontend UI can start early**, but JavaScript integration depends on backend API.
- **Testing and documentation are team-wide responsibilities** before final deployment.
