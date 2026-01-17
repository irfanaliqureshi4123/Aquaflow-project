<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) {
    header('Location: orders.php');
    exit;
}

// Fetch order details
$order_query = $conn->prepare("
    SELECT o.id, o.total_amount, o.status, o.order_date, o.customer_email
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$order_query->bind_param('ii', $order_id, $user_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows === 0) {
    header('Location: orders.php');
    exit;
}

$order = $order_result->fetch_assoc();

// Fetch order items
$items_query = $conn->prepare("
    SELECT oi.product_id, p.name, p.description, oi.price, oi.quantity
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items_query->bind_param('i', $order_id);
$items_query->execute();
$items_result = $items_query->get_result();

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - AquaFlow</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', sans-serif;
            background: #f5f7fa;
            min-height: 100vh;
        }

        .details-container {
            max-width: 900px;
            margin: 40px auto;
            padding: 20px;
        }

        .back-link {
            margin-bottom: 20px;
        }

        .back-link a {
            color: #00d084;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: color 0.3s ease;
        }

        .back-link a:hover {
            color: #00a86b;
        }

        .page-header {
            margin-bottom: 30px;
        }

        .page-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .page-header p {
            color: #666;
            font-size: 16px;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecf1;
        }

        .card-title {
            color: #333;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f0f4ff;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 14px 0;
            border-bottom: 1px solid #e8ecf1;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            color: #666;
            font-weight: 600;
        }

        .info-value {
            color: #333;
            font-weight: 600;
            text-align: right;
        }

        .info-value.status {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-cod {
            background: #fff3cd;
            color: #856404;
        }

        .status-pending {
            background: #cfe2ff;
            color: #084298;
        }

        .status-paid {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-completed {
            background: #d1e7dd;
            color: #0f5132;
        }

        .status-cancelled {
            background: #f8d7da;
            color: #842029;
        }

        .summary-card {
            background: linear-gradient(135deg, #f5f7fa 0%, #f9fafc 100%);
            border: 1px solid #e8ecf1;
        }

        .summary-item {
            padding: 14px 0;
            border-bottom: 1px solid #e8ecf1;
        }

        .summary-item:last-child {
            border-bottom: none;
        }

        .summary-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        .summary-value {
            color: #333;
            font-size: 18px;
            font-weight: 700;
        }

        .summary-value.total {
            color: #00d084;
            font-size: 28px;
        }

        .items-section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecf1;
        }

        .items-section .card-title {
            margin-top: 0;
        }

        .items-table {
            width: 100%;
            border-collapse: collapse;
        }

        .table-header {
            background: #f8fafb;
            border-bottom: 2px solid #e8ecf1;
        }

        .table-header th {
            padding: 14px;
            text-align: left;
            color: #666;
            font-weight: 700;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .table-body td {
            padding: 16px 14px;
            border-bottom: 1px solid #e8ecf1;
        }

        .table-body tr:last-child td {
            border-bottom: none;
        }

        .item-name {
            color: #333;
            font-weight: 600;
        }

        .item-description {
            color: #999;
            font-size: 13px;
            margin-top: 4px;
        }

        .qty-badge {
            background: #f0f4ff;
            color: #667eea;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
        }

        .item-price {
            color: #00d084;
            font-weight: 700;
            font-size: 16px;
        }

        .item-total {
            color: #333;
            font-weight: 700;
            text-align: right;
        }

        .timeline {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecf1;
            margin-bottom: 30px;
        }

        .timeline-item {
            display: flex;
            gap: 15px;
            padding: 16px 0;
            border-bottom: 1px solid #e8ecf1;
        }

        .timeline-item:last-child {
            border-bottom: none;
        }

        .timeline-dot {
            width: 12px;
            height: 12px;
            background: #00d084;
            border-radius: 50%;
            margin-top: 6px;
            flex-shrink: 0;
        }

        .timeline-content h4 {
            color: #333;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .timeline-content p {
            color: #666;
            font-size: 14px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 12px 20px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            text-align: center;
        }

        .btn-primary {
            background: linear-gradient(135deg, #00d084 0%, #00a86b 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(0, 208, 132, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 208, 132, 0.4);
        }

        .btn-secondary {
            background: white;
            color: #00d084;
            border: 2px solid #00d084;
        }

        .btn-secondary:hover {
            background: #f8fafb;
        }

        @media (max-width: 768px) {
            .details-grid {
                grid-template-columns: 1fr;
            }

            .details-container {
                padding: 15px;
                margin: 20px auto;
            }

            .page-header h1 {
                font-size: 24px;
            }

            .action-buttons {
                flex-direction: column;
            }

            .table-header th:nth-child(3),
            .table-body td:nth-child(3) {
                display: none;
            }
        }
    </style>
</head>
<body>
    <div class="details-container">
        <div class="back-link">
            <a href="orders.php">← Back to Orders</a>
        </div>

        <div class="page-header">
            <h1>Order Details</h1>
            <p>Order #<?php echo $order['id']; ?></p>
        </div>

        <div class="details-grid">
            <div class="card">
                <h2 class="card-title">Order Information</h2>
                
                <div class="info-row">
                    <span class="info-label">Order ID</span>
                    <span class="info-value">#<?php echo $order['id']; ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Order Date</span>
                    <span class="info-value"><?php echo date('F d, Y', strtotime($order['order_date'])); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Order Time</span>
                    <span class="info-value"><?php echo date('h:i A', strtotime($order['order_date'])); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Email</span>
                    <span class="info-value" style="font-size: 14px; word-break: break-word;"><?php echo htmlspecialchars($order['customer_email']); ?></span>
                </div>

                <div class="info-row">
                    <span class="info-label">Status</span>
                    <span class="info-value status status-<?php echo strtolower(str_replace(' ', '', $order['status'])); ?>">
                        <?php echo htmlspecialchars($order['status']); ?>
                    </span>
                </div>
            </div>

            <div class="card summary-card">
                <h2 class="card-title">Order Summary</h2>

                <div class="summary-item">
                    <span class="summary-label">Subtotal</span>
                    <span class="summary-value">PKR <?php echo number_format($order['total_amount'], 0); ?></span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Shipping</span>
                    <span class="summary-value">Free</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Tax</span>
                    <span class="summary-value">Included</span>
                </div>

                <div class="summary-item">
                    <span class="summary-label">Total</span>
                    <span class="summary-value total">PKR <?php echo number_format($order['total_amount'], 0); ?></span>
                </div>
            </div>
        </div>

        <div class="items-section">
            <h2 class="card-title">Order Items</h2>
            
            <table class="items-table">
                <thead class="table-header">
                    <tr>
                        <th>Product</th>
                        <th>Quantity</th>
                        <th>Price</th>
                        <th style="text-align: right;">Total</th>
                    </tr>
                </thead>
                <tbody class="table-body">
                    <?php while ($item = $items_result->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                                <?php if ($item['description']): ?>
                                    <div class="item-description"><?php echo htmlspecialchars(substr($item['description'], 0, 50)) . '...'; ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="qty-badge"><?php echo $item['quantity']; ?> x</span>
                            </td>
                            <td>
                                <span class="item-price">PKR <?php echo number_format($item['price'], 0); ?></span>
                            </td>
                            <td>
                                <span class="item-total">PKR <?php echo number_format($item['price'] * $item['quantity'], 0); ?></span>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <div class="timeline">
            <h2 class="card-title">Order Status Timeline</h2>
            
            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>Order Placed</h4>
                    <p><?php echo date('F d, Y - h:i A', strtotime($order['order_date'])); ?></p>
                </div>
            </div>

            <div class="timeline-item">
                <div class="timeline-dot"></div>
                <div class="timeline-content">
                    <h4>Order Confirmed</h4>
                    <p>Payment received and processed</p>
                </div>
            </div>

            <?php if ($order['status'] === 'Completed' || $order['status'] === 'Paid'): ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>Order Shipped</h4>
                        <p>Your order is on the way</p>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>Order Delivered</h4>
                        <p>Order successfully delivered</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="timeline-item">
                    <div class="timeline-dot"></div>
                    <div class="timeline-content">
                        <h4>Order Processing</h4>
                        <p>We're preparing your order for shipment</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="action-buttons">
            <a href="orders.php" class="btn btn-primary">← Back to Orders</a>
            <a href="download_invoice.php?order_id=<?php echo $order['id']; ?>" class="btn btn-secondary">📥 Download Invoice</a>
        </div>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>
</html>
