# LNHS Documents Request Portal

A comprehensive web-based system for managing document requests at Laoag National High School (LNHS). This portal allows students and alumni to request official documents online without visiting the school premises.

## 🎓 Features

### For Students & Alumni
- **Online Registration & Login** - Secure account creation and authentication
- **Document Request Form** - Request various certificates and documents
- **File Upload System** - Upload required IDs and supporting documents
- **Request Tracking** - Track request status with progress indicators
- **Real-time Notifications** - Get updates via email and portal notifications
- **Request History** - View all past and current requests

### For Administrators
- **Admin Dashboard** - Comprehensive overview of all requests and statistics
- **Request Management** - Review, approve, deny, and update request statuses
- **User Management** - Manage student and alumni accounts
- **Document Types** - Configure available documents with pricing and requirements
- **Reports Generation** - Generate and export reports (Excel/PDF)
- **Notification System** - Send automated notifications to users

### Available Documents
- Certificate of Enrollment
- Good Moral Certificate
- Transcript of Records
- Diploma Copy
- Certificate of Grades
- Honorable Dismissal

## 🚀 Installation Guide

### Prerequisites
- **XAMPP** (Apache, MySQL, PHP 7.4 or higher)
- **Web Browser** (Chrome, Firefox, Safari, Edge)
- **Text Editor** (Optional: VS Code, Sublime Text)

