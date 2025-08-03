# LNHS Documents Request Portal - Installation Guide

## 🚀 Quick Start Installation

### Prerequisites
- **XAMPP** (Apache + MySQL + PHP)
- **PHP 7.4 or higher**
- **MySQL 5.7 or higher**
- **Web browser** (Chrome, Firefox, Safari, Edge)

### Step 1: Download and Extract
1. Download the LNHS Portal files
2. Extract to your XAMPP `htdocs` folder
3. Rename the folder to `lnhs-portal` (optional)

### Step 2: Start XAMPP Services
1. Open XAMPP Control Panel
2. Start **Apache** and **MySQL** services
3. Ensure both services show green status

### Step 3: Create Database
1. Open your web browser
2. Go to `http://localhost/phpmyadmin`
3. Click **"New"** to create a new database
4. Enter database name: `lnhs_portal`
5. Click **"Create"**

### Step 4: Import Database Structure
1. In phpMyAdmin, select the `lnhs_portal` database
2. Click **"Import"** tab
3. Click **"Choose File"** and select `database.sql`
4. Click **"Go"** to import the database structure

### Step 5: Configure Database Connection
1. Open `config/database.php` in a text editor
2. Verify the database settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'lnhs_portal');
   define('DB_USER', 'root');
   define('DB_PASS', '');
   ```
3. Save the file

### Step 6: Set File Permissions
1. Create `uploads` folder in the project root (if not exists)
2. Set folder permissions to **777** (read/write/execute for all)
3. On Windows: Right-click → Properties → Security → Edit → Add → Everyone → Full Control
4. On Linux/Mac: `chmod 777 uploads/`

### Step 7: Access the System
1. Open your web browser
2. Go to `http://localhost/lnhs-portal`
3. You should see the login page

### Step 8: Default Login Credentials
- **Username:** `admin`
- **Password:** `password`

## 🔧 Detailed Configuration

### Database Configuration
The system uses the following database settings by default:
```php
// config/database.php
define('DB_HOST', 'localhost');     // Database host
define('DB_NAME', 'lnhs_portal');   // Database name
define('DB_USER', 'root');          // Database username
define('DB_PASS', '');              // Database password
```

### File Upload Configuration
- **Maximum file size:** 5MB
- **Allowed file types:** JPG, PNG, GIF, PDF
- **Upload directory:** `uploads/`

### Security Settings
The system includes:
- Password hashing with bcrypt
- SQL injection protection
- XSS prevention
- CSRF protection
- Session security

## 📁 File Structure
```
lnhs-portal/
├── config/
│   └── database.php          # Database configuration
├── uploads/                  # File upload directory
├── index.php                 # Login page
├── dashboard.php             # Main dashboard
├── request_document.php      # Document request form
├── my_requests.php          # User's request history
├── admin_requests.php       # Admin request management
├── admin_users.php          # User management
├── admin_reports.php        # Reports and analytics
├── admin_documents.php      # Document type management
├── notifications.php        # Notification system
├── profile.php              # User profile
├── logout.php               # Logout functionality
├── view_request.php         # Request details view
├── database.sql             # Database structure
├── README.md                # System documentation
├── INSTALLATION.md          # This file
└── .htaccess               # Apache configuration
```

## 🛠️ Troubleshooting

### Common Issues

#### 1. Database Connection Error
**Error:** "Connection failed"
**Solution:**
- Verify XAMPP MySQL is running
- Check database name in `config/database.php`
- Ensure database exists in phpMyAdmin

#### 2. File Upload Not Working
**Error:** "Failed to upload file"
**Solution:**
- Check `uploads/` folder permissions (777)
- Verify PHP file upload settings
- Check available disk space

#### 3. Page Not Found (404)
**Error:** "Page not found"
**Solution:**
- Verify Apache is running in XAMPP
- Check file paths and URLs
- Ensure `.htaccess` file is present

#### 4. Login Not Working
**Error:** "Invalid username or password"
**Solution:**
- Use default credentials: admin/password
- Check database import was successful
- Verify database connection

#### 5. Permission Denied
**Error:** "Permission denied"
**Solution:**
- Set proper file permissions
- Check folder ownership
- Verify web server permissions

### PHP Requirements
Ensure your PHP installation includes:
- **PDO MySQL** extension
- **GD Library** (for image processing)
- **FileInfo** extension
- **OpenSSL** extension

### Browser Compatibility
The system works with:
- **Chrome** 80+
- **Firefox** 75+
- **Safari** 13+
- **Edge** 80+

## 🔒 Security Recommendations

### 1. Change Default Password
After first login:
1. Go to Profile page
2. Change the default admin password
3. Use a strong password

### 2. Database Security
- Change default MySQL root password
- Create a dedicated database user
- Update `config/database.php` with new credentials

### 3. File Permissions
- Set `uploads/` folder to 755 (more secure)
- Restrict access to sensitive files
- Regular backup of database

### 4. SSL Certificate
For production use:
- Install SSL certificate
- Force HTTPS connections
- Update all URLs to use HTTPS

## 📊 System Features

### For Students/Alumni
- ✅ Document request submission
- ✅ File upload functionality
- ✅ Request status tracking
- ✅ Progress visualization
- ✅ Notification system
- ✅ Profile management

### For Administrators
- ✅ Request management
- ✅ User management
- ✅ Document type configuration
- ✅ Reports and analytics
- ✅ Export functionality
- ✅ Activity logging

## 🚀 Production Deployment

### 1. Server Requirements
- **Web Server:** Apache 2.4+ or Nginx
- **PHP:** 7.4+ with required extensions
- **MySQL:** 5.7+ or MariaDB 10.2+
- **SSL Certificate:** Required for production

### 2. Security Checklist
- [ ] Change default admin password
- [ ] Configure SSL certificate
- [ ] Set proper file permissions
- [ ] Enable firewall rules
- [ ] Regular database backups
- [ ] Monitor error logs

### 3. Performance Optimization
- Enable PHP OPcache
- Configure MySQL query cache
- Use CDN for static assets
- Implement caching strategies

## 📞 Support

### Getting Help
If you encounter issues:
1. Check the troubleshooting section
2. Verify all prerequisites are met
3. Review error logs in XAMPP
4. Contact system administrator

### Error Logs
- **Apache logs:** `xampp/apache/logs/`
- **PHP errors:** Check browser console
- **MySQL logs:** `xampp/mysql/data/`

### Contact Information
- **Email:** admin@lnhs.edu.ph
- **Phone:** (049) 123-4567
- **Office Hours:** Monday-Friday 8:00 AM - 5:00 PM

---

**🎉 Congratulations!** Your LNHS Documents Request Portal is now ready to use.

**Next Steps:**
1. Login with admin credentials
2. Add document types
3. Create user accounts
4. Test the system
5. Train users on the system

**Remember:** Always keep your system updated and regularly backup your database!