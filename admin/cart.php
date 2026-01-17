<?php
session_start();
require_once('../includes/db_connect.php');

// Access Control: Admin should NOT have shopping cart
// Customers use /customer/cart.php instead
// Admin should create orders through manual order creation form

// If somehow accessed, redirect to admin dashboard
header('Location: dashboard.php');
exit;
?>
