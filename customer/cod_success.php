<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Get order ID from session or URL parameter
$order_id = null;
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    unset($_SESSION['last_order_id']); // Clear session if order_id is in GET
} elseif (isset($_SESSION['last_order_id'])) {
    $order_id = intval($_SESSION['last_order_id']);
    unset($_SESSION['last_order_id']);
}

if (!$order_id) {
    header("Location: dashboard.php");
    exit;
}

// Fetch order details
$order_q = $conn->prepare("
    SELECT o.id, o.total_amount, o.status, o.order_date, o.customer_email
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$order_q->bind_param("ii", $order_id, $_SESSION['user_id']);
$order_q->execute();
$order = $order_q->get_result()->fetch_assoc();

if (!$order) {
    header("Location: dashboard.php");
    exit;
}

// Fetch order items
$items_q = $conn->prepare("
    SELECT oi.product_id, p.name, oi.price, oi.quantity
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$items_q->bind_param("i", $order_id);
$items_q->execute();
$order_items = $items_q->get_result();

include('../includes/header.php');
?>

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
        padding: 20px;
    }

    .success-container {
        background: white;
        border-radius: 20px;
        box-shadow: 0 10px 40px rgba(0, 0, 0, 0.08);
        max-width: 700px;
        width: 100%;
        margin: 40px auto;
        overflow: hidden;
    }

    .success-header {
        background: linear-gradient(135deg, #00d084 0%, #00a86b 100%);
        padding: 50px 30px 40px;
        text-align: center;
        position: relative;
        overflow: hidden;
    }

    .success-header::before {
        content: '';
        position: absolute;
        top: -50%;
        right: -20%;
        width: 300px;
        height: 300px;
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .success-icon {
        width: 90px;
        height: 90px;
        background: rgba(255, 255, 255, 0.25);
        border-radius: 50%;
        display: flex;
        justify-content: center;
        align-items: center;
        margin: 0 auto 20px;
        position: relative;
        animation: popIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55);
    }

    @keyframes popIn {
        0% {
            transform: scale(0) rotateZ(-45deg);
            opacity: 0;
        }
        50% {
            transform: scale(1.1);
        }
        100% {
            transform: scale(1) rotateZ(0);
            opacity: 1;
        }
    }

    .success-icon svg {
        width: 50px;
        height: 50px;
        stroke: white;
        stroke-width: 2.5;
        fill: none;
        stroke-linecap: round;
        stroke-linejoin: round;
    }

    h1 {
        color: white;
        font-size: 32px;
        margin-bottom: 10px;
        font-weight: 700;
        letter-spacing: -0.5px;
    }

    .subtitle {
        color: rgba(255, 255, 255, 0.9);
        font-size: 16px;
        margin-bottom: 0;
        font-weight: 500;
    }

    .success-body {
        padding: 40px 30px;
    }

    .order-id-box {
        background: linear-gradient(135deg, #f5f7fa 0%, #f9fafc 100%);
        border: 2px dashed #00d084;
        border-radius: 12px;
        padding: 25px;
        text-align: center;
        margin-bottom: 30px;
    }

    .order-id-label {
        color: #666;
        font-size: 13px;
        text-transform: uppercase;
        letter-spacing: 1px;
        font-weight: 600;
        margin-bottom: 8px;
    }

    .order-id-value {
        font-size: 36px;
        color: #00d084;
        font-weight: 800;
        font-family: 'Monaco', 'Courier New', monospace;
    }

    .message-box {
        background: linear-gradient(135deg, #e8f8f5 0%, #f0fdf4 100%);
        border-left: 5px solid #00d084;
        padding: 20px;
        border-radius: 10px;
        margin-bottom: 30px;
    }

    .message-title {
        color: #00a86b;
        font-weight: 700;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .message-text {
        color: #2d6a4f;
        font-size: 14px;
        line-height: 1.6;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 30px;
    }

    .info-card {
        background: #f8fafb;
        padding: 16px;
        border-radius: 10px;
        border: 1px solid #e8ecf1;
    }

    .info-label {
        color: #999;
        font-size: 12px;
        text-transform: uppercase;
        font-weight: 600;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
        display: block;
    }

    .info-value {
        color: #333;
        font-size: 16px;
        font-weight: 600;
    }

    .items-section {
        margin-bottom: 30px;
    }

    .items-title {
        color: #333;
        font-weight: 700;
        margin-bottom: 15px;
        font-size: 15px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #999;
    }

    .items-list {
        background: #f8fafb;
        border-radius: 10px;
        overflow: hidden;
        border: 1px solid #e8ecf1;
    }

    .item-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 14px 16px;
        border-bottom: 1px solid #e8ecf1;
    }

    .item-row:last-child {
        border-bottom: none;
    }

    .item-info {
        flex: 1;
    }

    .item-name {
        color: #333;
        font-weight: 600;
        margin-bottom: 4px;
    }

    .item-qty {
        color: #999;
        font-size: 13px;
    }

    .item-price {
        color: #00d084;
        font-weight: 700;
        font-size: 15px;
        text-align: right;
    }

    .total-section {
        background: linear-gradient(135deg, #f5f7fa 0%, #f9fafc 100%);
        padding: 20px 16px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border: 1px solid #e8ecf1;
        margin-bottom: 30px;
    }

    .total-label {
        color: #333;
        font-weight: 700;
        font-size: 16px;
    }

    .total-amount {
        color: #00d084;
        font-weight: 800;
        font-size: 28px;
    }

    .divider {
        height: 1px;
        background: #e8ecf1;
        margin: 25px 0;
    }

    .action-buttons {
        display: flex;
        gap: 12px;
    }

    .btn {
        flex: 1;
        padding: 14px 20px;
        border: none;
        border-radius: 10px;
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

    .contact-info {
        color: #999;
        font-size: 13px;
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #e8ecf1;
    }

    .contact-info a {
        color: #00d084;
        text-decoration: none;
        font-weight: 600;
    }

    .contact-info a:hover {
        text-decoration: underline;
    }

    @media (max-width: 600px) {
        .success-body {
            padding: 30px 20px;
        }

        .info-grid {
            grid-template-columns: 1fr;
        }

        .action-buttons {
            flex-direction: column;
        }

        h1 {
            font-size: 26px;
        }

        .order-id-value {
            font-size: 28px;
        }
    }
</style>

<div class="success-container">
    <div class="success-header">
        <div class="success-icon">
            <svg viewBox="0 0 24 24">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
        </div>
        <h1>Order Confirmed!</h1>
        <p class="subtitle">Your payment has been received</p>
    </div>

    <div class="success-body">
        <div class="order-id-box">
            <span class="order-id-label">Order ID</span>
            <div class="order-id-value">#<?php echo $order_id; ?></div>
        </div>

        <div class="message-box">
            <div class="message-title">📦 Cash on Delivery</div>
            <p class="message-text">Your order will be delivered to your address. You can pay the full amount upon delivery. Our team will contact you shortly to confirm the delivery details.</p>
        </div>

        <div class="info-grid">
            <div class="info-card">
                <span class="info-label">Order Date</span>
                <div class="info-value"><?php echo date('M d, Y', strtotime($order['order_date'])); ?></div>
            </div>
            <div class="info-card">
                <span class="info-label">Order Time</span>
                <div class="info-value"><?php echo date('h:i A', strtotime($order['order_date'])); ?></div>
            </div>
            <div class="info-card">
                <span class="info-label">Email</span>
                <div class="info-value" style="font-size: 14px; word-break: break-all;"><?php echo htmlspecialchars($order['customer_email']); ?></div>
            </div>
            <div class="info-card">
                <span class="info-label">Status</span>
                <div class="info-value" style="color: #ff9800;">Pending - COD</div>
            </div>
        </div>

        <div class="items-section">
            <span class="items-title">Order Items</span>
            <div class="items-list">
                <?php while ($item = $order_items->fetch_assoc()): ?>
                    <div class="item-row">
                        <div class="item-info">
                            <div class="item-name"><?php echo htmlspecialchars($item['name']); ?></div>
                            <div class="item-qty">Qty: <?php echo $item['quantity']; ?> × PKR <?php echo number_format($item['price'], 0); ?></div>
                        </div>
                        <div class="item-price">PKR <?php echo number_format($item['price'] * $item['quantity'], 0); ?></div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>

        <div class="total-section">
            <span class="total-label">Total Amount</span>
            <span class="total-amount">PKR <?php echo number_format($order['total_amount'], 0); ?></span>
        </div>

        <div class="divider"></div>

        <div class="action-buttons">
            <a href="dashboard.php" class="btn btn-primary">📊 View Orders</a>
            <a href="../products.php" class="btn btn-secondary">🛒 Continue Shopping</a>
        </div>

        <div class="contact-info">
            Need help? <a href="javascript:void(0)" onclick="alert('Contact support at support@aquaflow.com or call +92-300-XXXXXXX')">Contact Support</a>
        </div>
    </div>
</div>

<?php include('../includes/footer.php'); ?>
