<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_user':
                $username = trim($_POST['username']);
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $user_type = $_POST['user_type'];
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                
                // Check if username or email already exists
                $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
                $stmt->execute([$username, $email]);
                if ($stmt->fetch()) {
                    $error = 'Username or email already exists.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO users (username, password, email, full_name, user_type, student_id, course, year_level, contact_number, address) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $username, $password, $email, $full_name, $user_type,
                        $_POST['student_id'] ?? null,
                        $_POST['course'] ?? null,
                        $_POST['year_level'] ?? null,
                        $_POST['contact_number'] ?? null,
                        $_POST['address'] ?? null
                    ]);
                    $success = 'User added successfully!';
                }
                break;
                
            case 'update_user':
                $update_user_id = $_POST['user_id'];
                $email = trim($_POST['email']);
                $full_name = trim($_POST['full_name']);
                $user_type = $_POST['user_type'];
                
                $stmt = $pdo->prepare("
                    UPDATE users 
                    SET email = ?, full_name = ?, user_type = ?, student_id = ?, course = ?, year_level = ?, contact_number = ?, address = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $email, $full_name, $user_type,
                    $_POST['student_id'] ?? null,
                    $_POST['course'] ?? null,
                    $_POST['year_level'] ?? null,
                    $_POST['contact_number'] ?? null,
                    $_POST['address'] ?? null,
                    $update_user_id
                ]);
                $success = 'User updated successfully!';
                break;
                
            case 'delete_user':
                $delete_user_id = $_POST['user_id'];
                if ($delete_user_id != $user_id) { // Prevent self-deletion
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$delete_user_id]);
                    $success = 'User deleted successfully!';
                } else {
                    $error = 'You cannot delete your own account.';
                }
                break;
        }
    }
}

