<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

$user_id = $_SESSION['user_id'];
$email = $_SESSION['user_email'] ?? $_SESSION['email'] ?? '';

// Fetch all orders for the customer
$orders_query = $conn->prepare("
    SELECT o.id, o.total_amount, o.status, o.order_date, o.customer_email
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.order_date DESC
");
$orders_query->bind_param('i', $user_id);
$orders_query->execute();
$orders_result = $orders_query->get_result();

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Orders - AquaFlow</title>
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

        .orders-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 20px;
        }

        .orders-header {
            margin-bottom: 30px;
        }

        .orders-header h1 {
            color: #333;
            font-size: 32px;
            margin-bottom: 10px;
        }

        .orders-header p {
            color: #666;
            font-size: 16px;
        }

        .orders-grid {
            display: grid;
            gap: 20px;
        }

        .order-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #e8ecf1;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .order-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid #e8ecf1;
        }

        .order-id {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }

        .order-date {
            color: #666;
            font-size: 14px;
        }

        .status-badge {
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
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

        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .detail-item {
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            color: #999;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 600;
            margin-bottom: 6px;
        }

        .detail-value {
            color: #333;
            font-size: 16px;
            font-weight: 600;
        }

        .detail-value.amount {
            color: #00d084;
            font-size: 20px;
        }

        .order-footer {
            display: flex;
            gap: 10px;
            margin-top: 16px;
            padding-top: 16px;
            border-top: 1px solid #e8ecf1;
        }

        .btn {
            flex: 1;
            padding: 10px 16px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            text-align: center;
        }

        .btn-view {
            background: linear-gradient(135deg, #00d084 0%, #00a86b 100%);
            color: white;
        }

        .btn-view:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 208, 132, 0.3);
        }

        .btn-receipt {
            background: #f8fafb;
            color: #00d084;
            border: 1.5px solid #00d084;
        }

        .btn-receipt:hover {
            background: #f0fdf4;
        }

        .empty-state {
            background: white;
            border-radius: 12px;
            padding: 60px 30px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .empty-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .empty-title {
            color: #333;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 10px;
        }

        .empty-text {
            color: #666;
            font-size: 16px;
            margin-bottom: 30px;
        }

        .btn-shop {
            background: linear-gradient(135deg, #00d084 0%, #00a86b 100%);
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .btn-shop:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 208, 132, 0.3);
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

        @media (max-width: 600px) {
            .orders-container {
                padding: 15px;
                margin: 20px auto;
            }

            .orders-header h1 {
                font-size: 24px;
            }

            .order-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .order-details {
                grid-template-columns: 1fr 1fr;
            }

            .order-footer {
                flex-direction: column;
            }

            .btn {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="orders-container">
        <div class="back-link">
            <a href="dashboard.php">← Back to Dashboard</a>
        </div>

        <div class="orders-header">
            <h1>My Orders</h1>
            <p>View and manage your order history</p>
        </div>

        <?php if ($orders_result->num_rows > 0): ?>
            <div class="orders-grid">
                <?php while ($order = $orders_result->fetch_assoc()): ?>
                    <div class="order-card">
                        <div class="order-header">
                            <div>
                                <div class="order-id">Order #<?php echo $order['id']; ?></div>
                                <div class="order-date"><?php echo date('F d, Y - h:i A', strtotime($order['order_date'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower(str_replace(' ', '', $order['status'])); ?>">
                                <?php echo htmlspecialchars($order['status']); ?>
                            </span>
                        </div>

                        <div class="order-details">
                            <div class="detail-item">
                                <span class="detail-label">Order Date</span>
                                <span class="detail-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Total Amount</span>
                                <span class="detail-value amount">PKR <?php echo number_format($order['total_amount'], 0); ?></span>
                            </div>
                            <div class="detail-item">
                                <span class="detail-label">Status</span>
                                <span class="detail-value"><?php echo htmlspecialchars($order['status']); ?></span>
                            </div>
                        </div>

                        <div class="order-footer">
                            <a href="order_details.php?order_id=<?php echo $order['id']; ?>" class="btn btn-view">
                                📄 View Details
                            </a>
                            <a href="javascript:void(0)" onclick="alert('Invoice download feature coming soon!')" class="btn btn-receipt">
                                🧾 Receipt
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <div class="empty-icon">📦</div>
                <div class="empty-title">No Orders Yet</div>
                <p class="empty-text">You haven't placed any orders yet. Start shopping today!</p>
                <a href="../products.php" class="btn-shop">🛒 Shop Now</a>
            </div>
        <?php endif; ?>
    </div>

    <?php include('../includes/footer.php'); ?>
</body>
</html>
