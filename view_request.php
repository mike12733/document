<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$request_id = $_GET['id'] ?? 0;

// Get request details
$stmt = $pdo->prepare("
    SELECT dr.*, dt.name as document_name, dt.fee, dt.processing_days, u.full_name, u.email, u.student_id
    FROM document_requests dr 
    JOIN document_types dt ON dr.document_type_id = dt.id 
    JOIN users u ON dr.user_id = u.id 
    WHERE dr.id = ? AND dr.user_id = ?
");
$stmt->execute([$request_id, $user_id]);
$request = $stmt->fetch();

if (!$request) {
    header("Location: my_requests.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Request - LNHS Portal</title>
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
                            <a class="nav-link" href="my_requests.php">
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
                            <h2 class="mb-1">Request Details</h2>
                            <p class="text-muted mb-0">Request ID: #<?php echo $request['id']; ?></p>
                        </div>
                        <div>
                            <a href="my_requests.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Back to My Requests
                            </a>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Request Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-file-alt me-2"></i>Request Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <h6>Document Details</h6>
                                            <p><strong>Document:</strong> <?php echo htmlspecialchars($request['document_name']); ?></p>
                                            <p><strong>Fee:</strong> ₱<?php echo number_format($request['fee'], 2); ?></p>
                                            <p><strong>Processing Time:</strong> <?php echo $request['processing_days']; ?> days</p>
                                            <p><strong>Status:</strong> 
                                                <span class="status-badge status-<?php echo $request['status']; ?>">
                                                    <?php echo ucfirst(str_replace('_', ' ', $request['status'])); ?>
                                                </span>
                                            </p>
                                        </div>
                                        <div class="col-md-6">
                                            <h6>Request Details</h6>
                                            <p><strong>Request Date:</strong> <?php echo date('M d, Y H:i', strtotime($request['request_date'])); ?></p>
                                            <?php if ($request['preferred_release_date']): ?>
                                                <p><strong>Preferred Date:</strong> <?php echo date('M d, Y', strtotime($request['preferred_release_date'])); ?></p>
                                            <?php endif; ?>
                                            <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($request['updated_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <hr>
                                    
                                    <h6>Purpose</h6>
                                    <p><?php echo nl2br(htmlspecialchars($request['purpose'])); ?></p>
                                    
                                    <?php if ($request['uploaded_file']): ?>
                                        <h6>Uploaded File</h6>
                                        <a href="<?php echo htmlspecialchars($request['uploaded_file']); ?>" target="_blank" class="btn btn-outline-primary">
                                            <i class="fas fa-download me-2"></i>View Uploaded File
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php if ($request['admin_notes']): ?>
                                        <hr>
                                        <h6>Admin Notes</h6>
                                        <div class="alert alert-info">
                                            <i class="fas fa-comment me-2"></i>
                                            <?php echo nl2br(htmlspecialchars($request['admin_notes'])); ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Progress Tracker -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-route me-2"></i>Request Progress
                                    </h5>
                                </div>
                                <div class="card-body">
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
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <!-- Status Information -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Status Information
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($request['status'] == 'ready_for_pickup'): ?>
                                        <div class="alert alert-success">
                                            <i class="fas fa-check-circle me-2"></i>
                                            <strong>Your document is ready for pickup!</strong><br>
                                            <small>Please bring a valid ID when claiming your document.</small>
                                        </div>
                                    <?php elseif ($request['status'] == 'approved'): ?>
                                        <div class="alert alert-info">
                                            <i class="fas fa-info-circle me-2"></i>
                                            <strong>Your request has been approved!</strong><br>
                                            <small>Please proceed with payment to claim your document.</small>
                                        </div>
                                    <?php elseif ($request['status'] == 'processing'): ?>
                                        <div class="alert alert-warning">
                                            <i class="fas fa-cog me-2"></i>
                                            <strong>Your request is being processed.</strong><br>
                                            <small>We are currently working on your document.</small>
                                        </div>
                                    <?php elseif ($request['status'] == 'pending'): ?>
                                        <div class="alert alert-secondary">
                                            <i class="fas fa-clock me-2"></i>
                                            <strong>Your request is pending.</strong><br>
                                            <small>We will review your request soon.</small>
                                        </div>
                                    <?php elseif ($request['status'] == 'denied'): ?>
                                        <div class="alert alert-danger">
                                            <i class="fas fa-times-circle me-2"></i>
                                            <strong>Your request has been denied.</strong><br>
                                            <small>Please contact the administration for more details.</small>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <hr>
                                    
                                    <h6>Next Steps</h6>
                                    <ul class="list-unstyled">
                                        <?php if ($request['status'] == 'ready_for_pickup'): ?>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>Visit the school office</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>Bring a valid ID</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>Pay the required fee</li>
                                            <li><i class="fas fa-arrow-right text-success me-2"></i>Claim your document</li>
                                        <?php elseif ($request['status'] == 'approved'): ?>
                                            <li><i class="fas fa-arrow-right text-info me-2"></i>Complete payment</li>
                                            <li><i class="fas fa-arrow-right text-info me-2"></i>Wait for processing</li>
                                            <li><i class="fas fa-arrow-right text-info me-2"></i>Check for updates</li>
                                        <?php else: ?>
                                            <li><i class="fas fa-arrow-right text-muted me-2"></i>Wait for status updates</li>
                                            <li><i class="fas fa-arrow-right text-muted me-2"></i>Check notifications</li>
                                            <li><i class="fas fa-arrow-right text-muted me-2"></i>Contact admin if needed</li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </div>
                            
                            <!-- Contact Information -->
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-phone me-2"></i>Need Help?
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <p class="mb-2"><strong>School Office:</strong></p>
                                    <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>Laguna National High School</p>
                                    <p class="mb-1"><i class="fas fa-phone me-2"></i>(049) 123-4567</p>
                                    <p class="mb-3"><i class="fas fa-envelope me-2"></i>admin@lnhs.edu.ph</p>
                                    
                                    <hr>
                                    
                                    <p class="mb-2"><strong>Office Hours:</strong></p>
                                    <p class="mb-1">Monday - Friday: 8:00 AM - 5:00 PM</p>
                                    <p class="mb-0">Saturday: 8:00 AM - 12:00 PM</p>
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