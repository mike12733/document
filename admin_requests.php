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

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    $request_id = $_POST['request_id'];
    $new_status = $_POST['new_status'];
    $admin_notes = trim($_POST['admin_notes'] ?? '');
    
    try {
        $stmt = $pdo->prepare("
            UPDATE document_requests 
            SET status = ?, admin_notes = ?, updated_at = CURRENT_TIMESTAMP 
            WHERE id = ?
        ");
        $stmt->execute([$new_status, $admin_notes, $request_id]);
        
        // Get request details for notification
        $stmt = $pdo->prepare("
            SELECT dr.*, u.email, u.full_name, dt.name as document_name 
            FROM document_requests dr 
            JOIN users u ON dr.user_id = u.id 
            JOIN document_types dt ON dr.document_type_id = dt.id 
            WHERE dr.id = ?
        ");
        $stmt->execute([$request_id]);
        $request = $stmt->fetch();
        
        // Create notification
        $notification_title = 'Request Status Updated';
        $notification_message = "Your request for {$request['document_name']} has been updated to: " . ucfirst(str_replace('_', ' ', $new_status));
        
        if ($new_status == 'approved') {
            $notification_message .= '. Please proceed with payment to claim your document.';
        } elseif ($new_status == 'ready_for_pickup') {
            $notification_message .= '. Your document is ready for pickup. Please bring a valid ID.';
        } elseif ($new_status == 'denied') {
            $notification_message .= '. Please contact the administration for more details.';
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$request['user_id'], $notification_title, $notification_message, 'portal']);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activity_logs (user_id, action, description, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id, 
            'Updated request status', 
            "Request ID: {$request_id}, New Status: {$new_status}",
            $_SERVER['REMOTE_ADDR']
        ]);
        
        $success = 'Request status updated successfully!';
        
    } catch (Exception $e) {
        $error = 'An error occurred while updating the request.';
    }
}

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = [];
$params = [];

if ($status_filter) {
    $where_conditions[] = "dr.status = ?";
    $params[] = $status_filter;
}

if ($date_from) {
    $where_conditions[] = "DATE(dr.request_date) >= ?";
    $params[] = $date_from;
}

if ($date_to) {
    $where_conditions[] = "DATE(dr.request_date) <= ?";
    $params[] = $date_to;
}

