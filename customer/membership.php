<?php
// Start session to get user information
session_start();

// Include necessary files and initialize database connection
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Access Control: Only customers can access this page
require_customer();

include '../includes/header.php';

// Get the logged-in user's ID
$user_id = $_SESSION['user_id'];

// Fetch the user's memberships from the database
$user_memberships_query = "
    SELECT 
        um.id,
        m.id as membership_id,
        m.name AS membership_name,
        m.price,
        m.bottles_per_week,
        m.duration_days,
        um.start_date,
        um.end_date,
        um.status,
        um.payment_method,
        CASE 
            WHEN um.status = 'pending' THEN 'pending'
            WHEN um.status = 'cancelled' THEN 'cancelled'
            WHEN um.end_date < CURDATE() THEN 'expired'
            WHEN um.end_date = CURDATE() THEN 'expiring-today'
            WHEN um.end_date < DATE_ADD(CURDATE(), INTERVAL 7 DAY) THEN 'expiring-soon'
            WHEN um.status = 'active' THEN 'active'
            ELSE 'expired'
        END as display_status
    FROM 
        user_memberships um
    INNER JOIN 
        memberships m ON um.membership_id = m.id
    WHERE 
        um.user_id = ?
    ORDER BY 
        CASE display_status
            WHEN 'active' THEN 0
            WHEN 'expiring-today' THEN 1
            WHEN 'expiring-soon' THEN 2
            WHEN 'expired' THEN 3
            WHEN 'pending' THEN 4
            WHEN 'cancelled' THEN 5
        END,
        um.end_date DESC
";
$stmt = $conn->prepare($user_memberships_query);
$stmt->bind_param('i', $user_id);
$stmt->execute();
$user_memberships = $stmt->get_result();

// Fetch all available membership plans
$available_query = "SELECT id, name, description, bottles_per_week, price, duration_days FROM memberships ORDER BY price ASC";
$available_result = $conn->query($available_query);

// Get user's current membership IDs to avoid duplicate subscriptions
$user_active_memberships = [];
$active_check_query = "
    SELECT DISTINCT membership_id 
    FROM user_memberships 
    WHERE user_id = ? AND status = 'active' AND end_date >= CURDATE()
