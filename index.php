<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';

// If user is already logged in, redirect to dashboard
if ($auth->isLoggedIn()) {
    if ($auth->isAdmin()) {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: student/dashboard.php');
    }
    exit();
}

$error_message = '';
$success_message = '';

// Handle login form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    if (empty($email) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        $result = $auth->login($email, $password);
        if ($result['success']) {
            // Redirect based on user type
            if ($result['user']['user_type'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: student/dashboard.php');
            }
            exit();
        } else {
            $error_message = $result['message'];
        }
    }
}

// Handle registration form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'register') {
    $data = [
        'student_id' => trim($_POST['student_id']),
        'email' => trim($_POST['email']),
        'password' => $_POST['password'],
        'first_name' => trim($_POST['first_name']),
        'last_name' => trim($_POST['last_name']),
        'middle_name' => trim($_POST['middle_name']),
        'user_type' => $_POST['user_type'],
        'contact_number' => trim($_POST['contact_number']),
        'address' => trim($_POST['address']),
        'graduation_year' => !empty($_POST['graduation_year']) ? $_POST['graduation_year'] : null,
        'course' => trim($_POST['course'])
    ];
    
    // Validate required fields
    if (empty($data['email']) || empty($data['password']) || empty($data['first_name']) || empty($data['last_name'])) {
        $error_message = 'Please fill in all required fields.';
    } else if (strlen($data['password']) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        $result = $auth->register($data);
        if ($result['success']) {
            $success_message = 'Registration successful! You can now log in.';
        } else {
            $error_message = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LNHS Documents Request Portal</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <nav class="navbar">
            <div class="logo">
                <h1>🎓 LNHS Portal</h1>
            </div>
            <ul class="nav-links">
                <li><a href="#home">Home</a></li>
                <li><a href="#about">About</a></li>
                <li><a href="#services">Services</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </div>

    <div class="container">
        <div class="row">
            <!-- Welcome Section -->
            <div class="col-6">
                <div class="card">
                    <div class="card-body">
                        <h2 class="mb-4">Welcome to LNHS Documents Request Portal</h2>
                        <p class="mb-3">Access the convenient online portal for requesting official documents from Laoag National High School. Students and alumni can now request certificates and documents without visiting the school premises.</p>
                        
                        <div class="mt-4">
                            <h3>Available Services:</h3>
                            <ul style="margin-left: 2rem; margin-top: 1rem;">
                                <li>📜 Certificate of Enrollment</li>
                                <li>🏆 Good Moral Certificate</li>
                                <li>📊 Transcript of Records</li>
                                <li>🎓 Diploma Copy</li>
                                <li>📋 Certificate of Grades</li>
                                <li>📄 Honorable Dismissal</li>
                            </ul>
                        </div>
                        
                        <div class="mt-4">
                            <h3>How it works:</h3>
                            <div class="progress-steps mt-3">
                                <div class="step">
                                    <div class="step-number">1</div>
                                    <div class="step-title">Register/Login</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">2</div>
                                    <div class="step-title">Submit Request</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">3</div>
                                    <div class="step-title">Track Status</div>
                                </div>
                                <div class="step">
                                    <div class="step-number">4</div>
                                    <div class="step-title">Pickup Document</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login/Register Forms -->
            <div class="col-6">
                <!-- Display messages -->
                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <?php echo htmlspecialchars($error_message); ?>
                    </div>
                <?php endif; ?>
                
                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($success_message); ?>
                    </div>
                <?php endif; ?>

                <!-- Login Form -->
                <div class="card" id="login-form">
                    <div class="card-header">
                        <h2>Login</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="login">
                            
                            <div class="form-group">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                Login
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Don't have an account? <a href="#" onclick="toggleForms()" style="color: var(--primary-color);">Register here</a></p>
                        <p><small>Admin login: admin@lnhs.edu.ph / password</small></p>
                    </div>
                </div>

                <!-- Registration Form -->
                <div class="card" id="register-form" style="display: none;">
                    <div class="card-header">
                        <h2>Register</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="register">
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="reg_first_name" class="form-label">First Name *</label>
                                        <input type="text" id="reg_first_name" name="first_name" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="form-group">
                                        <label for="reg_last_name" class="form-label">Last Name *</label>
                                        <input type="text" id="reg_last_name" name="last_name" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_middle_name" class="form-label">Middle Name</label>
                                <input type="text" id="reg_middle_name" name="middle_name" class="form-control">
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_student_id" class="form-label">Student ID</label>
                                <input type="text" id="reg_student_id" name="student_id" class="form-control" placeholder="e.g., 2020-1234">
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_email" class="form-label">Email Address *</label>
                                <input type="email" id="reg_email" name="email" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_password" class="form-label">Password *</label>
                                <input type="password" id="reg_password" name="password" class="form-control" required minlength="6">
                                <small class="form-text">Minimum 6 characters</small>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_user_type" class="form-label">Account Type *</label>
                                <select id="reg_user_type" name="user_type" class="form-control form-select" required>
                                    <option value="">Select Type</option>
                                    <option value="student">Current Student</option>
                                    <option value="alumni">Alumni</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_contact" class="form-label">Contact Number</label>
                                <input type="tel" id="reg_contact" name="contact_number" class="form-control" placeholder="09XXXXXXXXX">
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_course" class="form-label">Course/Track</label>
                                <input type="text" id="reg_course" name="course" class="form-control" placeholder="e.g., STEM, ABM, HUMSS">
                            </div>
                            
                            <div class="form-group" id="graduation-year-group" style="display: none;">
                                <label for="reg_graduation_year" class="form-label">Graduation Year</label>
                                <input type="number" id="reg_graduation_year" name="graduation_year" class="form-control" min="1950" max="2030">
                            </div>
                            
                            <div class="form-group">
                                <label for="reg_address" class="form-label">Address</label>
                                <textarea id="reg_address" name="address" class="form-control" rows="2"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;">
                                Register
                            </button>
                        </form>
                    </div>
                    <div class="card-footer text-center">
                        <p>Already have an account? <a href="#" onclick="toggleForms()" style="color: var(--primary-color);">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleForms() {
            const loginForm = document.getElementById('login-form');
            const registerForm = document.getElementById('register-form');
            
            if (loginForm.style.display === 'none') {
                loginForm.style.display = 'block';
                registerForm.style.display = 'none';
            } else {
                loginForm.style.display = 'none';
                registerForm.style.display = 'block';
            }
        }
        
        // Show graduation year field for alumni
        document.getElementById('reg_user_type').addEventListener('change', function() {
            const graduationGroup = document.getElementById('graduation-year-group');
            if (this.value === 'alumni') {
                graduationGroup.style.display = 'block';
                document.getElementById('reg_graduation_year').required = true;
            } else {
                graduationGroup.style.display = 'none';
                document.getElementById('reg_graduation_year').required = false;
            }
        });
    </script>
</body>
</html>