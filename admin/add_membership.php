<?php
/**
 * Add New Membership Plan
 * 
 * Admin page to create new membership/subscription plans.
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

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration_days = intval($_POST['duration_days'] ?? 30);
    $bottles_per_week = intval($_POST['bottles_per_week'] ?? 4);
    
    // Validation
    if (empty($name)) {
        $error = 'Membership name is required.';
    } elseif ($price <= 0) {
        $error = 'Price must be greater than 0.';
    } elseif ($duration_days <= 0) {
        $error = 'Duration must be greater than 0.';
    } elseif ($bottles_per_week <= 0) {
        $error = 'Bottles per week must be greater than 0.';
    } else {
        // Insert membership
        $stmt = $conn->prepare("
            INSERT INTO memberships (name, description, price, duration_days, bottles_per_week, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        if ($stmt->execute([$name, $description, $price, $duration_days, $bottles_per_week])) {
            $success = 'Membership plan created successfully!';
            // Redirect after 2 seconds
            echo '<script>setTimeout(function() { window.location.href = "memberships.php?tab=plans"; }, 2000);</script>';
        } else {
            $error = 'Error creating membership plan: ' . $stmt->error;
        }
        $stmt->close();
    }
}

include('../includes/header.php');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Membership Plan - Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- HEADER -->
    <section class="bg-cyan-700 text-white text-center py-10">
        <h1 class="text-3xl font-bold">Add New Membership Plan</h1>
        <p class="opacity-90">Create a new membership subscription plan</p>
    </section>

    <!-- MAIN CONTENT -->
    <section class="py-10 bg-gray-50 px-4 min-h-screen">
        <div class="max-w-2xl mx-auto">
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

            <!-- FORM -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <form method="POST" class="space-y-6">
                    <!-- Membership Name -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-crown mr-2 text-indigo-600"></i>Membership Name *
                        </label>
                        <input 
                            type="text" 
                            name="name" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600"
                            placeholder="e.g., Premium Monthly"
                            value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
                            required
                        >
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">
                            <i class="fas fa-align-left mr-2 text-indigo-600"></i>Description
                        </label>
                        <textarea 
                            name="description" 
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-indigo-600"
                            placeholder="Describe the membership plan benefits..."
                            rows="3"
                        ><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Grid for Price, Duration, Bottles -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Price -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-money-bill-wave mr-2 text-green-600"></i>Price (Rs) *
                            </label>
                            <input 
                                type="number" 
                                name="price" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-green-600"
                                placeholder="0.00"
                                step="0.01"
                                min="0"
                                value="<?= htmlspecialchars($_POST['price'] ?? '') ?>"
                                required
                            >
                        </div>

                        <!-- Duration -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-calendar-days mr-2 text-blue-600"></i>Duration (Days) *
                            </label>
                            <input 
                                type="number" 
                                name="duration_days" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-blue-600"
                                placeholder="30"
                                min="1"
                                value="<?= htmlspecialchars($_POST['duration_days'] ?? '30') ?>"
                                required
                            >
                        </div>

                        <!-- Bottles Per Week -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i class="fas fa-droplet mr-2 text-cyan-600"></i>Bottles/Week *
                            </label>
                            <input 
                                type="number" 
                                name="bottles_per_week" 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:border-cyan-600"
                                placeholder="4"
                                min="1"
                                value="<?= htmlspecialchars($_POST['bottles_per_week'] ?? '4') ?>"
                                required
                            >
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                        <h3 class="font-semibold text-gray-800 mb-2">Summary:</h3>
                        <p class="text-gray-600 text-sm">
                            This plan will provide customers with 
                            <span id="bottles-summary">4</span> bottles per week for 
                            <span id="days-summary">30</span> days at Rs <span id="price-summary">0</span>.
                        </p>
                        <p class="text-gray-600 text-sm mt-2">
                            Total bottles: <span class="font-bold" id="total-bottles">28</span>
                        </p>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-4">
                        <button 
                            type="submit" 
                            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-lg transition"
                        >
                            <i class="fas fa-plus mr-2"></i>Create Membership Plan
                        </button>
                        <a 
                            href="memberships.php?tab=plans" 
                            class="flex-1 bg-gray-400 hover:bg-gray-500 text-white font-bold py-3 rounded-lg transition text-center"
                        >
                            <i class="fas fa-times mr-2"></i>Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <?php include('../includes/footer.php'); ?>

    <script>
        // Update summary when inputs change
        document.querySelector('input[name="price"]').addEventListener('change', updateSummary);
        document.querySelector('input[name="duration_days"]').addEventListener('change', updateSummary);
        document.querySelector('input[name="bottles_per_week"]').addEventListener('change', updateSummary);

        function updateSummary() {
            const price = parseFloat(document.querySelector('input[name="price"]').value) || 0;
            const days = parseInt(document.querySelector('input[name="duration_days"]').value) || 30;
            const bottles = parseInt(document.querySelector('input[name="bottles_per_week"]').value) || 4;
            const weeks = Math.ceil(days / 7);
            const totalBottles = weeks * bottles;

            document.getElementById('price-summary').textContent = price.toFixed(2);
            document.getElementById('days-summary').textContent = days;
            document.getElementById('bottles-summary').textContent = bottles;
            document.getElementById('total-bottles').textContent = totalBottles;
        }

        // Initial update
        updateSummary();
    </script>
</body>
</html>

<?php $conn->close(); ?>
