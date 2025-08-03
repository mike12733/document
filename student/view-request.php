<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require login
$auth->requireLogin();
$current_user = $auth->getCurrentUser();

$db = getDBConnection();
$request_id = $_GET['id'] ?? 0;

// Get request details
$stmt = $db->prepare("
    SELECT dr.*, dt.name as document_name, dt.description, dt.requirements, dt.processing_days,
           u_approved.first_name as approved_by_name, u_approved.last_name as approved_by_lastname
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    LEFT JOIN users u_approved ON dr.approved_by = u_approved.id
    WHERE dr.id = ? AND dr.user_id = ?
");
$stmt->execute([$request_id, $current_user['id']]);
$request = $stmt->fetch();

if (!$request) {
    header('Location: my-requests.php');
    exit();
}

// Get uploaded files
$stmt = $db->prepare("SELECT * FROM request_files WHERE request_id = ? ORDER BY created_at");
$stmt->execute([$request_id]);
$files = $stmt->fetchAll();

// Get status history
$stmt = $db->prepare("
    SELECT rsh.*, u.first_name, u.last_name 
    FROM request_status_history rsh 
    LEFT JOIN users u ON rsh.changed_by = u.id 
    WHERE rsh.request_id = ? 
    ORDER BY rsh.created_at ASC
");
$stmt->execute([$request_id]);
$status_history = $stmt->fetchAll();

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

function getStepClass($request_status, $step_status) {
    $status_order = ['pending', 'processing', 'approved', 'ready_for_pickup', 'completed'];
    $current_index = array_search($request_status, $status_order);
    $step_index = array_search($step_status, $status_order);
    
    if ($request_status === 'denied') {
        return $step_status === 'pending' ? 'completed' : '';
    }
    
    if ($step_index <= $current_index) {
        return 'completed';
    } elseif ($step_index === $current_index + 1) {
        return 'active';
    }
    
    return '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request #<?php echo $request['id']; ?> - LNHS Portal</title>
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
                                <h2>📄 Request #<?php echo $request['id']; ?></h2>
                                <p><?php echo htmlspecialchars($request['document_name']); ?></p>
                            </div>
                            <div style="text-align: right;">
                                <span class="badge <?php echo getBadgeClass($request['status']); ?>" style="font-size: 1rem; padding: 0.5rem 1rem;">
                                    <?php echo getStatusIcon($request['status']); ?> <?php echo formatStatus($request['status']); ?>
                                </span>
                                <br>
                                <a href="my-requests.php" class="btn btn-outline-primary mt-2">← Back to My Requests</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Steps -->
        <?php if ($request['status'] !== 'denied'): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h2>📊 Request Progress</h2>
                    </div>
                    <div class="card-body">
                        <div class="progress-steps">
                            <div class="step <?php echo getStepClass($request['status'], 'pending'); ?>">
                                <div class="step-number">1</div>
                                <div class="step-title">Submitted</div>
                            </div>
                            <div class="step <?php echo getStepClass($request['status'], 'processing'); ?>">
                                <div class="step-number">2</div>
                                <div class="step-title">Processing</div>
                            </div>
                            <div class="step <?php echo getStepClass($request['status'], 'approved'); ?>">
                                <div class="step-number">3</div>
                                <div class="step-title">Approved</div>
                            </div>
                            <div class="step <?php echo getStepClass($request['status'], 'ready_for_pickup'); ?>">
                                <div class="step-number">4</div>
                                <div class="step-title">Ready for Pickup</div>
                            </div>
                            <div class="step <?php echo getStepClass($request['status'], 'completed'); ?>">
                                <div class="step-number">5</div>
                                <div class="step-title">Completed</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="row">
            <!-- Request Details -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h2>📋 Request Details</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-6">
                                <h4>Document Information</h4>
                                <table style="width: 100%; margin-bottom: 1.5rem;">
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Document Type:</strong></td>
                                        <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($request['document_name']); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Quantity:</strong></td>
                                        <td style="padding: 0.5rem 0;"><?php echo $request['quantity']; ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Total Amount:</strong></td>
                                        <td style="padding: 0.5rem 0; color: var(--primary-color); font-weight: 600;">
                                            ₱<?php echo number_format($request['total_amount'], 2); ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Payment Status:</strong></td>
                                        <td style="padding: 0.5rem 0;">
                                            <span class="badge <?php echo $request['payment_status'] === 'paid' ? 'badge-approved' : 'badge-pending'; ?>">
                                                <?php echo ucfirst($request['payment_status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="col-6">
                                <h4>Request Information</h4>
                                <table style="width: 100%; margin-bottom: 1.5rem;">
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Date Submitted:</strong></td>
                                        <td style="padding: 0.5rem 0;"><?php echo date('M d, Y h:i A', strtotime($request['created_at'])); ?></td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Preferred Release:</strong></td>
                                        <td style="padding: 0.5rem 0;">
                                            <?php echo $request['preferred_release_date'] ? date('M d, Y', strtotime($request['preferred_release_date'])) : 'No preference'; ?>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Processing Time:</strong></td>
                                        <td style="padding: 0.5rem 0;"><?php echo $request['processing_days']; ?> days</td>
                                    </tr>
                                    <?php if ($request['approved_by_name']): ?>
                                    <tr>
                                        <td style="padding: 0.5rem 0;"><strong>Approved By:</strong></td>
                                        <td style="padding: 0.5rem 0;"><?php echo htmlspecialchars($request['approved_by_name'] . ' ' . $request['approved_by_lastname']); ?></td>
                                    </tr>
                                    <?php endif; ?>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <h4>Purpose of Request</h4>
                            <div style="background-color: var(--light-bg); padding: 1rem; border-radius: 0.5rem; border: 1px solid var(--border-color);">
                                <?php echo nl2br(htmlspecialchars($request['purpose'])); ?>
                            </div>
                        </div>

                        <div class="form-group">
                            <h4>Document Description</h4>
                            <p><?php echo htmlspecialchars($request['description']); ?></p>
                        </div>

                        <div class="form-group">
                            <h4>Required Documents</h4>
                            <p><?php echo htmlspecialchars($request['requirements']); ?></p>
                        </div>

                        <?php if ($request['admin_notes']): ?>
                        <div class="form-group">
                            <h4>Admin Notes</h4>
                            <div class="alert alert-info">
                                <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Uploaded Files -->
                <?php if (!empty($files)): ?>
                <div class="card mt-4">
                    <div class="card-header">
                        <h2>📁 Uploaded Files</h2>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($files as $file): ?>
                            <div class="col-6 mb-3">
                                <div style="border: 1px solid var(--border-color); border-radius: 0.5rem; padding: 1rem;">
                                    <h4 style="font-size: 1rem; margin-bottom: 0.5rem;">
                                        <?php echo htmlspecialchars($file['file_name']); ?>
                                    </h4>
                                    <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.5rem;">
                                        Type: <?php echo ucfirst($file['upload_type']); ?> | 
                                        Size: <?php echo number_format($file['file_size'] / 1024, 2); ?> KB
                                    </p>
                                    <small class="text-muted">
                                        Uploaded: <?php echo date('M d, Y h:i A', strtotime($file['created_at'])); ?>
                                    </small>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Sidebar -->
            <div class="col-4">
                <!-- Status History -->
                <div class="card">
                    <div class="card-header">
                        <h2>📜 Status History</h2>
                    </div>
                    <div class="card-body">
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php foreach ($status_history as $history): ?>
                            <div style="border-bottom: 1px solid var(--border-color); padding: 1rem 0;">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <h4 style="font-size: 1rem; margin-bottom: 0.25rem;">
                                            <?php echo formatStatus($history['new_status']); ?>
                                        </h4>
                                        <p style="font-size: 0.875rem; color: var(--text-light); margin-bottom: 0.25rem;">
                                            <?php echo $history['first_name'] ? htmlspecialchars($history['first_name'] . ' ' . $history['last_name']) : 'System'; ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('M d, Y h:i A', strtotime($history['created_at'])); ?>
                                        </small>
                                        <?php if ($history['notes']): ?>
                                            <p style="font-size: 0.875rem; margin-top: 0.5rem; font-style: italic;">
                                                "<?php echo htmlspecialchars($history['notes']); ?>"
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge <?php echo getBadgeClass($history['new_status']); ?>">
                                        <?php echo getStatusIcon($history['new_status']); ?>
                                    </span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h2>⚡ Actions</h2>
                    </div>
                    <div class="card-body">
                        <div class="d-flex" style="flex-direction: column; gap: 1rem;">
                            <?php if ($request['status'] === 'ready_for_pickup'): ?>
                                <div class="alert alert-success text-center">
                                    <h4>📦 Ready for Pickup!</h4>
                                    <p>Your document is ready. Please visit the registrar's office during business hours.</p>
                                    <small>Office Hours: Mon-Fri, 8:00 AM - 5:00 PM</small>
                                </div>
                            <?php endif; ?>
                            
                            <a href="my-requests.php" class="btn btn-outline-primary">
                                📋 View All My Requests
                            </a>
                            
                            <a href="new-request.php" class="btn btn-primary">
                                📄 Submit New Request
                            </a>
                            
                            <?php if ($request['status'] === 'pending'): ?>
                                <div class="alert alert-info">
                                    <small>⏳ Your request is being reviewed. You will be notified of any updates.</small>
                                </div>
                            <?php elseif ($request['status'] === 'denied'): ?>
                                <div class="alert alert-error">
                                    <small>❌ This request was denied. Check admin notes for details.</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contact Support -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h2>📞 Need Help?</h2>
                    </div>
                    <div class="card-body">
                        <p style="font-size: 0.875rem;">For questions about this request:</p>
                        <div style="margin-top: 1rem;">
                            <strong>📧 Email:</strong><br>
                            <a href="mailto:registrar@lnhs.edu.ph">registrar@lnhs.edu.ph</a>
                        </div>
                        <div style="margin-top: 1rem;">
                            <strong>📱 Phone:</strong><br>
                            077-123-4567
                        </div>
                        <div style="margin-top: 1rem; font-size: 0.875rem;">
                            <strong>Reference:</strong> Request #<?php echo $request['id']; ?>
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