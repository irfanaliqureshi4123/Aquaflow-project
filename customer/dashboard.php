<?php
session_start();

// Include the database connection
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Fetch customer info
$username = $_SESSION['name'] ?? 'Customer';
$email = $_SESSION['email'] ?? '';
$user_id = $_SESSION['user_id'] ?? 0;

// Check if the connection is established
if (!$conn) {
    die("Database connection not established.");
}

// Fetch customer orders by user_id
$stmt = $conn->prepare("SELECT * FROM orders WHERE user_id = ? ORDER BY order_date DESC LIMIT 5");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders = $stmt->get_result();

include('../includes/header.php');
?>

<!-- HEADER -->
<section class="bg-teal-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Welcome, <?= htmlspecialchars($username) ?>!</h1>
  <p class="opacity-90">Your AquaFlow Customer Dashboard</p>
</section>

<!-- MAIN -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-5xl mx-auto">
    <!-- Quick Links -->
    <div class="grid sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
      <a href="orders.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-shopping-bag text-teal-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">My Orders</p>
      </a>

      <a href="profile.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-user text-blue-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">My Profile</p>
      </a>

      <a href="../logout.php" class="bg-white shadow-lg hover:shadow-xl rounded-xl p-6 text-center transition">
        <i class="fas fa-sign-out-alt text-red-600 text-4xl mb-3"></i>
        <p class="font-semibold text-gray-800">Logout</p>
      </a>
    </div>

    <!-- Recent Orders -->
    <div class="bg-white shadow-lg rounded-xl p-6">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">Recent Orders</h2>
      <table class="min-w-full border border-gray-200">
        <thead class="bg-gray-100 text-gray-700">
          <tr>
            <th class="py-2 px-4 border-b">Order ID</th>
            <th class="py-2 px-4 border-b">Total</th>
            <th class="py-2 px-4 border-b">Status</th>
            <th class="py-2 px-4 border-b">Date</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($orders->num_rows > 0): ?>
            <?php while ($row = $orders->fetch_assoc()): ?>
              <tr class="text-center">
                <td class="py-2 px-4 border-b">#<?= $row['id'] ?></td>
                <td class="py-2 px-4 border-b">Rs <?= number_format($row['total_amount'], 2) ?></td>
                <td class="py-2 px-4 border-b">
                  <?php if ($row['status'] === 'Completed' || $row['status'] === 'Paid'): ?>
                    <span class="px-3 py-1 text-sm rounded-full bg-green-100 text-green-700">Paid</span>
                  <?php else: ?>
                    <span class="px-3 py-1 text-sm rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                  <?php endif; ?>
                </td>
                <td class="py-2 px-4 border-b"><?= date('d M, Y', strtotime($row['order_date'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="4" class="text-center py-4 text-gray-500">No orders found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
