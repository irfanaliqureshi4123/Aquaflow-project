<?php
/**
 * Confirm Membership Payment (COD)
 * 
 * Admin/Staff page to confirm Cash on Delivery payments for memberships
 * and activate subscriptions after payment is received.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin/staff can access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header('Location: ../login.php');
    exit;
}

if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'staff') {
    $_SESSION['error'] = 'You do not have permission to access this page.';
    header('Location: ../index.php');
    exit;
}

$error = '';
$success = '';

// Handle payment confirmation via AJAX or POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subscription_id = isset($_POST['subscription_id']) ? (int)$_POST['subscription_id'] : 0;
    $confirm_action = isset($_POST['confirm_action']) ? sanitize_input($_POST['confirm_action']) : '';

    if (!$subscription_id || !$confirm_action) {
        $error = 'Invalid request.';
    } else {
        // Verify subscription exists and is pending
        $check_stmt = $conn->prepare("
            SELECT um.id, um.user_id, um.membership_id, um.status, u.email, u.name, m.name as membership_name
            FROM user_memberships um
            JOIN users u ON um.user_id = u.id
            JOIN memberships m ON um.membership_id = m.id
            WHERE um.id = ?
        ");
        $check_stmt->execute([$subscription_id]);
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows === 0) {
            $error = 'Subscription not found.';
        } else {
            $subscription = $check_result->fetch_assoc();

            if ($confirm_action === 'confirm' && $subscription['status'] === 'pending') {
                // Confirm payment and activate subscription
                $update_stmt = $conn->prepare("
                    UPDATE user_memberships 
                    SET status = 'active', updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($update_stmt->execute([$subscription_id])) {
                    $success = "Payment confirmed! Subscription activated for {$subscription['name']} - {$subscription['membership_name']}";
                    
                    // Log the confirmation
                    $log_stmt = $conn->prepare("
                        INSERT INTO activity_log (action, created_at) 
                        VALUES (?, NOW())
                    ");
                    if ($log_stmt) {
                        $log_action = "Membership payment confirmed for user {$subscription['name']} - Subscription ID: {$subscription_id}";
                        $log_stmt->execute([$log_action]);
                    }
                } else {
                    $error = 'Error confirming payment: ' . $update_stmt->error;
                }
                $update_stmt->close();
            } elseif ($confirm_action === 'reject' && $subscription['status'] === 'pending') {
                // Reject payment and cancel subscription
                $update_stmt = $conn->prepare("
                    UPDATE user_memberships 
                    SET status = 'cancelled', updated_at = NOW()
                    WHERE id = ?
                ");
                
                if ($update_stmt->execute([$subscription_id])) {
                    $success = "Subscription cancelled for {$subscription['name']}";
                    
                    // Log the rejection
                    $log_stmt = $conn->prepare("
                        INSERT INTO activity_log (action, created_at) 
                        VALUES (?, NOW())
                    ");
                    if ($log_stmt) {
                        $log_action = "Membership payment rejected for user {$subscription['name']} - Subscription ID: {$subscription_id}";
                        $log_stmt->execute([$log_action]);
                    }
                } else {
                    $error = 'Error rejecting subscription: ' . $update_stmt->error;
                }
                $update_stmt->close();
            } else {
                $error = 'Invalid action or subscription status.';
            }
        }
        $check_stmt->close();
    }

    // Return JSON for AJAX requests
    if (isset($_POST['ajax']) && $_POST['ajax'] === '1') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => empty($error),
            'message' => $error ?: $success
        ]);
        exit;
    }
}

// Fetch pending subscriptions
$pending_query = $conn->query("
    SELECT 
        um.id,
        um.user_id,
        u.name as customer_name,
        u.email,
        u.phone,
        m.name as membership_name,
        m.price,
        m.bottles_per_week,
        um.start_date,
        um.status,
        um.payment_method,
        um.created_at
    FROM user_memberships um
    JOIN users u ON um.user_id = u.id
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.status = 'pending'
    ORDER BY um.created_at DESC
");

$pending_subscriptions = [];
if ($pending_query) {
    while ($row = $pending_query->fetch_assoc()) {
        $pending_subscriptions[] = $row;
    }
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirm Membership Payments - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .pending-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .pending-card:hover {
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            border-color: #06b6d4;
        }
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        .btn-confirm {
            background: #10b981;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-confirm:hover {
            background: #059669;
        }
        .btn-reject {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-reject:hover {
            background: #dc2626;
        }
        .payment-badge-cod {
            background: #fef3c7;
            color: #92400e;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }
        .payment-badge-card {
            background: #d1fae5;
            color: #065f46;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Confirm Membership Payments</h1>
        <p class="opacity-90">Verify and confirm pending membership subscription payments</p>
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

            <!-- PENDING PAYMENTS COUNT -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-gray-600 text-sm uppercase font-semibold">Pending Payments Awaiting Confirmation</h3>
                        <p class="text-3xl font-bold text-gray-800 mt-2"><?= count($pending_subscriptions) ?></p>
                    </div>
                    <i class="fas fa-hourglass-half text-4xl text-yellow-500 opacity-25"></i>
                </div>
            </div>

            <!-- PENDING SUBSCRIPTIONS LIST -->
            <?php if (count($pending_subscriptions) > 0): ?>
                <div class="bg-white rounded-lg shadow-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-6">Pending Member Subscriptions</h2>
                    
                    <?php foreach ($pending_subscriptions as $sub): ?>
                        <div class="pending-card">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">Customer Name</p>
                                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($sub['customer_name']) ?></p>
                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($sub['email']) ?></p>
                                    <p class="text-gray-600 text-sm"><?= htmlspecialchars($sub['phone'] ?? 'N/A') ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">Membership Plan</p>
                                    <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($sub['membership_name']) ?></p>
                                    <p class="text-gray-600 text-sm">
                                        <i class="fas fa-droplet text-cyan-600 mr-1"></i><?= htmlspecialchars($sub['bottles_per_week']) ?> bottles/week
                                    </p>
                                    <p class="text-lg font-bold text-green-600 mt-2">Rs <?= number_format($sub['price'], 2) ?></p>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 py-4 border-t border-b border-gray-200">
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">Start Date</p>
                                    <p class="text-gray-800 font-semibold"><?= date('M d, Y', strtotime($sub['start_date'])) ?></p>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">Payment Method</p>
                                    <span class="payment-badge-<?= strtolower(str_replace('-', '', $sub['payment_method'])) ?>">
                                        <?= $sub['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Card Payment' ?>
                                    </span>
                                </div>
                                <div>
                                    <p class="text-gray-500 text-xs uppercase font-semibold">Requested On</p>
                                    <p class="text-gray-800 font-semibold"><?= date('M d, Y H:i', strtotime($sub['created_at'])) ?></p>
                                </div>
                            </div>

                            <div class="flex gap-2">
                                <button 
                                    onclick="confirmPayment(<?= $sub['id'] ?>)" 
                                    class="btn-confirm flex-1"
                                >
                                    <i class="fas fa-check mr-2"></i>Confirm Payment & Activate
                                </button>
                                <button 
                                    onclick="rejectPayment(<?= $sub['id'] ?>)" 
                                    class="btn-reject flex-1"
                                >
                                    <i class="fas fa-times mr-2"></i>Reject & Cancel
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="bg-green-50 border border-green-200 rounded-lg p-8 text-center">
                    <i class="fas fa-check-circle text-4xl text-green-600 mb-4 block"></i>
                    <h3 class="text-xl font-bold text-green-800 mb-2">All Payments Confirmed!</h3>
                    <p class="text-green-700">No pending membership payments at this time.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <?php include('../includes/footer.php'); ?>

    <script>
        function confirmPayment(subscriptionId) {
            if (confirm('Confirm payment and activate this subscription?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="subscription_id" value="${subscriptionId}">
                    <input type="hidden" name="confirm_action" value="confirm">
                    <input type="hidden" name="ajax" value="1">
                `;
                
                fetch('confirm_membership_payment.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Request failed: ' + error);
                });
            }
        }

        function rejectPayment(subscriptionId) {
            if (confirm('Reject payment and cancel this subscription?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="subscription_id" value="${subscriptionId}">
                    <input type="hidden" name="confirm_action" value="reject">
                    <input type="hidden" name="ajax" value="1">
                `;
                
                fetch('confirm_membership_payment.php', {
                    method: 'POST',
                    body: new FormData(form)
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Request failed: ' + error);
                });
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
