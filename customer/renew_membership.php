<?php
/**
 * Renew Membership Subscription
 * 
 * Allows customers to renew expired subscriptions with same or new plan.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Only logged-in customers can renew
require_customer();

$user_id = $_SESSION['user_id'];
$old_subscription_id = isset($_GET['subscription_id']) ? (int)$_GET['subscription_id'] : 0;
$new_membership_id = isset($_POST['membership_id']) ? (int)$_POST['membership_id'] : 0;

// Fetch old subscription details
if ($old_subscription_id > 0) {
    $old_sub_stmt = $conn->prepare("
        SELECT um.id, um.membership_id, um.status, m.name, m.price, m.duration_days, m.bottles_per_week
        FROM user_memberships um
        JOIN memberships m ON um.membership_id = m.id
        WHERE um.id = ? AND um.user_id = ?
    ");
    $old_sub_stmt->execute([$old_subscription_id, $user_id]);
    $old_sub_result = $old_sub_stmt->get_result();
    
    if ($old_sub_result->num_rows === 0) {
        $_SESSION['error'] = 'Subscription not found.';
        header('Location: ./membership.php');
        exit;
    }
    
    $old_subscription = $old_sub_result->fetch_assoc();
    
    // Can only renew expired subscriptions
    if ($old_subscription['status'] !== 'expired') {
        $_SESSION['error'] = 'Only expired subscriptions can be renewed.';
        header('Location: ./membership.php');
        exit;
    }
}

// Handle renewal submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $new_membership_id > 0) {
    // Verify new membership exists
    $verify_stmt = $conn->prepare("
        SELECT id, name, price, bottles_per_week, duration_days 
        FROM memberships WHERE id = ?
    ");
    $verify_stmt->execute([$new_membership_id]);
    $verify_result = $verify_stmt->get_result();
    
    if ($verify_result->num_rows === 0) {
        $_SESSION['error'] = 'Selected membership plan not found.';
        header('Location: ./membership.php');
        exit;
    }
    
    $new_membership = $verify_result->fetch_assoc();
    
    // Create new pending subscription record
    $conn->begin_transaction();
    
    try {
        $start_date = date('Y-m-d');
        $end_date = date('Y-m-d', strtotime('+' . $new_membership['duration_days'] . ' days'));
        
        $insert_stmt = $conn->prepare("
            INSERT INTO user_memberships 
            (user_id, membership_id, start_date, end_date, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        $insert_stmt->execute([$user_id, $new_membership_id, $start_date, $end_date]);
        
        $new_subscription_id = $conn->insert_id;
        
        // Log the renewal
        $log_stmt = $conn->prepare("
            INSERT INTO activity_log (action, created_at) 
            VALUES (?, NOW())
        ");
        if ($log_stmt) {
            $log_action = "Membership renewed - Customer ID: $user_id, Old Subscription: $old_subscription_id, New Subscription: $new_subscription_id, Plan: {$new_membership['name']}";
            $log_stmt->execute([$log_action]);
        }
        
        $conn->commit();
        
        // Store renewal data in session
        $_SESSION['renewal_data'] = [
            'old_subscription_id' => $old_subscription_id,
            'new_subscription_id' => $new_subscription_id,
            'membership_id' => $new_membership_id,
            'membership_name' => $new_membership['name'],
            'price' => $new_membership['price'],
            'duration_days' => $new_membership['duration_days'],
            'bottles_per_week' => $new_membership['bottles_per_week']
        ];
        
        // Redirect to checkout
        header('Location: ./membership_checkout.php?renewal=1');
        exit;
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = 'Error renewing subscription: ' . $e->getMessage();
        header('Location: ./membership.php');
        exit;
    }
}

// If no POST data, show renewal options
include '../includes/header.php';

// Fetch all available membership plans
$plans_stmt = $conn->query("SELECT id, name, description, price, duration_days, bottles_per_week FROM memberships ORDER BY price ASC");
$available_plans = [];
while ($plan = $plans_stmt->fetch_assoc()) {
    $available_plans[] = $plan;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Renew Membership - AquaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .plan-option {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 1rem;
        }
        .plan-option:hover {
            border-color: #06b6d4;
            box-shadow: 0 4px 6px rgba(6, 182, 212, 0.1);
        }
        .plan-option.selected {
            border-color: #06b6d4;
            background: #ecf8fb;
        }
        .plan-option input[type="radio"] {
            cursor: pointer;
        }
        .old-plan-badge {
            display: inline-block;
            background: #fef3c7;
            color: #92400e;
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
    </style>
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Renew Your Membership</h1>
        <p class="opacity-90">Extend your subscription to continue enjoying water delivery</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-4xl mx-auto">
            <!-- BACK BUTTON -->
            <a href="./membership.php" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-700 font-semibold mb-6">
                <i class="fas fa-arrow-left"></i>Back to Memberships
            </a>

            <!-- OLD SUBSCRIPTION INFO -->
            <?php if ($old_subscription): ?>
                <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Your Expired Subscription</h2>
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <p class="text-gray-500 text-xs uppercase font-semibold">Plan Name</p>
                            <p class="text-lg font-semibold text-gray-800"><?= htmlspecialchars($old_subscription['name']) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs uppercase font-semibold">Price</p>
                            <p class="text-lg font-semibold text-green-600">Rs <?= number_format($old_subscription['price'], 2) ?></p>
                        </div>
                        <div>
                            <p class="text-gray-500 text-xs uppercase font-semibold">Bottles/Week</p>
                            <p class="text-lg font-semibold text-cyan-600"><?= htmlspecialchars($old_subscription['bottles_per_week']) ?></p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- SELECT RENEWAL PLAN -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-2">Select Your New Plan</h2>
                <p class="text-gray-600 mb-6">Choose the same plan to continue or select a new one</p>

                <form method="POST" class="space-y-6">
                    <?php foreach ($available_plans as $plan): ?>
                        <label class="plan-option" onclick="selectPlan(this)">
                            <div class="flex items-start gap-4">
                                <input type="radio" name="membership_id" value="<?= $plan['id'] ?>" class="mt-1" style="cursor: pointer;">
                                
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-2">
                                        <h3 class="text-xl font-bold text-cyan-700"><?= htmlspecialchars($plan['name']) ?></h3>
                                        <?php if ($old_subscription && $old_subscription['membership_id'] == $plan['id']): ?>
                                            <span class="old-plan-badge">Your Current Plan</span>
                                        <?php endif; ?>
                                    </div>
                                    <p class="text-gray-600 text-sm mb-3"><?= htmlspecialchars($plan['description']) ?></p>
                                    
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Price</p>
                                            <p class="text-lg font-bold text-green-600">Rs <?= number_format($plan['price'], 2) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Duration</p>
                                            <p class="text-lg font-bold text-gray-800"><?= $plan['duration_days'] ?> days</p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Bottles/Week</p>
                                            <p class="text-lg font-bold text-cyan-600"><?= $plan['bottles_per_week'] ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Total Bottles</p>
                                            <p class="text-lg font-bold text-indigo-600"><?= ceil($plan['duration_days'] / 7) * $plan['bottles_per_week'] ?></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </label>
                    <?php endforeach; ?>

                    <!-- BUTTONS -->
                    <div class="flex gap-4 pt-6 border-t">
                        <button 
                            type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition disabled:opacity-50 disabled:cursor-not-allowed"
                            id="renewBtn"
                            disabled
                        >
                            <i class="fas fa-redo mr-2"></i>Continue to Payment
                        </button>
                        <a 
                            href="./membership.php" 
                            class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-3 rounded-lg transition text-center"
                        >
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>

    <script>
        function selectPlan(element) {
            // Uncheck all radios
            document.querySelectorAll('input[name="membership_id"]').forEach(radio => {
                radio.parentElement.classList.remove('selected');
            });
            
            // Check selected radio and highlight
            const radio = element.querySelector('input[name="membership_id"]');
            if (radio) {
                radio.checked = true;
                element.classList.add('selected');
                document.getElementById('renewBtn').disabled = false;
            }
        }

        // Initial state
        document.querySelectorAll('input[name="membership_id"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.getElementById('renewBtn').disabled = !this.checked;
            });
        });
    </script>
</body>
</html>

<?php $conn->close(); ?>
