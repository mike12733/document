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

// Handle document type actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add_document':
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $processing_days = (int)$_POST['processing_days'];
                $fee = (float)$_POST['fee'];
                
                if (empty($name)) {
                    $error = 'Document name is required.';
                } else {
                    $stmt = $pdo->prepare("
                        INSERT INTO document_types (name, description, processing_days, fee) 
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$name, $description, $processing_days, $fee]);
                    $success = 'Document type added successfully!';
                }
                break;
                
            case 'update_document':
                $document_id = $_POST['document_id'];
                $name = trim($_POST['name']);
                $description = trim($_POST['description']);
                $processing_days = (int)$_POST['processing_days'];
                $fee = (float)$_POST['fee'];
                $is_active = isset($_POST['is_active']) ? 1 : 0;
                
                $stmt = $pdo->prepare("
                    UPDATE document_types 
                    SET name = ?, description = ?, processing_days = ?, fee = ?, is_active = ?
                    WHERE id = ?
                ");
                $stmt->execute([$name, $description, $processing_days, $fee, $is_active, $document_id]);
                $success = 'Document type updated successfully!';
                break;
                
            case 'delete_document':
                $document_id = $_POST['document_id'];
                
                // Check if document type is being used
                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM document_requests WHERE document_type_id = ?");
                $stmt->execute([$document_id]);
                $count = $stmt->fetch()['count'];
                
                if ($count > 0) {
                    $error = 'Cannot delete document type. It is being used by ' . $count . ' request(s).';
                } else {
                    $stmt = $pdo->prepare("DELETE FROM document_types WHERE id = ?");
                    $stmt->execute([$document_id]);
                    $success = 'Document type deleted successfully!';
                }
                break;
        }
    }
}

// Get document types
$stmt = $pdo->prepare("SELECT * FROM document_types ORDER BY name");
$stmt->execute();
$document_types = $stmt->fetchAll();

// Get document type statistics
$stmt = $pdo->prepare("
    SELECT dt.*, COUNT(dr.id) as request_count, SUM(dt.fee) as total_revenue
    FROM document_types dt
    LEFT JOIN document_requests dr ON dt.id = dr.document_type_id
    GROUP BY dt.id
    ORDER BY dt.name
");
$stmt->execute();
$document_stats = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Document Types - LNHS Portal</title>
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
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 10px;
            font-weight: 600;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-inactive { background: #f8d7da; color: #721c24; }
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
                            <a class="nav-link" href="admin_reports.php">
                                <i class="fas fa-chart-bar me-2"></i> Reports
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link active" href="admin_documents.php">
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
                            <h2 class="mb-1">Manage Document Types</h2>
                            <p class="text-muted mb-0">Configure available documents and fees</p>
                        </div>
                        <div>
                            <button type="button" class="btn btn-primary me-2" data-bs-toggle="modal" data-bs-target="#addDocumentModal">
                                <i class="fas fa-plus me-2"></i>Add Document Type
                            </button>
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
                    
                    <!-- Document Types Table -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-file-alt me-2"></i>Document Types (<?php echo count($document_types); ?> total)
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($document_types)): ?>
                                <p class="text-muted text-center">No document types found. Add your first document type.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Document Name</th>
                                                <th>Description</th>
                                                <th>Processing Days</th>
                                                <th>Fee</th>
                                                <th>Status</th>
                                                <th>Statistics</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($document_stats as $doc): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($doc['name']); ?></strong>
                                                    </td>
                                                    <td>
                                                        <div style="max-width: 200px;">
                                                            <?php echo htmlspecialchars($doc['description']); ?>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $doc['processing_days']; ?> days</span>
                                                    </td>
                                                    <td>
                                                        <strong>₱<?php echo number_format($doc['fee'], 2); ?></strong>
                                                    </td>
                                                    <td>
                                                        <span class="status-badge status-<?php echo $doc['is_active'] ? 'active' : 'inactive'; ?>">
                                                            <?php echo $doc['is_active'] ? 'Active' : 'Inactive'; ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <small class="text-muted">
                                                            <i class="fas fa-file me-1"></i><?php echo $doc['request_count']; ?> requests<br>
                                                            <i class="fas fa-money-bill me-1"></i>₱<?php echo number_format($doc['total_revenue'] ?? 0, 2); ?>
                                                        </small>
                                                    </td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm" role="group">
                                                            <button type="button" class="btn btn-outline-primary" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#editDocumentModal<?php echo $doc['id']; ?>">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <?php if ($doc['request_count'] == 0): ?>
                                                                <button type="button" class="btn btn-outline-danger" 
                                                                        onclick="deleteDocument(<?php echo $doc['id']; ?>, '<?php echo htmlspecialchars($doc['name']); ?>')">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Edit Document Modal -->
                                                <div class="modal fade" id="editDocumentModal<?php echo $doc['id']; ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title">Edit Document Type: <?php echo htmlspecialchars($doc['name']); ?></h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <form method="POST" action="">
                                                                <div class="modal-body">
                                                                    <input type="hidden" name="action" value="update_document">
                                                                    <input type="hidden" name="document_id" value="<?php echo $doc['id']; ?>">
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Document Name</label>
                                                                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($doc['name']); ?>" required>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Description</label>
                                                                        <textarea name="description" class="form-control" rows="3"><?php echo htmlspecialchars($doc['description']); ?></textarea>
                                                                    </div>
                                                                    
                                                                    <div class="row">
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Processing Days</label>
                                                                            <input type="number" name="processing_days" class="form-control" value="<?php echo $doc['processing_days']; ?>" min="1" required>
                                                                        </div>
                                                                        <div class="col-md-6 mb-3">
                                                                            <label class="form-label">Fee (₱)</label>
                                                                            <input type="number" name="fee" class="form-control" value="<?php echo $doc['fee']; ?>" min="0" step="0.01" required>
                                                                        </div>
                                                                    </div>
                                                                    
                                                                    <div class="mb-3">
                                                                        <div class="form-check">
                                                                            <input type="checkbox" name="is_active" class="form-check-input" id="is_active<?php echo $doc['id']; ?>" <?php echo $doc['is_active'] ? 'checked' : ''; ?>>
                                                                            <label class="form-check-label" for="is_active<?php echo $doc['id']; ?>">
                                                                                Active (available for requests)
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                    <button type="submit" class="btn btn-primary">
                                                                        <i class="fas fa-save me-2"></i>Update Document
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
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Document Modal -->
    <div class="modal fade" id="addDocumentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Document Type</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="action" value="add_document">
                        
                        <div class="mb-3">
                            <label class="form-label">Document Name</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Certificate of Enrollment" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Brief description of the document..."></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Processing Days</label>
                                <input type="number" name="processing_days" class="form-control" value="3" min="1" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fee (₱)</label>
                                <input type="number" name="fee" class="form-control" value="50.00" min="0" step="0.01" required>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Add Document
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteDocument(documentId, documentName) {
            if (confirm('Are you sure you want to delete document type "' + documentName + '"? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="action" value="delete_document">
                    <input type="hidden" name="document_id" value="${documentId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }
    </script>
</body>
</html>