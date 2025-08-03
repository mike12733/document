<?php
session_start();
require_once '../config/database.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDBConnection();
    }
    
    // Login function
    public function login($email, $password) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['student_id'] = $user['student_id'];
                
                // Update last login (optional)
                $this->updateLastLogin($user['id']);
                
                return ['success' => true, 'user' => $user];
            } else {
                return ['success' => false, 'message' => 'Invalid email or password'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Login error: ' . $e->getMessage()];
        }
    }
    
    // Register function
    public function register($data) {
        try {
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$data['email']]);
            if ($stmt->fetch()) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Check if student ID already exists
            if (!empty($data['student_id'])) {
                $stmt = $this->db->prepare("SELECT id FROM users WHERE student_id = ?");
                $stmt->execute([$data['student_id']]);
                if ($stmt->fetch()) {
                    return ['success' => false, 'message' => 'Student ID already exists'];
                }
            }
            
            // Hash password
            $hashed_password = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Insert new user
            $sql = "INSERT INTO users (student_id, email, password, first_name, last_name, middle_name, user_type, contact_number, address, graduation_year, course) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $data['student_id'],
                $data['email'],
                $hashed_password,
                $data['first_name'],
                $data['last_name'],
                $data['middle_name'] ?? null,
                $data['user_type'],
                $data['contact_number'],
                $data['address'],
                $data['graduation_year'] ?? null,
                $data['course'] ?? null
            ]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Registration successful'];
            } else {
                return ['success' => false, 'message' => 'Registration failed'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Registration error: ' . $e->getMessage()];
        }
    }
    
    // Logout function
    public function logout() {
        session_destroy();
        return true;
    }
    
    // Check if user is logged in
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Check if user is admin
    public function isAdmin() {
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
    
    // Get current user info
    public function getCurrentUser() {
        if ($this->isLoggedIn()) {
            return [
                'id' => $_SESSION['user_id'],
                'email' => $_SESSION['email'],
                'first_name' => $_SESSION['first_name'],
                'last_name' => $_SESSION['last_name'],
                'user_type' => $_SESSION['user_type'],
                'student_id' => $_SESSION['student_id']
            ];
        }
        return null;
    }
    
    // Require login (redirect to login page if not logged in)
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    // Require admin (redirect to login if not admin)
    public function requireAdmin() {
        if (!$this->isAdmin()) {
            header('Location: login.php');
            exit();
        }
    }
    
    // Update last login timestamp
    private function updateLastLogin($user_id) {
        try {
            $stmt = $this->db->prepare("UPDATE users SET updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $stmt->execute([$user_id]);
        } catch (Exception $e) {
            // Log error but don't fail login
        }
    }
    
    // Change password
    public function changePassword($user_id, $current_password, $new_password) {
        try {
            // Verify current password
            $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();
            
            if (!password_verify($current_password, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user_id]);
            
            if ($result) {
                return ['success' => true, 'message' => 'Password changed successfully'];
            } else {
                return ['success' => false, 'message' => 'Failed to change password'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }
}

// Initialize auth instance
$auth = new Auth();
?>