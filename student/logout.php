<?php
session_start();
require_once '../includes/auth.php';

// Logout the user
$auth->logout();

// Redirect to home page
header('Location: ../index.php');
exit();
?>