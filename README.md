# INF1005_WebSys


## File Structure
```
/project-root/
├── /public/                # Publicly accessible files
│   ├── index.php           # Main landing page
│   ├── about.php           # "About Us" page
│   ├── products.php        # Products/services page
│   ├── contact.php         # Contact page
│   └── assets/             # Static files (CSS, JS, Images)
│       ├── css/
│       │   └── styles.css  # Custom styles
│       ├── js/
│       │   └── scripts.js  # Custom JS
│       └── images/         # Images for the website
│
├── /includes/              # Common reusable PHP files
│   ├── header.php          # Website header (navigation bar, meta tags)
│   ├── footer.php          # Website footer
│   ├── db_connect.php      # Database connection script
│   └── functions.php       # Common utility functions
│
├── /config/                # Configuration files
│   └── config.php          # App-wide settings (database credentials, site settings)
│
├── /admin/                 # Admin panel for CRUD operations
│   ├── dashboard.php       # Admin dashboard
│   ├── manage_users.php    # User management
│   ├── manage_products.php # Product catalog management
│   └── orders.php          # Manage shopping cart/orders
│
├── /auth/                  # Authentication (Login, Register, Logout)
│   ├── login.php           # Login page
│   ├── register.php        # Registration page
│   └── logout.php          # Logout script
│
├── /database/              # Database-related files
│   ├── schema.sql          # SQL file to create database structure
│   └── seed.sql            # Sample data to populate the database
│
├── /uploads/               # Directory for user-uploaded content (ensure security measures)
│
├── .htaccess               # URL rewriting and security settings (if using Apache)
└── README.md               # Documentation about the project
```


# Project Workload Distribution

## Team Members & Responsibilities

| Member | Role | Responsibilities |
|--------|------|-----------------|
| **Person 1** | Project Manager & Backend Developer | - Set up Google Cloud SQL (MySQL) <br> - Configure database schema & queries <br> - Implement API endpoints for frontend integration |
| **Person 2** | Frontend Developer | - Design UI using Bootstrap, HTML5, and CSS <br> - Implement JavaScript for dynamic features <br> - Ensure responsiveness & accessibility (WCAG compliance) |
| **Person 3** | Full-Stack Developer | - Develop user authentication system (Register/Login) <br> - Integrate database with frontend <br> - Handle CRUD operations for users & products |
| **Person 4** | Cloud Engineer | - Deploy project on Google App Engine <br> - Configure Cloud Storage for file uploads <br> - Set up IAM roles & security policies |
| **Person 5** | Tester & Documentation | - Perform testing & bug tracking <br> - Write project documentation (README, API docs) <br> - Ensure security measures (XSS, SQL injection prevention) |

---

## **Project Milestones**
### **Week 1: Setup & Initial Development**
- [ ] Set up Google Cloud SQL database
- [ ] Configure App Engine deployment
- [ ] Design frontend layout (Bootstrap, HTML, CSS)
- [ ] Implement authentication system (Login/Register)

### **Week 2: Feature Development**
- [ ] Create backend APIs for product catalog & orders
- [ ] Implement CRUD operations for products & users
- [ ] Set up Cloud Storage for images/files
- [ ] Test database interactions & API calls

### **Week 3: Testing & Deployment**
- [ ] Perform security testing (SQL injection, XSS)
- [ ] Debug frontend & backend issues
- [ ] Deploy final version to Google Cloud
- [ ] Finalize documentation & presentation

---

## **Collaboration & Tools**
- **Version Control:** GitHub repository for code sharing
- **Communication:** Telegram for updates
- **Code Reviews:** Weekly review sessions

---

## **Submission Guidelines**
- Final submission includes:
  - [ ] Deployed website link
  - [ ] Database export (`schema.sql`)
  - [ ] Presentation recording
  - [ ] Full project documentation (`README.md`)



