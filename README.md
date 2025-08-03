# LNHS Documents Request Portal

A comprehensive web-based document request system for Laguna National High School that allows students and alumni to request documents online without visiting the school physically.

## 🎯 System Overview

**Title:** LNHS Documents Request Portal  
**Purpose:** Provide online access for students and alumni to request forms and certificates from the school without physical visits.

## ✨ Features

### 🔐 Authentication System
- Secure login system for students, alumni, and staff
- Role-based access control (Admin, Student, Alumni)
- Session management and security

### 📋 Document Request System
- **Online Request Form** with fields for:
  - Document type selection
  - Purpose of request
  - Preferred release date
  - File upload for requirements (JPG, PNG, GIF, PDF)
- **Document Types Available:**
  - Certificate of Enrollment
  - Good Moral Certificate
  - Transcript of Records
  - Certificate of Graduation
  - Certificate of Transfer

### 📊 Request Tracking System
- **Status Tracking:** Pending → Processing → Approved/Denied → Ready for Pickup
- **Progress Visualization** with step-by-step progress indicators
- **Real-time Status Updates** with notifications
- **Request History** with filtering and search

### 👨‍💼 Admin Dashboard
- **Request Management:** View, filter, and update request statuses
- **User Management:** Manage students, alumni, and staff accounts
- **Document Type Management:** Configure available documents and fees
- **Reports Generation:** Export data to Excel or PDF
- **Activity Logs:** Track all system activities

### 🔔 Notification System
- **Portal Notifications:** Real-time in-app notifications
- **Email Notifications:** Automated email alerts (configurable)
- **SMS Notifications:** Text message alerts (configurable)
- **Status Updates:** Automatic notifications when request status changes

### 📈 Reporting & Analytics
- **Request Statistics:** Dashboard with key metrics
- **Export Functionality:** Generate reports in Excel/PDF format
- **Activity Monitoring:** Track user activities and system usage

## 🛠️ Technical Requirements

### Server Requirements
- **Web Server:** Apache/Nginx
- **PHP Version:** 7.4 or higher
- **Database:** MySQL 5.7 or higher
- **XAMPP:** Compatible with XAMPP for local development

### PHP Extensions Required
- PDO MySQL
- GD Library (for image processing)
- FileInfo (for file uploads)
- OpenSSL (for security)

## 📦 Installation Guide

### Step 1: Database Setup
1. Open phpMyAdmin in your XAMPP
2. Create a new database named `lnhs_portal`
3. Import the `database.sql` file to set up all tables

### Step 2: File Configuration
1. Copy all files to your XAMPP `htdocs` folder
2. Ensure the `uploads/` directory has write permissions (777)
3. Update database connection in `config/database.php` if needed

### Step 3: Default Login
- **Admin Account:**
  - Username: `admin`
  - Password: `password`

### Step 4: Access the System
1. Start Apache and MySQL in XAMPP
2. Navigate to `http://localhost/your-folder-name`
3. Login with the default admin credentials

## 🗄️ Database Structure

### Core Tables
- **users:** User accounts and profiles
- **document_types:** Available document types and fees
- **document_requests:** All request data and status
- **notifications:** System notifications
- **activity_logs:** User activity tracking

### Key Features
- **Foreign Key Relationships:** Maintains data integrity
- **Indexed Fields:** Optimized for performance
- **Audit Trail:** Complete activity logging
- **Soft Deletes:** Data preservation

## 🎨 User Interface

### Modern Design
- **Responsive Layout:** Works on desktop, tablet, and mobile
- **Bootstrap 5:** Modern CSS framework
- **Font Awesome Icons:** Professional iconography
- **Gradient Themes:** Beautiful visual design

### User Experience
- **Intuitive Navigation:** Easy-to-use interface
- **Progress Indicators:** Visual request tracking
- **Real-time Updates:** Live status changes
- **Mobile-Friendly:** Optimized for all devices

## 🔧 Configuration

### Database Connection
Edit `config/database.php`:
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'lnhs_portal');
define('DB_USER', 'root');
define('DB_PASS', '');
```

### File Upload Settings
- **Maximum File Size:** 5MB
- **Allowed Formats:** JPG, PNG, GIF, PDF
- **Upload Directory:** `uploads/`

### Email Configuration (Optional)
Configure SMTP settings for email notifications in the admin panel.

## 🚀 Usage Guide

### For Students/Alumni
1. **Login** with your credentials
2. **Request Document** by filling out the form
3. **Track Progress** through the status updates
4. **Receive Notifications** about your request status
5. **View History** of all your requests

### For Administrators
1. **Login** with admin credentials
2. **Manage Requests** by updating statuses
3. **View Reports** and export data
4. **Manage Users** and document types
5. **Monitor System** activity and logs

## 🔒 Security Features

- **Password Hashing:** Secure password storage
- **SQL Injection Protection:** Prepared statements
- **XSS Prevention:** Input sanitization
- **CSRF Protection:** Form token validation
- **Session Security:** Secure session management

## 📱 Mobile Compatibility

- **Responsive Design:** Adapts to all screen sizes
- **Touch-Friendly:** Optimized for mobile interaction
- **Fast Loading:** Optimized for mobile networks
- **Cross-Browser:** Works on all modern browsers

## 🔄 System Workflow

1. **Student/Alumni** submits document request
2. **System** creates notification and logs activity
3. **Admin** reviews and updates request status
4. **System** sends notifications to user
5. **User** tracks progress and receives updates
6. **Admin** marks as ready for pickup
7. **User** receives final notification

## 📊 Status Flow

```
Pending → Processing → Approved → Ready for Pickup
    ↓         ↓          ↓           ↓
   Denied   Denied     Denied      Denied
```

## 🎯 Benefits

### For Students/Alumni
- **Convenience:** No need to visit school physically
- **Time-Saving:** Submit requests anytime, anywhere
- **Transparency:** Real-time status tracking
- **Notifications:** Instant updates on request progress

### For School Administration
- **Efficiency:** Streamlined request processing
- **Organization:** Centralized request management
- **Reporting:** Comprehensive analytics and reports
- **Reduced Workload:** Automated notifications and tracking

## 🛠️ Maintenance

### Regular Tasks
- **Database Backups:** Weekly automated backups
- **Log Cleanup:** Monthly activity log cleanup
- **File Cleanup:** Regular upload directory cleanup
- **Security Updates:** Keep PHP and dependencies updated

### Monitoring
- **Error Logs:** Monitor PHP error logs
- **Performance:** Track system performance metrics
- **User Activity:** Monitor user engagement and patterns

## 📞 Support

For technical support or questions:
- **Email:** admin@lnhs.edu.ph
- **Phone:** [School Contact Number]
- **Documentation:** See inline code comments

## 📄 License

This system is developed for Laguna National High School. All rights reserved.

---

**Developed with ❤️ for LNHS Community**