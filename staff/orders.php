<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only staff-related roles can access this page
require_staff();

include('../includes/header.php');

// Filter orders
$filter = isset($_GET['status']) ? $_GET['status'] : 'all';
$query = "SELECT * FROM orders";
if ($filter == 'pending') {
    // Pending means empty/null status
    $query .= " WHERE status = '' OR status IS NULL";
} elseif ($filter != 'all' && $filter != '') {
    $query .= " WHERE status = '$filter'";
}
$query .= " ORDER BY order_date DESC";
$orders = $conn->query($query);
?>

<!-- PAGE HEADER -->
<section class="bg-blue-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">View Orders</h1>
    <p class="text-lg opacity-90">View all customer orders and their status</p>
  </div>
</section>

<!-- FILTER BAR -->
<section class="py-8 bg-gray-50 border-b border-gray-200">
  <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
    <div class="flex items-center gap-3">
      <label for="status" class="font-medium text-gray-700">Filter by status:</label>
      <select id="status" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-600 focus:outline-none"
        onchange="window.location='orders.php?status='+this.value">
        <option value="all" <?= $filter == 'all' ? 'selected' : '' ?>>All Orders</option>
        <option value="pending" <?= $filter == 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="confirmed" <?= $filter == 'confirmed' ? 'selected' : '' ?>>Confirmed</option>
        <option value="delivered" <?= $filter == 'delivered' ? 'selected' : '' ?>>Delivered</option>
        <option value="cancelled" <?= $filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </div>
  </div>
</section>

<!-- ORDERS TABLE -->
<section class="py-12 bg-white">
  <div class="container mx-auto px-4 overflow-x-auto">
    <table class="min-w-full border border-gray-200 shadow-md rounded-lg overflow-hidden">
      <thead class="bg-blue-600 text-white text-left">
        <tr>
          <th class="py-3 px-6">Order ID</th>
          <th class="py-3 px-6">Customer</th>
          <th class="py-3 px-6">Total</th>
          <th class="py-3 px-6">Payment Method</th>
          <th class="py-3 px-6">Order Status</th>
          <th class="py-3 px-6">Payment Status</th>
          <th class="py-3 px-6">Order Date</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-gray-200">
        <?php if ($orders && $orders->num_rows > 0): ?>
          <?php while ($row = $orders->fetch_assoc()): ?>
            <?php
              // Check if payment exists for this order
              $payment_result = $conn->query("SELECT * FROM payments WHERE invoice_no = '" . $row['invoice_no'] . "' LIMIT 1");
              $payment = $payment_result ? $payment_result->fetch_assoc() : null;
              
              // Determine payment status
              if ($payment && isset($payment['status']) && $payment['status'] != '') {
                $payment_status = $payment['status'];
              } elseif ($row['payment_method'] == 'Cash on Delivery') {
                $payment_status = 'pending';
              } else {
                $payment_status = 'pending';
              }
            ?>
            <tr class="hover:bg-gray-50 transition">
              <td class="py-3 px-6 font-medium text-gray-800">#<?= $row['id'] ?></td>
              <td class="py-3 px-6 text-gray-700"><?= htmlspecialchars($row['customer_email'] ?? 'N/A') ?></td>
              <td class="py-3 px-6 font-semibold text-blue-700">Rs <?= number_format($row['total_amount'], 2) ?></td>
              <td class="py-3 px-6 text-gray-700"><?= htmlspecialchars($row['payment_method']) ?></td>
              <td class="py-3 px-6">
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                  <?php
                    $status_lower = strtolower($row['status']);
                    switch ($status_lower) {
                      case 'delivered': echo 'bg-green-100 text-green-700'; break;
                      case 'confirmed': echo 'bg-yellow-100 text-yellow-700'; break;
                      case 'cancelled': echo 'bg-red-100 text-red-700'; break;
                      default: echo 'bg-gray-100 text-gray-700';
                    }
                  ?>">
                  <?= htmlspecialchars(ucfirst($row['status']) ?? 'Pending') ?>
                </span>
              </td>
              <td class="py-3 px-6">
                <span class="px-3 py-1 rounded-full text-sm font-medium 
                  <?php
                    $payment_lower = strtolower($payment_status);
                    switch ($payment_lower) {
                      case 'paid':
                      case 'success':
                      case 'completed': echo 'bg-green-100 text-green-700'; break;
                      case 'failed': echo 'bg-red-100 text-red-700'; break;
                      case 'processing': echo 'bg-blue-100 text-blue-700'; break;
                      default: echo 'bg-yellow-100 text-yellow-700';
                    }
                  ?>">
                  <?= htmlspecialchars(ucfirst($payment_status)) ?>
                </span>
              </td>
              <td class="py-3 px-6 text-gray-600 text-sm"><?= date("M d, Y h:i A", strtotime($row['order_date'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="py-8 text-center text-gray-500">No orders found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
