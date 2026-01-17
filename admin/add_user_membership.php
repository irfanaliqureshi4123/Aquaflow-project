<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

$message = "";

// Fetch all customers
$customers = $conn->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name ASC");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $plan_name = $_POST['plan_name'];
    $delivery_frequency = $_POST['delivery_frequency'];
    $bottles_per_delivery = $_POST['bottles_per_delivery'];
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];
    $status = $_POST['status'];

    if (empty($user_id) || empty($plan_name) || empty($start_date) || empty($end_date)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>⚠️ Please fill in all required fields.</div>";
    } else {
        $stmt = $conn->prepare("INSERT INTO memberships (user_id, plan_name, delivery_frequency, bottles_per_delivery, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ississs", $user_id, $plan_name, $delivery_frequency, $bottles_per_delivery, $start_date, $end_date, $status);

        if ($stmt->execute()) {
            $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>✅ Membership added successfully.</div>";
        } else {
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Error: " . $conn->error . "</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Add User Membership - AquaFlow Admin</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">

  <div class="bg-white shadow-lg rounded-xl p-8 w-full max-w-2xl">
    <h2 class="text-2xl font-bold text-cyan-700 mb-6 text-center">Add User Membership</h2>

    <?= $message ?>

    <form method="POST">
      <label class="block mb-2 font-semibold text-gray-700">Select Customer</label>
      <select name="user_id" required class="w-full border p-3 rounded-md mb-4">
        <option value="">-- Select Customer --</option>
        <?php while ($u = $customers->fetch_assoc()): ?>
          <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['name']) ?> (<?= htmlspecialchars($u['email']) ?>)</option>
        <?php endwhile; ?>
      </select>

      <label class="block mb-2 font-semibold text-gray-700">Plan Name</label>
      <input type="text" name="plan_name" required placeholder="e.g. Premium Plan" class="w-full border p-3 rounded-md mb-4">

      <label class="block mb-2 font-semibold text-gray-700">Delivery Frequency</label>
      <select name="delivery_frequency" class="w-full border p-3 rounded-md mb-4">
        <option value="Weekly">Weekly</option>
        <option value="Bi-weekly">Bi-weekly</option>
        <option value="Monthly">Monthly</option>
      </select>

      <label class="block mb-2 font-semibold text-gray-700">Bottles Per Delivery</label>
      <input type="number" name="bottles_per_delivery" min="1" placeholder="e.g. 2" class="w-full border p-3 rounded-md mb-4">

      <label class="block mb-2 font-semibold text-gray-700">Start Date</label>
      <input type="date" name="start_date" required class="w-full border p-3 rounded-md mb-4">

      <label class="block mb-2 font-semibold text-gray-700">End Date</label>
      <input type="date" name="end_date" required class="w-full border p-3 rounded-md mb-4">

      <label class="block mb-2 font-semibold text-gray-700">Status</label>
      <select name="status" class="w-full border p-3 rounded-md mb-6">
        <option value="active">Active</option>
        <option value="expired">Expired</option>
        <option value="pending">Pending</option>
      </select>

      <div class="flex justify-between">
        <a href="memberships.php" class="text-gray-600 hover:underline">← Back</a>
        <button type="submit" class="bg-cyan-700 text-white px-6 py-2 rounded-md hover:bg-cyan-800 transition">Add Membership</button>
      </div>
    </form>
  </div>

</body>
</html>
