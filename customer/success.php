<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

$user_id = $_SESSION['user_id'];

// Retrieve order ID from GET or SESSION
$order_id = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    if ($order_id <= 0) {
        die('Invalid order ID.');
    }
    unset($_SESSION['last_order_id']); // Clear session if order_id is in GET
} elseif (isset($_SESSION['last_order_id'])) {
    $order_id = intval($_SESSION['last_order_id']);
    if ($order_id <= 0) {
        die('Invalid order ID.');
    }
    unset($_SESSION['last_order_id']);
}

if (!$order_id) {
    die("No order to show.");
}

// SECURITY: Fetch Order with user ownership verification
$order_q = $conn->prepare("
    SELECT o.*, o.invoice_no FROM orders o 
    WHERE o.id = ? AND o.user_id = ?
");
$order_q->bind_param("ii", $order_id, $user_id);
$order_q->execute();
$order = $order_q->get_result()->fetch_assoc();

// SECURITY: Ensure order belongs to this user
if (!$order) {
    // Order not found or doesn't belong to this user
    header('Location: dashboard.php');
    exit;
}

// Fetch Order Items
$item_q = $conn->prepare("
    SELECT oi.*, p.name as product_name, (oi.price * oi.quantity) as total_price 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$item_q->bind_param("i", $order_id);
$item_q->execute();
$items = $item_q->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Order Successful</title>
    <style>
        body {
            background: #f0f9ff;
            font-family: Arial;
        }
        .box {
            max-width: 600px;
            margin: 30px auto;
            background: white;
            padding: 25px;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        h1 {
            color: #00a859;
            text-align: center;
        }
        .invoice {
            font-size: 18px;
            margin-bottom: 20px;
            text-align: center;
            font-weight: bold;
        }
        table {
            width: 100%;
            margin-top: 15px;
            border-collapse: collapse;
        }
        th, td {
            border-bottom: 1px solid #ddd;
            padding: 10px;
        }
        .total {
            font-size: 20px;
            text-align: right;
            font-weight: bold;
            margin-top: 15px;
        }
        .btn {
            display: block;
            width: 100%;
            padding: 12px;
            margin-top: 20px;
            text-align: center;
            background: #00a859;
            color: white;
            border-radius: 8px;
            text-decoration: none;
            font-size: 18px;
        }
    </style>
</head>
<body>

<div class="box">
    <h1>🎉 Order Successful!</h1>
    <p class="invoice">Invoice #: <?php echo $order['invoice_no']; ?></p>

    <table>
        <tr>
            <th>Product</th><th>Qty</th><th>Price</th><th>Total</th>
        </tr>
        <?php while ($row = $items->fetch_assoc()) : ?>
        <tr>
            <td><?php echo htmlspecialchars($row['product_name']); ?></td>
            <td><?php echo $row['quantity']; ?></td>
            <td>₨<?php echo number_format($row['price'], 2); ?></td>
            <td>₨<?php echo number_format($row['total_price'], 2); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <p class="total">Grand Total: ₨<?php echo number_format($order['total_amount'], 2); ?></p>

    <a class="btn" href="../index.php">Continue Shopping</a>
</div>

</body>
</html>
