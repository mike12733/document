<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require admin login
$auth->requireAdmin();
$current_user = $auth->getCurrentUser();

$db = getDBConnection();

// Get dashboard statistics
$stats = [];

// Total requests
$stmt = $db->prepare("SELECT COUNT(*) as total FROM document_requests");
$stmt->execute();
$stats['total_requests'] = $stmt->fetch()['total'];

// Pending requests
$stmt = $db->prepare("SELECT COUNT(*) as total FROM document_requests WHERE status = 'pending'");
$stmt->execute();
$stats['pending_requests'] = $stmt->fetch()['total'];

// Processing requests
$stmt = $db->prepare("SELECT COUNT(*) as total FROM document_requests WHERE status = 'processing'");
$stmt->execute();
$stats['processing_requests'] = $stmt->fetch()['total'];

// Ready for pickup
$stmt = $db->prepare("SELECT COUNT(*) as total FROM document_requests WHERE status = 'ready_for_pickup'");
$stmt->execute();
$stats['ready_requests'] = $stmt->fetch()['total'];

// Total users
$stmt = $db->prepare("SELECT COUNT(*) as total FROM users WHERE user_type != 'admin'");
$stmt->execute();
$stats['total_users'] = $stmt->fetch()['total'];

// Today's requests
$stmt = $db->prepare("SELECT COUNT(*) as total FROM document_requests WHERE DATE(created_at) = CURDATE()");
$stmt->execute();
$stats['today_requests'] = $stmt->fetch()['total'];

