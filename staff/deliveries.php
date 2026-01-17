<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only staff-related roles can access this page
require_staff();

include('../includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-blue-700 text-white text-center py-12">
  <div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold">Delivery Tracking</h1>
    <p class="text-lg opacity-90 mt-2">View all delivery schedules and track order shipments</p>
  </div>
</section>

<div class="container mx-auto px-4 py-10">
  <!-- Search Controls -->
  <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
    <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" name="search" placeholder="Search by customer or order ID..."
        class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-600 w-full md:w-72"
        value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '' ?>" />
      <button type="submit"
        class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-md font-medium">Search</button>
    </form>

    <form method="GET" class="flex items-center gap-2">
      <label for="status_filter" class="text-gray-700 font-medium">Filter by Status:</label>
      <select name="status" id="status_filter" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-600"
        onchange="this.form.submit()">
        <option value="">All Statuses</option>
        <option value="Pending" <?= isset($_GET['status']) && $_GET['status'] == 'Pending' ? 'selected' : '' ?>>Pending</option>
        <option value="On the Way" <?= isset($_GET['status']) && $_GET['status'] == 'On the Way' ? 'selected' : '' ?>>On the Way</option>
        <option value="Delivered" <?= isset($_GET['status']) && $_GET['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
        <option value="Cancelled" <?= isset($_GET['status']) && $_GET['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
    </form>
  </div>

  <!-- Delivery Table -->
  <div class="overflow-x-auto bg-white rounded-xl shadow">
    <table class="min-w-full border border-gray-200 text-sm text-left">
      <thead class="bg-blue-700 text-white">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">Order ID</th>
          <th class="px-6 py-3">Customer</th>
          <th class="px-6 py-3">Address</th>
          <th class="px-6 py-3">Delivery Date</th>
          <th class="px-6 py-3">Status</th>
          <th class="px-6 py-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        
        $query = "SELECT * FROM deliveries WHERE 1";
        if (!empty($search)) {
          $search = $conn->real_escape_string($search);
          $query .= " AND (customer_name LIKE '%$search%' OR order_id LIKE '%$search%')";
        }
        if (!empty($status_filter)) {
          $status_filter = $conn->real_escape_string($status_filter);
          $query .= " AND status = '$status_filter'";
        }
        $query .= " ORDER BY delivery_date DESC";

        $result = $conn->query($query);
        if ($result && $result->num_rows > 0):
          while ($row = $result->fetch_assoc()):
        ?>
        <tr class="border-b hover:bg-gray-50 transition">
          <td class="px-6 py-4"><?= $row['id']; ?></td>
          <td class="px-6 py-4 font-semibold text-blue-700">#<?= htmlspecialchars($row['order_id']); ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['customer_name']); ?></td>
          <td class="px-6 py-4 text-gray-700"><?= htmlspecialchars($row['address']); ?></td>
          <td class="px-6 py-4"><?= date('M d, Y', strtotime($row['delivery_date'])); ?></td>
          <td class="px-6 py-4">
            <span class="px-3 py-1 rounded-full text-xs font-medium 
              <?= $row['status'] == 'Delivered' ? 'bg-green-100 text-green-700' : 
                ($row['status'] == 'On the Way' ? 'bg-blue-100 text-blue-700' : 
                ($row['status'] == 'Cancelled' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700')); ?>">
              <?= htmlspecialchars($row['status']); ?>
            </span>
          </td>
          <td class="px-6 py-4">
            <?php if ($_SESSION['role'] === 'delivery'): ?>
              <?php if ($row['status'] !== 'Delivered' && $row['status'] !== 'Cancelled'): ?>
                <div class="flex gap-2">
                  <?php if ($row['status'] !== 'On the Way'): ?>
                    <a href="update_delivery_status.php?id=<?= $row['id'] ?>&status=On the Way" 
                       class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-xs font-medium">
                      <i class="fas fa-truck mr-1"></i>On Way
                    </a>
                  <?php endif; ?>
                  <a href="update_delivery_status.php?id=<?= $row['id'] ?>&status=Delivered" 
                     class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-xs font-medium">
                    <i class="fas fa-check mr-1"></i>Delivered
                  </a>
                </div>
              <?php else: ?>
                <span class="text-gray-500 text-sm">No actions</span>
              <?php endif; ?>
            <?php else: ?>
              <span class="text-gray-500 text-sm">View only</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center py-6 text-gray-500">No deliveries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
