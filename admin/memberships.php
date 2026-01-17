<?php
/**
 * Manage Customer Memberships/Subscriptions
 * 
 * Admin page to view and manage all customer memberships and subscriptions.
 * 
 * Features:
 * - View all active and inactive subscriptions
 * - Manage membership plans
 * - View customer subscription history
 * - Update subscription status
 * - Generate reports
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch all memberships
$memberships_query = $conn->query("
    SELECT m.*, COUNT(um.id) AS active_count
    FROM memberships m
    LEFT JOIN user_memberships um ON m.id = um.membership_id AND um.status = 'active'
    GROUP BY m.id
    ORDER BY m.price ASC
");

// Fetch subscription statistics
$stats = [
    'total_active' => $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status = 'active'")->fetch_assoc()['total'] ?? 0,
    'total_pending' => $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status = 'pending'")->fetch_assoc()['total'] ?? 0,
    'total_expired' => $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status = 'expired'")->fetch_assoc()['total'] ?? 0,
    'total_cancelled' => $conn->query("SELECT COUNT(*) AS total FROM user_memberships WHERE status = 'cancelled'")->fetch_assoc()['total'] ?? 0,
];

// Get active tab
$tab = isset($_GET['tab']) ? sanitize_input($_GET['tab']) : 'overview';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Memberships - Admin Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .tab-button {
            padding: 0.75rem 1.5rem;
            border: none;
            background: #e5e7eb;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s;
            margin-right: 0.5rem;
            border-radius: 0.375rem;
        }
        .tab-button.active {
            background: #06b6d4;
            color: white;
        }
        .tab-button:hover {
            background: #0891b2;
            color: white;
        }
        .stat-card {
            background: white;
            border-left: 4px solid;
            padding: 1.5rem;
            border-radius: 0.5rem;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        .stat-card.active {
            border-left-color: #10b981;
        }
        .stat-card.pending {
            border-left-color: #f59e0b;
        }
        .stat-card.expired {
            border-left-color: #ef4444;
        }
        .stat-card.cancelled {
            border-left-color: #6b7280;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #111827;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .table-container {
            background: white;
            border-radius: 0.5rem;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th {
            background: #f9fafb;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #374151;
            border-bottom: 2px solid #e5e7eb;
        }
        td {
            padding: 1rem;
            border-bottom: 1px solid #e5e7eb;
        }
        tr:hover {
            background: #f9fafb;
        }
        .status-badge {
            display: inline-block;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        .badge-active {
            background: #d1fae5;
            color: #065f46;
        }
        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }
        .badge-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-cancelled {
            background: #f3f4f6;
            color: #4b5563;
        }
        .btn-sm {
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            border: none;
            cursor: pointer;
            font-size: 0.875rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-primary {
            background: #06b6d4;
            color: white;
        }
        .btn-primary:hover {
            background: #0891b2;
        }
        .btn-danger {
            background: #ef4444;
            color: white;
        }
        .btn-danger:hover {
            background: #dc2626;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Manage Memberships & Subscriptions</h1>
        <p class="opacity-90">Track and manage customer membership subscriptions</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-7xl mx-auto">
            <!-- ALERTS -->
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($_GET['success']) ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <!-- STATISTICS -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <div class="stat-card active">
                    <div class="stat-label">Active Subscriptions</div>
                    <div class="stat-value"><?= $stats['total_active'] ?></div>
                </div>
                <div class="stat-card pending">
                    <div class="stat-label">Pending Payment</div>
                    <div class="stat-value"><?= $stats['total_pending'] ?></div>
                </div>
                <div class="stat-card expired">
                    <div class="stat-label">Expired</div>
                    <div class="stat-value"><?= $stats['total_expired'] ?></div>
                </div>
                <div class="stat-card cancelled">
                    <div class="stat-label">Cancelled</div>
                    <div class="stat-value"><?= $stats['total_cancelled'] ?></div>
                </div>
            </div>

            <!-- TABS -->
            <div class="mb-8 flex flex-wrap gap-2">
                <button class="tab-button <?= $tab === 'overview' ? 'active' : '' ?>" onclick="location.href='?tab=overview'">
                    <i class="fas fa-chart-bar mr-2"></i>Overview
                </button>
                <button class="tab-button <?= $tab === 'subscriptions' ? 'active' : '' ?>" onclick="location.href='?tab=subscriptions'">
                    <i class="fas fa-list mr-2"></i>All Subscriptions
                </button>
                <button class="tab-button <?= $tab === 'pending' ? 'active' : '' ?>" onclick="location.href='confirm_membership_payment.php'">
                    <i class="fas fa-hourglass-half mr-2"></i>Pending Payments
                </button>
                <button class="tab-button <?= $tab === 'analytics' ? 'active' : '' ?>" onclick="location.href='membership_analytics.php'">
                    <i class="fas fa-chart-line mr-2"></i>Analytics
                </button>
                <button class="tab-button <?= $tab === 'plans' ? 'active' : '' ?>" onclick="location.href='?tab=plans'">
                    <i class="fas fa-crown mr-2"></i>Membership Plans
                </button>
            </div>

            <!-- TAB CONTENT -->
            
            <!-- OVERVIEW TAB -->
            <?php if ($tab === 'overview'): ?>
                <div class="bg-white rounded-lg shadow-lg p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6">Subscription Overview</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Summary Info -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Summary</h3>
                            <ul class="space-y-3">
                                <li class="flex justify-between">
                                    <span class="text-gray-600">Total Customers with Memberships:</span>
                                    <span class="font-bold text-gray-800">
                                        <?= $conn->query("SELECT COUNT(DISTINCT user_id) AS total FROM user_memberships")->fetch_assoc()['total'] ?? 0 ?>
                                    </span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600">Total Membership Plans:</span>
                                    <span class="font-bold text-gray-800">
                                        <?= $conn->query("SELECT COUNT(*) AS total FROM memberships")->fetch_assoc()['total'] ?? 0 ?>
                                    </span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600">Total Subscription Records:</span>
                                    <span class="font-bold text-gray-800">
                                        <?= $conn->query("SELECT COUNT(*) AS total FROM user_memberships")->fetch_assoc()['total'] ?? 0 ?>
                                    </span>
                                </li>
                                <li class="flex justify-between">
                                    <span class="text-gray-600">Monthly Recurring Revenue:</span>
                                    <span class="font-bold text-indigo-600">
                                        Rs <?= number_format($conn->query("SELECT SUM(m.price) AS total FROM user_memberships um JOIN memberships m ON um.membership_id = m.id WHERE um.status = 'active'")->fetch_assoc()['total'] ?? 0, 2) ?>
                                    </span>
                                </li>
                            </ul>
                        </div>

                        <!-- Status Distribution -->
                        <div>
                            <h3 class="text-lg font-semibold text-gray-800 mb-4">Status Distribution</h3>
                            <ul class="space-y-3">
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 bg-green-500 rounded-full"></span>
                                        <span class="text-gray-600">Active</span>
                                    </span>
                                    <span class="font-bold text-gray-800"><?= $stats['total_active'] ?> (<?= $stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled'] > 0 ? round(($stats['total_active'] / ($stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled']) * 100)) : 0 ?>%)</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 bg-yellow-500 rounded-full"></span>
                                        <span class="text-gray-600">Pending</span>
                                    </span>
                                    <span class="font-bold text-gray-800"><?= $stats['total_pending'] ?> (<?= $stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled'] > 0 ? round(($stats['total_pending'] / ($stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled']) * 100)) : 0 ?>%)</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 bg-red-500 rounded-full"></span>
                                        <span class="text-gray-600">Expired</span>
                                    </span>
                                    <span class="font-bold text-gray-800"><?= $stats['total_expired'] ?> (<?= $stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled'] > 0 ? round(($stats['total_expired'] / ($stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled']) * 100)) : 0 ?>%)</span>
                                </li>
                                <li class="flex items-center justify-between">
                                    <span class="flex items-center gap-2">
                                        <span class="inline-block w-3 h-3 bg-gray-500 rounded-full"></span>
                                        <span class="text-gray-600">Cancelled</span>
                                    </span>
                                    <span class="font-bold text-gray-800"><?= $stats['total_cancelled'] ?> (<?= $stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled'] > 0 ? round(($stats['total_cancelled'] / ($stats['total_active'] + $stats['total_pending'] + $stats['total_expired'] + $stats['total_cancelled']) * 100)) : 0 ?>%)</span>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- ANALYTICS LINK -->
                    <div class="mt-8 pt-8 border-t">
                        <a href="membership_analytics.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 px-6 rounded-lg transition">
                            <i class="fas fa-chart-bar"></i>View Detailed Analytics & Trends
                        </a>
                    </div>
                </div>

            <!-- SUBSCRIPTIONS TAB -->
            <?php elseif ($tab === 'subscriptions'): ?>
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th>Membership Plan</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th>Status</th>
                                <th>Bottles/Week</th>
                                <th>Price (Rs)</th>
                                <th>Days Remaining</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $subscriptions = $conn->query("
                                SELECT 
                                    um.id,
                                    um.user_id,
                                    u.name AS customer_name,
                                    u.email,
                                    m.name AS membership_name,
                                    m.price,
                                    m.bottles_per_week,
                                    um.start_date,
                                    um.end_date,
                                    um.status,
                                    DATEDIFF(um.end_date, CURDATE()) AS days_remaining
                                FROM user_memberships um
                                JOIN users u ON um.user_id = u.id
                                JOIN memberships m ON um.membership_id = m.id
                                ORDER BY um.end_date DESC
                            ");
                            
                            if ($subscriptions && $subscriptions->num_rows > 0):
                                while ($row = $subscriptions->fetch_assoc()):
                            ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['customer_name']) ?></td>
                                    <td><?= htmlspecialchars($row['membership_name']) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['start_date'])) ?></td>
                                    <td><?= date('M d, Y', strtotime($row['end_date'])) ?></td>
                                    <td>
                                        <span class="status-badge badge-<?= htmlspecialchars($row['status']) ?>">
                                            <?= ucfirst(htmlspecialchars($row['status'])) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($row['bottles_per_week']) ?></td>
                                    <td><?= number_format($row['price'], 2) ?></td>
                                    <td>
                                        <?php 
                                        if ($row['days_remaining'] < 0) {
                                            echo '<span class="text-red-600 font-bold">Expired</span>';
                                        } elseif ($row['days_remaining'] == 0) {
                                            echo '<span class="text-yellow-600 font-bold">Today</span>';
                                        } else {
                                            echo '<span class="text-green-600 font-bold">' . $row['days_remaining'] . ' days</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="view_subscription.php?id=<?= $row['id'] ?>" class="btn-sm btn-primary">View</a>
                                    </td>
                                </tr>
                            <?php 
                                endwhile;
                            else:
                            ?>
                                <tr>
                                    <td colspan="9" class="text-center text-gray-500 py-8">No subscriptions found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            <!-- PLANS TAB -->
            <?php elseif ($tab === 'plans'): ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <?php 
                    while ($plan = $memberships_query->fetch_assoc()):
                    ?>
                        <div class="bg-white rounded-lg shadow-lg p-6 border-t-4 border-indigo-600">
                            <h3 class="text-xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($plan['name']) ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($plan['description']) ?></p>
                            
                            <div class="grid grid-cols-2 gap-4 py-4 border-t border-b border-gray-200 mb-4">
                                <div>
                                    <p class="text-gray-500 text-xs font-semibold uppercase">Price</p>
                                    <p class="text-2xl font-bold text-indigo-600">Rs <?= number_format($plan['price'], 2) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-semibold uppercase">Duration</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?= htmlspecialchars($plan['duration_days']) ?> days</p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-semibold uppercase">Bottles/Week</p>
                                    <p class="text-2xl font-bold text-indigo-600"><?= htmlspecialchars($plan['bottles_per_week']) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs font-semibold uppercase">Subscriptions</p>
                                    <p class="text-2xl font-bold text-green-600"><?= htmlspecialchars($plan['active_count']) ?></p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <a href="edit_membership.php?id=<?= $plan['id'] ?>" class="flex-1 btn-sm btn-primary text-center">
                                    <i class="fas fa-edit mr-1"></i>Edit
                                </a>
                                <button onclick="confirmDelete(<?= $plan['id'] ?>)" class="flex-1 btn-sm btn-danger">
                                    <i class="fas fa-trash mr-1"></i>Delete
                                </button>
                            </div>
                        </div>
                    <?php endwhile; ?>

                    <!-- Add New Plan -->
                    <div class="bg-gray-50 rounded-lg border-2 border-dashed border-gray-300 p-6 flex items-center justify-center">
                        <a href="add_membership.php" class="text-center">
                            <i class="fas fa-plus-circle text-4xl text-indigo-600 mb-2 block"></i>
                            <p class="text-gray-800 font-semibold">Add New Membership Plan</p>
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include('../includes/footer.php'); ?>

    <script>
        function confirmDelete(id) {
            if (confirm('Are you sure you want to delete this membership plan?')) {
                window.location.href = 'delete_membership.php?id=' + id;
            }
        }
    </script>
</body>
</html>

<?php
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$conn->close();
?>
