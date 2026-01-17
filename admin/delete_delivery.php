<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    $stmt = $conn->prepare("DELETE FROM deliveries WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: deliveries.php?msg=Delivery deleted successfully");
        exit;
    } else {
        echo "Error deleting delivery: " . $conn->error;
    }

    $stmt->close();
}
$conn->close();
?>
