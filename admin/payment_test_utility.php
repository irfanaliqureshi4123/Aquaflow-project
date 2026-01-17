<?php
/**
 * Payment System Testing Utility
 * 
 * Provides developers with tools to test payment functionality:
 * - Simulate payment scenarios
 * - Check database integrity
 * - Verify webhook functionality
 * - Test validation rules
 * - View payment logs
 * 
 * SECURITY: Only available in development environment
 * DO NOT expose in production!
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');
include('../includes/PaymentValidator.php');
include('../includes/PaymentLogger.php');

// Access Control: Only admin can access this page
require_admin();

// SECURITY: Only allow in development
$app_env = $_ENV['APP_ENV'] ?? 'production';
if ($app_env !== 'development') {
    http_response_code(403);
    die("Testing utility is only available in development environment.");
}

// Test action handler
$test_action = $_GET['action'] ?? 'home';
$test_result = null;
$test_error = null;

switch ($test_action) {
    case 'validate_amount':
        try {
            $amount = $_POST['amount'] ?? 500;
            $result = PaymentValidator::validateAmount($amount);
            $test_result = "✅ Amount validation passed: " . $result;
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'validate_email':
        try {
            $email = $_POST['email'] ?? 'test@example.com';
            $result = PaymentValidator::validateEmail($email);
            $test_result = "✅ Email validation passed: " . $result;
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'validate_stripe_id':
        try {
            $pm_id = $_POST['pm_id'] ?? 'pm_1234567890';
            $result = PaymentValidator::validateStripePaymentMethodId($pm_id);
            $test_result = "✅ Stripe PM ID validation passed: " . $result;
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'log_test_transaction':
        try {
            $user_id = intval($_POST['user_id'] ?? 1);
            $order_id = intval($_POST['order_id'] ?? 1);
            $amount = floatval($_POST['amount'] ?? 500);
            
            PaymentLogger::logTransaction(
                $user_id,
                $order_id,
                $amount,
                'test',
                'test',
                'test-transaction-' . time(),
                ['test' => true],
                $conn
            );
            
            $test_result = "✅ Test transaction logged successfully";
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'check_tables':
        try {
            $tables = ['payments', 'payment_logs', 'orders', 'cart'];
            $missing = [];
            
            foreach ($tables as $table) {
                $result = $conn->query("SHOW TABLES LIKE '$table'");
                if ($result->num_rows === 0) {
                    $missing[] = $table;
                }
            }
            
            if (empty($missing)) {
                $test_result = "✅ All required tables exist";
            } else {
                $test_error = "❌ Missing tables: " . implode(', ', $missing);
            }
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'check_columns':
        try {
            $required_columns = [
                'payments' => ['order_id', 'user_id', 'amount', 'status'],
                'payment_logs' => ['user_id', 'order_id', 'status', 'data'],
                'orders' => ['payment_status', 'transaction_id']
            ];
            
            $missing = [];
            foreach ($required_columns as $table => $columns) {
                foreach ($columns as $col) {
                    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$col'");
                    if ($result->num_rows === 0) {
                        $missing[] = "$table.$col";
                    }
                }
            }
            
            if (empty($missing)) {
                $test_result = "✅ All required columns exist";
            } else {
                $test_error = "❌ Missing columns: " . implode(', ', $missing);
            }
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
    
    case 'view_logs':
        try {
            $limit = intval($_POST['limit'] ?? 10);
            $result = $conn->query("SELECT * FROM payment_logs ORDER BY created_at DESC LIMIT $limit");
            $test_result = "✅ Retrieved " . $result->num_rows . " log entries";
        } catch (Exception $e) {
            $test_error = $e->getMessage();
        }
        break;
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Testing Utility - Development</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-900 text-gray-100">

<div class="min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
        
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold mb-2">🧪 Payment System Testing Utility</h1>
            <p class="text-gray-400">Development environment only - DO NOT expose in production</p>
            <div class="mt-2 p-3 bg-red-900 border border-red-700 rounded">
                <strong>⚠️ WARNING:</strong> This page should only be available in development. 
                Ensure APP_ENV is set to 'development' in .env
            </div>
        </div>

        <!-- Test Results -->
        <?php if ($test_result): ?>
        <div class="mb-6 p-4 bg-green-900 border border-green-700 rounded">
            <strong>✅ Success:</strong> <?php echo htmlspecialchars($test_result); ?>
        </div>
        <?php endif; ?>

        <?php if ($test_error): ?>
        <div class="mb-6 p-4 bg-red-900 border border-red-700 rounded">
            <strong>❌ Error:</strong> <?php echo htmlspecialchars($test_error); ?>
        </div>
        <?php endif; ?>

        <!-- Testing Sections -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            
            <!-- Validation Tests -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4">📋 Validation Tests</h2>
                
                <!-- Test Amount Validation -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="validate_amount">
                    <label class="block text-sm font-semibold mb-2">Test Amount Validation</label>
                    <div class="flex gap-2">
                        <input type="number" name="amount" placeholder="Amount" value="500" 
                               class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded">
                            Test
                        </button>
                    </div>
                </form>

                <!-- Test Email Validation -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="validate_email">
                    <label class="block text-sm font-semibold mb-2">Test Email Validation</label>
                    <div class="flex gap-2">
                        <input type="email" name="email" placeholder="Email" value="test@example.com" 
                               class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded">
                            Test
                        </button>
                    </div>
                </form>

                <!-- Test Stripe ID Validation -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="validate_stripe_id">
                    <label class="block text-sm font-semibold mb-2">Test Stripe PM ID Validation</label>
                    <div class="flex gap-2">
                        <input type="text" name="pm_id" placeholder="pm_..." value="pm_1234567890123456789012" 
                               class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded text-xs">
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 rounded">
                            Test
                        </button>
                    </div>
                </form>

                <!-- Test Invalid Email -->
                <form method="POST">
                    <input type="hidden" name="action" value="validate_email">
                    <label class="block text-sm font-semibold mb-2">Test Invalid Email (should fail)</label>
                    <div class="flex gap-2">
                        <input type="hidden" name="email" value="invalid-email">
                        <button type="submit" class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded w-full">
                            Test Invalid Email
                        </button>
                    </div>
                </form>
            </div>

            <!-- Database Checks -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4">🗄️ Database Checks</h2>
                
                <!-- Check Tables -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="check_tables">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 rounded">
                        Check Required Tables
                    </button>
                </form>

                <!-- Check Columns -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="check_columns">
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 hover:bg-green-700 rounded">
                        Check Required Columns
                    </button>
                </form>

                <!-- View Payment Logs -->
                <form method="POST">
                    <input type="hidden" name="action" value="view_logs">
                    <label class="block text-sm font-semibold mb-2">View Payment Logs</label>
                    <div class="flex gap-2">
                        <input type="number" name="limit" placeholder="Limit" value="10" min="1" max="100"
                               class="flex-1 px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <button type="submit" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded">
                            View
                        </button>
                    </div>
                </form>
            </div>

            <!-- Logging Tests -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4">📝 Logging Tests</h2>
                
                <!-- Log Test Transaction -->
                <form method="POST" class="mb-4">
                    <input type="hidden" name="action" value="log_test_transaction">
                    <label class="block text-sm font-semibold mb-2">Log Test Transaction</label>
                    <div class="space-y-2">
                        <input type="number" name="user_id" placeholder="User ID" value="1" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <input type="number" name="order_id" placeholder="Order ID" value="1" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <input type="number" name="amount" placeholder="Amount" value="500" 
                               class="w-full px-3 py-2 bg-gray-700 border border-gray-600 rounded">
                        <button type="submit" class="w-full px-4 py-2 bg-yellow-600 hover:bg-yellow-700 rounded">
                            Log Test Transaction
                        </button>
                    </div>
                </form>
            </div>

            <!-- Reference Info -->
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h2 class="text-2xl font-bold mb-4">📚 Reference Info</h2>
                
                <div class="space-y-3 text-sm">
                    <div>
                        <strong>Stripe Test Cards:</strong>
                        <ul class="ml-4 mt-1 text-gray-400">
                            <li>Success: 4242 4242 4242 4242</li>
                            <li>Decline: 4000 0000 0000 0002</li>
                            <li>3D Secure: 4000 0025 0000 3155</li>
                        </ul>
                    </div>
                    
                    <div>
                        <strong>Test Amounts:</strong>
                        <ul class="ml-4 mt-1 text-gray-400">
                            <li>Min: 1 (PKR)</li>
                            <li>Max: 10,000,000 (PKR)</li>
                        </ul>
                    </div>
                    
                    <div>
                        <strong>Valid Email Format:</strong>
                        <code class="bg-gray-900 px-2 py-1 rounded text-gray-300">user@example.com</code>
                    </div>
                    
                    <div>
                        <strong>Valid Stripe PM ID:</strong>
                        <code class="bg-gray-900 px-2 py-1 rounded text-gray-300">pm_[24+ alphanumeric]</code>
                    </div>
                </div>
            </div>

        </div>

        <!-- View Current Logs -->
        <?php if ($test_action === 'view_logs'): ?>
        <div class="mt-8 bg-gray-800 rounded-lg p-6 border border-gray-700">
            <h2 class="text-2xl font-bold mb-4">📋 Payment Logs</h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-700">
                        <tr>
                            <th class="px-4 py-2 text-left">ID</th>
                            <th class="px-4 py-2 text-left">User</th>
                            <th class="px-4 py-2 text-left">Order</th>
                            <th class="px-4 py-2 text-left">Status</th>
                            <th class="px-4 py-2 text-left">Created At</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $logs = $conn->query("SELECT * FROM payment_logs ORDER BY created_at DESC LIMIT 10");
                        while ($log = $logs->fetch_assoc()): 
                        ?>
                        <tr class="border-t border-gray-700 hover:bg-gray-700">
                            <td class="px-4 py-2"><?php echo $log['id']; ?></td>
                            <td class="px-4 py-2"><?php echo $log['user_id']; ?></td>
                            <td class="px-4 py-2"><?php echo $log['order_id']; ?></td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 rounded text-xs font-semibold 
                                    <?php echo $log['status'] === 'paid' ? 'bg-green-900 text-green-200' : 'bg-gray-700 text-gray-200'; ?>">
                                    <?php echo $log['status']; ?>
                                </span>
                            </td>
                            <td class="px-4 py-2 text-gray-400"><?php echo date('M d h:i A', strtotime($log['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="mt-8 text-center text-gray-500 text-sm">
            <p>Development environment testing utility</p>
            <p class="text-xs mt-2">🔐 This page should never be accessible in production</p>
        </div>

    </div>
</div>

</body>
</html>
