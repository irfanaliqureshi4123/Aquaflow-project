<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_id'], $_POST['status'])) {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    $conn->query("UPDATE orders SET status = '$status' WHERE id = $order_id");
}

// Filter orders
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT * FROM orders";
if ($filter != 'all') {
    $query .= " WHERE status = '$filter'";
}
$query .= " ORDER BY order_date DESC";
$orders = $conn->query($query);
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Manage Orders</h1>
    <p class="text-lg opacity-90">View, filter, and update all customer orders</p>
  </div>
</section>

<!-- FILTER BAR -->
<section class="py-8 bg-gray-50 border-b border-gray-200">
  <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <label for="status" class="font-medium text-gray-700">Filter by status:</label>
      <select id="status" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none"
        onchange="window.location='orders.php?status='+this.value">
        <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All</option>
        <option value="Pending" <?= $filter == 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="Processing" <?= $filter == 'Processing' ? 'selected' : '' ?>>Processing</option>
        <option value="Delivered" <?= $filter == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
        <option value="Cancelled" <?= $filter == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
  </div>
</section>

<!-- ORDERS TABLE -->
<section class="py-12 bg-white">
  <div class="container mx-auto px-4 overflow-x-auto">
    <table class="min-w-full border border-gray-200 shadow-md rounded-lg overflow-hidden">
      <thead class="bg-cyan-600 text-white text-left">
        <tr>
          <th class="py-3 px-6">Order ID</th>
          <th class="py-3 px-6">Customer</th>
          <th class="py-3 px-6">Phone</th>
          <th class="py-3 px-6">Total</th>
          <th class="py-3 px-6">Payment</th>
          <th class="py-3 px-6">Status</th>
          <th class="py-3 px-6">Created At</th>
          <th class="py-3 px-6 text-center">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($orders && $orders->num_rows > 0): ?>
          <?php while ($row = $orders->fetch_assoc()): ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-6 font-medium text-gray-800">#<?= $row['id'] ?></td>
              <td class="py-3 px-6 text-gray-700"><?= htmlspecialchars($row['customer_email'] ?? 'N/A') ?></td>
              <td class="py-3 px-6 text-gray-700">N/A</td>
              <td class="py-3 px-6 font-semibold text-cyan-700">Rs <?= number_format($row['total_amount'], 2) ?></td>
              <td class="py-3 px-6 text-gray-700"><?= htmlspecialchars($row['payment_method']) ?></td>
              <td class="py-3 px-6">
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                  <?php
                    switch ($row['status']) {
                      case 'Delivered': echo 'bg-green-100 text-green-700'; break;
                      case 'Processing': echo 'bg-yellow-100 text-yellow-700'; break;
                      case 'Cancelled': echo 'bg-red-100 text-red-700'; break;
                      default: echo 'bg-gray-100 text-gray-700';
                    }
                  ?>">
                  <?= htmlspecialchars($row['status'] ?? 'Pending') ?>
                </span>
              </td>
              <td class="py-3 px-6 text-gray-600 text-sm"><?= date("M d, Y h:i A", strtotime($row['order_date'])) ?></td>
              <td class="py-3 px-6 text-center">
                <form method="POST" class="inline-block">
                  <input type="hidden" name="order_id" value="<?= $row['id'] ?>">
                  <select name="status" class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:ring-2 focus:ring-cyan-600">
                    <option value="Pending" <?= $row['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
                    <option value="Processing" <?= $row['status'] == 'Processing' ? 'selected' : '' ?>>Processing</option>
                    <option value="Delivered" <?= $row['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                    <option value="Cancelled" <?= $row['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
                  </select>
                  <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded-md ml-2 text-sm font-medium">
                    Update
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="8" class="py-8 text-center text-gray-500">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