";
$active_stmt = $conn->prepare($active_check_query);
$active_stmt->bind_param('i', $user_id);
$active_stmt->execute();
$active_result = $active_stmt->get_result();
while ($row = $active_result->fetch_assoc()) {
    $user_active_memberships[] = $row['membership_id'];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Memberships - AquaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .status-badge {
            padding: 0.375rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
        }
        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }
        .status-expiring-soon {
            background-color: #fef3c7;
            color: #92400e;
        }
        .status-expiring-today {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .status-expired {
            background-color: #fee2e2;
            color: #991b1b;
        }
        .status-pending {
            background-color: #dbeafe;
            color: #0c4a6e;
        }
        .status-cancelled {
            background-color: #f3f4f6;
            color: #374151;
        }
        .membership-card {
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background-color: #ffffff;
            transition: box-shadow 0.3s;
        }
        .membership-card:hover {
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .plan-card {
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            background-color: #ffffff;
            transition: all 0.3s;
        }
        .plan-card:hover {
            border-color: #06b6d4;
            box-shadow: 0 10px 25px -5px rgba(6, 182, 212, 0.2);
        }
        .plan-card.subscribed {
            border-color: #10b981;
            background-color: #f0fdf4;
        }
    </style>
</head>
<body>
    <!-- Page Container -->
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <!-- Page Header -->
        <div class="mb-8 flex items-center justify-between">
            <div>
                <h1 class="text-4xl font-bold text-cyan-700 mb-2">My Memberships</h1>
                <p class="text-gray-600">Manage your AquaFlow membership plans and subscriptions</p>
            </div>
            <a href="./membership_history.php" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition">
                <i class="fas fa-history mr-1"></i>View History
            </a>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6 flex items-center justify-between">
                <span><?php echo htmlspecialchars($_SESSION['success']); ?></span>
                <button onclick="this.parentElement.style.display='none';" class="text-green-700 font-bold">&times;</button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6 flex items-center justify-between">
                <span><?php echo htmlspecialchars($_SESSION['error']); ?></span>
                <button onclick="this.parentElement.style.display='none';" class="text-red-700 font-bold">&times;</button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Current Memberships Section -->
        <div class="mb-12">
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Your Current Memberships</h2>
            
            <?php if ($user_memberships->num_rows > 0): ?>
                <div class="grid gap-4">
                    <?php while ($membership = $user_memberships->fetch_assoc()): ?>
                        <div class="membership-card">
                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                                <div class="flex-1">
                                    <div class="flex items-center gap-3 mb-2">
                                        <h3 class="text-xl font-bold text-cyan-700"><?php echo htmlspecialchars($membership['membership_name']); ?></h3>
                                        <span class="status-badge status-<?php echo htmlspecialchars($membership['display_status']); ?>">
                                            <?php echo htmlspecialchars(str_replace('-', ' ', ucfirst($membership['display_status']))); ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($membership['display_status'] === 'pending'): ?>
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3 mb-4 text-sm text-blue-800">
                                            <i class="fas fa-info-circle mr-2"></i>
                                            <strong>Payment Pending:</strong> This subscription is awaiting payment confirmation. 
                                            <?php if ($membership['payment_method'] === 'cod' || strpos($membership['status'], 'pending') !== false): ?>
                                                Our staff will collect payment on first delivery.
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($membership['display_status'] === 'cancelled'): ?>
                                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4 text-sm text-red-800">
                                            <i class="fas fa-ban mr-2"></i>
                                            <strong>Subscription Cancelled:</strong> This membership has been cancelled. You can resubscribe anytime.
                                        </div>
                                    <?php endif; ?>
                                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm text-gray-600 mt-4">
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Bottles/Week</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo htmlspecialchars($membership['bottles_per_week']); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Price</p>
                                            <p class="text-lg font-semibold text-gray-800">Rs <?php echo number_format($membership['price'], 2); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">Start Date</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo date('M d, Y', strtotime($membership['start_date'])); ?></p>
                                        </div>
                                        <div>
                                            <p class="text-gray-500 text-xs uppercase font-semibold">End Date</p>
                                            <p class="text-lg font-semibold text-gray-800"><?php echo date('M d, Y', strtotime($membership['end_date'])); ?></p>
                                        </div>
                                    </div>

                                    <!-- ACTION BUTTONS -->
                                    <div class="mt-4 flex gap-2">
                                        <?php if ($membership['display_status'] === 'expired'): ?>
                                            <a href="./renew_membership.php?subscription_id=<?php echo $membership['id']; ?>" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                                                <i class="fas fa-redo mr-2"></i>Renew Membership
                                            </a>
                                        <?php elseif ($membership['display_status'] === 'expiring-soon'): ?>
                                            <a href="./renew_membership.php?subscription_id=<?php echo $membership['id']; ?>" class="flex-1 bg-yellow-600 hover:bg-yellow-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                                                <i class="fas fa-bell mr-2"></i>Renew Soon
                                            </a>
                                            <a href="./renew_membership.php?subscription_id=<?php echo $membership['id']; ?>" class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-2 px-4 rounded-lg transition text-center">
                                                <i class="fas fa-redo mr-2"></i>Renew Now
                                            </a>
                                        <?php elseif ($membership['display_status'] === 'active' || $membership['display_status'] === 'expiring-today'): ?>
                                            <button onclick="alert('You can only renew an expired or expiring membership.')" class="flex-1 bg-gray-400 text-white font-bold py-2 px-4 rounded-lg cursor-not-allowed">
                                                <i class="fas fa-lock mr-2"></i>Active - Cannot Renew Yet
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-6 text-center">
                    <p class="text-gray-700 mb-4">You don't have any active memberships yet.</p>
                    <p class="text-gray-600 text-sm">Browse our available plans below and subscribe to get started!</p>
                </div>
            <?php endif; ?>
        </div>

        <!-- Available Membership Plans Section -->
        <div>
            <h2 class="text-2xl font-bold text-gray-800 mb-6">Available Membership Plans</h2>
            
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
                <?php if ($available_result->num_rows > 0): ?>
                    <?php while ($plan = $available_result->fetch_assoc()): 
                        $is_subscribed = in_array($plan['id'], $user_active_memberships);
                    ?>
                        <div class="plan-card <?php echo $is_subscribed ? 'subscribed' : ''; ?>">
                            <h3 class="text-2xl font-bold text-cyan-700 mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-4"><?php echo htmlspecialchars($plan['description']); ?></p>
                            
                            <div class="mb-6 space-y-2 text-sm text-gray-700">
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Bottles per Week:</span>
                                    <span class="font-semibold"><?php echo htmlspecialchars($plan['bottles_per_week']); ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-gray-600">Duration:</span>
                                    <span class="font-semibold"><?php echo htmlspecialchars($plan['duration_days']); ?> days</span>
                                </div>
                            </div>

                            <div class="mb-6 border-t border-b border-gray-200 py-4">
                                <p class="text-4xl font-bold text-cyan-700">Rs <?php echo number_format($plan['price'], 2); ?></p>
                                <p class="text-gray-600 text-sm">per month</p>
                            </div>

                            <?php if ($is_subscribed): ?>
                                <button disabled class="w-full bg-green-500 text-white px-6 py-3 rounded-md font-semibold cursor-not-allowed opacity-75">
                                    ✓ Currently Subscribed
                                </button>
                            <?php else: ?>
                                <form method="POST" action="./subscribe_membership.php" class="mb-4">
                                    <input type="hidden" name="membership_id" value="<?php echo $plan['id']; ?>">
                                    <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition duration-200">
                                        Subscribe Now
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="text-gray-600">No membership plans available at this time.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include '../includes/footer.php'; ?>

</body>
</html>

<?php
// Close the database connection
$stmt->close();
$active_stmt->close();
if ($available_result) {
    $available_result->free();
}
$conn->close();
?>