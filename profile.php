<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    
    // Update profile
    $stmt = $pdo->prepare("
        UPDATE users 
        SET email = ?, full_name = ?, contact_number = ?, address = ? 
        WHERE id = ?
    ");
    $stmt->execute([$email, $full_name, $contact_number, $address, $user_id]);
    
    // Update session data
    $_SESSION['full_name'] = $full_name;
    
    $success = 'Profile updated successfully!';
    
    // Refresh user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - LNHS Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            margin: 0 auto 20px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar p-3">
                    <div class="text-center mb-4">
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-graduation-cap text-primary" style="font-size: 24px;"></i>
                        </div>
                        <h5 class="mt-2 mb-0">LNHS Portal</h5>
                        <small class="text-white-50">Documents Request System</small>
                    </div>
                    
                    <hr class="bg-white">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if ($_SESSION['user_type'] == 'student' || $_SESSION['user_type'] == 'alumni'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="request_document.php">
                                    <i class="fas fa-file-alt me-2"></i> Request Document
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="my_requests.php">
                                    <i class="fas fa-list me-2"></i> My Requests
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_requests.php">
                                    <i class="fas fa-tasks me-2"></i> Manage Requests
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_users.php">
                                    <i class="fas fa-users me-2"></i> Manage Users
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_reports.php">
                                    <i class="fas fa-chart-bar me-2"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell me-2"></i> Notifications
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link active" href="profile.php">
                                <i class="fas fa-user me-2"></i> Profile
                            </a>
                        </li>
                        
                        <li class="nav-item mt-4">
                            <a class="nav-link text-warning" href="logout.php">
                                <i class="fas fa-sign-out-alt me-2"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">My Profile</h2>
                            <p class="text-muted mb-0">Manage your account information</p>
                        </div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                    
                    <?php if ($success): ?>
                        <div class="alert alert-success" role="alert">
                            <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($error): ?>
                        <div class="alert alert-danger" role="alert">
                            <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-user me-2"></i>Profile Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="">
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                                <div class="form-text">Username cannot be changed</div>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">User Type</label>
                                                <input type="text" class="form-control" value="<?php echo ucfirst($user['user_type']); ?>" readonly>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                            </div>
                                        </div>
                                        
                                        <?php if ($user['student_id'] || $user['course'] || $user['year_level']): ?>
                                            <hr>
                                            <h6>Academic Information</h6>
                                            <div class="row">
                                                <?php if ($user['student_id']): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Student ID</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['student_id']); ?>" readonly>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($user['course']): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Course</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['course']); ?>" readonly>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($user['year_level']): ?>
                                                    <div class="col-md-4 mb-3">
                                                        <label class="form-label">Year Level</label>
                                                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['year_level']); ?>" readonly>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex justify-content-end">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save me-2"></i>Update Profile
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body text-center">
                                    <div class="profile-avatar">
                                        <i class="fas fa-user"></i>
                                    </div>
                                    <h5><?php echo htmlspecialchars($user['full_name']); ?></h5>
                                    <p class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></p>
                                    <span class="badge bg-primary"><?php echo ucfirst($user['user_type']); ?></span>
                                    
                                    <hr>
                                    
                                    <div class="text-start">
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-2"></i>
                                            Member since: <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                    
                                    <?php if ($user['contact_number']): ?>
                                        <div class="text-start mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-phone me-2"></i>
                                                <?php echo htmlspecialchars($user['contact_number']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($user['email']): ?>
                                        <div class="text-start mt-2">
                                            <small class="text-muted">
                                                <i class="fas fa-envelope me-2"></i>
                                                <?php echo htmlspecialchars($user['email']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>