### Step 1: Download and Setup XAMPP
1. Download XAMPP from [https://www.apachefriends.org/](https://www.apachefriends.org/)
2. Install XAMPP and start **Apache** and **MySQL** services
3. Open your web browser and go to `http://localhost/phpmyadmin`

### Step 2: Create Database
1. In phpMyAdmin, click **"New"** to create a new database
2. Name it `lnhs_portal` and click **"Create"**
3. Click on the `lnhs_portal` database
4. Click **"Import"** tab
5. Choose the `database.sql` file from the project folder
6. Click **"Go"** to import the database structure and sample data

### Step 3: Install the Portal
1. Copy the entire project folder to `C:\xampp\htdocs\` (Windows) or `/opt/lampp/htdocs/` (Linux)
2. Rename the folder to `lnhs-portal`
3. Open your web browser and go to `http://localhost/lnhs-portal`

### Step 4: Test the System
1. **Admin Login:**
   - Email: `admin@lnhs.edu.ph`
   - Password: `password`

2. **Create Student Account:**
   - Click "Register here" on the login page
   - Fill in the registration form
   - Use account type: "Current Student" or "Alumni"

## 📁 Project Structure

```
lnhs-portal/
├── admin/                  # Admin panel files
│   ├── dashboard.php      # Admin dashboard
│   ├── manage-requests.php
│   ├── manage-users.php
│   └── reports.php
├── assets/                 # Static assets
│   └── css/
│       └── style.css      # Main stylesheet
├── config/                 # Configuration files
│   └── database.php       # Database connection
├── includes/              # Shared PHP files
│   ├── auth.php          # Authentication system
│   └── notifications.php  # Notification system
├── student/               # Student/Alumni panel
│   ├── dashboard.php     # Student dashboard
│   ├── new-request.php   # Document request form
│   ├── my-requests.php   # Request tracking
│   ├── view-request.php  # Detailed request view
│   └── logout.php        # Logout functionality
├── uploads/               # File uploads directory
├── database.sql          # Database schema
├── index.php             # Main login page
└── README.md             # This file
```

## 🔧 Configuration

### Database Configuration
The database connection settings are in `config/database.php`:
```php
private $host = 'localhost';
private $db_name = 'lnhs_portal';
private $username = 'root';
private $password = '';
```

### Email Configuration
To enable email notifications, configure the SMTP settings in `includes/notifications.php`. For production use, consider using:
- **PHPMailer** for advanced email functionality
- **SendGrid** or **Mailgun** for reliable email delivery

### File Upload Settings
- Maximum file size: 5MB per file
- Allowed file types: JPG, PNG, PDF, DOC, DOCX
- Upload directory: `uploads/` (ensure it's writable)

## 🎯 Usage Guide

### For Students/Alumni
1. **Registration:**
   - Visit the portal homepage
   - Click "Register here"
   - Fill in all required information
   - Select account type (Student/Alumni)
   - Submit the form

2. **Document Request:**
   - Login to your account
   - Click "New Request"
   - Select document type
   - Fill in purpose and details
   - Upload required files (ID, etc.)
   - Submit request

3. **Track Requests:**
   - Go to "My Requests"
   - View status of all requests
   - Click "View" for detailed information
   - Get notifications for status updates

### For Administrators
1. **Managing Requests:**
   - Login with admin credentials
   - View pending requests on dashboard
   - Click "Review" to process requests
   - Update status (Approve/Deny/Process)
   - Add admin notes if needed

2. **User Management:**
   - Go to "Manage Users"
   - View all registered users
   - Activate/Deactivate accounts
   - Edit user information

3. **Generate Reports:**
   - Go to "Reports"
   - Select date range and filters
   - Export data as Excel or PDF

## 📊 Request Status Flow

```
Pending → Processing → Approved → Ready for Pickup → Completed
    ↓
  Denied (if rejected)
```

1. **Pending** - Request submitted, waiting for review
2. **Processing** - Request approved and being processed
3. **Approved** - Request approved, payment required
4. **Ready for Pickup** - Document ready at registrar's office
5. **Completed** - Document picked up successfully
6. **Denied** - Request rejected (with reason)

## 🔐 Security Features

- **Password Hashing** - Secure password storage using PHP's password_hash()
- **Session Management** - Secure session handling
- **SQL Injection Prevention** - Prepared statements for all database queries
- **File Upload Validation** - Strict file type and size validation
- **Role-Based Access** - Different access levels for students and admins
- **CSRF Protection** - Forms protected against cross-site request forgery

## 📱 Responsive Design

The portal is fully responsive and works on:
- 🖥️ Desktop computers
- 💻 Laptops
- 📱 Tablets
- 📱 Mobile phones

## 🛠️ Troubleshooting

### Common Issues

1. **"Connection error" message:**
   - Check if MySQL is running in XAMPP
   - Verify database name is `lnhs_portal`
   - Check database credentials in `config/database.php`

2. **File upload not working:**
   - Check if `uploads/` directory exists
   - Ensure the directory has write permissions
   - Verify file size is under 5MB

3. **Cannot access admin panel:**
   - Use default admin credentials:
     - Email: `admin@lnhs.edu.ph`
     - Password: `password`

4. **Page not found errors:**
   - Ensure project folder is in `htdocs`
   - Check that Apache is running
   - Verify the URL is correct

### Error Logs
- Check PHP error logs in XAMPP control panel
- Look for errors in browser developer console
- Enable error reporting for debugging (development only)

## 🔄 Updates & Maintenance

### Regular Maintenance
1. **Backup Database** - Regular backups of the `lnhs_portal` database
2. **Clean Old Files** - Remove old uploaded files periodically
3. **Update Passwords** - Change default admin password
4. **Monitor Logs** - Check error logs regularly

### Adding New Document Types
1. Login as admin
2. Go to "Settings" → "Document Types"
3. Add new document with price and requirements
4. Save changes

## 📞 Support

For technical support or questions:
- **Email:** registrar@lnhs.edu.ph
- **Phone:** 077-123-4567
- **Office Hours:** Monday - Friday, 8:00 AM - 5:00 PM

## 📄 License

This project is developed for Laoag National High School. All rights reserved.

## 🙏 Credits

Developed for the digitization of document request processes at LNHS, improving efficiency and reducing the need for physical visits to the school.

---

**LNHS Documents Request Portal v1.0**  
*Making document requests easier for students and alumni* 🎓