if ($search) {
    $where_conditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR dt.name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT dr.*, u.full_name, u.email, u.student_id, dt.name as document_name, dt.fee
    FROM document_requests dr 
    JOIN users u ON dr.user_id = u.id 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    $where_clause
    ORDER BY dr.request_date DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("
    SELECT COUNT(*) as count 
    FROM document_requests dr 
    JOIN users u ON dr.user_id = u.id 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    $where_clause
");
$stmt->execute($params);
$total_requests = $stmt->fetch()['count'];
$total_pages = ceil($total_requests / $per_page);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Requests - LNHS Portal</title>
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
        .filter-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
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
                            <a class="nav-link active" href="admin_requests.php">
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
                            <h2 class="mb-1">Manage Requests</h2>
                            <p class="text-muted mb-0">View and manage document requests</p>
                        </div>
                        <div>
                            <a href="admin_reports.php" class="btn btn-outline-primary me-2">
                                <i class="fas fa-download me-2"></i>Export
                            </a>
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
                    
                    <!-- Filters -->
                    <div class="filter-card p-3 mb-4">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="approved" <?php echo $status_filter == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="denied" <?php echo $status_filter == 'denied' ? 'selected' : ''; ?>>Denied</option>
                                    <option value="ready_for_pickup" <?php echo $status_filter == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Search</label>
                                <input type="text" name="search" class="form-control" placeholder="Name, email, or document..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="admin_requests.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Requests Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>Document Requests (<?php echo $total_requests; ?> total)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($requests)): ?>
                                <p class="text-muted text-center">No requests found matching your criteria.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Student</th>
                                                <th>Document</th>
                                                <th>Purpose</th>
                                                <th>Status</th>
                                                <th>Request Date</th>
                                                <th>Fee</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($requests as $request): ?>
                                                <tr>
                                                    <td>
                                                        <strong>#<?php echo $request['id']; ?></strong>
                                                    </td>
                                                    <td>
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($request['full_name']); ?></strong><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($request['email']); ?></small>
                                                            <?php if ($request['student_id']): ?>
                                                                <br><small class="text-muted">ID: <?php echo htmlspecialchars($request['student_id']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div style="max-width: 200px;">
                                                            <?php echo htmlspecialchars(substr($request['purpose'], 0, 100)); ?>
                                                            <?php if (strlen($request['purpose']) > 100): ?>
                                                                <span class="text-muted">...</span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $request['status']; ?>">
                                                            <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                                    </td>
                                                    <td>
                                                        <strong>₱<?php echo number_format($request['fee'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group" role="group">
                                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#viewModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-eye"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-success" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#updateModal<?php echo $request['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- View Modal -->
                                                <div class="modal fade" id="viewModal<?php echo $request['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog modal-lg">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Request Details #<?php echo $request['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <div class="row">
                                                                    <div class="col-md-6">
                                                                        <h6>Student Information</h6>
                                                                        <p><strong>Name:</strong> <?php echo htmlspecialchars($request['full_name']); ?></p>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($request['email']); ?></p>
                                                                        <?php if ($request['student_id']): ?>
                                                                            <p><strong>Student ID:</strong> <?php echo htmlspecialchars($request['student_id']); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                    <div class="col-md-6">
                                                                        <h6>Request Information</h6>
                                                                        <p><strong>Document:</strong> <?php echo htmlspecialchars($request['document_name']); ?></p>
                                                                        <p><strong>Fee:</strong> ₱<?php echo number_format($request['fee'], 2); ?></p>
                                                                        <p><strong>Status:</strong> 
                                                                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                                            </span>
                                                                        </p>
                                                                        <p><strong>Request Date:</strong> <?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></p>
                                                                        <?php if ($request['preferred_release_date']): ?>
                                                                            <p><strong>Preferred Date:</strong> <?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?></p>
                                                                        <?php endif; ?>
                                                                    </div>
                                                                </div>
                                                                <hr>
                                                                <h6>Purpose</h6>
                                                                <p><?php echo nl2br(htmlspecialchars($request['purpose'])); ?></p>
                                                                
                                                                <?php if ($request['uploaded_file']): ?>
                                                                    <h6>Uploaded File</h6>
                                                                    <a href="<?php echo htmlspecialchars($request['uploaded_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                        <i class="fas fa-download me-2"></i>View File
                                                                    </a>
                                                                <?php endif; ?>
                                                                
                                                                <?php if ($request['admin_notes']): ?>
                                                                    <h6>Admin Notes</h6>
                                                                    <p class="text-muted"><?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?></p>
                                                                <?php endif; ?>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                
                                                <!-- Update Modal -->
                                                <div class="modal fade" id="updateModal<?php echo $request['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Update Request #<?php echo $request['id']; ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="update_status">
                                                                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Current Status</label>
                                                                        <div>
                                                                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                                            </span>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="new_status" class="form-label">New Status</label>
                                                                        <select name="new_status" id="new_status" class="form-select" required>
                                                                            <option value="pending" <?php echo $request['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                                            <option value="processing" <?php echo $request['status'] == 'processing' ? 'selected' : ''; ?>>Processing</option>
                                                                            <option value="approved" <?php echo $request['status'] == 'approved' ? 'selected' : ''; ?>>Approved</option>
                                                                            <option value="denied" <?php echo $request['status'] == 'denied' ? 'selected' : ''; ?>>Denied</option>
                                                                            <option value="ready_for_pickup" <?php echo $request['status'] == 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                                                        </select>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label for="admin_notes" class="form-label">Admin Notes (Optional)</label>
                                                                        <textarea name="admin_notes" id="admin_notes" class="form-control" rows="3" 
                                                                                  placeholder="Add any notes or comments..."><?php echo htmlspecialchars($request['admin_notes'] ?? ''); ?></textarea>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fas fa-save me-2"></i>Update Status
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
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                                        Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>&search=<?php echo urlencode($search); ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>