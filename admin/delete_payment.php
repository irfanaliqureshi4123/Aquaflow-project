<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Validate CSRF token
if (!isset($_GET['token']) || !hash_equals($_SESSION['csrf_token'], $_GET['token'])) {
    die('Invalid CSRF token');
}

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM payments WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: payments.php?success=Payment+deleted+successfully");
        exit();
    } else {
        header("Location: payments.php?error=Failed+to+delete+payment");
        exit();
    }
} else {
    header("Location: payments.php");
    exit();
}
?>
