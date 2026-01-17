<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only staff-related roles can access this page
require_staff();

include('../includes/header.php');

$username = $_SESSION['name'] ?? 'Staff';

// Fetch recent orders assigned to this staff (if you track it)
$orders = $conn->query("SELECT * FROM orders ORDER BY order_date DESC LIMIT 5");
?>

<!-- HEADER -->
<section class="bg-blue-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Staff Dashboard</h1>
  <p class="opacity-90">Welcome back, <?= htmlspecialchars($username) ?>!</p>
</section>

<!-- MAIN -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-6xl mx-auto">
    <!-- Quick Actions -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
      <a href="orders.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-box text-blue-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">Manage Orders</p>
      </a>

      <a href="deliveries.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-truck text-green-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">Track Deliveries</p>
      </a>

      <a href="../logout.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-sign-out-alt text-red-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">Logout</p>
      </a>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white shadow-lg rounded-xl p-6">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Orders</h2>
      <table class="min-w-full border border-gray-200">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="py-2 px-4 border-b">Order ID</th>
            <th class="py-2 px-4 border-b">Customer</th>
            <th class="py-2 px-4 border-b">Total</th>
            <th class="py-2 px-4 border-b">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php while ($row = $orders->fetch_assoc()): ?>
            <tr class="text-center">
              <td class="py-2 px-4 border-b">#<?= $row['id'] ?></td>
              <td class="py-2 px-4 border-b"><?= htmlspecialchars($row['customer_email'] ?? 'Unknown') ?></td>
              <td class="py-2 px-4 border-b">Rs <?= number_format($row['total_amount'], 2) ?></td>
              <td class="py-2 px-4 border-b"><?= date('d M, Y', strtotime($row['order_date'])) ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
