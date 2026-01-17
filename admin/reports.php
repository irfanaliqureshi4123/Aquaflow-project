<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// ------------------ SUMMARY COUNTS ------------------
$totalSalesQuery = $conn->query("SELECT SUM(price * quantity) AS total FROM order_items");
$totalSales = $totalSalesQuery->fetch_assoc()['total'] ?? 0;

$totalOrdersQuery = $conn->query("SELECT COUNT(*) AS total FROM orders");
$totalOrders = $totalOrdersQuery->fetch_assoc()['total'] ?? 0;

$totalCustomersQuery = $conn->query("SELECT COUNT(*) AS total FROM users WHERE role_as = 0");
$totalCustomers = $totalCustomersQuery->fetch_assoc()['total'] ?? 0;

// ------------------ MONTHLY SALES DATA ------------------
$monthlyData = [];
$months = [];

$result = $conn->query("
    SELECT MONTHNAME(o.created_at) AS month, SUM(oi.price * oi.quantity) AS total
    FROM orders o
    JOIN order_items oi ON o.id = oi.order_id
    GROUP BY MONTH(o.created_at)
    ORDER BY MONTH(o.created_at)
");
while ($row = $result->fetch_assoc()) {
    $months[] = $row['month'];
    $monthlyData[] = $row['total'];
}

// ------------------ TOP SELLING PRODUCTS ------------------
$topProducts = $conn->query("
    SELECT p.name, SUM(oi.quantity) AS total_sold
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    GROUP BY p.name
    ORDER BY total_sold DESC
    LIMIT 5
");
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <!-- HEADER -->
  <div class="flex justify-between items-center mb-6">
    <h1 class="text-3xl font-bold text-gray-800">📊 Sales & Performance Reports</h1>
    <p class="text-gray-500 text-sm">Updated on <?= date('F j, Y') ?></p>
  </div>

  <!-- DASHBOARD CARDS -->
  <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 mb-10">
    <div class="bg-white rounded-xl p-6 shadow hover:shadow-lg transition">
      <h3 class="text-gray-500 text-sm mb-1">Total Revenue</h3>
      <p class="text-3xl font-bold text-cyan-700">Rs <?= number_format($totalSales, 2) ?></p>
    </div>
    <div class="bg-white rounded-xl p-6 shadow hover:shadow-lg transition">
      <h3 class="text-gray-500 text-sm mb-1">Total Orders</h3>
      <p class="text-3xl font-bold text-cyan-700"><?= $totalOrders ?></p>
    </div>
    <div class="bg-white rounded-xl p-6 shadow hover:shadow-lg transition">
      <h3 class="text-gray-500 text-sm mb-1">Total Customers</h3>
      <p class="text-3xl font-bold text-cyan-700"><?= $totalCustomers ?></p>
    </div>
  </div>

  <!-- CHARTS -->
  <div class="grid gap-8 md:grid-cols-2">
    <!-- Monthly Revenue -->
    <div class="bg-white rounded-xl p-6 shadow">
      <h2 class="text-lg font-bold text-gray-700 mb-4">Monthly Revenue</h2>
      <canvas id="revenueChart" height="200"></canvas>
    </div>

    <!-- Top Products -->
    <div class="bg-white rounded-xl p-6 shadow">
      <h2 class="text-lg font-bold text-gray-700 mb-4">Top Selling Products</h2>
      <canvas id="topProductsChart" height="200"></canvas>
    </div>
  </div>
</div>

<!-- CHART.JS -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
const ctxRevenue = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(ctxRevenue, {
  type: 'line',
  data: {
    labels: <?= json_encode($months) ?>,
    datasets: [{
      label: 'Revenue (Rs)',
      data: <?= json_encode($monthlyData) ?>,
      borderColor: '#06b6d4',
      backgroundColor: 'rgba(6, 182, 212, 0.2)',
      borderWidth: 3,
      tension: 0.4,
      fill: true
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true }
    }
  }
});

const ctxProducts = document.getElementById('topProductsChart').getContext('2d');
const topProductsChart = new Chart(ctxProducts, {
  type: 'bar',
  data: {
    labels: [<?php 
      $labels = [];
      $values = [];
      $topProductsResult = $conn->query("
        SELECT p.name, SUM(oi.quantity) AS total_sold
        FROM order_items oi
        JOIN products p ON oi.product_id = p.id
        GROUP BY p.name
        ORDER BY total_sold DESC
        LIMIT 5
      ");
      while($r = $topProductsResult->fetch_assoc()) {
        $labels[] = $r['name'];
        $values[] = $r['total_sold'];
      }
      echo "'" . implode("','", $labels) . "'";
    ?>],
    datasets: [{
      label: 'Units Sold',
      data: [<?= implode(",", $values) ?>],
      backgroundColor: ['#06b6d4', '#0891b2', '#0e7490', '#155e75', '#164e63']
    }]
  },
  options: {
    responsive: true,
    scales: {
      y: { beginAtZero: true }
    }
  }
});
</script>

<?php include('includes/footer.php'); ?>
