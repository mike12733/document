<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';

// Mark notification as read
if (isset($_POST['mark_read']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    $success = 'Notification marked as read.';
}

// Mark all notifications as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $success = 'All notifications marked as read.';
}

// Delete notification
if (isset($_POST['delete']) && isset($_POST['notification_id'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->execute([$notification_id, $user_id]);
    $success = 'Notification deleted.';
}

// Get notifications with pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 20;
$offset = ($page - 1) * $per_page;

$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? 
    ORDER BY created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll();

// Get total count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_notifications = $stmt->fetch()['count'];
$total_pages = ceil($total_notifications / $per_page);

// Get unread count
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0");
$stmt->execute([$user_id]);
$unread_count = $stmt->fetch()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - LNHS Portal</title>
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
        .notification-item {
            border-left: 4px solid #dee2e6;
            transition: all 0.3s;
        }
        .notification-item:hover {
            background: #f8f9fa;
        }
        .notification-item.unread {
            border-left-color: #007bff;
            background: #f8f9fa;
        }
        .notification-item.unread:hover {
            background: #e9ecef;
        }
        .notification-time {
            font-size: 12px;
            color: #6c757d;
        }
        .notification-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .type-portal { background: #e3f2fd; color: #1976d2; }
        .type-email { background: #f3e5f5; color: #7b1fa2; }
        .type-sms { background: #e8f5e8; color: #388e3c; }
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
                        
                        <?php if ($_SESSION['user_type'] == 'student' || $_SESSION['user_type'] == 'alumni'): ?>
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
                        
                        <?php if ($_SESSION['user_type'] == 'admin'): ?>
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
                                <a class="nav-link" href="admin_reports.php">
                                    <i class="fas fa-chart-bar me-2"></i> Reports
                                </a>
                            </li>
                        <?php endif; ?>
                        
                        <li class="nav-item">
                            <a class="nav-link active" href="notifications.php">
                                <i class="fas fa-bell me-2"></i> Notifications
                                <?php if ($unread_count > 0): ?>
                                    <span class="badge bg-danger ms-2"><?php echo $unread_count; ?></span>
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
                            <h2 class="mb-1">Notifications</h2>
                            <p class="text-muted mb-0">
                                <?php echo $total_notifications; ?> total notifications
                                <?php if ($unread_count > 0): ?>
                                    • <span class="text-primary"><?php echo $unread_count; ?> unread</span>
                                <?php endif; ?>
                            </p>
                        </div>
                        <div>
                            <?php if ($unread_count > 0): ?>
                                <form method="POST" action="" class="d-inline">
                                    <button type="submit" name="mark_all_read" class="btn btn-outline-primary me-2">
                                        <i class="fas fa-check-double me-2"></i>Mark All Read
                                    </button>
                                </form>
                            <?php endif; ?>
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
                    
                    <!-- Notifications List -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-bell me-2"></i>Your Notifications
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <?php if (empty($notifications)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No notifications</h5>
                                    <p class="text-muted">You're all caught up!</p>
                                </div>
                            <?php else: ?>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($notifications as $notification): ?>
                                        <div class="list-group-item notification-item <?php echo $notification['is_read'] ? '' : 'unread'; ?>">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <div class="d-flex align-items-center mb-2">
                                                        <h6 class="mb-0 me-2"><?php echo htmlspecialchars($notification['title']); ?></h6>
                                                        <span class="notification-type type-<?php echo $notification['type']; ?>">
                                                            <?php echo strtoupper($notification['type']); ?>
                                                        </span>
                                                        <?php if (!$notification['is_read']): ?>
                                                            <span class="badge bg-primary ms-2">New</span>
                                                        <?php endif; ?>
                                                    </div>
                                                    <p class="mb-2"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <small class="notification-time">
                                                            <i class="fas fa-clock me-1"></i>
                                                            <?php 
                                                            $time_diff = time() - strtotime($notification['created_at']);
                                                            if ($time_diff < 60) {
                                                                echo 'Just now';
                                                            } elseif ($time_diff < 3600) {
                                                                echo floor($time_diff / 60) . ' minutes ago';
                                                            } elseif ($time_diff < 86400) {
                                                                echo floor($time_diff / 3600) . ' hours ago';
                                                            } else {
                                                                echo date('M d, Y g:i A', strtotime($notification['created_at']));
                                                            }
                                                            ?>
                                                        </small>
                                                        <div class="btn-group btn-group-sm">
                                                            <?php if (!$notification['is_read']): ?>
                                                                <form method="POST" action="" class="d-inline">
                                                                    <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                                    <button type="submit" name="mark_read" class="btn btn-outline-primary btn-sm">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                </form>
                                                            <?php endif; ?>
                                                            <form method="POST" action="" class="d-inline">
                                                                <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                                                <button type="submit" name="delete" class="btn btn-outline-danger btn-sm" 
                                                                        onclick="return confirm('Are you sure you want to delete this notification?')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                
                                <!-- Pagination -->
                                <?php if ($total_pages > 1): ?>
                                    <div class="card-footer">
                                        <nav aria-label="Page navigation">
                                            <ul class="pagination justify-content-center mb-0">
                                                <?php if ($page > 1): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page - 1; ?>">
                                                            Previous
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                                
                                                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                                    <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                                        <a class="page-link" href="?page=<?php echo $i; ?>">
                                                            <?php echo $i; ?>
                                                        </a>
                                                    </li>
                                                <?php endfor; ?>
                                                
                                                <?php if ($page < $total_pages): ?>
                                                    <li class="page-item">
                                                        <a class="page-link" href="?page=<?php echo $page + 1; ?>">
                                                            Next
                                                        </a>
                                                    </li>
                                                <?php endif; ?>
                                            </ul>
                                        </nav>
                                    </div>
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