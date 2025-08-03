<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is student/alumni
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['student', 'alumni'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Build query
$where_conditions = ["dr.user_id = ?"];
$params = [$user_id];

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

$where_clause = 'WHERE ' . implode(' AND ', $where_conditions);

// Get requests with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee, dt.processing_days
    FROM document_requests dr 
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
    JOIN document_types dt ON dr.document_type_id = dt.id 
    $where_clause
");
$stmt->execute($params);
$total_requests = $stmt->fetch()['count'];
$total_pages = ceil($total_requests / $per_page);

// Get status counts
$stmt = $pdo->prepare("
    SELECT status, COUNT(*) as count 
    FROM document_requests 
    WHERE user_id = ? 
    GROUP BY status
");
$stmt->execute([$user_id]);
$status_counts = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - LNHS Portal</title>
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
        .progress-tracker {
            display: flex;
            justify-content: space-between;
            margin: 20px 0;
        }
        .progress-step {
            flex: 1;
            text-align: center;
            position: relative;
        }
        .progress-step:not(:last-child)::after {
            content: '';
            position: absolute;
            top: 15px;
            left: 50%;
            width: 100%;
            height: 2px;
            background: #dee2e6;
            z-index: 1;
        }
        .progress-step.active::after {
            background: #007bff;
        }
        .progress-step.completed::after {
            background: #28a745;
        }
        .step-icon {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #dee2e6;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            position: relative;
            z-index: 2;
        }
        .step-icon.active {
            background: #007bff;
            color: white;
        }
        .step-icon.completed {
            background: #28a745;
            color: white;
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
                        <li class="nav-item">
                            <a class="nav-link" href="request_document.php">
                                <i class="fas fa-file-alt me-2"></i> Request Document
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="my_requests.php">
                                <i class="fas fa-list me-2"></i> My Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="notifications.php">
                                <i class="fas fa-bell me-2"></i> Notifications
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
                            <h2 class="mb-1">My Requests</h2>
                            <p class="text-muted mb-0">Track your document requests</p>
                        </div>
                        <div>
                            <a href="request_document.php" class="btn btn-primary me-2">
                                <i class="fas fa-plus me-2"></i>New Request
                            </a>
                            <a href="dashboard.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                            </a>
                        </div>
                    </div>
                    
                    <!-- Status Summary -->
                    <div class="row mb-4">
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-primary mb-1"><?php echo $status_counts['pending'] ?? 0; ?></h4>
                                    <small class="text-muted">Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-info mb-1"><?php echo $status_counts['processing'] ?? 0; ?></h4>
                                    <small class="text-muted">Processing</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-success mb-1"><?php echo $status_counts['approved'] ?? 0; ?></h4>
                                    <small class="text-muted">Approved</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-warning mb-1"><?php echo $status_counts['ready_for_pickup'] ?? 0; ?></h4>
                                    <small class="text-muted">Ready</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-danger mb-1"><?php echo $status_counts['denied'] ?? 0; ?></h4>
                                    <small class="text-muted">Denied</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="card text-center">
                                <div class="card-body">
                                    <h4 class="text-secondary mb-1"><?php echo $total_requests; ?></h4>
                                    <small class="text-muted">Total</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Filters -->
                    <div class="filter-card p-3 mb-4">
                        <form method="GET" action="" class="row g-3">
                            <div class="col-md-4">
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
                            <div class="col-md-3">
                                <label class="form-label">From Date</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">To Date</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filter
                                </button>
                                <a href="my_requests.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Requests List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list me-2"></i>My Document Requests (<?php echo $total_requests; ?> total)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($requests)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No requests found</h5>
                                    <p class="text-muted">You haven't submitted any document requests yet.</p>
                                    <a href="request_document.php" class="btn btn-primary">
                                        <i class="fas fa-plus me-2"></i>Submit Your First Request
                                    </a>
                                </div>
                            <?php else: ?>
                                <?php foreach ($requests as $request): ?>
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($request['document_name']); ?></h6>
                                                            <p class="text-muted mb-0">Request ID: #<?php echo $request['id']; ?></p>
                                                        </div>
                                                        <div class="text-end">
                                                            <span class="status-badge status-<?php echo $request['status']; ?>">
                                                                <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                            </span>
                                                            <div class="mt-1">
                                                                <small class="text-muted">
                                                                    Requested: <?php echo date('M d, Y', strtotime($request['request_date'])); ?>
                                                                </small>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    
                                                    <p class="mb-2"><strong>Purpose:</strong> <?php echo htmlspecialchars($request['purpose']); ?></p>
                                                    
                                                    <div class="row">
                                                        <div class="col-md-4">
                                                            <small class="text-muted">
                                                                <i class="fas fa-money-bill me-1"></i>
                                                                Fee: ₱<?php echo number_format($request['fee'], 2); ?>
                                                            </small>
                                                        </div>
                                                        <div class="col-md-4">
                                                            <small class="text-muted">
                                                                <i class="fas fa-clock me-1"></i>
                                                                Processing: <?php echo $request['processing_days']; ?> days
                                                            </small>
                                                        </div>
                                                        <?php if ($request['preferred_release_date']): ?>
                                                            <div class="col-md-4">
                                                                <small class="text-muted">
                                                                    <i class="fas fa-calendar me-1"></i>
                                                                    Preferred: <?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?>
                                                                </small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                    
                                                    <?php if ($request['admin_notes']): ?>
                                                        <div class="mt-3 p-3 bg-light rounded">
                                                            <small class="text-muted">
                                                                <i class="fas fa-comment me-1"></i>
                                                                <strong>Admin Notes:</strong> <?php echo htmlspecialchars($request['admin_notes']); ?>
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="col-md-4">
                                                    <!-- Progress Tracker -->
                                                    <div class="progress-tracker">
                                                        <div class="progress-step <?php echo in_array($request['status'], ['pending', 'processing', 'approved', 'ready_for_pickup']) ? 'completed' : ''; ?>">
                                                            <div class="step-icon <?php echo in_array($request['status'], ['pending', 'processing', 'approved', 'ready_for_pickup']) ? 'completed' : 'active'; ?>">
                                                                <i class="fas fa-paper-plane"></i>
                                                            </div>
                                                            <small>Submitted</small>
                                                        </div>
                                                        <div class="progress-step <?php echo in_array($request['status'], ['processing', 'approved', 'ready_for_pickup']) ? 'completed' : ($request['status'] == 'pending' ? 'active' : ''); ?>">
                                                            <div class="step-icon <?php echo in_array($request['status'], ['processing', 'approved', 'ready_for_pickup']) ? 'completed' : ($request['status'] == 'pending' ? 'active' : ''); ?>">
                                                                <i class="fas fa-cog"></i>
                                                            </div>
                                                            <small>Processing</small>
                                                        </div>
                                                        <div class="progress-step <?php echo in_array($request['status'], ['approved', 'ready_for_pickup']) ? 'completed' : ($request['status'] == 'processing' ? 'active' : ''); ?>">
                                                            <div class="step-icon <?php echo in_array($request['status'], ['approved', 'ready_for_pickup']) ? 'completed' : ($request['status'] == 'processing' ? 'active' : ''); ?>">
                                                                <i class="fas fa-check"></i>
                                                            </div>
                                                            <small>Approved</small>
                                                        </div>
                                                        <div class="progress-step <?php echo $request['status'] == 'ready_for_pickup' ? 'completed' : ($request['status'] == 'approved' ? 'active' : ''); ?>">
                                                            <div class="step-icon <?php echo $request['status'] == 'ready_for_pickup' ? 'completed' : ($request['status'] == 'approved' ? 'active' : ''); ?>">
                                                                <i class="fas fa-box"></i>
                                                            </div>
                                                            <small>Ready</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        <?php if ($request['uploaded_file']): ?>
                                                            <a href="<?php echo htmlspecialchars($request['uploaded_file']); ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                                                <i class="fas fa-download me-1"></i>View File
                                                            </a>
                                                        <?php endif; ?>
                                                        
                                                        <?php if ($request['status'] == 'ready_for_pickup'): ?>
                                                            <div class="alert alert-success mt-2">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                <small>Your document is ready for pickup. Please bring a valid ID.</small>
                                                            </div>
                                                        <?php elseif ($request['status'] == 'approved'): ?>
                                                            <div class="alert alert-info mt-2">
                                                                <i class="fas fa-info-circle me-1"></i>
                                                                <small>Your request has been approved. Please proceed with payment.</small>
                                                            </div>
                                                        <?php elseif ($request['status'] == 'denied'): ?>
                                                            <div class="alert alert-warning mt-2">
                                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                                <small>Your request has been denied. Please contact the administration.</small>
                                                            </div>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <nav aria-label="Page navigation" class="mt-4">
                                        <ul class="pagination justify-content-center">
                                            <?php if ($page > 1): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page - 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                        Previous
                                                    </a>
                                                </li>
                                            <?php endif; ?>
                                            
                                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                    <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
                                                        <?php echo $i; ?>
                                                    </a>
                                                </li>
                                            <?php endfor; ?>
                                            
                                            <?php if ($page < $total_pages): ?>
                                                <li class="page-item">
                                                    <a class="page-link" href="?page=<?php echo $page + 1; ?>&status=<?php echo $status_filter; ?>&date_from=<?php echo $date_from; ?>&date_to=<?php echo $date_to; ?>">
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