// Revenue this month
$stmt = $db->prepare("
    SELECT SUM(total_amount) as revenue 
    FROM document_requests 
    WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
    AND YEAR(created_at) = YEAR(CURRENT_DATE())
    AND payment_status = 'paid'
");
$stmt->execute();
$stats['monthly_revenue'] = $stmt->fetch()['revenue'] ?? 0;

// Get recent requests
$stmt = $db->prepare("
    SELECT dr.*, dt.name as document_name, u.first_name, u.last_name, u.student_id
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    ORDER BY dr.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_requests = $stmt->fetchAll();

// Get requests by status for quick access
$stmt = $db->prepare("
    SELECT dr.*, dt.name as document_name, u.first_name, u.last_name, u.student_id
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id
    WHERE dr.status = 'pending'
    ORDER BY dr.created_at ASC 
    LIMIT 5
");
$stmt->execute();
$pending_requests = $stmt->fetchAll();

function getBadgeClass($status) {
    switch ($status) {
        case 'pending': return 'badge-pending';
        case 'processing': return 'badge-processing';
        case 'approved': return 'badge-approved';
        case 'denied': return 'badge-denied';
        case 'ready_for_pickup': return 'badge-ready';
        case 'completed': return 'badge-completed';
        default: return 'badge-pending';
    }
}

function formatStatus($status) {
    return ucwords(str_replace('_', ' ', $status));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - LNHS Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <nav class="navbar">
            <div class="logo">
                <h1>🎓 LNHS Portal - Admin</h1>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage-requests.php">Manage Requests</a></li>
                <li><a href="manage-users.php">Users</a></li>
                <li><a href="reports.php">Reports</a></li>
                <li><a href="settings.php">Settings</a></li>
            </ul>
            <div class="user-menu">
                <div class="user-info" onclick="toggleDropdown()">
                    <span>👤 <?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?> (Admin)</span>
                    <span>▼</span>
                </div>
                <div class="dropdown-menu" id="user-dropdown">
                    <a href="profile.php">👤 Profile</a>
                    <a href="change-password.php">🔒 Change Password</a>
                    <a href="../student/logout.php">🚪 Logout</a>
                </div>
            </div>
        </nav>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Welcome to Admin Dashboard 👨‍💼</h2>
                        <p>Manage document requests, users, and system settings from this central dashboard.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--primary-color);"><?php echo number_format($stats['total_requests']); ?></h3>
                        <p>Total Requests</p>
                        <small class="text-muted">All time</small>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--warning-color);"><?php echo number_format($stats['pending_requests']); ?></h3>
                        <p>Pending Review</p>
                        <small class="text-muted">Requires attention</small>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--success-color);"><?php echo number_format($stats['ready_requests']); ?></h3>
                        <p>Ready for Pickup</p>
                        <small class="text-muted">Completed documents</small>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--primary-color);">₱<?php echo number_format($stats['monthly_revenue'], 2); ?></h3>
                        <p>Monthly Revenue</p>
                        <small class="text-muted"><?php echo date('F Y'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 style="color: var(--secondary-color);"><?php echo number_format($stats['total_users']); ?></h4>
                        <p>Registered Users</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 style="color: var(--info-color);"><?php echo number_format($stats['today_requests']); ?></h4>
                        <p>Today's Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-4">
                <div class="card">
                    <div class="card-body text-center">
                        <h4 style="color: var(--primary-color);"><?php echo number_format($stats['processing_requests']); ?></h4>
                        <p>In Processing</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Pending Requests -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>⏳ Pending Requests</h2>
                            <a href="manage-requests.php?status=pending" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($pending_requests)): ?>
                            <div class="text-center" style="padding: 2rem;">
                                <p>✅ No pending requests!</p>
                                <small class="text-muted">All requests have been reviewed</small>
                            </div>
                        <?php else: ?>
                            <div style="max-height: 400px; overflow-y: auto;">
                                <?php foreach ($pending_requests as $request): ?>
                                <div style="border-bottom: 1px solid var(--border-color); padding: 1rem 0;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">
                                                #<?php echo $request['id']; ?> - <?php echo htmlspecialchars($request['document_name']); ?>
                                            </h4>
                                            <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">
                                                <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                                <?php if ($request['student_id']): ?>
                                                    (<?php echo htmlspecialchars($request['student_id']); ?>)
                                                <?php endif; ?>
                                            </p>
                                            <small class="text-muted">
                                                Submitted: <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                            </small>
                                        </div>
                                        <div style="text-align: right;">
                                            <span class="badge <?php echo getBadgeClass($request['status']); ?>">
                                                <?php echo formatStatus($request['status']); ?>
                                            </span>
                                            <br>
                                            <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                               class="btn btn-sm btn-primary mt-2">Review</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-6">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h2>📋 Recent Activity</h2>
                            <a href="manage-requests.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($recent_requests as $request): ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding: 1rem 0;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">
                                            #<?php echo $request['id']; ?> - <?php echo htmlspecialchars($request['document_name']); ?>
                                        </h4>
                                        <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">
                                            <?php echo htmlspecialchars($request['first_name'] . ' ' . $request['last_name']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?>
                                        </small>
                                    </div>
                                    <div style="text-align: right;">
                                        <span class="badge <?php echo getBadgeClass($request['status']); ?>">
                                            <?php echo formatStatus($request['status']); ?>
                                        </span>
                                        <br>
                                        <small style="color: var(--primary-color); font-weight: 500;">
                                            ₱<?php echo number_format($request['total_amount'], 2); ?>
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2>🚀 Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <a href="manage-requests.php?status=pending" class="btn btn-warning" style="width: 100%; height: 80px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 1.5rem;">⏳</div>
                                    <div>Review Pending</div>
                                    <small>(<?php echo $stats['pending_requests']; ?> waiting)</small>
                                </a>
                            </div>
                            <div class="col-3">
                                <a href="manage-requests.php" class="btn btn-primary" style="width: 100%; height: 80px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 1.5rem;">📋</div>
                                    <div>All Requests</div>
                                    <small>Manage all</small>
                                </a>
                            </div>
                            <div class="col-3">
                                <a href="reports.php" class="btn btn-success" style="width: 100%; height: 80px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 1.5rem;">📊</div>
                                    <div>Generate Report</div>
                                    <small>Export data</small>
                                </a>
                            </div>
                            <div class="col-3">
                                <a href="manage-users.php" class="btn btn-secondary" style="width: 100%; height: 80px; display: flex; flex-direction: column; justify-content: center; align-items: center;">
                                    <div style="font-size: 1.5rem;">👥</div>
                                    <div>Manage Users</div>
                                    <small>(<?php echo $stats['total_users']; ?> users)</small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- System Status -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2>🔧 System Status</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-3">
                                <div class="text-center">
                                    <div style="color: var(--success-color); font-size: 2rem;">✅</div>
                                    <h4>Database</h4>
                                    <p>Connected</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <div style="color: var(--success-color); font-size: 2rem;">📁</div>
                                    <h4>File Uploads</h4>
                                    <p>Working</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <div style="color: var(--warning-color); font-size: 2rem;">📧</div>
                                    <h4>Email</h4>
                                    <p>Not Configured</p>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="text-center">
                                    <div style="color: var(--warning-color); font-size: 2rem;">📱</div>
                                    <h4>SMS</h4>
                                    <p>Not Configured</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleDropdown() {
            const dropdown = document.getElementById('user-dropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        window.addEventListener('click', function(event) {
            if (!event.target.matches('.user-info') && !event.target.closest('.user-info')) {
                const dropdown = document.getElementById('user-dropdown');
                if (dropdown.classList.contains('show')) {
                    dropdown.classList.remove('show');
                }
            }
        });

        // Auto-refresh every 30 seconds
        setTimeout(function() {
            location.reload();
        }, 30000);
    </script>
</body>
</html>