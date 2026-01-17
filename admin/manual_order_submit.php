<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['error'] = 'Invalid request. Please try again.';
    header('Location: manual_order.php');
    exit;
}

// Validate input
$customer_id = intval($_POST['customer_id'] ?? 0);
$product_ids = $_POST['product_id'] ?? [];
$quantities = $_POST['quantity'] ?? [];
$payment_method = trim($_POST['payment_method'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// Validate customer exists
if ($customer_id <= 0) {
    $_SESSION['error'] = 'Please select a valid customer.';
    header('Location: manual_order.php');
    exit;
}

$customerStmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'customer'");
$customerStmt->bind_param('i', $customer_id);
$customerStmt->execute();
if (!$customerStmt->get_result()->fetch_assoc()) {
    $_SESSION['error'] = 'Invalid customer selected.';
    header('Location: manual_order.php');
    exit;
}

// Validate items
if (empty($product_ids) || count($product_ids) === 0) {
    $_SESSION['error'] = 'Please add at least one item to the order.';
    header('Location: manual_order.php');
    exit;
}

// Validate payment method
$valid_methods = ['cash_on_delivery', 'credit_card', 'bank_transfer', 'check'];
if (!in_array($payment_method, $valid_methods)) {
    $_SESSION['error'] = 'Invalid payment method selected.';
    header('Location: manual_order.php');
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Calculate total amount
    $total_amount = 0;
    $order_items = [];
    
    // Validate and prepare all items
    for ($i = 0; $i < count($product_ids); $i++) {
        $product_id = intval($product_ids[$i]);
        $quantity = intval($quantities[$i] ?? 0);
        
        // Validate quantity
        if ($quantity <= 0 || $quantity > 9999) {
            throw new Exception('Invalid quantity for product.');
        }
        
        // Fetch product
        $productStmt = $conn->prepare("SELECT id, price FROM products WHERE id = ?");
        $productStmt->bind_param('i', $product_id);
        $productStmt->execute();
        $product = $productStmt->get_result()->fetch_assoc();
        
        if (!$product) {
            throw new Exception('One or more selected products are invalid.');
        }
        
        $subtotal = $product['price'] * $quantity;
        $total_amount += $subtotal;
        
        $order_items[] = [
            'product_id' => $product_id,
            'quantity' => $quantity,
            'price' => $product['price'],
            'subtotal' => $subtotal
        ];
    }
    
    // Validate total amount
    if ($total_amount <= 0 || $total_amount > 1000000) {
        throw new Exception('Order total is invalid.');
    }
    
    // Create order
    $order_date = date('Y-m-d H:i:s');
    $status = 'pending';
    
    $orderStmt = $conn->prepare(
        "INSERT INTO orders (user_id, total_amount, status, order_date, payment_method, notes, created_by_admin) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    
    $admin_id = $_SESSION['user_id'];
    $orderStmt->bind_param('idsssssi', $customer_id, $total_amount, $status, $order_date, $payment_method, $notes, $admin_id);
    
    if (!$orderStmt->execute()) {
        throw new Exception('Failed to create order: ' . $conn->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Insert order items
    $itemStmt = $conn->prepare(
        "INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)"
    );
    
    foreach ($order_items as $item) {
        $itemStmt->bind_param('iiii', $order_id, $item['product_id'], $item['quantity'], $item['price']);
        if (!$itemStmt->execute()) {
            throw new Exception('Failed to add item to order: ' . $conn->error);
        }
    }
    
    // Create initial payment record based on payment method
    $payment_status = $payment_method === 'cash_on_delivery' ? 'pending' : 'pending';
    
    $paymentStmt = $conn->prepare(
        "INSERT INTO payments (order_id, amount, method, status, payment_date) VALUES (?, ?, ?, ?, ?)"
    );
    
    $payment_date = date('Y-m-d H:i:s');
    $paymentStmt->bind_param('idsss', $order_id, $total_amount, $payment_method, $payment_status, $payment_date);
    
    if (!$paymentStmt->execute()) {
        throw new Exception('Failed to create payment record: ' . $conn->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    // Log activity
    $activity_message = "Admin created manual order #$order_id for customer ID $customer_id. Total: \$$total_amount";
    $logStmt = $conn->prepare(
        "INSERT INTO activity_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())"
    );
    $logStmt->bind_param('iss', $admin_id, $activity_message, $activity_message);
    $logStmt->execute();
    
    $_SESSION['success'] = "Order #$order_id created successfully!";
    header('Location: orders.php?id=' . $order_id);
    exit;
    
} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();
    
    $_SESSION['error'] = 'Error creating order: ' . $e->getMessage();
    header('Location: manual_order.php');
    exit;
}
?>
