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

// Handle export functionality
if (isset($_POST['export'])) {
    $export_type = $_POST['export_type'];
    $date_from = $_POST['date_from'] ?? '';
    $date_to = $_POST['date_to'] ?? '';
    
    // Build query based on export type
    switch ($export_type) {
        case 'requests':
            $where_conditions = [];
            $params = [];
            
            if ($date_from) {
                $where_conditions[] = "DATE(dr.request_date) >= ?";
                $params[] = $date_from;
            }
            if ($date_to) {
                $where_conditions[] = "DATE(dr.request_date) <= ?";
                $params[] = $date_to;
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            $stmt = $pdo->prepare("
                SELECT dr.*, u.full_name, u.email, u.student_id, dt.name as document_name, dt.fee
                FROM document_requests dr 
                JOIN users u ON dr.user_id = u.id 
                JOIN document_types dt ON dr.document_type_id = dt.id 
                $where_clause
                ORDER BY dr.request_date DESC
            ");
            $stmt->execute($params);
            $data = $stmt->fetchAll();
            
            // Generate CSV
            $filename = "requests_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Student Name', 'Email', 'Student ID', 'Document', 'Purpose', 'Status', 'Fee', 'Request Date', 'Preferred Date']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['full_name'],
                    $row['email'],
                    $row['student_id'],
                    $row['document_name'],
                    $row['purpose'],
                    $row['status'],
                    $row['fee'],
                    $row['request_date'],
                    $row['preferred_release_date']
                ]);
            }
            fclose($output);
            exit();
            
        case 'users':
            $stmt = $pdo->prepare("SELECT * FROM users ORDER BY created_at DESC");
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            $filename = "users_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'Username', 'Full Name', 'Email', 'User Type', 'Student ID', 'Course', 'Year Level', 'Contact', 'Created Date']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['username'],
                    $row['full_name'],
                    $row['email'],
                    $row['user_type'],
                    $row['student_id'],
                    $row['course'],
                    $row['year_level'],
                    $row['contact_number'],
                    $row['created_at']
                ]);
            }
            fclose($output);
            exit();
            
        case 'activity':
            $stmt = $pdo->prepare("
                SELECT al.*, u.full_name, u.username 
                FROM activity_logs al 
                LEFT JOIN users u ON al.user_id = u.id 
                ORDER BY al.created_at DESC
            ");
            $stmt->execute();
            $data = $stmt->fetchAll();
            
            $filename = "activity_export_" . date('Y-m-d') . ".csv";
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            fputcsv($output, ['ID', 'User', 'Action', 'Description', 'IP Address', 'Date']);
            
            foreach ($data as $row) {
                fputcsv($output, [
                    $row['id'],
                    $row['full_name'] ? $row['full_name'] . ' (@' . $row['username'] . ')' : 'System',
                    $row['action'],
                    $row['description'],
                    $row['ip_address'],
                    $row['created_at']
                ]);
            }
            fclose($output);
            exit();
    }
}

// Get statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM document_requests");
$stmt->execute();
$total_requests = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE user_type IN ('student', 'alumni')");
$stmt->execute();
$total_users = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM document_requests WHERE status = 'pending'");
$stmt->execute();
$pending_requests = $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM document_requests WHERE status = 'ready_for_pickup'");
$stmt->execute();
$ready_requests = $stmt->fetch()['total'];

