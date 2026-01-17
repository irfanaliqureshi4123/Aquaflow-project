<?php
require_once('../includes/db_connect.php');

if (!isset($_GET['id'])) {
    header("Location: ../admin/users.php");
    exit();
}

$id = intval($_GET['id']);

// Prevent deleting the main admin (optional)
if ($id == 1) {
    echo "<script>alert('🚫 You cannot delete the main admin account!'); window.location='../admin/users.php';</script>";
    exit;
}

// Try deleting user safely
try {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        echo "<script>alert('🗑️ User deleted successfully!'); window.location='../admin/users.php';</script>";
    } else {
        echo "<script>alert('⚠️ User not found or already deleted!'); window.location='../admin/users.php';</script>";
    }
    $stmt->close();
} catch (Exception $e) {
    echo "<script>alert('❌ Cannot delete this user due to linked records (foreign key constraint).'); window.location='../admin/users.php';</script>";
}

$conn->close();
?>
