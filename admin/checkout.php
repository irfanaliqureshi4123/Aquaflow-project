<?php
session_start();
require_once('../includes/db_connect.php');

// Access Control: Admin should NOT checkout as customer
// This functionality has been removed in the role-based access control update
// Admin creates orders through /admin/manual_order.php instead

// Redirect to admin dashboard
header('Location: dashboard.php');
exit;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name']);
  $phone = trim($_POST['phone']);
  $address = trim($_POST['address']);
  $payment = $_POST['payment_method'];

  // Validate
  if ($name && $phone && $address && $payment) {
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
      $total += $item['price'] * $item['quantity'];
    }

    try {
        $conn->autocommit(false); // Start transaction

        // Insert order
        $orderStmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, status) VALUES (?, ?, 'pending')");
        $orderStmt->bind_param("id", $userId, $totalAmount);
        $orderStmt->execute();
        $orderId = $conn->insert_id;

        // Insert order items
        $itemStmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($cartItems as $item) {
            $itemStmt->bind_param("iiid", $orderId, $item['product_id'], $item['quantity'], $item['price']);
            $itemStmt->execute();
        }

        // Process payment
        $paymentStmt = $conn->prepare("INSERT INTO payments (order_id, amount, method, status) VALUES (?, ?, ?, 'completed')");
        $paymentStmt->bind_param("ids", $orderId, $totalAmount, $paymentMethod);
        $paymentStmt->execute();

        $conn->commit();
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Checkout failed: " . $e->getMessage());
        die('Checkout processing failed');
    }
    $order_id = $stmt->insert_id;

    // Insert order items
    foreach ($_SESSION['cart'] as $item) {
      $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price) VALUES (?, ?, ?, ?)");
      $stmt_item->bind_param("iiid", $order_id, $item['id'], $item['quantity'], $item['price']);
      $stmt_item->execute();
    }

    unset($_SESSION['cart']); // Clear cart after checkout

    header("Location: order_success.php?id=" . $order_id);
    exit;
  }
}
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Checkout</h1>
    <p class="text-lg opacity-90">Complete your order and enjoy fresh, pure water at your door</p>
  </div>
</section>

<!-- CHECKOUT FORM -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4 grid md:grid-cols-2 gap-10">
    
    <!-- LEFT: Customer Details -->
    <div class="bg-white shadow-lg rounded-xl p-8">
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Billing Details</h2>
      <form method="POST" class="space-y-5">
        <div>
          <label class="block text-gray-700 mb-2 font-medium">Full Name</label>
          <input type="text" name="name" required
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
        </div>

        <div>
          <label class="block text-gray-700 mb-2 font-medium">Phone Number</label>
          <input type="text" name="phone" required
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
        </div>

        <div>
          <label class="block text-gray-700 mb-2 font-medium">Delivery Address</label>
          <textarea name="address" rows="3" required
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none"></textarea>
        </div>

        <div>
          <label class="block text-gray-700 mb-2 font-medium">Payment Method</label>
          <select name="payment_method" required
            class="w-full border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
            <option value="">Select Payment Method</option>
            <option value="COD">Cash on Delivery</option>
            <option value="Card">Credit/Debit Card</option>
            <option value="EasyPaisa">EasyPaisa</option>
            <option value="JazzCash">JazzCash</option>
          </select>
        </div>

        <button type="submit"
          class="w-full bg-green-600 hover:bg-green-700 text-white font-semibold text-lg py-3 rounded-md transition">
          Place Order
        </button>
      </form>
    </div>

    <!-- RIGHT: Order Summary -->
    <div class="bg-white shadow-lg rounded-xl p-8">
      <h2 class="text-2xl font-bold text-gray-800 mb-6">Order Summary</h2>
      <div class="divide-y divide-gray-200">
        <?php
        $total = 0;
        foreach ($_SESSION['cart'] as $item):
          $subtotal = $item['price'] * $item['quantity'];
          $total += $subtotal;
        ?>
        <div class="flex justify-between py-3">
          <div>
            <p class="font-medium text-gray-800"><?= htmlspecialchars($item['name']) ?></p>
            <p class="text-gray-500 text-sm">Qty: <?= $item['quantity'] ?></p>
          </div>
          <p class="font-semibold text-gray-700">Rs <?= number_format($subtotal, 2) ?></p>
        </div>
        <?php endforeach; ?>
      </div>

      <div class="border-t border-gray-200 mt-6 pt-4">
        <div class="flex justify-between text-lg font-semibold text-gray-800">
          <span>Total</span>
          <span class="text-cyan-700">Rs <?= number_format($total, 2) ?></span>
        </div>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>