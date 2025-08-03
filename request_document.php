<?php
session_start();
require_once 'config/database.php';

// Check if user is logged in and is student/alumni
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['student', 'alumni'])) {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

// Get document types
$stmt = $pdo->prepare("SELECT * FROM document_types WHERE is_active = 1");
$stmt->execute();
$document_types = $stmt->fetchAll();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $document_type_id = $_POST['document_type_id'];
    $purpose = trim($_POST['purpose']);
    $preferred_release_date = $_POST['preferred_release_date'];
    
    // Validate inputs
    if (empty($document_type_id) || empty($purpose) || empty($preferred_release_date)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Handle file upload
        $uploaded_file = '';
        if (isset($_FILES['requirements']) && $_FILES['requirements']['error'] == 0) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];
            $max_size = 5 * 1024 * 1024; // 5MB
            
            if (!in_array($_FILES['requirements']['type'], $allowed_types)) {
                $error = 'Invalid file type. Only JPG, PNG, GIF, and PDF files are allowed.';
            } elseif ($_FILES['requirements']['size'] > $max_size) {
                $error = 'File size too large. Maximum size is 5MB.';
            } else {
                $upload_dir = 'uploads/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $file_extension = pathinfo($_FILES['requirements']['name'], PATHINFO_EXTENSION);
                $file_name = 'req_' . $user_id . '_' . time() . '.' . $file_extension;
                $file_path = $upload_dir . $file_name;
                
                if (move_uploaded_file($_FILES['requirements']['tmp_name'], $file_path)) {
                    $uploaded_file = $file_path;
                } else {
                    $error = 'Failed to upload file. Please try again.';
                }
            }
        }
        
        if (empty($error)) {
            try {
                // Insert request
                $stmt = $pdo->prepare("
                    INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date, uploaded_file) 
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$user_id, $document_type_id, $purpose, $preferred_release_date, $uploaded_file]);
                
                $request_id = $pdo->lastInsertId();
                
                // Create notification
                $stmt = $pdo->prepare("
                    INSERT INTO notifications (user_id, title, message, type) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([
                    $user_id, 
                    'Document Request Submitted', 
                    'Your document request has been submitted successfully. Request ID: ' . $request_id,
                    'portal'
                ]);
                
                // Log activity
                $stmt = $pdo->prepare("
                    INSERT INTO activity_logs (user_id, action, ip_address) 
                    VALUES (?, ?, ?)
                ");
                $stmt->execute([$user_id, 'Submitted document request', $_SERVER['REMOTE_ADDR']]);
                
                $success = 'Document request submitted successfully! Your request ID is: ' . $request_id;
                
                // Clear form data
                $_POST = array();
                
            } catch (Exception $e) {
                $error = 'An error occurred while submitting your request. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Document - LNHS Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }
        .form-control, .form-select {
            border-radius: 10px;
            border: 2px solid #e9ecef;
            padding: 12px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }
        .btn-submit {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 30px;
            font-weight: 600;
        }
        .btn-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        .file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s;
        }
        .file-upload:hover {
            border-color: #667eea;
            background: #f8f9fa;
        }
        .file-upload.dragover {
            border-color: #667eea;
            background: #e3f2fd;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="bg-dark text-white p-3" style="min-height: 100vh;">
                    <div class="text-center mb-4">
                        <div class="bg-white rounded-circle d-inline-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                            <i class="fas fa-graduation-cap text-dark" style="font-size: 24px;"></i>
                        </div>
                        <h5 class="mt-2 mb-0">LNHS Portal</h5>
                        <small class="text-white-50">Documents Request System</small>
                    </div>
                    
                    <hr class="bg-white">
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="dashboard.php">
                                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white" href="request_document.php">
                                <i class="fas fa-file-alt me-2"></i> Request Document
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="my_requests.php">
                                <i class="fas fa-list me-2"></i> My Requests
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="notifications.php">
                                <i class="fas fa-bell me-2"></i> Notifications
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="profile.php">
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
                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h2 class="mb-1">Request Document</h2>
                            <p class="text-muted mb-0">Submit a new document request</p>
                        </div>
                        <a href="dashboard.php" class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                        </a>
                    </div>
                    
                    <div class="form-container">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-file-alt me-2"></i>Document Request Form
                                </h5>
                            </div>
                            <div class="card-body">
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
                                
                                <form method="POST" action="" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="document_type_id" class="form-label">
                                                <i class="fas fa-file me-2"></i>Document Type <span class="text-danger">*</span>
                                            </label>
                                            <select class="form-select" id="document_type_id" name="document_type_id" required>
                                                <option value="">Select Document Type</option>
                                                <?php foreach ($document_types as $doc_type): ?>
                                                    <option value="<?php echo $doc_type['id']; ?>" 
                                                            data-fee="<?php echo $doc_type['fee']; ?>"
                                                            data-days="<?php echo $doc_type['processing_days']; ?>"
                                                            <?php echo (isset($_POST['document_type_id']) && $_POST['document_type_id'] == $doc_type['id']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($doc_type['name']); ?> 
                                                        (₱<?php echo number_format($doc_type['fee'], 2); ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <div class="form-text">
                                                <i class="fas fa-info-circle"></i> 
                                                Processing time and fees vary by document type
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="preferred_release_date" class="form-label">
                                                <i class="fas fa-calendar me-2"></i>Preferred Release Date <span class="text-danger">*</span>
                                            </label>
                                            <input type="date" class="form-control" id="preferred_release_date" 
                                                   name="preferred_release_date" 
                                                   value="<?php echo isset($_POST['preferred_release_date']) ? $_POST['preferred_release_date'] : ''; ?>"
                                                   min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required>
                                            <div class="form-text">
                                                <i class="fas fa-info-circle"></i> 
                                                Select your preferred pickup date
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="purpose" class="form-label">
                                            <i class="fas fa-comment me-2"></i>Purpose of Request <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control" id="purpose" name="purpose" rows="4" 
                                                  placeholder="Please describe the purpose of your document request..." required><?php echo isset($_POST['purpose']) ? htmlspecialchars($_POST['purpose']) : ''; ?></textarea>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i> 
                                            Be specific about why you need this document
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label class="form-label">
                                            <i class="fas fa-upload me-2"></i>Upload Requirements (Optional)
                                        </label>
                                        <div class="file-upload" id="fileUpload">
                                            <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                            <h5>Drag and drop files here</h5>
                                            <p class="text-muted">or</p>
                                            <input type="file" class="form-control" id="requirements" name="requirements" 
                                                   accept=".jpg,.jpeg,.png,.gif,.pdf" style="display: none;">
                                            <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('requirements').click()">
                                                <i class="fas fa-folder-open me-2"></i>Choose File
                                            </button>
                                            <div class="mt-2">
                                                <small class="text-muted">
                                                    <i class="fas fa-info-circle"></i> 
                                                    Accepted formats: JPG, PNG, GIF, PDF (Max: 5MB)
                                                </small>
                                            </div>
                                        </div>
                                        <div id="fileInfo" class="mt-2" style="display: none;">
                                            <div class="alert alert-info">
                                                <i class="fas fa-file me-2"></i>
                                                <span id="fileName"></span>
                                                <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="removeFile()">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <h6><i class="fas fa-info-circle me-2"></i>Important Information</h6>
                                        <ul class="mb-0">
                                            <li>All requests are subject to approval by school administration</li>
                                            <li>Processing time may vary depending on document type and current workload</li>
                                            <li>You will be notified via email and portal notifications about your request status</li>
                                            <li>Payment must be made before document release</li>
                                        </ul>
                                    </div>
                                    
                                    <div class="d-flex justify-content-between">
                                        <a href="dashboard.php" class="btn btn-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                        <button type="submit" class="btn btn-primary btn-submit">
                                            <i class="fas fa-paper-plane me-2"></i>Submit Request
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // File upload handling
        const fileInput = document.getElementById('requirements');
        const fileUpload = document.getElementById('fileUpload');
        const fileInfo = document.getElementById('fileInfo');
        const fileName = document.getElementById('fileName');
        
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileName.textContent = file.name;
                fileInfo.style.display = 'block';
                fileUpload.style.display = 'none';
            }
        });
        
        function removeFile() {
            fileInput.value = '';
            fileInfo.style.display = 'none';
            fileUpload.style.display = 'block';
        }
        
        // Drag and drop functionality
        fileUpload.addEventListener('dragover', function(e) {
            e.preventDefault();
            fileUpload.classList.add('dragover');
        });
        
        fileUpload.addEventListener('dragleave', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
        });
        
        fileUpload.addEventListener('drop', function(e) {
            e.preventDefault();
            fileUpload.classList.remove('dragover');
            const files = e.dataTransfer.files;
            if (files.length > 0) {
                fileInput.files = files;
                fileName.textContent = files[0].name;
                fileInfo.style.display = 'block';
                fileUpload.style.display = 'none';
            }
        });
    </script>
</body>
</html>