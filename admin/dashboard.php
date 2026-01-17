<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch key stats
$totalProducts = $conn->query("SELECT COUNT(*) AS total FROM products")->fetch_assoc()['total'];
$totalOrders   = $conn->query("SELECT COUNT(*) AS total FROM orders")->fetch_assoc()['total'];
$totalCustomers = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role = 'customer'")->fetch_assoc()['total'];
$totalRevenue  = $conn->query("SELECT SUM(total_amount) AS total FROM orders")->fetch_assoc()['total'] ?? 0;

// Fetch membership stats
$totalSubscriptions = $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status = 'active'")->fetch_assoc()['total'] ?? 0;
$membershipRevenue = $conn->query("SELECT SUM(m.price) AS total FROM user_memberships um JOIN memberships m ON um.membership_id = m.id WHERE um.status = 'active'")->fetch_assoc()['total'] ?? 0;

// Fetch monthly order data for chart
$orderData = $conn->query("
  SELECT DATE_FORMAT(order_date, '%b') AS month, SUM(total_amount) AS total
  FROM orders
  WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
  GROUP BY month
  ORDER BY order_date ASC
");
$months = [];
$totals = [];
while ($row = $orderData->fetch_assoc()) {
  $months[] = $row['month'];
  $totals[] = $row['total'];
}
?>

<!-- HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Admin Dashboard</h1>
  <p class="opacity-90">Overview of AquaFlow Performance</p>
</section>

<!-- MAIN DASHBOARD -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-6xl mx-auto">
    <!-- SUMMARY CARDS -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-10">
      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-cyan-100 text-cyan-700 p-3 rounded-full">
          <i class="fas fa-bottle-water text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Total Products</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalProducts ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-green-100 text-green-700 p-3 rounded-full">
          <i class="fas fa-shopping-cart text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Total Orders</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalOrders ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-yellow-100 text-yellow-700 p-3 rounded-full">
          <i class="fas fa-users text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Customers</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalCustomers ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-purple-100 text-purple-700 p-3 rounded-full">
          <i class="fas fa-wallet text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Revenue (Rs)</h3>
          <p class="text-2xl font-bold text-gray-800"><?= number_format($totalRevenue, 2) ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-indigo-100 text-indigo-700 p-3 rounded-full">
          <i class="fas fa-star text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Active Subscriptions</h3>
          <p class="text-2xl font-bold text-gray-800"><?= $totalSubscriptions ?></p>
        </div>
      </div>

      <div class="bg-white shadow-lg rounded-xl p-6 flex items-center gap-4 hover:shadow-xl transition">
        <div class="bg-pink-100 text-pink-700 p-3 rounded-full">
          <i class="fas fa-credit-card text-2xl"></i>
        </div>
        <div>
          <h3 class="text-sm text-gray-500">Membership Revenue (Rs)</h3>
          <p class="text-2xl font-bold text-gray-800"><?= number_format($membershipRevenue, 2) ?></p>
        </div>
      </div>
    </div>
    <!-- QUICK LINKS -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-5 gap-6 mb-6">
      <a href="products.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-bottle-water text-cyan-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">Manage Products</p>
      </a>

      <a href="orders.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-box text-green-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">View Orders</p>
      </a>

      <a href="customers.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-user text-yellow-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">View Customers</p>
      </a>

      <a href="staff.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-users-gear text-purple-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">Manage Staff</p>
      </a>

      <a href="manual_order.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition">
        <i class="fas fa-plus-circle text-orange-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">Create Order</p>
      </a>

      <a href="memberships.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 flex flex-col items-center justify-center transition border-2 border-indigo-300">
        <i class="fas fa-crown text-indigo-600 text-4xl mb-2"></i>
        <p class="text-gray-800 font-semibold text-sm">Manage Subscriptions</p>
      </a>
    </div>

    <!-- PARTNERSHIPS SECTION -->
    <div class="bg-gradient-to-r from-cyan-50 to-blue-50 shadow-lg rounded-xl p-8 mb-10">
      <div class="grid md:grid-cols-2 gap-8 items-center">
        <div>
          <h2 class="text-2xl font-bold text-cyan-700 mb-4">Manage Partnerships</h2>
          <p class="text-gray-700 mb-6">
            <strong>Need bulk supply or have questions?</strong><br>
            We provide special pricing for wholesalers and offices.
          </p>
          <a href="partnerships.php" class="inline-block bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white px-6 py-3 rounded-lg font-semibold transition shadow-lg hover:shadow-xl">
            <i class="fas fa-handshake mr-2"></i>Manage Partnerships
          </a>
        </div>
        <div class="text-center">
          <div class="bg-white rounded-lg p-8 shadow-md">
            <i class="fas fa-building text-cyan-600 text-6xl mb-4"></i>
            <p class="text-gray-700 font-semibold">Partnership Opportunities</p>
            <p class="text-sm text-gray-500 mt-2">Expand your business with us</p>
          </div>
        </div>
      </div>
    </div>

    <!-- CHART SECTION -->
    <div class="bg-white shadow-lg rounded-xl p-8 mb-10">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">📈 Sales Overview (Last 6 Months)</h2>
      <canvas id="ordersChart" height="100"></canvas>
    </div>

    <!-- end quick links (moved above) -->
  </div>
</section>

<!-- CHART.JS SCRIPT -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const ctx = document.getElementById('ordersChart').getContext('2d');
  const ordersChart = new Chart(ctx, {
    type: 'line',
    data: {
      labels: <?= json_encode($months) ?>,
      datasets: [{
        label: 'Monthly Revenue (Rs)',
        data: <?= json_encode($totals) ?>,
        backgroundColor: 'rgba(14, 165, 233, 0.2)',
        borderColor: '#0891b2',
        borderWidth: 3,
        tension: 0.3,
        fill: true,
        pointBackgroundColor: '#0891b2',
      }]
    },
    options: {
      responsive: true,
      scales: {
        y: {
          beginAtZero: true,
          ticks: { color: '#374151' }
        },
        x: {
          ticks: { color: '#374151' }
        }
      },
      plugins: {
        legend: { display: true, labels: { color: '#111827' } }
      }
    }
  });
</script>

<?php include('../includes/footer.php'); ?>
