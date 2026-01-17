<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch new orders (placed in the last 24 hours)
$newOrders = $conn->query("
  SELECT id, customer_name, total_amount, created_at 
  FROM orders 
  WHERE created_at >= NOW() - INTERVAL 1 DAY
  ORDER BY created_at DESC
");

// Fetch new payments (made in the last 24 hours)
$newPayments = $conn->query("
  SELECT p.id, o.customer_name, p.amount, p.status, p.created_at
  FROM payments p
  JOIN orders o ON p.order_id = o.id
  WHERE p.created_at >= NOW() - INTERVAL 1 DAY
  ORDER BY p.created_at DESC
");

// Fetch low stock products (stock < 10)
$lowStock = $conn->query("
  SELECT id, name, stock 
  FROM products 
  WHERE stock < 10
  ORDER BY stock ASC
");
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">🔔 Notifications & Alerts</h1>

  <!-- New Orders -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2">
      🛒 Recent Orders
    </h2>

    <?php if ($newOrders->num_rows > 0): ?>
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-cyan-600 text-white">
            <tr>
              <th class="py-3 px-4 text-left">Order ID</th>
              <th class="py-3 px-4 text-left">Customer</th>
              <th class="py-3 px-4 text-left">Total (Rs)</th>
              <th class="py-3 px-4 text-left">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $newOrders->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800">#<?= $row['id'] ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($row['customer_name']) ?></td>
                <td class="py-3 px-4 text-cyan-700 font-semibold"><?= $row['total_amount'] ?></td>
                <td class="py-3 px-4 text-gray-500"><?= date('d M, Y h:i A', strtotime($row['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-gray-500 italic">No new orders in the last 24 hours.</p>
    <?php endif; ?>
  </section>

  <!-- New Payments -->
  <section class="mb-8">
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2">
      💳 Recent Payments
    </h2>

    <?php if ($newPayments->num_rows > 0): ?>
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-green-600 text-white">
            <tr>
              <th class="py-3 px-4 text-left">Payment ID</th>
              <th class="py-3 px-4 text-left">Customer</th>
              <th class="py-3 px-4 text-left">Amount (Rs)</th>
              <th class="py-3 px-4 text-left">Status</th>
              <th class="py-3 px-4 text-left">Date</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($pay = $newPayments->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800">#<?= $pay['id'] ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($pay['customer_name']) ?></td>
                <td class="py-3 px-4 text-green-700 font-semibold"><?= $pay['amount'] ?></td>
                <td class="py-3 px-4">
                  <?php if ($pay['status'] == 'Completed'): ?>
                    <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">Completed</span>
                  <?php else: ?>
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="py-3 px-4 text-gray-500"><?= date('d M, Y h:i A', strtotime($pay['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-gray-500 italic">No new payments in the last 24 hours.</p>
    <?php endif; ?>
  </section>

  <!-- Low Stock Alerts -->
  <section>
    <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center gap-2">
      ⚠️ Low Stock Products
    </h2>

    <?php if ($lowStock->num_rows > 0): ?>
      <div class="bg-white rounded-xl shadow overflow-hidden">
        <table class="min-w-full border border-gray-200">
          <thead class="bg-red-600 text-white">
            <tr>
              <th class="py-3 px-4 text-left">Product ID</th>
              <th class="py-3 px-4 text-left">Name</th>
              <th class="py-3 px-4 text-left">Stock Left</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($prod = $lowStock->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="py-3 px-4 font-medium text-gray-800">#<?= $prod['id'] ?></td>
                <td class="py-3 px-4"><?= htmlspecialchars($prod['name']) ?></td>
                <td class="py-3 px-4 text-red-600 font-semibold"><?= $prod['stock'] ?></td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="text-gray-500 italic">All products have sufficient stock.</p>
    <?php endif; ?>
  </section>
</div>

<?php include('includes/footer.php'); ?>
