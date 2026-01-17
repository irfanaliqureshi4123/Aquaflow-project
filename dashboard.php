<?php
session_start();

// If not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role from session
$role = $_SESSION['role'] ?? 'customer';

// Redirect based on role
switch ($role) {
    case 'admin':
        header("Location: admin/dashboard.php");
        exit();

    case 'staff':
        header("Location: staff/dashboard.php");
        exit();

    case 'customer':
        header("Location: customer/dashboard.php");
        exit();

    default:
        // Invalid role — logout for safety
        session_destroy();
        header("Location: login.php?error=invalid_role");
        exit();
}
?>
