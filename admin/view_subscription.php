<?php
/**
 * View Subscription Details
 * 
 * Admin page to view and manage individual customer subscriptions.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

$error = '';
$success = '';
$subscription = null;

// Get subscription ID
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    header('Location: memberships.php');
    exit;
}

// Fetch subscription details
$stmt = $conn->prepare("
    SELECT 
        um.id,
        um.user_id,
        um.membership_id,
        um.start_date,
        um.end_date,
        um.status,
        um.payment_method,
        um.created_at,
        u.id as customer_id,
        u.name as customer_name,
        u.email as customer_email,
        u.phone as customer_phone,
        u.address as customer_address,
        m.name as membership_name,
        m.description as membership_description,
        m.price as membership_price,
        m.duration_days,
        m.bottles_per_week
    FROM user_memberships um
    JOIN users u ON um.user_id = u.id
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.id = ?
");

$stmt->execute([$id]);
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: memberships.php');
    exit;
}

$subscription = $result->fetch_assoc();
$stmt->close();

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $new_status = '';

    if ($action === 'cancel') {
        $new_status = 'cancelled';
    } elseif ($action === 'reactivate') {
        $new_status = 'active';
    }

    if (!empty($new_status)) {
        $update_stmt = $conn->prepare("UPDATE user_memberships SET status = ? WHERE id = ?");
        
        if ($update_stmt->execute([$new_status, $id])) {
            $success = "Subscription status updated to " . ucfirst($new_status);
            $subscription['status'] = $new_status;
        } else {
            $error = "Error updating subscription: " . $update_stmt->error;
        }
        $update_stmt->close();
    }
}

// Calculate days remaining
$end_date = new DateTime($subscription['end_date']);
$today = new DateTime();
$interval = $today->diff($end_date);
$days_remaining = $interval->invert === 0 ? $interval->days : -$interval->days;

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Details - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        .info-card {
            background: #f9fafb;
            padding: 1.5rem;
            border-radius: 0.5rem;
            border-left: 4px solid #06b6d4;
        }
        .info-label {
            color: #6b7280;
            font-size: 0.875rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }
        .info-value {
            color: #111827;
            font-size: 1.125rem;
            font-weight: 600;
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
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Subscription Details</h1>
        <p class="opacity-90">View and manage customer subscription</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-4xl mx-auto">
            <!-- ALERTS -->
            <?php if ($error): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-exclamation-circle mr-2"></i><?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if ($success): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    <i class="fas fa-check-circle mr-2"></i><?= htmlspecialchars($success) ?>
                </div>
            <?php endif; ?>

            <!-- BACK BUTTON -->
            <a href="memberships.php?tab=subscriptions" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-semibold mb-6">
                <i class="fas fa-arrow-left"></i>Back to Subscriptions
            </a>

            <!-- SUBSCRIPTION STATUS -->
            <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
                <div class="flex items-center justify-between mb-6 pb-6 border-b">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-800">Subscription #<?= htmlspecialchars($subscription['id']) ?></h2>
                        <p class="text-gray-600">Created on <?= date('M d, Y', strtotime($subscription['created_at'])) ?></p>
                    </div>
                    <span class="status-badge badge-<?= htmlspecialchars($subscription['status']) ?>">
                        <?= ucfirst(htmlspecialchars($subscription['status'])) ?>
                    </span>
                </div>

                <!-- CUSTOMER INFO -->
                <h3 class="text-xl font-bold text-gray-800 mb-4">Customer Information</h3>
                <div class="info-grid mb-6">
                    <div class="info-card">
                        <div class="info-label">Customer Name</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['customer_name']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Email Address</div>
                        <div class="info-value">
                            <a href="mailto:<?= htmlspecialchars($subscription['customer_email']) ?>" class="text-indigo-600 hover:text-indigo-700">
                                <?= htmlspecialchars($subscription['customer_email']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Phone Number</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['customer_phone'] ?? 'N/A') ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?= htmlspecialchars(substr($subscription['customer_address'] ?? 'N/A', 0, 50)) ?></div>
                    </div>
                </div>

                <!-- MEMBERSHIP INFO -->
                <h3 class="text-xl font-bold text-gray-800 mb-4">Membership Information</h3>
                <div class="info-grid mb-6">
                    <div class="info-card">
                        <div class="info-label">Plan Name</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['membership_name']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Price</div>
                        <div class="info-value text-green-600">Rs <?= number_format($subscription['membership_price'], 2) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Bottles Per Week</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['bottles_per_week']) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Duration</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['duration_days']) ?> days</div>
                    </div>
                </div>

                <!-- SUBSCRIPTION DATES -->
                <h3 class="text-xl font-bold text-gray-800 mb-4">Subscription Timeline</h3>
                <div class="info-grid mb-6">
                    <div class="info-card">
                        <div class="info-label">Start Date</div>
                        <div class="info-value"><?= date('M d, Y', strtotime($subscription['start_date'])) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">End Date</div>
                        <div class="info-value"><?= date('M d, Y', strtotime($subscription['end_date'])) ?></div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Days Remaining</div>
                        <div class="info-value <?= $days_remaining < 0 ? 'text-red-600' : 'text-green-600' ?>">
                            <?= $days_remaining < 0 ? 'Expired (' . abs($days_remaining) . ' days ago)' : $days_remaining . ' days' ?>
                        </div>
                    </div>
                    <div class="info-card">
                        <div class="info-label">Payment Method</div>
                        <div class="info-value"><?= htmlspecialchars($subscription['payment_method'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <!-- ACTIONS -->
                <?php if ($subscription['status'] !== 'cancelled'): ?>
                    <h3 class="text-xl font-bold text-gray-800 mb-4">Actions</h3>
                    <div class="flex gap-4">
                        <?php if ($subscription['status'] === 'active' || $subscription['status'] === 'pending'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="cancel">
                                <button type="submit" onclick="return confirm('Are you sure you want to cancel this subscription?')" class="bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                    <i class="fas fa-ban mr-2"></i>Cancel Subscription
                                </button>
                            </form>
                        <?php endif; ?>
                        
                        <?php if ($subscription['status'] === 'cancelled' || $subscription['status'] === 'expired'): ?>
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="reactivate">
                                <button type="submit" class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded-lg transition">
                                    <i class="fas fa-redo mr-2"></i>Reactivate Subscription
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <?php include('../includes/footer.php'); ?>
</body>
</html>

<?php $conn->close(); ?>
