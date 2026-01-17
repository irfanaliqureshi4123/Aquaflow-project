<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id']);
    $order_id = trim($_POST['order_id']);
    $customer_name = trim($_POST['customer_name']);
    $address = trim($_POST['address']);
    $delivery_date = $_POST['delivery_date'];
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("UPDATE deliveries SET order_id = ?, customer_name = ?, address = ?, delivery_date = ?, status = ? WHERE id = ?");
    $stmt->bind_param("sssssi", $order_id, $customer_name, $address, $delivery_date, $status, $id);

    if ($stmt->execute()) {
        header("Location: deliveries.php?msg=Delivery updated successfully");
        exit;
    } else {
        echo "Error updating delivery: " . $conn->error;
    }

    $stmt->close();
}
$conn->close();
?>
