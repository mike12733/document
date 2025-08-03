<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];

// Get user data
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get notifications count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$notifications_count = $stmt->fetch()['count'];

// Get recent requests for students/alumni
$recent_requests = [];
if ($user_type == 'student' || $user_type == 'alumni') {
    $stmt = $pdo->prepare("
        SELECT dr.*, dt.name as document_name, dt.fee 
        FROM document_requests dr 
        JOIN document_types dt ON dr.document_type_id = dt.id 
        WHERE dr.user_id = ? 
        ORDER BY dr.request_date DESC 
        LIMIT 5
    ");
    $stmt->execute([$user_id]);
    $recent_requests = $stmt->fetchAll();
}

// Get pending requests count for admin
$pending_count = 0;
if ($user_type == 'admin') {
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM document_requests WHERE status = 'pending'");
    $stmt->execute();
    $pending_count = $stmt->fetch()['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LNHS Documents Request Portal</title>
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
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .status-badge {
            padding: 8px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-processing { background: #cce5ff; color: #004085; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-denied { background: #f8d7da; color: #721c24; }
        .status-ready { background: #d1ecf1; color: #0c5460; }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
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
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        
                        <?php if ($user_type == 'student' || $user_type == 'alumni'): ?>
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
                        
                        <?php if ($user_type == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin_requests.php">
                                    <i class="fas fa-tasks me-2"></i> Manage Requests
                                    <?php if ($pending_count > 0): ?>
                                        <span class="badge bg-danger ms-2"><?php echo $pending_count; ?></span>
                                    <?php endif; ?>
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
                                <?php if ($notifications_count > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $notifications_count; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                        
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">
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
                            <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['full_name']); ?>!</h2>
                            <p class="text-muted mb-0"><?php echo ucfirst($user_type); ?> Dashboard</p>
                        </div>
                        <div class="d-flex align-items-center">
                            <div class="position-relative me-3">
                                <a href="notifications.php" class="btn btn-outline-primary">
                                    <i class="fas fa-bell"></i>
                                    <?php if ($notifications_count > 0): ?>
                                        <span class="notification-badge"><?php echo $notifications_count; ?></span>
                                    <?php endif; ?>
                                </a>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['username']); ?>
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Dashboard Content -->
                    <?php if ($user_type == 'student' || $user_type == 'alumni'): ?>
                        <!-- Student/Alumni Dashboard -->
                        <div class="row">
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="bg-primary rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-file-alt text-white" style="font-size: 24px;"></i>
                                        </div>
                                        <h5 class="card-title">Request Document</h5>
                                        <p class="card-text text-muted">Submit a new document request</p>
                                        <a href="request_document.php" class="btn btn-primary">New Request</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="bg-success rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-list text-white" style="font-size: 24px;"></i>
                                        </div>
                                        <h5 class="card-title">My Requests</h5>
                                        <p class="card-text text-muted">View and track your requests</p>
                                        <a href="my_requests.php" class="btn btn-success">View Requests</a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card">
                                    <div class="card-body text-center">
                                        <div class="bg-info rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 60px; height: 60px;">
                                            <i class="fas fa-bell text-white" style="font-size: 24px;"></i>
                                        </div>
                                        <h5 class="card-title">Notifications</h5>
                                        <p class="card-text text-muted">Check your notifications</p>
                                        <a href="notifications.php" class="btn btn-info">View Notifications</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Requests -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-clock me-2"></i>Recent Requests
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <?php if (empty($recent_requests)): ?>
                                            <p class="text-muted text-center">No recent requests found.</p>
                                        <?php else: ?>
                                            <div class="table-responsive">
                                                <table class="table table-hover">
                                                    <thead>
                                                        <tr>
                                                            <th>Document</th>
                                                            <th>Purpose</th>
                                                            <th>Status</th>
                                                            <th>Request Date</th>
                                                            <th>Action</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($recent_requests as $request): ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                                                <td><?php echo htmlspecialchars($request['purpose']); ?></td>
                                                                <td>
                                                                    <span class="status-badge status-<?php echo $request['status']; ?>">
                                                                        <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                                    </span>
                                                                </td>
                                                                <td><?php echo date('M d, Y', strtotime($request['request_date'])); ?></td>
                                                                <td>
                                                                    <a href="view_request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-eye"></i>
                                                                    </a>
                                                                </td>
                                                            </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                    <?php else: ?>
                        <!-- Admin Dashboard -->
                        <div class="row">
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card bg-primary text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0"><?php echo $pending_count; ?></h4>
                                                <p class="mb-0">Pending Requests</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-clock fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card bg-success text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    <?php 
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM document_requests WHERE status = 'approved'");
                                                    $stmt->execute();
                                                    echo $stmt->fetch()['count'];
                                                    ?>
                                                </h4>
                                                <p class="mb-0">Approved</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-check-circle fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    <?php 
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE user_type IN ('student', 'alumni')");
                                                    $stmt->execute();
                                                    echo $stmt->fetch()['count'];
                                                    ?>
                                                </h4>
                                                <p class="mb-0">Total Users</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-users fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6 col-lg-3 mb-4">
                                <div class="card bg-warning text-white">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between">
                                            <div>
                                                <h4 class="mb-0">
                                                    <?php 
                                                    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM document_requests WHERE status = 'ready_for_pickup'");
                                                    $stmt->execute();
                                                    echo $stmt->fetch()['count'];
                                                    ?>
                                                </h4>
                                                <p class="mb-0">Ready for Pickup</p>
                                            </div>
                                            <div class="align-self-center">
                                                <i class="fas fa-box fa-2x"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Quick Actions -->
                        <div class="row">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0">
                                            <i class="fas fa-bolt me-2"></i>Quick Actions
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 mb-3">
                                                <a href="admin_requests.php" class="btn btn-primary w-100">
                                                    <i class="fas fa-tasks me-2"></i>Manage Requests
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="admin_users.php" class="btn btn-success w-100">
                                                    <i class="fas fa-users me-2"></i>Manage Users
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="admin_reports.php" class="btn btn-info w-100">
                                                    <i class="fas fa-chart-bar me-2"></i>Generate Reports
                                                </a>
                                            </div>
                                            <div class="col-md-3 mb-3">
                                                <a href="admin_documents.php" class="btn btn-warning w-100">
                                                    <i class="fas fa-file-alt me-2"></i>Document Types
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>