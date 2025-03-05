# INF1005 Web Systems & Technologies - Group Project

## 📁 Project File Structure

```
/project-root  
│── /backend  
│   ├── database.sql             # MySQL database schema with user authentication  
│   ├── config.php              # Database connection & security settings  
│   ├── api/  
│   │   ├── auth.php            # Authentication & user session endpoints  
│   │   ├── products.php        # Blog posts & content API endpoints  
│   │   ├── users.php           # User management API endpoints  
│── /frontend  
│   ├── index.php              # Blog homepage  
│   ├── about.php              # About Us page  
│   ├── products.php           # Blog posts listing page  
│   ├── login.php              # Login/Registration page  
│   ├── /css  
│   │   ├── styles.css          # Main stylesheet with responsive design  
│   ├── /js  
│   │   ├── scripts.js          # Frontend functionality & API integration  
│── .gitignore                  # Git version control exclusions  
│── README.md                   # Project documentation  
```

## 👥 Team Roles and Responsibilities  

### **1️⃣ Backend Developer**  
- Set up MySQL database with user authentication and blog functionality
- Develop API endpoints (Authentication, Blog posts, User management)
- Manage database schema and ensure proper data flow  

### **2️⃣ Frontend Developer (UI/UX Focus)**  
- Design and implement the user interface using HTML5, Bootstrap, and CSS  
- Ensure responsive and mobile-friendly design  
- Optimize layout for usability and accessibility  

### **3️⃣ Frontend Developer (JavaScript & Dynamic Features)**  
- Implement client-side dynamic features using JavaScript  
- Manage user interactions and form validation  
- Handle AJAX calls for smooth frontend-backend communication  

### **4️⃣ Authentication & Security Developer**  
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
