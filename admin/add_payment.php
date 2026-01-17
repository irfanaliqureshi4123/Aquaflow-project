<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $order_id = filter_input(INPUT_POST, 'order_id', FILTER_SANITIZE_STRING);
    $customer_name = filter_input(INPUT_POST, 'customer_name', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_VALIDATE_FLOAT, [
        'options' => [
            'min_range' => 0.01,
            'max_range' => 100000
        ]
    ]);
    
    if ($amount === false || $amount === null) {
        header("Location: payments.php?error=Invalid+payment+amount");
        exit();
    }
    
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);

    // Generate a simple invoice number (you might want a more sophisticated generation)
    $invoice_no = 'INV-' . time() . '-' . uniqid();

    $stmt = $conn->prepare("INSERT INTO payments (order_id, invoice_no, customer_name, amount, payment_method, status) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssdss", $order_id, $invoice_no, $customer_name, $amount, $payment_method, $status);

    if ($stmt->execute()) {
        header("Location: payments.php?success=Payment+added+successfully");
        exit();
    } else {
        header("Location: payments.php?error=Failed+to+add+payment");
        exit();
    }
} else {
    header("Location: payments.php");
    exit();
}
?>