// Get monthly statistics
$stmt = $pdo->prepare("
    SELECT DATE_FORMAT(request_date, '%Y-%m') as month, COUNT(*) as count 
    FROM document_requests 
    WHERE request_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(request_date, '%Y-%m')
    ORDER BY month DESC
");
$stmt->execute();
$monthly_stats = $stmt->fetchAll();

// Get document type statistics
$stmt = $pdo->prepare("
    SELECT dt.name, COUNT(dr.id) as count, SUM(dt.fee) as total_fee
    FROM document_types dt
    LEFT JOIN document_requests dr ON dt.id = dr.document_type_id
    GROUP BY dt.id, dt.name
    ORDER BY count DESC
");
$stmt->execute();
$document_stats = $stmt->fetchAll();

// Get recent activity
$stmt = $pdo->prepare("
    SELECT al.*, u.full_name, u.username 
    FROM activity_logs al 
    LEFT JOIN users u ON al.user_id = u.id 
    ORDER BY al.created_at DESC 
    LIMIT 10
");
$stmt->execute();
$recent_activity = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - LNHS Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
        }
        .chart-container {
            position: relative;
            height: 300px;
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
                            <a class="nav-link" href="admin_users.php">
                                <i class="fas fa-users me-2"></i> Manage Users
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_reports.php">
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
                            <h2 class="mb-1">Reports & Analytics</h2>
                            <p class="text-muted mb-0">System statistics and data exports</p>
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
                    
                    <!-- Statistics Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($total_requests); ?></h3>
                                    <p class="mb-0">Total Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($total_users); ?></h3>
                                    <p class="mb-0">Total Users</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($pending_requests); ?></h3>
                                    <p class="mb-0">Pending Requests</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card stats-card">
                                <div class="card-body text-center">
                                    <h3 class="mb-0"><?php echo number_format($ready_requests); ?></h3>
                                    <p class="mb-0">Ready for Pickup</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Charts Row -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-line me-2"></i>Monthly Requests
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="monthlyChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-chart-pie me-2"></i>Document Type Distribution
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="chart-container">
                                        <canvas id="documentChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Export Section -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-download me-2"></i>Export Data
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="POST" action="" class="row g-3">
                                        <div class="col-md-3">
                                            <label class="form-label">Export Type</label>
                                            <select name="export_type" class="form-select" required>
                                                <option value="">Select Export Type</option>
                                                <option value="requests">Document Requests</option>
                                                <option value="users">User Accounts</option>
                                                <option value="activity">Activity Logs</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">From Date</label>
                                            <input type="date" name="date_from" class="form-control">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">To Date</label>
                                            <input type="date" name="date_to" class="form-control">
                                        </div>
                                        <div class="col-md-3 d-flex align-items-end">
                                            <button type="submit" name="export" class="btn btn-primary">
                                                <i class="fas fa-download me-2"></i>Export CSV
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Document Statistics -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-file-alt me-2"></i>Document Type Statistics
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Document Type</th>
                                                    <th>Total Requests</th>
                                                    <th>Total Revenue</th>
                                                    <th>Average Processing Time</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($document_stats as $doc): ?>
                                                    <tr>
                                                        <td><strong><?php echo htmlspecialchars($doc['name']); ?></strong></td>
                                                        <td><?php echo number_format($doc['count']); ?></td>
                                                        <td>₱<?php echo number_format($doc['total_fee'] ?? 0, 2); ?></td>
                                                        <td>3-5 days</td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Activity -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-history me-2"></i>Recent Activity
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if (empty($recent_activity)): ?>
                                        <p class="text-muted text-center">No recent activity found.</p>
                                    <?php else: ?>
                                        <div class="list-group list-group-flush">
                                            <?php foreach ($recent_activity as $activity): ?>
                                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($activity['action']); ?></strong>
                                                        <?php if ($activity['description']): ?>
                                                            <br><small class="text-muted"><?php echo htmlspecialchars($activity['description']); ?></small>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="text-end">
                                                        <small class="text-muted">
                                                            <?php echo $activity['full_name'] ? htmlspecialchars($activity['full_name']) : 'System'; ?>
                                                        </small><br>
                                                        <small class="text-muted">
                                                            <?php echo date('M d, Y H:i', strtotime($activity['created_at'])); ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
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
    <script>
        // Monthly Chart
        const monthlyCtx = document.getElementById('monthlyChart').getContext('2d');
        new Chart(monthlyCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($monthly_stats, 'month')); ?>,
                datasets: [{
                    label: 'Requests',
                    data: <?php echo json_encode(array_column($monthly_stats, 'count')); ?>,
                    borderColor: '#667eea',
                    backgroundColor: 'rgba(102, 126, 234, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Document Chart
        const documentCtx = document.getElementById('documentChart').getContext('2d');
        new Chart(documentCtx, {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($document_stats, 'name')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($document_stats, 'count')); ?>,
                    backgroundColor: [
                        '#667eea',
                        '#764ba2',
                        '#f093fb',
                        '#f5576c',
                        '#4facfe'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    </script>
</body>
</html>