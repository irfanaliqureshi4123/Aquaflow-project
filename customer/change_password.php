<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

include('../includes/header.php');

$userId = $_SESSION['id'] ?? 0;
$message = "";

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Fetch existing password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    // Validation checks
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>⚠️ Please fill in all fields.</div>";
    } elseif (!password_verify($current_password, $hashed_password)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Current password is incorrect.</div>";
    } elseif ($new_password !== $confirm_password) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ New passwords do not match.</div>";
    } elseif (strlen($new_password) < 6) {
        $message = "<div class='bg-yellow-100 text-yellow-700 p-3 rounded-md mb-4'>⚠️ Password must be at least 6 characters long.</div>";
    } else {
        // Update new password
        $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
        $update->bind_param("si", $new_hashed, $userId);
        if ($update->execute()) {
            $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>✅ Password changed successfully!</div>";
        } else {
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Failed to update password.</div>";
        }
        $update->close();
    }
}
?>

<!-- HEADER -->
<section class="bg-teal-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Change Password</h1>
  <p class="opacity-90">Keep your account secure</p>
</section>

<!-- MAIN -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-xl mx-auto bg-white shadow-lg rounded-xl p-8">
    <?= $message ?>
    <form method="POST" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-semibold mb-2">Current Password</label>
        <input type="password" name="current_password"
               class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none" required>
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-2">New Password</label>
        <input type="password" name="new_password"
               class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none" required>
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-2">Confirm New Password</label>
        <input type="password" name="confirm_password"
               class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none" required>
      </div>

      <div class="text-center">
        <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded-md font-semibold hover:bg-teal-700 transition">
          Update Password
        </button>
      </div>
    </form>

    <div class="text-center mt-6">
      <a href="profile.php" class="text-teal-600 hover:underline">
        ← Back to Profile
      </a>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
