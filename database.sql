-- LNHS DOCUMENTS REQUEST PORTAL DATABASE
-- Compatible with phpMyAdmin/XAMPP
-- Created for students, alumni, and admin users

-- Create database
CREATE DATABASE IF NOT EXISTS lnhs_portal;
USE lnhs_portal;

-- Users table (students, alumni, admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id VARCHAR(20) UNIQUE,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    first_name VARCHAR(50) NOT NULL,
    last_name VARCHAR(50) NOT NULL,
    middle_name VARCHAR(50),
    user_type ENUM('student', 'alumni', 'admin') NOT NULL,
    contact_number VARCHAR(15),
    address TEXT,
    graduation_year YEAR NULL,
    course VARCHAR(100),
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Document types table
CREATE TABLE document_types (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) DEFAULT 0.00,
    processing_days INT DEFAULT 3,
    requirements TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Document requests table
CREATE TABLE document_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    document_type_id INT NOT NULL,
    purpose TEXT NOT NULL,
    preferred_release_date DATE,
    quantity INT DEFAULT 1,
    status ENUM('pending', 'processing', 'approved', 'denied', 'ready_for_pickup', 'completed') DEFAULT 'pending',
    admin_notes TEXT,
    payment_status ENUM('unpaid', 'paid', 'waived') DEFAULT 'unpaid',
    total_amount DECIMAL(10,2) DEFAULT 0.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    approved_by INT NULL,
    approved_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (document_type_id) REFERENCES document_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
);

-- Document request files (uploaded IDs and requirements)
CREATE TABLE request_files (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_type VARCHAR(50),
    file_size INT,
    upload_type ENUM('id', 'requirement', 'other') DEFAULT 'requirement',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE
);

-- Request status history
CREATE TABLE request_status_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    request_id INT NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id)
);

-- Notifications table
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    request_id INT,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('email', 'sms', 'portal') DEFAULT 'portal',
    status ENUM('sent', 'pending', 'failed') DEFAULT 'pending',
    read_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (request_id) REFERENCES document_requests(id) ON DELETE SET NULL
);

-- System settings
CREATE TABLE system_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default document types
INSERT INTO document_types (name, description, price, processing_days, requirements) VALUES
('Certificate of Enrollment', 'Official certificate proving current enrollment status', 50.00, 3, 'Valid ID, Student ID'),
('Good Moral Certificate', 'Certificate of good moral character', 50.00, 5, 'Valid ID, Student ID, Clearance'),
('Transcript of Records', 'Official academic transcript', 100.00, 7, 'Valid ID, Student ID, Request Form'),
('Diploma Copy', 'Certified copy of diploma', 150.00, 10, 'Valid ID, Original Diploma, Affidavit if lost'),
('Certificate of Grades', 'Official certificate showing final grades', 75.00, 3, 'Valid ID, Student ID'),
('Honorable Dismissal', 'Transfer credentials for students', 100.00, 7, 'Valid ID, Student ID, Clearance');

-- Insert default admin user
INSERT INTO users (student_id, email, password, first_name, last_name, user_type, contact_number) VALUES
('ADMIN001', 'admin@lnhs.edu.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System', 'Administrator', 'admin', '09123456789');
-- Default password is 'password'

-- Insert system settings
INSERT INTO system_settings (setting_key, setting_value, description) VALUES
('school_name', 'LNHS - Laoag National High School', 'Official school name'),
('school_address', 'Laoag City, Ilocos Norte', 'School address'),
('contact_email', 'info@lnhs.edu.ph', 'Official contact email'),
('contact_phone', '077-123-4567', 'Official contact phone'),
('max_file_size', '5242880', 'Maximum file upload size in bytes (5MB)'),
('allowed_file_types', 'jpg,jpeg,png,pdf,doc,docx', 'Allowed file extensions'),
('email_notifications', '1', 'Enable email notifications'),
('sms_notifications', '0', 'Enable SMS notifications');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_student_id ON users(student_id);
CREATE INDEX idx_requests_user_id ON document_requests(user_id);
CREATE INDEX idx_requests_status ON document_requests(status);
CREATE INDEX idx_requests_created_at ON document_requests(created_at);
CREATE INDEX idx_notifications_user_id ON notifications(user_id);
CREATE INDEX idx_files_request_id ON request_files(request_id);