<?php
/**
 * Membership History
 * 
 * Customer page to view all past and current subscriptions with history.
 * Shows subscription timeline, payments, and renewals.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Only logged-in customers can access
require_customer();

$user_id = $_SESSION['user_id'];

// Get user info
$user_stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_result = $user_stmt->get_result();
$user = $user_result->fetch_assoc();

// Fetch all subscriptions (active, expired, cancelled)
$subscriptions_stmt = $conn->prepare("
    SELECT 
        um.id,
        um.membership_id,
        um.user_id,
        um.start_date,
        um.end_date,
        um.status,
        um.payment_method,
        um.created_at,
        um.updated_at,
        m.name as membership_name,
        m.price,
        m.bottles_per_week,
        m.duration_days,
        CASE 
            WHEN um.end_date < CURDATE() THEN 'expired'
            WHEN um.end_date = CURDATE() THEN 'expiring-today'
            WHEN um.end_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'expiring-soon'
            WHEN um.status = 'active' THEN 'active'
            WHEN um.status = 'pending' THEN 'pending'
            WHEN um.status = 'cancelled' THEN 'cancelled'
            ELSE 'expired'
        END as display_status,
        DATEDIFF(um.end_date, CURDATE()) as days_remaining,
        DATEDIFF(um.end_date, um.start_date) as duration_actual
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.user_id = ?
    ORDER BY um.created_at DESC
");

$subscriptions_stmt->execute([$user_id]);
$subscriptions_result = $subscriptions_stmt->get_result();
$subscriptions = [];

while ($row = $subscriptions_result->fetch_assoc()) {
    $subscriptions[] = $row;
}

// Calculate statistics
$stats = [
    'total_subscriptions' => count($subscriptions),
    'active' => 0,
    'expired' => 0,
    'pending' => 0,
    'cancelled' => 0,
    'total_spent' => 0,
    'total_bottles' => 0
];

foreach ($subscriptions as $sub) {
    $status = $sub['status'] ?? 'unknown';
    
    if (isset($stats[$status])) {
        $stats[$status]++;
    }
    
    if ($sub['status'] === 'active' || $sub['display_status'] === 'active') {
        $stats['total_spent'] += $sub['price'] ?? 0;
    } elseif ($sub['status'] !== 'pending') {
        $stats['total_spent'] += $sub['price'] ?? 0;
    }
    
    $duration = $sub['duration_actual'] ?? $sub['duration_days'] ?? 30;
    $bottles = $sub['bottles_per_week'] ?? 4;
    $stats['total_bottles'] += ceil($duration / 7) * $bottles;
}

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership History - AquaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stat-box {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: bold;
            color: #06b6d4;
        }
        .stat-label {
            color: #6b7280;
            font-size: 0.875rem;
            text-transform: uppercase;
        }
        .timeline-item {
            padding: 1.5rem;
            border-left: 4px solid #e5e7eb;
            margin-bottom: 1rem;
            background: white;
            border-radius: 0.375rem;
        }
        .timeline-item.active {
            border-left-color: #10b981;
            background: #f0fdf4;
        }
        .timeline-item.expired {
            border-left-color: #ef4444;
            background: #fef2f2;
        }
        .timeline-item.pending {
            border-left-color: #3b82f6;
            background: #eff6ff;
        }
        .timeline-item.cancelled {
            border-left-color: #9ca3af;
            background: #f9fafb;
        }
        .timeline-item .date {
            color: #6b7280;
            font-size: 0.875rem;
        }
        .timeline-item .title {
            font-weight: 600;
            font-size: 1.125rem;
            margin: 0.5rem 0;
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
        .badge-expired {
            background: #fee2e2;
            color: #991b1b;
        }
        .badge-pending {
            background: #dbeafe;
            color: #0c4a6e;
        }
        .badge-cancelled {
            background: #f3f4f6;
            color: #374151;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Membership History</h1>
        <p class="opacity-90">View all your subscriptions and purchase history</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-5xl mx-auto">
            <!-- ALERTS -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6 flex items-center justify-between">
                    <span><i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($_SESSION['success']) ?></span>
                    <button onclick="this.parentElement.style.display='none';" class="text-green-700 font-bold">&times;</button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6 flex items-center justify-between">
                    <span><i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($_SESSION['error']) ?></span>
                    <button onclick="this.parentElement.style.display='none';" class="text-red-700 font-bold">&times;</button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- BACK BUTTON -->
            <a href="./membership.php" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-semibold mb-6">
                <i class="fas fa-arrow-left"></i>Back to Memberships
            </a>

            <!-- STATISTICS -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
                <div class="stat-box">
                    <div class="stat-value"><?= $stats['total_subscriptions'] ?></div>
                    <div class="stat-label">Total Subscriptions</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #10b981;"><?= $stats['active'] ?></div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #ef4444;"><?= $stats['expired'] ?></div>
                    <div class="stat-label">Expired</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #f59e0b;"><?= $stats['total_spent'] ?></div>
                    <div class="stat-label">Total Spent (Rs)</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value" style="color: #3b82f6;"><?= $stats['total_bottles'] ?></div>
                    <div class="stat-label">Total Bottles</div>
                </div>
            </div>

            <!-- TIMELINE -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">Subscription Timeline</h2>

                <?php if (count($subscriptions) > 0): ?>
                    <div>
                        <?php foreach ($subscriptions as $sub): ?>
                            <div class="timeline-item <?= htmlspecialchars($sub['display_status']) ?>">
                                <div class="flex items-start justify-between gap-4 mb-2">
                                    <div>
                                        <div class="date">
                                            <i class="fas fa-calendar mr-1"></i>
                                            <?= date('M d, Y', strtotime($sub['start_date'])) ?> 
                                            to 
                                            <?= date('M d, Y', strtotime($sub['end_date'])) ?>
                                        </div>
                                        <div class="title"><?= htmlspecialchars($sub['membership_name']) ?></div>
                                    </div>
                                    <span class="status-badge badge-<?= htmlspecialchars($sub['display_status']) ?>">
                                        <?= ucfirst(str_replace('-', ' ', htmlspecialchars($sub['display_status']))) ?>
                                    </span>
                                </div>

                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm mt-3 pt-3 border-t border-gray-200">
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Price</p>
                                        <p class="font-semibold text-gray-800">Rs <?= number_format($sub['price'], 2) ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Duration</p>
                                        <p class="font-semibold text-gray-800"><?= $sub['duration_days'] ?> days</p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Bottles/Week</p>
                                        <p class="font-semibold text-gray-800"><?= $sub['bottles_per_week'] ?></p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Total Bottles</p>
                                        <p class="font-semibold text-gray-800"><?= ceil($sub['duration_actual'] / 7) * $sub['bottles_per_week'] ?></p>
                                    </div>
                                </div>

                                <div class="mt-3 pt-3 border-t border-gray-200 flex items-center gap-4 text-sm flex-wrap">
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Payment Method</p>
                                        <p class="text-gray-800">
                                            <i class="fas <?= $sub['payment_method'] === 'card' ? 'fa-credit-card' : 'fa-money-bill' ?> mr-1"></i>
                                            <?= $sub['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Credit Card' ?>
                                        </p>
                                    </div>
                                    <div>
                                        <p class="text-gray-500 text-xs uppercase">Subscribed</p>
                                        <p class="text-gray-800"><?= date('M d, Y', strtotime($sub['created_at'])) ?></p>
                                    </div>
                                    <div class="ml-auto flex gap-2">
                                        <?php if ($sub['display_status'] === 'expired'): ?>
                                            <a href="./renew_membership.php?subscription_id=<?= $sub['id'] ?>" class="text-indigo-600 hover:text-indigo-700 font-semibold whitespace-nowrap">
                                                <i class="fas fa-redo mr-1"></i>Renew
                                            </a>
                                        <?php endif; ?>
                                        
                                        <?php if ($sub['display_status'] === 'expired' || $sub['display_status'] === 'cancelled'): ?>
                                            <a href="./delete_subscription_history.php?id=<?= $sub['id'] ?>" onclick="return confirm('Are you sure you want to delete this subscription record from your history? This action cannot be undone.');" class="text-red-600 hover:text-red-700 font-semibold whitespace-nowrap border-l pl-2">
                                                <i class="fas fa-trash mr-1"></i>Delete
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-12">
                        <i class="fas fa-inbox text-4xl text-gray-300 mb-4 block"></i>
                        <p class="text-gray-500 text-lg">No subscription history yet.</p>
                        <p class="text-gray-400 mb-6">Start by subscribing to one of our membership plans!</p>
                        <a href="./membership.php" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-6 rounded-lg transition">
                            Browse Plans
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- INVOICE SECTION -->
            <div class="bg-white rounded-lg shadow-lg p-8 mt-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">
                    <i class="fas fa-receipt mr-2 text-indigo-600"></i>Payment Summary
                </h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-500 text-sm uppercase">Total Amount Paid</p>
                        <p class="text-3xl font-bold text-green-600">Rs <?= number_format($stats['total_spent'], 2) ?></p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-500 text-sm uppercase">Total Bottles Received</p>
                        <p class="text-3xl font-bold text-cyan-600"><?= $stats['total_bottles'] ?></p>
                    </div>
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-gray-500 text-sm uppercase">Average Cost Per Bottle</p>
                        <p class="text-3xl font-bold text-indigo-600">
                            Rs <?= $stats['total_bottles'] > 0 ? number_format($stats['total_spent'] / $stats['total_bottles'], 2) : 0 ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php $conn->close(); ?>
