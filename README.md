# INF1005 Web Systems & Technologies - Group Project

## 📁 Project File Structure

```
/
│── .gitignore          # Git ignore file
│── database.sql        # Database schema
│── README.md           # Project documentation
│── admin/              # Admin panel (restricted access)
│   ├── dashboard.php   # Admin dashboard
│   ├── manage_categories.php # Manage blog categories
│   ├── manage_posts.php # Manage blog posts
│   ├── manage_user.php # Manage users
│── backend/            # Backend logic (not web-accessible)
│   ├── admin.php       # Admin-related functions
│   ├── auth.php        # Authentication logic
│   ├── connect_db.php  # Database connection
│   ├── logout.php      # Logout functionality
│   ├── posts.php       # Post-related functions
│   ├── products.php    # Product-related functions
│   ├── setup_db.php    # Database setup
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
│   ├── logs.log        # Log file
│── public/             # Public-facing website
│   ├── create_logs_dir.php # Create logs directory
│   ├── create-post.php # Create post page
│   ├── index.php       # Main entry point
│   ├── log_viewer.php  # View logs
│   ├── login.php       # Login page
│   ├── logout.php      # Logout page
│   ├── post.php        # View post page
│   ├── profile.php     # Profile page
│   ├── register.php    # Registration page
│   ├── test_logging.php # Test logging
│   ├── assets/         # Static files
│   │   ├── css/        # CSS stylesheets
│   │   │   ├── styles.css # Main stylesheet
│   │   ├── images/     # Image files
│   │   ├── include/    # Reusable components
│   │   │   ├── footer.php # Footer component
│   │   │   ├── navbar.php # Navigation bar
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
```

## 📋 File Purpose Documentation

| File/Directory | Purpose |
|----------------|---------|
| **Root Directory** | |
| `.gitignore` | Specifies files and directories to be ignored by Git version control |
| `database.sql` | Contains SQL schema for creating and setting up the database |
| `README.md` | Project documentation with team roles and work order |
| **admin/** | Admin panel with restricted access |
| `admin/dashboard.php` | Main admin dashboard showing site statistics and overview |
| `admin/manage_categories.php` | Interface for managing blog categories (CRUD operations) |
| `admin/manage_posts.php` | Interface for managing all blog posts (edit, delete, publish) |
| `admin/manage_user.php` | Interface for managing users (activate/deactivate, grant admin) |
| **backend/** | Backend logic (not directly web-accessible) |
| `backend/admin.php` | Functions for admin operations and privilege management |
| `backend/auth.php` | Authentication logic (login, register, password hashing) |
| `backend/connect_db.php` | Database connection and configuration |
| `backend/logout.php` | Handles user logout functionality |
| `backend/posts.php` | Functions for blog post operations |
| `backend/products.php` | Functions for product-related operations |
| `backend/setup_db.php` | Database setup and initialization |
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
| `logs/logs.log` | Application log file |
| **public/** | Public-facing website files |
| `public/create_logs_dir.php` | Utility to create logs directory |
| `public/create-post.php` | Page for creating new blog posts |
| `public/index.php` | Main entry point and homepage |
| `public/log_viewer.php` | Interface for viewing application logs |
| `public/login.php` | User login page |
| `public/logout.php` | User logout page |
| `public/post.php` | Page for viewing individual blog posts |
| `public/profile.php` | Redirect to user profile page |
| `public/register.php` | User registration page |
| `public/test_logging.php` | Utility for testing logging functionality |
| **public/assets/** | Static assets for the public website |
| `public/assets/css/styles.css` | Main CSS stylesheet |
| `public/assets/images/` | Directory for image files |
| `public/assets/include/` | Reusable PHP components |
| `public/assets/include/footer.php` | Footer component included on all pages |
| `public/assets/include/navbar.php` | Navigation bar component included on all pages |
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

## 👥 Team Roles and Responsibilities  

### **1️⃣ Backend Developer**  (Zachary Phoon)
- Set up MySQL database on Google Cloud SQL  
- Develop API endpoints (User authentication, Products, CRUD operations)  
- Manage database schema and ensure proper data flow  

### **2️⃣ Frontend Developer (UI/UX Focus)**  (Zong Yang)
- Design and implement the user interface using HTML5, Bootstrap, and CSS  
- Ensure responsive and mobile-friendly design  
- Optimize layout for usability and accessibility  

### **3️⃣ Frontend Developer (JavaScript & Dynamic Features)**  (Ayesha)
- Implement client-side dynamic features using JavaScript  
- Manage user interactions and form validation  
- Handle AJAX calls for smooth frontend-backend communication  

### **4️⃣ Authentication & Security Developer** (Nicholas)   
- Implement user authentication and session management  
- Secure the system against SQL injection, XSS, and other vulnerabilities  
- Ensure password hashing and proper access control  

### **5️⃣ Cloud & Deployment Engineer**  
- Deploy the project on Google Cloud App Engine  
- Set up Cloud Storage for media uploads and backups  
- Optimize cloud configurations, security policies, and IAM roles  

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
