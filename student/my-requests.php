<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require login
$auth->requireLogin();
$current_user = $auth->getCurrentUser();

// Redirect admin to admin dashboard
if ($current_user['user_type'] === 'admin') {
    header('Location: ../admin/dashboard.php');
    exit();
}

$db = getDBConnection();

// Get filter parameters
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

// Build query
$where_conditions = ['dr.user_id = ?'];
$params = [$current_user['id']];

if (!empty($status_filter)) {
    $where_conditions[] = 'dr.status = ?';
    $params[] = $status_filter;
}

if (!empty($search)) {
    $where_conditions[] = '(dt.name LIKE ? OR dr.purpose LIKE ? OR dr.id = ?)';
    $search_param = '%' . $search . '%';
    $params[] = $search_param;
    $params[] = $search_param;
    $params[] = $search;
}

$where_clause = implode(' AND ', $where_conditions);

// Get user's requests with pagination
$page = max(1, intval($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$stmt = $db->prepare("
    SELECT dr.*, dt.name as document_name, dt.price, dt.processing_days,
           u_approved.first_name as approved_by_name, u_approved.last_name as approved_by_lastname
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    LEFT JOIN users u_approved ON dr.approved_by = u_approved.id
    WHERE {$where_clause}
    ORDER BY dr.created_at DESC 
    LIMIT {$per_page} OFFSET {$offset}
");
$stmt->execute($params);
$requests = $stmt->fetchAll();

// Get total count for pagination
$count_stmt = $db->prepare("
    SELECT COUNT(*) as total 
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    WHERE {$where_clause}
");
$count_stmt->execute($params);
$total_requests = $count_stmt->fetch()['total'];
$total_pages = ceil($total_requests / $per_page);

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

function getStatusIcon($status) {
    switch ($status) {
        case 'pending': return '⏳';
        case 'processing': return '🔄';
        case 'approved': return '✅';
        case 'denied': return '❌';
        case 'ready_for_pickup': return '📦';
        case 'completed': return '✔️';
        default: return '⏳';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requests - LNHS Portal</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="header">
        <nav class="navbar">
            <div class="logo">
                <h1>🎓 LNHS Portal</h1>
            </div>
            <ul class="nav-links">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="new-request.php">New Request</a></li>
                <li><a href="my-requests.php">My Requests</a></li>
                <li><a href="profile.php">Profile</a></li>
            </ul>
            <div class="user-menu">
                <div class="user-info" onclick="toggleDropdown()">
                    <span><?php echo htmlspecialchars($current_user['first_name'] . ' ' . $current_user['last_name']); ?></span>
                    <span>▼</span>
                </div>
                <div class="dropdown-menu" id="user-dropdown">
                    <a href="profile.php">👤 Profile</a>
                    <a href="change-password.php">🔒 Change Password</a>
                    <a href="logout.php">🚪 Logout</a>
                </div>
            </div>
        </nav>
    </div>

    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h2>📋 My Document Requests</h2>
                                <p>Track and manage your document requests</p>
                            </div>
                            <a href="new-request.php" class="btn btn-primary">
                                📄 New Request
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <form method="GET" action="" class="d-flex gap-3 align-items-center">
                            <div class="form-group mb-0" style="min-width: 200px;">
                                <select name="status" class="form-control form-select">
                                    <option value="">All Statuses</option>
                                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="processing" <?php echo $status_filter === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                    <option value="denied" <?php echo $status_filter === 'denied' ? 'selected' : ''; ?>>Denied</option>
                                    <option value="ready_for_pickup" <?php echo $status_filter === 'ready_for_pickup' ? 'selected' : ''; ?>>Ready for Pickup</option>
                                    <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                </select>
                            </div>
                            <div class="form-group mb-0" style="flex: 1;">
                                <input type="text" name="search" class="form-control" 
                                       placeholder="Search by document name, purpose, or request ID..." 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <button type="submit" class="btn btn-primary">🔍 Filter</button>
                            <a href="my-requests.php" class="btn btn-outline-primary">Clear</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Requests List -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2>Requests (<?php echo $total_requests; ?> total)</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($requests)): ?>
                            <div class="text-center" style="padding: 3rem;">
                                <h3>📝 No requests found</h3>
                                <p>You haven't submitted any document requests yet.</p>
                                <a href="new-request.php" class="btn btn-primary">Submit Your First Request</a>
                            </div>
                        <?php else: ?>
                            <div class="table table-hover">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Document</th>
                                            <th>Purpose</th>
                                            <th>Status</th>
                                            <th>Amount</th>
                                            <th>Date Submitted</th>
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
                                                    <strong><?php echo htmlspecialchars($request['document_name']); ?></strong>
                                                    <br><small>Qty: <?php echo $request['quantity']; ?></small>
                                                </div>
                                            </td>
                                            <td>
                                                <small><?php echo htmlspecialchars(substr($request['purpose'], 0, 100)) . (strlen($request['purpose']) > 100 ? '...' : ''); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo getBadgeClass($request['status']); ?>">
                                                    <?php echo getStatusIcon($request['status']); ?> <?php echo formatStatus($request['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong style="color: var(--primary-color);">
                                                    ₱<?php echo number_format($request['total_amount'], 2); ?>
                                                </strong>
                                                <br>
                                                <small class="badge <?php echo $request['payment_status'] === 'paid' ? 'badge-approved' : 'badge-pending'; ?>">
                                                    <?php echo ucfirst($request['payment_status']); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php echo date('M d, Y', strtotime($request['created_at'])); ?>
                                                <br><small><?php echo date('h:i A', strtotime($request['created_at'])); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1" style="flex-direction: column;">
                                                    <a href="view-request.php?id=<?php echo $request['id']; ?>" 
                                                       class="btn btn-sm btn-outline-primary">👁️ View</a>
                                                    <?php if ($request['status'] === 'ready_for_pickup'): ?>
                                                        <span class="btn btn-sm btn-success" style="font-size: 0.75rem;">
                                                            📦 Ready!
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Pagination -->
                            <?php if ($total_pages > 1): ?>
                            <div class="d-flex justify-content-center mt-4">
                                <div class="d-flex gap-2">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=<?php echo $page - 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn btn-outline-primary">← Previous</a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $start = max(1, $page - 2);
                                    $end = min($total_pages, $page + 2);
                                    for ($i = $start; $i <= $end; $i++): ?>
                                        <a href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" 
                                           class="btn btn-outline-primary">Next →</a>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Status Legend -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2>📊 Status Guide</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-pending">⏳ Pending</span>
                                    <p><small>Request submitted, waiting for review</small></p>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-processing">🔄 Processing</span>
                                    <p><small>Request approved and being processed</small></p>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-approved">✅ Approved</span>
                                    <p><small>Request approved, payment required</small></p>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-denied">❌ Denied</span>
                                    <p><small>Request was denied or rejected</small></p>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-ready">📦 Ready</span>
                                    <p><small>Document ready for pickup</small></p>
                                </div>
                            </div>
                            <div class="col-2">
                                <div class="text-center">
                                    <span class="badge badge-completed">✔️ Completed</span>
                                    <p><small>Document picked up successfully</small></p>
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
    </script>
</body>
</html>