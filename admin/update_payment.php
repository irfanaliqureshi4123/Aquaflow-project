<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    $id = $_POST['id'];
    $order_id = $_POST['order_id'];
    $customer_name = $_POST['customer_name'];
    $amount = $_POST['amount'];
    $payment_method = $_POST['payment_method'];
    $status = $_POST['status'];

    $stmt = $conn->prepare("UPDATE payments SET order_id=?, customer_name=?, amount=?, payment_method=?, status=? WHERE id=?");
    $stmt->bind_param("ssdssi", $order_id, $customer_name, $amount, $payment_method, $status, $id);

    if ($stmt->execute()) {
        header("Location: payments.php?success=Payment+updated+successfully");
        exit();
    } else {
        header("Location: payments.php?error=Failed+to+update+payment");
        exit();
    }
}

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch existing payment for editing
if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $conn->prepare("SELECT * FROM payments WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        header("Location: payments.php?error=Payment+not+found");
        exit();
    }
} else {
    header("Location: payments.php");
    exit();
}

include('includes/header.php');
include('includes/sidebar.php');
?>

<div class="p-6 bg-gray-100 min-h-screen">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-xl font-bold mb-4">Edit Payment</h1>
        <form action="update_payment.php" method="POST">
            <input type="hidden" name="id" value="<?= $payment['id'] ?>">

            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Order ID</label>
                <input type="text" name="order_id" value="<?= $payment['order_id'] ?>" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Customer Name</label>
                <input type="text" name="customer_name" value="<?= $payment['customer_name'] ?>" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Amount</label>
                <input type="number" step="0.01" name="amount" value="<?= $payment['amount'] ?>" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
                    <option <?= $payment['payment_method'] == 'Cash' ? 'selected' : '' ?>>Cash</option>
                    <option <?= $payment['payment_method'] == 'Credit Card' ? 'selected' : '' ?>>Credit Card</option>
                    <option <?= $payment['payment_method'] == 'Bank Transfer' ? 'selected' : '' ?>>Bank Transfer</option>
                    <option <?= $payment['payment_method'] == 'Online Payment' ? 'selected' : '' ?>>Online Payment</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 text-sm mb-1">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
                    <option <?= $payment['status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
                    <option <?= $payment['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option <?= $payment['status'] == 'Failed' ? 'selected' : '' ?>>Failed</option>
                </select>
            </div>

            <div class="flex justify-end gap-2">
                <a href="payments.php" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</a>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Update</button>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        </form>
    </div>
</div>

<?php include('includes/footer.php'); ?>
