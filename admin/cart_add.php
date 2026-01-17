<?php
session_start();
require_once('../includes/db_connect.php');

// Access Control: Admin should NOT add items to cart
// This functionality has been removed in the role-based access control update
// Admin creates orders through /admin/manual_order.php instead

// Redirect to admin dashboard
header('Location: dashboard.php');
exit;
?>
