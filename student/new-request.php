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
$error_message = '';
$success_message = '';

// Get available document types
$stmt = $db->prepare("SELECT * FROM document_types WHERE status = 'active' ORDER BY name");
$stmt->execute();
$document_types = $stmt->fetchAll();

// Create uploads directory if it doesn't exist
$upload_dir = '../uploads/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// Handle form submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_request') {
    $document_type_id = $_POST['document_type_id'];
    $purpose = trim($_POST['purpose']);
    $preferred_release_date = !empty($_POST['preferred_release_date']) ? $_POST['preferred_release_date'] : null;
    $quantity = intval($_POST['quantity']);
    
    // Validate required fields
    if (empty($document_type_id) || empty($purpose)) {
        $error_message = 'Please fill in all required fields.';
    } else {
        try {
            $db->beginTransaction();
            
            // Get document type details
            $stmt = $db->prepare("SELECT * FROM document_types WHERE id = ?");
            $stmt->execute([$document_type_id]);
            $doc_type = $stmt->fetch();
            
            if (!$doc_type) {
                throw new Exception('Invalid document type selected.');
            }
            
            // Calculate total amount
            $total_amount = $doc_type['price'] * $quantity;
            
            // Insert document request
            $stmt = $db->prepare("
                INSERT INTO document_requests (user_id, document_type_id, purpose, preferred_release_date, quantity, total_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");
            $stmt->execute([$current_user['id'], $document_type_id, $purpose, $preferred_release_date, $quantity, $total_amount]);
            $request_id = $db->lastInsertId();
            
            // Handle file uploads
            $upload_errors = [];
            if (!empty($_FILES['files']['name'][0])) {
                for ($i = 0; $i < count($_FILES['files']['name']); $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $file_name = $_FILES['files']['name'][$i];
                        $file_tmp = $_FILES['files']['tmp_name'][$i];
                        $file_size = $_FILES['files']['size'][$i];
                        $file_type = $_FILES['files']['type'][$i];
                        
                        // Validate file
                        $allowed_types = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
                        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                        
                        if (!in_array($file_ext, $allowed_types)) {
                            $upload_errors[] = "Invalid file type for {$file_name}. Allowed: " . implode(', ', $allowed_types);
                            continue;
                        }
                        
                        if ($file_size > 5242880) { // 5MB
                            $upload_errors[] = "File {$file_name} is too large. Maximum size: 5MB";
                            continue;
                        }
                        
                        // Generate unique filename
                        $unique_name = $request_id . '_' . time() . '_' . $i . '.' . $file_ext;
                        $file_path = $upload_dir . $unique_name;
                        
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            // Save file info to database
                            $stmt = $db->prepare("
                                INSERT INTO request_files (request_id, file_name, file_path, file_type, file_size, upload_type) 
                                VALUES (?, ?, ?, ?, ?, ?)
                            ");
                            $upload_type = (strpos(strtolower($file_name), 'id') !== false) ? 'id' : 'requirement';
                            $stmt->execute([$request_id, $file_name, $file_path, $file_type, $file_size, $upload_type]);
                        } else {
                            $upload_errors[] = "Failed to upload {$file_name}";
                        }
                    } else if ($_FILES['files']['error'][$i] !== UPLOAD_ERR_NO_FILE) {
                        $upload_errors[] = "Error uploading {$_FILES['files']['name'][$i]}";
                    }
                }
            }
            
            // Add status history
            $stmt = $db->prepare("
                INSERT INTO request_status_history (request_id, old_status, new_status, changed_by, notes) 
                VALUES (?, NULL, 'pending', ?, 'Request submitted')
            ");
            $stmt->execute([$request_id, $current_user['id']]);
            
            // Create notification
            $stmt = $db->prepare("
                INSERT INTO notifications (user_id, request_id, title, message, type) 
                VALUES (?, ?, ?, ?, 'portal')
            ");
            $notification_message = "Your request for {$doc_type['name']} has been submitted successfully. Request ID: #{$request_id}";
            $stmt->execute([$current_user['id'], $request_id, 'Request Submitted', $notification_message]);
            
            $db->commit();
            
            if (empty($upload_errors)) {
                $success_message = "Your document request has been submitted successfully! Request ID: #{$request_id}";
            } else {
                $success_message = "Your document request has been submitted successfully! Request ID: #{$request_id}. However, some files could not be uploaded: " . implode(', ', $upload_errors);
            }
            
        } catch (Exception $e) {
            $db->rollBack();
            $error_message = 'Error submitting request: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Document Request - LNHS Portal</title>
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
                        <h2>📄 New Document Request</h2>
                        <p>Fill out the form below to request official documents from LNHS.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Display messages -->
        <?php if ($error_message): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
                <div class="mt-3">
                    <a href="my-requests.php" class="btn btn-primary">View My Requests</a>
                    <a href="new-request.php" class="btn btn-outline-primary">Submit Another Request</a>
                </div>
            </div>
        <?php endif; ?>

        <div class="row">
            <!-- Request Form -->
            <div class="col-8">
                <div class="card">
                    <div class="card-header">
                        <h2>Document Request Form</h2>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="" enctype="multipart/form-data" id="request-form">
                            <input type="hidden" name="action" value="submit_request">
                            
                            <!-- Document Type -->
                            <div class="form-group">
                                <label for="document_type_id" class="form-label">Document Type *</label>
                                <select id="document_type_id" name="document_type_id" class="form-control form-select" required onchange="updateDocumentInfo()">
                                    <option value="">Select Document Type</option>
                                    <?php foreach ($document_types as $doc): ?>
                                    <option value="<?php echo $doc['id']; ?>" 
                                            data-price="<?php echo $doc['price']; ?>"
                                            data-days="<?php echo $doc['processing_days']; ?>"
                                            data-requirements="<?php echo htmlspecialchars($doc['requirements']); ?>"
                                            data-description="<?php echo htmlspecialchars($doc['description']); ?>">
                                        <?php echo htmlspecialchars($doc['name']); ?> - ₱<?php echo number_format($doc['price'], 2); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Document Info Display -->
                            <div id="document-info" style="display: none;" class="mb-3">
                                <div class="card" style="background-color: var(--light-bg);">
                                    <div class="card-body">
                                        <h4>Document Information</h4>
                                        <p id="doc-description"></p>
                                        <div class="row">
                                            <div class="col-6">
                                                <strong>Price:</strong> <span id="doc-price"></span>
                                            </div>
                                            <div class="col-6">
                                                <strong>Processing Time:</strong> <span id="doc-days"></span>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <strong>Requirements:</strong> <span id="doc-requirements"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Purpose -->
                            <div class="form-group">
                                <label for="purpose" class="form-label">Purpose of Request *</label>
                                <textarea id="purpose" name="purpose" class="form-control" rows="3" required 
                                          placeholder="Please specify the purpose for requesting this document"></textarea>
                            </div>

                            <!-- Quantity -->
                            <div class="form-group">
                                <label for="quantity" class="form-label">Quantity</label>
                                <input type="number" id="quantity" name="quantity" class="form-control" 
                                       min="1" max="10" value="1" onchange="updateTotal()">
                                <small class="form-text">Maximum 10 copies per request</small>
                            </div>

                            <!-- Preferred Release Date -->
                            <div class="form-group">
                                <label for="preferred_release_date" class="form-label">Preferred Release Date</label>
                                <input type="date" id="preferred_release_date" name="preferred_release_date" 
                                       class="form-control" min="<?php echo date('Y-m-d', strtotime('+3 days')); ?>">
                                <small class="form-text">Minimum processing time applies. This is your preferred date, not guaranteed.</small>
                            </div>

                            <!-- Total Amount Display -->
                            <div class="form-group">
                                <div class="card" style="background-color: var(--light-bg);">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <strong>Total Amount:</strong>
                                            <strong style="color: var(--primary-color); font-size: 1.25rem;">
                                                ₱<span id="total-amount">0.00</span>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- File Upload -->
                            <div class="form-group">
                                <label for="files" class="form-label">Upload Requirements</label>
                                <div class="form-file">
                                    <input type="file" id="files" name="files[]" class="form-file-input" 
                                           multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" onchange="displaySelectedFiles()">
                                    <label for="files" class="form-file-label">
                                        📁 Click to upload files or drag and drop<br>
                                        <small>Upload your Valid ID and other required documents</small>
                                    </label>
                                </div>
                                <small class="form-text">
                                    Accepted formats: JPG, PNG, PDF, DOC, DOCX. Maximum file size: 5MB each.
                                </small>
                                <div id="selected-files" class="mt-2"></div>
                            </div>

                            <!-- Submit Button -->
                            <div class="form-group">
                                <button type="submit" class="btn btn-primary btn-lg" style="width: 100%;" id="submit-btn">
                                    <span class="spinner" id="submit-spinner" style="display: none;"></span>
                                    Submit Request
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Instructions -->
            <div class="col-4">
                <div class="card">
                    <div class="card-header">
                        <h2>📋 Instructions</h2>
                    </div>
                    <div class="card-body">
                        <h4>Before submitting your request:</h4>
                        <ol style="margin-left: 1.5rem;">
                            <li>Select the appropriate document type</li>
                            <li>Provide a clear purpose for your request</li>
                            <li>Upload all required documents (especially Valid ID)</li>
                            <li>Review the total amount to be paid</li>
                            <li>Submit your request</li>
                        </ol>
                        
                        <h4 class="mt-4">Required Documents:</h4>
                        <ul style="margin-left: 1.5rem;">
                            <li>Valid Government ID (Primary requirement)</li>
                            <li>Student ID (if applicable)</li>
                            <li>Additional documents as specified per document type</li>
                        </ul>
                        
                        <h4 class="mt-4">Processing Notes:</h4>
                        <ul style="margin-left: 1.5rem;">
                            <li>Processing time starts after approval</li>
                            <li>You will receive notifications on status updates</li>
                            <li>Payment is required before document release</li>
                            <li>Documents are ready for pickup at the registrar's office</li>
                        </ul>
                    </div>
                </div>

                <!-- Support Contact -->
                <div class="card mt-3">
                    <div class="card-header">
                        <h2>📞 Need Help?</h2>
                    </div>
                    <div class="card-body">
                        <p>If you need assistance with your request:</p>
                        <div style="margin-top: 1rem;">
                            <strong>📧 Email:</strong><br>
                            <a href="mailto:registrar@lnhs.edu.ph">registrar@lnhs.edu.ph</a>
                        </div>
                        <div style="margin-top: 1rem;">
                            <strong>📱 Phone:</strong><br>
                            077-123-4567
                        </div>
                        <div style="margin-top: 1rem;">
                            <strong>🕐 Office Hours:</strong><br>
                            Monday - Friday<br>
                            8:00 AM - 5:00 PM
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

        function updateDocumentInfo() {
            const select = document.getElementById('document_type_id');
            const infoDiv = document.getElementById('document-info');
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                document.getElementById('doc-description').textContent = option.dataset.description;
                document.getElementById('doc-price').textContent = '₱' + parseFloat(option.dataset.price).toLocaleString('en-US', {minimumFractionDigits: 2});
                document.getElementById('doc-days').textContent = option.dataset.days + ' days';
                document.getElementById('doc-requirements').textContent = option.dataset.requirements;
                infoDiv.style.display = 'block';
                updateTotal();
            } else {
                infoDiv.style.display = 'none';
            }
        }

        function updateTotal() {
            const select = document.getElementById('document_type_id');
            const quantity = parseInt(document.getElementById('quantity').value) || 1;
            
            if (select.value) {
                const option = select.options[select.selectedIndex];
                const price = parseFloat(option.dataset.price);
                const total = price * quantity;
                document.getElementById('total-amount').textContent = total.toLocaleString('en-US', {minimumFractionDigits: 2});
            } else {
                document.getElementById('total-amount').textContent = '0.00';
            }
        }

        function displaySelectedFiles() {
            const input = document.getElementById('files');
            const container = document.getElementById('selected-files');
            container.innerHTML = '';
            
            if (input.files.length > 0) {
                container.innerHTML = '<strong>Selected files:</strong><br>';
                for (let i = 0; i < input.files.length; i++) {
                    const file = input.files[i];
                    const fileSize = (file.size / 1024 / 1024).toFixed(2);
                    container.innerHTML += `<small>• ${file.name} (${fileSize} MB)</small><br>`;
                }
            }
        }

        // Form submission with loading state
        document.getElementById('request-form').addEventListener('submit', function() {
            const submitBtn = document.getElementById('submit-btn');
            const spinner = document.getElementById('submit-spinner');
            
            submitBtn.disabled = true;
            spinner.style.display = 'inline-block';
            submitBtn.innerHTML = '<span class="spinner"></span> Submitting Request...';
        });

        // Set minimum date to 3 days from now
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('preferred_release_date');
            const today = new Date();
            today.setDate(today.getDate() + 3);
            dateInput.min = today.toISOString().split('T')[0];
        });
    </script>
</body>
</html>