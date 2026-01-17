<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = trim($_POST['order_id']);
    $customer_name = trim($_POST['customer_name']);
    $address = trim($_POST['address']);
    $delivery_date = $_POST['delivery_date'];
    $status = trim($_POST['status']);

    $stmt = $conn->prepare("INSERT INTO deliveries (order_id, customer_name, address, delivery_date, status) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssss", $order_id, $customer_name, $address, $delivery_date, $status);

    if ($stmt->execute()) {
        header("Location: deliveries.php?msg=Delivery scheduled successfully");
        exit;
    } else {
        echo "Error adding delivery: " . $conn->error;
    }

    $stmt->close();
}
$conn->close();
?>
