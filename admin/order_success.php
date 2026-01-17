<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

if (!isset($_GET['id'])) {
  header("Location: index.php");
  exit;
}

$order_id = intval($_GET['id']);
$order_query = $conn->query("SELECT * FROM orders WHERE id = $order_id");
$order = $order_query->fetch_assoc();

if (!$order) {
  echo "<div class='text-center py-20 text-gray-600 text-lg'>Order not found.</div>";
  include('includes/footer.php');
  exit;
}

$items_query = $conn->query("SELECT oi.*, p.name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = $order_id");
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Order Confirmed!</h1>
    <p class="text-lg opacity-90">Thank you for choosing AquaFlow 💧</p>
  </div>
</section>

<!-- ORDER SUCCESS -->
<section class="py-16 bg-gray-50 min-h-[60vh]">
  <div class="container mx-auto px-4 max-w-3xl bg-white shadow-xl rounded-2xl p-8">
    <div class="text-center mb-10">
      <div class="inline-block bg-green-100 text-green-600 rounded-full p-4 mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10 mx-auto" fill="none" viewBox="0 0 24 24" stroke="currentColor">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
        </svg>
      </div>
      <h2 class="text-2xl font-bold text-gray-800">Your order has been placed successfully!</h2>
      <p class="text-gray-600 mt-2">We’ll deliver your water bottles soon at your doorstep.</p>
    </div>

    <!-- Order Info -->
    <div class="border-t border-gray-200 pt-6">
      <h3 class="text-xl font-semibold text-gray-800 mb-4">Order Details</h3>

      <div class="bg-gray-50 rounded-lg p-5 mb-6">
        <p class="text-gray-700"><span class="font-medium">Order ID:</span> #<?= $order['id'] ?></p>
        <p class="text-gray-700"><span class="font-medium">Customer:</span> <?= htmlspecialchars($order['customer_name']) ?></p>
        <p class="text-gray-700"><span class="font-medium">Phone:</span> <?= htmlspecialchars($order['phone']) ?></p>
        <p class="text-gray-700"><span class="font-medium">Address:</span> <?= htmlspecialchars($order['address']) ?></p>
        <p class="text-gray-700"><span class="font-medium">Payment Method:</span> <?= htmlspecialchars($order['payment_method']) ?></p>
        <p class="text-cyan-700 font-semibold mt-2 text-lg">Total: Rs <?= number_format($order['total_amount'], 2) ?></p>
      </div>

      <h4 class="text-lg font-semibold text-gray-800 mb-2">Ordered Items</h4>
      <div class="divide-y divide-gray-200">
        <?php while ($item = $items_query->fetch_assoc()): ?>
          <div class="flex justify-between py-3">
            <p class="text-gray-700"><?= htmlspecialchars($item['name']) ?> (x<?= $item['quantity'] ?>)</p>
            <p class="font-medium text-gray-800">Rs <?= number_format($item['price'] * $item['quantity'], 2) ?></p>
          </div>
        <?php endwhile; ?>
      </div>
    </div>

    <!-- ACTIONS -->
    <div class="text-center mt-8">
      <a href="products.php" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold px-8 py-3 rounded-md transition">
        Continue Shopping
      </a>
      <a href="index.php" class="ml-4 bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-3 rounded-md transition">
        Go to Home
      </a>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>

// Assume $orderId contains the confirmed order's ID

// Automatically generate invoice after order confirmation
$invoiceUrl = "http://localhost/aquaWater/admin/generate_invoice.php?order_id=" . urlencode($orderId);
$response = file_get_contents($invoiceUrl);

// Optionally, handle the response (e.g., display success message, log, etc.)
if ($response !== false) {
    // You can log or display $response if needed
    // echo $response;
} else {
    // Handle error
    // echo "Failed to generate invoice.";
}