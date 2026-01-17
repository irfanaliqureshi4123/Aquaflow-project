<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch existing settings
$query = $conn->query("SELECT * FROM settings LIMIT 1");
$settings = $query->fetch_assoc();

// Handle form submission
if (isset($_POST['update_settings'])) {
  $company_name = mysqli_real_escape_string($conn, $_POST['company_name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);
  $phone = mysqli_real_escape_string($conn, $_POST['phone']);
  $address = mysqli_real_escape_string($conn, $_POST['address']);
  $delivery_rate = floatval($_POST['delivery_rate']);
  $about = mysqli_real_escape_string($conn, $_POST['about']);

  if ($settings) {
    // Update existing record
    $update = $conn->query("
      UPDATE settings 
      SET company_name='$company_name', email='$email', phone='$phone', address='$address', 
          delivery_rate='$delivery_rate', about='$about'
    ");
  } else {
    // Insert if not exists
    $update = $conn->query("
      INSERT INTO settings (company_name, email, phone, address, delivery_rate, about)
      VALUES ('$company_name','$email','$phone','$address','$delivery_rate','$about')
    ");
  }

  if ($update) {
    $success = "✅ Settings updated successfully!";
  } else {
    $error = "❌ Failed to update settings: " . $conn->error;
  }

  // Refresh settings data
  $query = $conn->query("SELECT * FROM settings LIMIT 1");
  $settings = $query->fetch_assoc();
}
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">⚙️ Website Settings</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>

  <?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= $error ?></div>
  <?php endif; ?>

  <form method="POST" class="bg-white rounded-xl p-6 shadow-md space-y-5 max-w-3xl">
    <!-- Company Info -->
    <div>
      <label class="block text-gray-600 mb-1 font-medium">Company Name</label>
      <input type="text" name="company_name" value="<?= $settings['company_name'] ?? '' ?>"
        class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
    </div>

    <!-- Contact Info -->
    <div class="grid md:grid-cols-2 gap-4">
      <div>
        <label class="block text-gray-600 mb-1 font-medium">Email</label>
        <input type="email" name="email" value="<?= $settings['email'] ?? '' ?>"
          class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
      </div>
      <div>
        <label class="block text-gray-600 mb-1 font-medium">Phone</label>
        <input type="text" name="phone" value="<?= $settings['phone'] ?? '' ?>"
          class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
      </div>
    </div>

    <!-- Address -->
    <div>
      <label class="block text-gray-600 mb-1 font-medium">Address</label>
      <textarea name="address" rows="2"
        class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"><?= $settings['address'] ?? '' ?></textarea>
    </div>

    <!-- Delivery Rate -->
    <div>
      <label class="block text-gray-600 mb-1 font-medium">Delivery Rate (Rs)</label>
      <input type="number" name="delivery_rate" step="0.01" value="<?= $settings['delivery_rate'] ?? '' ?>"
        class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
    </div>

    <!-- About Company -->
    <div>
      <label class="block text-gray-600 mb-1 font-medium">About Company</label>
      <textarea name="about" rows="3"
        class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500"><?= $settings['about'] ?? '' ?></textarea>
    </div>

    <div class="pt-4">
      <button type="submit" name="update_settings"
        class="bg-cyan-600 text-white px-6 py-2 rounded-lg hover:bg-cyan-700 transition">
        💾 Save Settings
      </button>
    </div>
  </form>
</div>

<?php include('includes/footer.php'); ?>
