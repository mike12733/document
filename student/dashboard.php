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

// Get user's recent requests
$stmt = $db->prepare("
    SELECT dr.*, dt.name as document_name, dt.price, dt.processing_days 
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    WHERE dr.user_id = ? 
    ORDER BY dr.created_at DESC 
    LIMIT 5
");
$stmt->execute([$current_user['id']]);
$recent_requests = $stmt->fetchAll();

// Get request statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_requests,
        SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing_requests,
        SUM(CASE WHEN status = 'ready_for_pickup' THEN 1 ELSE 0 END) as ready_requests
    FROM document_requests 
    WHERE user_id = ?
");
$stmt->execute([$current_user['id']]);
$stats = $stmt->fetch();

// Get available document types
$stmt = $db->prepare("SELECT * FROM document_types WHERE status = 'active' ORDER BY name");
$stmt->execute();
$document_types = $stmt->fetchAll();

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
    <title>Student Dashboard - LNHS Portal</title>
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
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h2>Welcome, <?php echo htmlspecialchars($current_user['first_name']); ?>! 👋</h2>
                        <p>Manage your document requests and track their progress from your dashboard.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--primary-color);"><?php echo $stats['total_requests']; ?></h3>
                        <p>Total Requests</p>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--warning-color);"><?php echo $stats['pending_requests']; ?></h3>
                        <p>Pending</p>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--primary-color);"><?php echo $stats['processing_requests']; ?></h3>
                        <p>Processing</p>
                    </div>
                </div>
            </div>
            <div class="col-3">
                <div class="card">
                    <div class="card-body text-center">
                        <h3 style="color: var(--success-color);"><?php echo $stats['ready_requests']; ?></h3>
                        <p>Ready for Pickup</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- Recent Requests -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Recent Requests</h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_requests)): ?>
                            <div class="text-center" style="padding: 2rem;">
                                <p>No requests found. <a href="new-request.php" class="btn btn-primary">Create your first request</a></p>
                            </div>
                        <?php else: ?>
                            <div class="table table-hover">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Request ID</th>
                                            <th>Document</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_requests as $request): ?>
                                        <tr>
                                            <td>#<?php echo $request['id']; ?></td>
                                            <td><?php echo htmlspecialchars($request['document_name']); ?></td>
                                            <td>
                                                <span class="badge <?php echo getBadgeClass($request['status']); ?>">
                                                    <?php echo formatStatus($request['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($request['created_at'])); ?></td>
                                            <td>
                                                <a href="view-request.php?id=<?php echo $request['id']; ?>" class="btn btn-sm btn-outline-primary">View</a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="text-center mt-3">
                                <a href="my-requests.php" class="btn btn-outline-primary">View All Requests</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h2>Quick Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex" style="flex-direction: column; gap: 1rem;">
                            <a href="new-request.php" class="btn btn-primary">
                                📄 New Document Request
                            </a>
                            <a href="my-requests.php" class="btn btn-outline-primary">
                                📋 View My Requests
                            </a>
                            <a href="profile.php" class="btn btn-outline-primary">
                                👤 Update Profile
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Available Documents -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h2>Available Documents</h2>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 300px; overflow-y: auto;">
                            <?php foreach ($document_types as $doc): ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding: 0.75rem 0;">
                                <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($doc['name']); ?>
                                </h4>
                                <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">
                                    <?php echo htmlspecialchars($doc['description']); ?>
                                </p>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span style="font-weight: 500; color: var(--primary-color);">
                                        ₱<?php echo number_format($doc['price'], 2); ?>
                                    </span>
                                    <span style="font-size: 0.75rem; color: var(--text-light);">
                                        <?php echo $doc['processing_days']; ?> days
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="mt-3">
                            <a href="new-request.php" class="btn btn-primary" style="width: 100%;">
                                Request Document
                            </a>
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