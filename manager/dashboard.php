<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only manager can access this page
require_manager();

include('../includes/header.php');

$username = $_SESSION['name'] ?? 'Manager';
$manager_id = $_SESSION['user_id'];

// Fetch key statistics for managers
$totalOrders = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'];
$totalStaff = $conn->query("SELECT COUNT(*) AS total FROM staff WHERE role IN ('staff', 'manager', 'delivery')")->fetch_assoc()['total'];
$totalRevenue = $conn->query("SELECT SUM(total_amount) AS total FROM orders WHERE status IN ('completed', 'delivered')")->fetch_assoc()['total'] ?? 0;
$pendingOrders = $conn->query("SELECT COUNT(*) AS total FROM orders WHERE status = 'pending'")->fetch_assoc()['total'];

// Fetch recent orders
$recentOrders = $conn->query("
  SELECT id, user_id, total_amount, status, payment_method, order_date 
  FROM orders 
  ORDER BY order_date DESC 
  LIMIT 10
");

// Fetch staff performance (most active staff members)
$staffPerformance = $conn->query("
  SELECT name, role, email, created_at
  FROM staff
  ORDER BY created_at DESC
  LIMIT 5
");
?>

<!-- PAGE HEADER -->
<section class="bg-gradient-to-r from-blue-600 to-indigo-600 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Manager Dashboard</h1>
    <p class="text-lg opacity-90">Oversee operations and manage team performance</p>
  </div>
</section>

<!-- DASHBOARD CONTENT -->
<section class="py-10 bg-gray-50">
  <div class="container mx-auto px-4">

    <!-- WELCOME MESSAGE -->
    <div class="mb-8 bg-white shadow-lg rounded-xl p-6">
      <h2 class="text-2xl font-bold text-gray-800">Welcome, <?= htmlspecialchars($username) ?>! 👋</h2>
      <p class="text-gray-600 mt-2">Here's an overview of your team's performance and operations.</p>
    </div>

    <!-- KEY STATISTICS -->
    <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-blue-100 text-blue-700 p-3 rounded-full">
          <i class="fas fa-shopping-cart text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Total Orders</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalOrders ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-yellow-100 text-yellow-700 p-3 rounded-full">
          <i class="fas fa-clock text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Pending Orders</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $pendingOrders ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-purple-100 text-purple-700 p-3 rounded-full">
          <i class="fas fa-wallet text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Total Revenue</h3>
          <p class="text-2xl font-bold text-gray-800">$<?= number_format($totalRevenue, 2) ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-green-100 text-green-700 p-3 rounded-full">
          <i class="fas fa-users text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Team Members</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalStaff ?></p>
        </div>
      </div>
    </div>

    <!-- QUICK ACTIONS -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
      <a href="orders.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-box text-blue-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold">View Orders</p>
      </a>

      <a href="deliveries.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-truck text-orange-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold">Track Deliveries</p>
      </a>

      <a href="#staff-list" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-chart-line text-green-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold">Team Performance</p>
      </a>
    </div>

    <!-- RECENT ORDERS -->
    <div class="grid lg:grid-cols-3 gap-8 mb-8">
      <div class="lg:col-span-2 bg-white shadow-lg rounded-xl p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-4">📦 Recent Orders</h3>
        <div class="overflow-x-auto">
          <table class="w-full text-sm">
            <thead class="bg-gray-100">
              <tr>
                <th class="px-4 py-2 text-left">Order ID</th>
                <th class="px-4 py-2 text-left">Amount</th>
                <th class="px-4 py-2 text-left">Status</th>
                <th class="px-4 py-2 text-left">Payment</th>
                <th class="px-4 py-2 text-left">Date</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($order = $recentOrders->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                  <td class="px-4 py-2">
                    <a href="../admin/orders.php?id=<?= $order['id'] ?>" class="text-blue-600 hover:underline font-semibold">
                      #<?= $order['id'] ?>
                    </a>
                  </td>
                  <td class="px-4 py-2 font-bold">$<?= number_format($order['total_amount'], 2) ?></td>
                  <td class="px-4 py-2">
                    <span class="px-3 py-1 rounded-full text-xs font-bold
                      <?php 
                        switch($order['status']) {
                          case 'completed': echo 'bg-green-100 text-green-800'; break;
                          case 'pending': echo 'bg-yellow-100 text-yellow-800'; break;
                          case 'cancelled': echo 'bg-red-100 text-red-800'; break;
                          default: echo 'bg-gray-100 text-gray-800';
                        }
                      ?>">
                      <?= ucfirst($order['status']) ?>
                    </span>
                  </td>
                  <td class="px-4 py-2"><?= ucwords(str_replace('_', ' ', $order['payment_method'])) ?></td>
                  <td class="px-4 py-2"><?= date('M d, Y', strtotime($order['order_date'])) ?></td>
                </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
        <a href="orders.php" class="text-blue-600 hover:underline text-sm mt-4 inline-block">View all orders →</a>
      </div>

      <!-- TEAM STATUS -->
      <div class="bg-white shadow-lg rounded-xl p-6" id="staff-list">
        <h3 class="text-lg font-bold text-gray-800 mb-4">👥 Recent Team Additions</h3>
        <div class="space-y-3">
          <?php while ($staff = $staffPerformance->fetch_assoc()): ?>
            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
              <div>
                <p class="font-semibold text-gray-800"><?= htmlspecialchars($staff['name']) ?></p>
                <p class="text-xs text-gray-500"><?= ucfirst($staff['role']) ?></p>
              </div>
              <span class="text-xs font-bold px-2 py-1 rounded
                <?php echo $staff['role'] === 'manager' ? 'bg-purple-100 text-purple-800' : 'bg-blue-100 text-blue-800'; ?>">
                <?= ucfirst($staff['role']) ?>
              </span>
            </div>
          <?php endwhile; ?>
        </div>
        <a href="../admin/staff.php" class="text-blue-600 hover:underline text-sm mt-4 inline-block">Manage team →</a>
      </div>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
