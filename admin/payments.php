<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

include('includes/header.php');
include('includes/sidebar.php');

// Fetch all payments
$payments = $conn->query("SELECT * FROM payments ORDER BY created_at DESC");
?>

<div class="p-6 bg-gray-100 min-h-screen">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-700">Payments Management</h1>
        <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg shadow">
            + Add Payment
        </button>
    </div>

    <!-- Payments Table -->
    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full text-sm text-left text-gray-700">
            <thead class="bg-gray-200 text-gray-600 uppercase text-xs font-semibold">
                <tr>
                    <th class="py-3 px-4">#</th>
                    <th class="py-3 px-4">Order ID</th>
                    <th class="py-3 px-4">Customer</th>
                    <th class="py-3 px-4">Amount</th>
                    <th class="py-3 px-4">Method</th>
                    <th class="py-3 px-4">Status</th>
                    <th class="py-3 px-4">Date</th>
                    <th class="py-3 px-4 text-right">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($payments->num_rows > 0): ?>
                    <?php while ($row = $payments->fetch_assoc()): ?>
                        <tr class="border-b hover:bg-gray-50">
                            <td class="py-3 px-4"><?php echo $row['id']; ?></td>
                            <td class="py-3 px-4 font-semibold"><?php echo $row['order_id']; ?></td>
                            <td class="py-3 px-4"><?php echo $row['customer_name']; ?></td>
                            <td class="py-3 px-4 text-green-600 font-medium">Rs <?php echo number_format($row['amount'], 2); ?></td>
                            <td class="py-3 px-4"><?php echo $row['payment_method']; ?></td>
                            <td class="py-3 px-4">
                                <span class="px-2 py-1 rounded-full text-xs font-semibold
                                    <?php echo $row['status'] == 'Paid' ? 'bg-green-100 text-green-700' : ($row['status'] == 'Pending' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'); ?>">
                                    <?php echo $row['status']; ?>
                                </span>
                            </td>
                            <td class="py-3 px-4"><?php echo date("d M Y", strtotime($row['created_at'])); ?></td>
                            <td class="py-3 px-4 text-right">
                                <a href="update_payment.php?id=<?php echo $row['id']; ?>" class="text-blue-500 hover:underline">Edit</a> |
                                <a href="delete_payment.php?id=<?php echo $row['id']; ?>&token=<?= $_SESSION['csrf_token'] ?>" onclick="return confirm('Delete this payment?')" class="text-red-500 hover:underline">Delete</a>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="8" class="text-center py-4 text-gray-500">No payments found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add Payment Modal -->
<div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex justify-center items-center z-50">
    <div class="bg-white rounded-lg w-full max-w-md p-6 shadow-lg">
        <h2 class="text-xl font-semibold mb-4">Add Payment</h2>
        <form action="add_payment.php" method="POST">
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Order ID</label>
                <input type="text" name="order_id" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Customer Name</label>
                <input type="text" name="customer_name" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Amount</label>
                <input type="number" name="amount" step="0.01" required class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
            </div>
            <div class="mb-3">
                <label class="block text-gray-600 text-sm mb-1">Payment Method</label>
                <select name="payment_method" class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
                    <option>Cash</option>
                    <option>Credit Card</option>
                    <option>Bank Transfer</option>
                    <option>Online Payment</option>
                </select>
            </div>
            <div class="mb-4">
                <label class="block text-gray-600 text-sm mb-1">Status</label>
                <select name="status" class="w-full border-gray-300 rounded-lg p-2 focus:ring focus:ring-blue-200">
                    <option>Paid</option>
                    <option>Pending</option>
                    <option>Failed</option>
                </select>
            </div>
            <div class="flex justify-end gap-2">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">Cancel</button>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save</button>
            </div>
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
        </form>
    </div>
</div>

<script>
    function openModal() { document.getElementById('paymentModal').classList.remove('hidden'); }
    function closeModal() { document.getElementById('paymentModal').classList.add('hidden'); }
</script>

<?php include('includes/footer.php'); ?>