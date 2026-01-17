<?php
include '../includes/db_connect.php';

// Check if user is logged in and is staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

// Get all active memberships with subscriber counts
$memberships_query = "SELECT 
    m.id, m.name, m.description, m.price, m.duration_days, m.bottles_per_week,
    0 as subscriber_count
FROM memberships m
WHERE m.status = 'active'
ORDER BY m.price";
$memberships_result = $conn->query($memberships_query);

// Try to get subscriber counts if table exists
if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows > 0) {
    $memberships_query_with_subs = "SELECT 
        m.id, m.name, m.description, m.price, m.duration_days, m.bottles_per_week,
        COUNT(um.id) as subscriber_count
    FROM memberships m
    LEFT JOIN user_memberships um ON m.id = um.membership_id AND um.status = 'active'
    WHERE m.status = 'active'
    GROUP BY m.id, m.name, m.description, m.price, m.duration_days, m.bottles_per_week
    ORDER BY m.price";
    $memberships_result = $conn->query($memberships_query_with_subs);
}

// Calculate total subscribers and revenue
$total_subscribers = 0;
$total_revenue = 0;
if ($conn->query("SHOW TABLES LIKE 'user_memberships'")->num_rows > 0) {
    $total_subs_query = "SELECT COUNT(*) as total FROM user_memberships WHERE status = 'active'";
    $total_subs_result = $conn->query($total_subs_query);
    $total_subscribers = $total_subs_result->fetch_assoc()['total'];
    
    $revenue_query = "SELECT SUM(m.price) as total_revenue FROM user_memberships um 
                     JOIN memberships m ON um.membership_id = m.id 
                     WHERE um.status = 'active'";
    $revenue_result = $conn->query($revenue_query);
    $revenue_data = $revenue_result->fetch_assoc();
    $total_revenue = $revenue_data['total_revenue'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Plans - Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>

    <!-- Membership Stats Section -->
    <section class="bg-cyan-700 text-white py-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center mb-8">
                <h1 class="text-4xl font-bold mb-2">Membership Plans Overview</h1>
                <p class="text-lg opacity-90">Monitor subscription plans and subscriber engagement</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                <!-- Total Subscribers -->
                <div class="bg-cyan-600 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-100 text-sm font-medium">Total Active Subscribers</p>
                            <p class="text-4xl font-bold mt-2"><?php echo $total_subscribers; ?></p>
                        </div>
                        <div class="bg-cyan-500 p-4 rounded-full">
                            <i class="fas fa-users text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Active Plans -->
                <div class="bg-cyan-600 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-100 text-sm font-medium">Active Plans</p>
                            <p class="text-4xl font-bold mt-2"><?php echo $memberships_result->num_rows; ?></p>
                        </div>
                        <div class="bg-cyan-500 p-4 rounded-full">
                            <i class="fas fa-layer-group text-2xl"></i>
                        </div>
                    </div>
                </div>

                <!-- Total Revenue -->
                <div class="bg-cyan-600 rounded-lg p-6">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-cyan-100 text-sm font-medium">Total Active Revenue</p>
                            <p class="text-3xl font-bold mt-2">Rs <?php echo number_format($total_revenue, 0); ?></p>
                        </div>
                        <div class="bg-cyan-500 p-4 rounded-full">
                            <i class="fas fa-coins text-2xl"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Membership Plans Section -->
    <section class="py-16 bg-gray-50">
        <div class="container mx-auto px-4">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-800 mb-4">Available Membership Plans</h2>
                <p class="text-lg text-gray-600">View all active plans and their subscriber information</p>
            </div>

            <?php if ($memberships_result && $memberships_result->num_rows > 0): ?>
                <!-- Plans Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 md:gap-8 max-w-7xl mx-auto">
                    <?php while ($plan = $memberships_result->fetch_assoc()): ?>
                        <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden group">
                            <!-- Plan Header -->
                            <div class="bg-gradient-to-r from-cyan-600 to-cyan-700 p-6 text-white">
                                <h3 class="text-2xl font-bold mb-2"><?php echo htmlspecialchars($plan['name']); ?></h3>
                                <p class="text-cyan-100 text-sm"><?php echo htmlspecialchars($plan['description']); ?></p>
                            </div>

                            <!-- Plan Details -->
                            <div class="p-6">
                                <!-- Price -->
                                <div class="mb-6">
                                    <p class="text-4xl font-bold text-cyan-700 mb-2">
                                        Rs <?php echo number_format($plan['price'], 2); ?>
                                    </p>
                                    <p class="text-gray-600 text-sm">per month</p>
                                </div>

                                <!-- Plan Features -->
                                <div class="space-y-3 mb-6 pb-6 border-b border-gray-200">
                                    <div class="flex items-center text-gray-700">
                                        <span class="text-cyan-600 font-bold w-8">
                                            <i class="fas fa-water"></i>
                                        </span>
                                        <span class="ml-3">
                                            <strong><?php echo $plan['bottles_per_week']; ?></strong> Bottles per Week
                                        </span>
                                    </div>
                                    <div class="flex items-center text-gray-700">
                                        <span class="text-cyan-600 font-bold w-8">
                                            <i class="fas fa-calendar"></i>
                                        </span>
                                        <span class="ml-3">
                                            <strong><?php echo $plan['duration_days']; ?></strong> Days Duration
                                        </span>
                                    </div>
                                </div>

                                <!-- Subscriber Stats -->
                                <div class="bg-cyan-50 rounded-lg p-4 mb-4">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="text-gray-600 text-sm font-medium">Active Subscribers</p>
                                            <p class="text-3xl font-bold text-cyan-700 mt-1"><?php echo intval($plan['subscriber_count']); ?></p>
                                        </div>
                                        <div class="text-center">
                                            <i class="fas fa-user-check text-cyan-600 text-3xl"></i>
                                        </div>
                                    </div>
                                </div>

                                <!-- Subscription Progress -->
                                <div class="mb-4">
                                    <div class="flex justify-between items-center mb-2">
                                        <span class="text-xs font-semibold text-gray-700">Subscription Rate</span>
                                        <span class="text-xs text-gray-600"><?php echo intval($plan['subscriber_count']); ?> active</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="bg-gradient-to-r from-cyan-500 to-cyan-600 h-2 rounded-full transition-all duration-300" 
                                             style="width: <?php echo ($plan['subscriber_count'] > 0) ? min(($plan['subscriber_count'] / 20) * 100, 100) : 5; ?>%">
                                        </div>
                                    </div>
                                </div>

                                <!-- Benefits List -->
                                <div class="space-y-2 text-sm text-gray-700">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Home Delivery</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>24/7 Support</span>
                                    </div>
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                        </svg>
                                        <span>Flexible Plans</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else: ?>
                <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-8 text-center max-w-2xl mx-auto">
                    <i class="fas fa-info-circle text-yellow-600 text-4xl mb-4"></i>
                    <p class="text-yellow-800 text-lg">No active membership plans available.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Info Section -->
    <section class="py-16 bg-white">
        <div class="max-w-6xl mx-auto px-4">
            <h2 class="text-3xl font-bold text-center text-gray-800 mb-12">Why Our Membership Plans?</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-truck-fast text-cyan-600 text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Fast Delivery</h3>
                    <p class="text-gray-600">Quick and reliable delivery to your doorstep every week</p>
                </div>

                <div class="text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-leaf text-cyan-600 text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">Pure Quality</h3>
                    <p class="text-gray-600">100% pure mineral water with rigorous quality checks</p>
                </div>

                <div class="text-center">
                    <div class="mb-4 flex justify-center">
                        <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center">
                            <i class="fas fa-headset text-cyan-600 text-2xl"></i>
                        </div>
                    </div>
                    <h3 class="text-xl font-bold text-gray-800 mb-2">24/7 Support</h3>
                    <p class="text-gray-600">Always here to help with any questions or concerns</p>
                </div>
            </div>
        </div>
    </section>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