// Get filter parameters
$user_type_filter = $_GET['user_type'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($user_type_filter) {
    $where_conditions[] = "user_type = ?";
    $params[] = $user_type_filter;
}

if ($search) {
    $where_conditions[] = "(username LIKE ? OR email LIKE ? OR full_name LIKE ? OR student_id LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get users with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT * FROM users 
    $where_clause
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users $where_clause");
$stmt->execute($params);
$total_users = $stmt->fetch()['count'];
$total_pages = ceil($total_users / $per_page);

// Get user type counts
$stmt = $pdo->prepare("SELECT user_type, COUNT(*) as count FROM users GROUP BY user_type");
$stmt->execute();
$user_type_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - LNHS Portal</title>
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
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .user-type-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .type-admin { background: #dc3545; color: white; }
        .type-student { background: #007bff; color: white; }
        .type-alumni { background: #28a745; color: white; }
        .type-staff { background: #ffc107; color: #212529; }
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
                        <small class="text-white-50">Admin Panel</small>
                    </div>
                    
                    <hr class="bg-white">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_requests.php">
                                <i class="fas fa-tasks me-2"></i> Manage Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_users.php">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar me-2"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="admin_documents.php">
                                <i class="fas fa-file-alt me-2"></i> Document Types
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
                            <h2 class="mb-1">Manage Users</h2>
                            <p class="text-muted mb-0">Manage student, alumni, and staff accounts</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addUserModal">
                                <i class="fas fa-plus me-2"></i>Add User
                            </button>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
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
                    
                    <!-- User Type Summary -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-primary mb-1"><?php echo $user_type_counts['student'] ?? 0; ?></h4>
                                    <small class="text-muted">Students</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-success mb-1"><?php echo $user_type_counts['alumni'] ?? 0; ?></h4>
                                    <small class="text-muted">Alumni</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-warning mb-1"><?php echo $user_type_counts['staff'] ?? 0; ?></h4>
                                    <small class="text-muted">Staff</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-danger mb-1"><?php echo $user_type_counts['admin'] ?? 0; ?></h4>
                                    <small class="text-muted">Admins</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card p-3 mb-4">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">User Type</label>
                                <select name="user_type" class="form-select">
                                    <option value="">All Types</option>
                                    <option value="student" <?php echo $user_type_filter == 'student' ? 'selected' : ''; ?>>Student</option>
                                    <option value="alumni" <?php echo $user_type_filter == 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                                    <option value="staff" <?php echo $user_type_filter == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                    <option value="admin" <?php echo $user_type_filter == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Username, email, name, or student ID..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="admin_users.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Users Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users me-2"></i>User Accounts (<?php echo $total_users; ?> total)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($users)): ?>
                                <p class="text-muted text-center">No users found matching your criteria.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>User</th>
                                                <th>Type</th>
                                                <th>Contact</th>
                                                <th>Student Info</th>
                                                <th>Created</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($user['full_name']); ?></strong><br>
                                                            <small class="text-muted">@<?php echo htmlspecialchars($user['username']); ?></small><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="user-type-badge type-<?php echo $user['user_type']; ?>">
                                                            <?php echo ucfirst($user['user_type']); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['contact_number']): ?>
                                                            <small><i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($user['contact_number']); ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">No contact</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <?php if ($user['student_id']): ?>
                                                            <small><strong>ID:</strong> <?php echo htmlspecialchars($user['student_id']); ?></small><br>
                                                        <?php endif; ?>
                                                        <?php if ($user['course']): ?>
                                                            <small><strong>Course:</strong> <?php echo htmlspecialchars($user['course']); ?></small><br>
                                                        <?php endif; ?>
                                                        <?php if ($user['year_level']): ?>
                                                            <small><strong>Year:</strong> <?php echo htmlspecialchars($user['year_level']); ?></small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editUserModal<?php echo $user['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($user['id'] != $user_id): ?>
                                                                <button type="button" class="btn btn-outline-danger" 
                                                                        onclick="deleteUser(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['full_name']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Edit User Modal -->
                                                <div class="modal fade" id="editUserModal<?php echo $user['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit User: <?php echo htmlspecialchars($user['full_name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="update_user">
                                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Username</label>
                                                                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" readonly>
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Email</label>
                                                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Full Name</label>
                                                                            <input type="text" name="full_name" class="form-control" value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">User Type</label>
                                                                            <select name="user_type" class="form-select" required>
                                                                                <option value="student" <?php echo $user['user_type'] == 'student' ? 'selected' : ''; ?>>Student</option>
                                                                                <option value="alumni" <?php echo $user['user_type'] == 'alumni' ? 'selected' : ''; ?>>Alumni</option>
                                                                                <option value="staff" <?php echo $user['user_type'] == 'staff' ? 'selected' : ''; ?>>Staff</option>
                                                                                <option value="admin" <?php echo $user['user_type'] == 'admin' ? 'selected' : ''; ?>>Admin</option>
                                                                            </select>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Student ID</label>
                                                                            <input type="text" name="student_id" class="form-control" value="<?php echo htmlspecialchars($user['student_id'] ?? ''); ?>">
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Contact Number</label>
                                                                            <input type="text" name="contact_number" class="form-control" value="<?php echo htmlspecialchars($user['contact_number'] ?? ''); ?>">
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Course</label>
                                                                            <input type="text" name="course" class="form-control" value="<?php echo htmlspecialchars($user['course'] ?? ''); ?>">
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Year Level</label>
                                                                            <input type="text" name="year_level" class="form-control" value="<?php echo htmlspecialchars($user['year_level'] ?? ''); ?>">
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Address</label>
                                                                        <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fas fa-save me-2"></i>Update User
                                                                    </button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&user_type=<?php echo $user_type_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                        Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&user_type=<?php echo $user_type_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&user_type=<?php echo $user_type_filter; ?>&search=<?php echo urlencode($search); ?>">
                                                        Next
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                        </ul>
                                    </nav>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_user">
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Username</label>
                                                <input type="text" name="username" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Email</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="full_name" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">User Type</label>
                                                <select name="user_type" class="form-select" required>
                                                    <option value="">Select User Type</option>
                                                    <option value="student">Student</option>
                                                    <option value="alumni">Alumni</option>
                                                    <option value="staff">Staff</option>
                                                    <option value="admin">Admin</option>
                                                </select>
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Password</label>
                                                <input type="password" name="password" class="form-control" required>
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Student ID</label>
                                                <input type="text" name="student_id" class="form-control">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Contact Number</label>
                                                <input type="text" name="contact_number" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Course</label>
                                                <input type="text" name="course" class="form-control">
                                            </div>
                                        </div>
                                        
                                        <div class="row">
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Year Level</label>
                                                <input type="text" name="year_level" class="form-control">
                                            </div>
                                            <div class="col-md-6 mb-3">
                                                <label class="form-label">Address</label>
                                                <textarea name="address" class="form-control" rows="3"></textarea>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Add User
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteUser(userId, userName) {
            if (confirm('Are you sure you want to delete user "' + userName + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>