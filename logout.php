<?php
session_start();
require_once 'config/database.php';

// Log the logout activity if user was logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activity_logs (user_id, action, ip_address) 
        VALUES (?, ?, ?)
    ");
    $stmt->execute([$user_id, 'User logged out', $_SERVER['REMOTE_ADDR']]);
}

// Destroy all session data
session_destroy();

// Redirect to login page
header("Location: index.php");
exit();
?>