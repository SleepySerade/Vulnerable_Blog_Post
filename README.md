# INF1005 Web Systems & Technologies - Group Project

## 📁 Project File Structure

```
/var/www/html/
│── public/            # Public-facing website
│   ├── index.php      # Main entry point
│   ├── assets/        # Static files (CSS, JS, images)
│   │   ├── css/
│   │   ├── js/
│   │   ├── images/
│   ├── pages/         # Public pages (about, contact, etc.)
│   ├── login.php      # User login page
│   ├── register.php      # User login page
│── admin/             # Admin panel (restricted access)
│   ├── index.php      # Admin dashboard
│   ├── users.php      # Manage users
│   ├── settings.php   # Admin settings
│   ├── assets/        # Admin-specific CSS, JS, images
│── backend/           # Backend logic (not web-accessible)
│   ├── config.php     # Database config
│   ├── db.php         # Database connection
│   ├── auth.php       # Authentication logic
│   ├── functions.php  # Helper functions
│── uploads/           # User uploads (if needed)
│── logs/              # Server logs
│── .htaccess          # Security rules (if using Apache)
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


