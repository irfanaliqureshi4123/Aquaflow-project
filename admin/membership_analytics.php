<?php
/**
 * Membership Analytics Dashboard
 * 
 * Admin analytics for membership trends, revenue forecasts, and customer insights.
 * Shows charts, graphs, and detailed metrics about subscription performance.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin
require_admin();

// Date range filter
$date_from = isset($_GET['from']) ? $_GET['from'] : date('Y-m-01');
$date_to = isset($_GET['to']) ? $_GET['to'] : date('Y-m-d');

// Fetch key metrics
$metrics_query = $conn->prepare("
    SELECT 
        COUNT(DISTINCT CASE WHEN status = 'active' THEN id END) as active_subscriptions,
        COUNT(DISTINCT CASE WHEN status = 'pending' THEN id END) as pending_subscriptions,
        COUNT(DISTINCT CASE WHEN status = 'expired' THEN id END) as expired_subscriptions,
        COUNT(DISTINCT CASE WHEN status = 'cancelled' THEN id END) as cancelled_subscriptions,
        SUM(CASE WHEN status = 'active' THEN m.price ELSE 0 END) as active_revenue,
        COUNT(DISTINCT user_id) as total_customers,
        AVG(m.price) as avg_plan_price,
        MAX(created_at) as last_subscription_date
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.created_at >= ? AND um.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
");

$metrics_query->execute([$date_from, $date_to]);
$metrics = $metrics_query->fetch_assoc();

// Revenue trend (daily)
$trend_query = $conn->prepare("
    SELECT 
        DATE(um.created_at) as date,
        COUNT(DISTINCT CASE WHEN um.status = 'active' THEN um.id END) as active_count,
        SUM(CASE WHEN um.status = 'active' THEN m.price ELSE 0 END) as daily_revenue,
        COUNT(DISTINCT um.id) as new_subscriptions
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.created_at >= ? AND um.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY DATE(um.created_at)
    ORDER BY date ASC
");

$trend_query->execute([$date_from, $date_to]);
$trend_data = [];
while ($row = $trend_query->fetch_assoc()) {
    $trend_data[] = $row;
}

// Top plans
$top_plans_query = $conn->prepare("
    SELECT 
        m.id,
        m.name,
        m.price,
        COUNT(um.id) as subscription_count,
        SUM(CASE WHEN um.status = 'active' THEN 1 ELSE 0 END) as active_count,
        SUM(m.price) as total_revenue
    FROM memberships m
    LEFT JOIN user_memberships um ON m.id = um.membership_id
        AND um.created_at >= ? AND um.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY m.id
    ORDER BY subscription_count DESC
    LIMIT 5
");

$top_plans_query->execute([$date_from, $date_to]);
$top_plans = [];
while ($row = $top_plans_query->fetch_assoc()) {
    $top_plans[] = $row;
}

// Customer status breakdown
$customer_query = $conn->prepare("
    SELECT 
        um.status,
        COUNT(DISTINCT um.user_id) as customer_count
    FROM user_memberships um
    WHERE um.created_at >= ? AND um.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY um.status
");

$customer_query->execute([$date_from, $date_to]);
$customer_breakdown = [];
while ($row = $customer_query->fetch_assoc()) {
    $customer_breakdown[$row['status']] = $row['customer_count'];
}

// Payment method breakdown
$payment_query = $conn->prepare("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(m.price) as revenue
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.payment_method IS NOT NULL 
        AND um.created_at >= ? 
        AND um.created_at <= DATE_ADD(?, INTERVAL 1 DAY)
    GROUP BY payment_method
");

$payment_query->execute([$date_from, $date_to]);
$payment_breakdown = [];
while ($row = $payment_query->fetch_assoc()) {
    $payment_breakdown[] = $row;
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Analytics - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <style>
        .metric-card {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .metric-value {
            font-size: 2.5rem;
            font-weight: bold;
            color: #06b6d4;
            margin: 0.5rem 0;
        }
        .metric-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .metric-card.trend-up .metric-value {
            color: #10b981;
        }
        .metric-card.trend-down .metric-value {
            color: #ef4444;
        }
        .chart-container {
            position: relative;
            height: 300px;
            margin-bottom: 2rem;
        }
        .chart-wrapper {
            background: white;
            border-radius: 0.5rem;
            padding: 1.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .date-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
        }
        .date-filter input {
            padding: 0.5rem 1rem;
            border: 1px solid #e5e7eb;
            border-radius: 0.375rem;
        }
        .date-filter button {
            background: #06b6d4;
            color: white;
            border: none;
            padding: 0.5rem 1.5rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
        }
        .date-filter button:hover {
            background: #0891b2;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f3f4f6;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Membership Analytics</h1>
        <p class="opacity-90">Comprehensive insights into membership performance and trends</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- DATE FILTER -->
            <div class="date-filter">
                <form method="GET" class="flex gap-2 flex-wrap">
                    <label>From: <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>"></label>
                    <label>To: <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>"></label>
                    <button type="submit"><i class="fas fa-filter mr-1"></i>Filter</button>
                    <a href="?from=<?= date('Y-m-01') ?>&to=<?= date('Y-m-d') ?>" class="bg-gray-400 hover:bg-gray-500 text-white px-3 py-2 rounded inline-flex items-center gap-1">
                        <i class="fas fa-redo"></i>This Month
                    </a>
                </form>
            </div>

            <!-- KEY METRICS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-check-circle text-green-600 mr-1"></i>Active Subscriptions</div>
                    <div class="metric-value"><?= $metrics['active_subscriptions'] ?? 0 ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-clock text-yellow-600 mr-1"></i>Pending Payments</div>
                    <div class="metric-value"><?= $metrics['pending_subscriptions'] ?? 0 ?></div>
                </div>
                <div class="metric-card">
                    <div class="metric-label"><i class="fas fa-users text-indigo-600 mr-1"></i>Total Customers</div>
                    <div class="metric-value"><?= $metrics['total_customers'] ?? 0 ?></div>
                </div>
                <div class="metric-card trend-up">
                    <div class="metric-label"><i class="fas fa-chart-line text-green-600 mr-1"></i>Active Revenue</div>
                    <div class="metric-value">Rs <?= number_format($metrics['active_revenue'] ?? 0, 0) ?></div>
                </div>
            </div>

            <!-- REVENUE TREND CHART -->
            <div class="chart-wrapper">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Revenue Trend</h2>
                <div class="chart-container">
                    <canvas id="revenueChart"></canvas>
                </div>
            </div>

            <!-- CHARTS ROW -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                <!-- SUBSCRIPTION STATUS BREAKDOWN -->
                <div class="chart-wrapper">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Subscription Status</h2>
                    <div class="chart-container">
                        <canvas id="statusChart"></canvas>
                    </div>
                </div>

                <!-- PAYMENT METHOD BREAKDOWN -->
                <div class="chart-wrapper">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Payment Method Distribution</h2>
                    <div class="chart-container">
                        <canvas id="paymentChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- TOP PLANS TABLE -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Top Performing Plans</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Plan Name</th>
                            <th>Price</th>
                            <th>Total Subscriptions</th>
                            <th>Active Subscriptions</th>
                            <th>Total Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_plans as $plan): ?>
                            <tr>
                                <td class="font-semibold"><?= htmlspecialchars($plan['name']) ?></td>
                                <td>Rs <?= number_format($plan['price'], 2) ?></td>
                                <td><?= $plan['subscription_count'] ?? 0 ?></td>
                                <td><span class="bg-green-100 text-green-800 px-3 py-1 rounded"><?= $plan['active_count'] ?? 0 ?></span></td>
                                <td class="font-semibold">Rs <?= number_format($plan['total_revenue'] ?? 0, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- DETAILED METRICS -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Subscription Status Breakdown</h3>
                    <ul class="space-y-2">
                        <li class="flex justify-between">
                            <span class="text-green-600"><i class="fas fa-check-circle mr-2"></i>Active</span>
                            <span class="font-bold"><?= $customer_breakdown['active'] ?? 0 ?> customers</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-yellow-600"><i class="fas fa-clock mr-2"></i>Pending</span>
                            <span class="font-bold"><?= $customer_breakdown['pending'] ?? 0 ?> customers</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-red-600"><i class="fas fa-times-circle mr-2"></i>Expired</span>
                            <span class="font-bold"><?= $customer_breakdown['expired'] ?? 0 ?> customers</span>
                        </li>
                        <li class="flex justify-between">
                            <span class="text-gray-600"><i class="fas fa-ban mr-2"></i>Cancelled</span>
                            <span class="font-bold"><?= $customer_breakdown['cancelled'] ?? 0 ?> customers</span>
                        </li>
                    </ul>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Payment Methods</h3>
                    <ul class="space-y-2">
                        <?php foreach ($payment_breakdown as $payment): ?>
                            <li class="flex justify-between">
                                <span class="text-gray-700">
                                    <i class="fas <?= $payment['payment_method'] === 'card' ? 'fa-credit-card' : 'fa-money-bill' ?> mr-2"></i>
                                    <?= $payment['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Credit Card' ?>
                                </span>
                                <span class="font-bold"><?= $payment['count'] ?> transactions</span>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>

                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h3 class="text-lg font-bold text-gray-800 mb-4">Key Metrics</h3>
                    <ul class="space-y-3">
                        <li>
                            <p class="text-gray-600 text-sm">Avg Plan Price</p>
                            <p class="text-2xl font-bold text-cyan-600">Rs <?= number_format($metrics['avg_plan_price'] ?? 0, 2) ?></p>
                        </li>
                        <li>
                            <p class="text-gray-600 text-sm">Expired Subscriptions</p>
                            <p class="text-2xl font-bold text-red-600"><?= $metrics['expired_subscriptions'] ?? 0 ?></p>
                        </li>
                        <li>
                            <p class="text-gray-600 text-sm">Cancelled Subscriptions</p>
                            <p class="text-2xl font-bold text-gray-600"><?= $metrics['cancelled_subscriptions'] ?? 0 ?></p>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Revenue Trend Chart
        const trendData = <?= json_encode($trend_data) ?>;
        const revenueCtx = document.getElementById('revenueChart').getContext('2d');
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: trendData.map(d => d.date),
                datasets: [{
                    label: 'Daily Revenue (Rs)',
                    data: trendData.map(d => d.daily_revenue || 0),
                    borderColor: '#06b6d4',
                    backgroundColor: 'rgba(6, 182, 212, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: true, position: 'bottom' }
                }
            }
        });

        // Status Breakdown Chart
        const statusCtx = document.getElementById('statusChart').getContext('2d');
        new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Active', 'Pending', 'Expired', 'Cancelled'],
                datasets: [{
                    data: [
                        <?= $metrics['active_subscriptions'] ?? 0 ?>,
                        <?= $metrics['pending_subscriptions'] ?? 0 ?>,
                        <?= $metrics['expired_subscriptions'] ?? 0 ?>,
                        <?= $metrics['cancelled_subscriptions'] ?? 0 ?>
                    ],
                    backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#9ca3af']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom' } }
            }
        });

        // Payment Method Chart
        const paymentData = <?= json_encode($payment_breakdown) ?>;
        const paymentCtx = document.getElementById('paymentChart').getContext('2d');
        new Chart(paymentCtx, {
            type: 'pie',
            data: {
                labels: paymentData.map(p => p.payment_method === 'cod' ? 'Cash on Delivery' : 'Credit Card'),
                datasets: [{
                    data: paymentData.map(p => p.count),
                    backgroundColor: ['#3b82f6', '#ec4899']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: true, position: 'bottom' } }
            }
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
