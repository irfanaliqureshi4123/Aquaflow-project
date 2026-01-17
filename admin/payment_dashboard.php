<?php
/**
 * Payment Monitoring & Analytics Dashboard
 * 
 * Admin dashboard for monitoring payment transactions, tracking logs,
 * and analyzing payment trends.
 * 
 * Features:
 * - Real-time payment statistics
 * - Payment success/failure rates
 * - Recent transactions
 * - Payment method breakdown
 * - Revenue analytics
 * - Failed payment alerts
 * - Webhook event status
 * 
 * Security:
 * - Admin authentication required
 * - Session verification
 * - Database query parameterization
 * - Output escaping
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

session_start();
include('../includes/db_connect.php');

// SECURITY: Verify admin is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// SECURITY: Verify user is admin (if roles are implemented)
// For now, allow all logged-in users to see this dashboard
// TODO: Add role verification when admin roles are implemented

// Fetch payment statistics
$stats_query = $conn->query("
    SELECT 
        COUNT(*) as total_payments,
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as successful_payments,
        SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) as failed_payments,
        SUM(CASE WHEN status = 'Pending' THEN 1 ELSE 0 END) as pending_payments,
        SUM(amount) as total_amount,
        AVG(amount) as avg_amount,
        MAX(created_at) as last_payment
    FROM payments
");
$stats = $stats_query->fetch_assoc();

// Fetch payment method breakdown
$methods_query = $conn->query("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total_amount,
        SUM(CASE WHEN status = 'Paid' THEN 1 ELSE 0 END) as successful
    FROM payments
    GROUP BY payment_method
    ORDER BY count DESC
");

// Fetch recent transactions
$recent_query = $conn->prepare("
    SELECT 
        p.id,
        p.invoice_no,
        p.customer_name,
        p.amount,
        p.payment_method,
        p.status,
        p.created_at,
        o.user_id
    FROM payments p
    LEFT JOIN orders o ON p.order_id = o.id
    ORDER BY p.created_at DESC
    LIMIT 20
");
$recent_query->execute();
$recent = $recent_query->get_result();

// Fetch daily revenue (last 7 days)
$revenue_query = $conn->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as transaction_count,
        SUM(CASE WHEN status = 'Paid' THEN amount ELSE 0 END) as daily_revenue,
        SUM(CASE WHEN status = 'Failed' THEN 1 ELSE 0 END) as failed_count
    FROM payments
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date DESC
");

// Fetch failed payments (last 24 hours)
$failed_query = $conn->query("
    SELECT 
        id,
        invoice_no,
        customer_name,
        amount,
        payment_method,
        created_at
    FROM payments
    WHERE status = 'Failed'
    AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ORDER BY created_at DESC
");

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Dashboard - AquaWater Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="bg-gray-100">

<div class="min-h-screen">
    <div class="max-w-7xl mx-auto px-4 py-8">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 mb-2">💳 Payment Dashboard</h1>
            <p class="text-gray-600">Monitor payments, transactions, and revenue analytics</p>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4 mb-8">
            
            <!-- Total Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Payments</p>
                        <p class="text-3xl font-bold text-gray-900">
                            <?php echo $stats['total_payments'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="text-4xl text-blue-500">📊</div>
                </div>
            </div>

            <!-- Successful Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Successful</p>
                        <p class="text-3xl font-bold text-green-600">
                            <?php echo $stats['successful_payments'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="text-4xl text-green-500">✅</div>
                </div>
            </div>

            <!-- Failed Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Failed</p>
                        <p class="text-3xl font-bold text-red-600">
                            <?php echo $stats['failed_payments'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="text-4xl text-red-500">❌</div>
                </div>
            </div>

            <!-- Pending Payments -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Pending</p>
                        <p class="text-3xl font-bold text-yellow-600">
                            <?php echo $stats['pending_payments'] ?? 0; ?>
                        </p>
                    </div>
                    <div class="text-4xl text-yellow-500">⏳</div>
                </div>
            </div>

            <!-- Total Revenue -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">Total Revenue</p>
                        <p class="text-3xl font-bold text-green-700">
                            Rs <?php echo number_format($stats['total_amount'] ?? 0, 0); ?>
                        </p>
                    </div>
                    <div class="text-4xl text-green-600">💰</div>
                </div>
            </div>

        </div>

        <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            
            <!-- Payment Method Breakdown -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Payment Method Breakdown</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Method</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Count</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Successful</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Revenue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($method = $methods_query->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo htmlspecialchars($method['payment_method']); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">
                                    <?php echo $method['count']; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <span class="bg-green-100 text-green-800 px-2 py-1 rounded">
                                        <?php echo $method['successful']; ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                    Rs <?php echo number_format($method['total_amount'] ?? 0, 0); ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Daily Revenue (Last 7 Days) -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Daily Revenue (Last 7 Days)</h2>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Date</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Transactions</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Revenue</th>
                                <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Failed</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($day = $revenue_query->fetch_assoc()): ?>
                            <tr class="border-t">
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($day['date'])); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right text-gray-600">
                                    <?php echo $day['transaction_count']; ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right font-semibold text-green-700">
                                    Rs <?php echo number_format($day['daily_revenue'] ?? 0, 0); ?>
                                </td>
                                <td class="px-4 py-3 text-sm text-right">
                                    <?php if ($day['failed_count'] > 0): ?>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded">
                                            <?php echo $day['failed_count']; ?>
                                        </span>
                                    <?php else: ?>
                                        <span class="text-gray-400">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>

        <!-- Failed Payments Alert -->
        <?php if ($failed_query->num_rows > 0): ?>
        <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-8">
            <h2 class="text-xl font-bold text-red-900 mb-4">⚠️ Failed Payments (Last 24 Hours)</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-red-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-red-900">Invoice</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-red-900">Customer</th>
                            <th class="px-4 py-2 text-right text-sm font-semibold text-red-900">Amount</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-red-900">Method</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-red-900">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($failed = $failed_query->fetch_assoc()): ?>
                        <tr class="border-t border-red-200">
                            <td class="px-4 py-3 text-sm font-mono text-red-700">
                                <?php echo htmlspecialchars($failed['invoice_no']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php echo htmlspecialchars($failed['customer_name']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                Rs <?php echo number_format($failed['amount'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="bg-gray-200 text-gray-800 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($failed['payment_method']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo date('h:i A', strtotime($failed['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Transactions -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Recent Transactions (Last 20)</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Invoice</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Customer</th>
                            <th class="px-4 py-2 text-right text-sm font-semibold text-gray-600">Amount</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Method</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Status</th>
                            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-600">Time</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($trans = $recent->fetch_assoc()): ?>
                        <tr class="border-t hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm font-mono text-blue-700">
                                <?php echo htmlspecialchars($trans['invoice_no']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900">
                                <?php echo htmlspecialchars($trans['customer_name']); ?>
                            </td>
                            <td class="px-4 py-3 text-sm text-right font-semibold text-gray-900">
                                Rs <?php echo number_format($trans['amount'], 2); ?>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($trans['payment_method']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm">
                                <?php 
                                $status_colors = [
                                    'Paid' => 'bg-green-100 text-green-800',
                                    'Failed' => 'bg-red-100 text-red-800',
                                    'Pending' => 'bg-yellow-100 text-yellow-800'
                                ];
                                $status_class = $status_colors[$trans['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="<?php echo $status_class; ?> px-2 py-1 rounded">
                                    <?php echo htmlspecialchars($trans['status']); ?>
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600">
                                <?php echo date('M d, h:i A', strtotime($trans['created_at'])); ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-600 text-sm">
            <p>Last updated: <?php echo date('M d, Y h:i A'); ?></p>
            <p>Auto-refresh every 5 minutes</p>
        </div>

    </div>
</div>

<!-- Auto-refresh every 5 minutes -->
<script>
    setTimeout(() => {
        location.reload();
    }, 5 * 60 * 1000);
</script>

</body>
</html>
