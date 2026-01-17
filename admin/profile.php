<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch admin info
$query = $conn->query("SELECT * FROM admins WHERE id = $admin_id");
$admin = $query->fetch_assoc();

// Handle profile update
if (isset($_POST['update_profile'])) {
  $name = mysqli_real_escape_string($conn, $_POST['name']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);

  // Image upload
  if (!empty($_FILES['profile_image']['name'])) {
    $target_dir = "uploads/";
    if (!is_dir($target_dir)) mkdir($target_dir);
    $file_name = time() . "_" . basename($_FILES["profile_image"]["name"]);
    $target_file = $target_dir . $file_name;
    move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file);

    // Delete old image if exists
    if (!empty($admin['profile_image']) && file_exists($admin['profile_image'])) {
      unlink($admin['profile_image']);
    }

    $conn->query("UPDATE admins SET name='$name', email='$email', profile_image='$target_file' WHERE id=$admin_id");
  } else {
    $conn->query("UPDATE admins SET name='$name', email='$email' WHERE id=$admin_id");
  }

  $success = "✅ Profile updated successfully!";
  $admin = $conn->query("SELECT * FROM admins WHERE id = $admin_id")->fetch_assoc();
}

// Handle password change
if (isset($_POST['change_password'])) {
  $current = $_POST['current_password'];
  $new = $_POST['new_password'];
  $confirm = $_POST['confirm_password'];

  if (password_verify($current, $admin['password'])) {
    if ($new === $confirm) {
      $hash = password_hash($new, PASSWORD_BCRYPT);
      $conn->query("UPDATE admins SET password='$hash' WHERE id=$admin_id");
      $success = "🔒 Password changed successfully!";
    } else {
      $error = "❌ New passwords do not match.";
    }
  } else {
    $error = "❌ Current password is incorrect.";
  }
}
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">👤 Admin Profile</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>
  <?php if (!empty($error)): ?>
    <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= $error ?></div>
  <?php endif; ?>

  <div class="grid md:grid-cols-2 gap-6">
    <!-- Profile Info -->
    <div class="bg-white rounded-xl shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">🪪 Profile Information</h2>
      <form method="POST" enctype="multipart/form-data" class="space-y-4">
        <div class="flex items-center gap-4">
          <img src="<?= !empty($admin['profile_image']) ? $admin['profile_image'] : 'assets/img/default-avatar.png' ?>"
               alt="Profile" class="w-20 h-20 rounded-full object-cover border">
          <div>
            <label class="block text-sm font-medium text-gray-700">Change Photo</label>
            <input type="file" name="profile_image" class="text-sm">
          </div>
        </div>

        <div>
          <label class="block text-gray-600 mb-1 font-medium">Full Name</label>
          <input type="text" name="name" value="<?= htmlspecialchars($admin['name']) ?>"
                 class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
        </div>

        <div>
          <label class="block text-gray-600 mb-1 font-medium">Email Address</label>
          <input type="email" name="email" value="<?= htmlspecialchars($admin['email']) ?>"
                 class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
        </div>

        <button type="submit" name="update_profile"
                class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-lg transition">
          💾 Save Changes
        </button>
      </form>
    </div>

    <!-- Password Change -->
    <div class="bg-white rounded-xl shadow-md p-6">
      <h2 class="text-xl font-semibold mb-4">🔐 Change Password</h2>
      <form method="POST" class="space-y-4">
        <div>
          <label class="block text-gray-600 mb-1 font-medium">Current Password</label>
          <input type="password" name="current_password"
                 class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
        </div>
        <div>
          <label class="block text-gray-600 mb-1 font-medium">New Password</label>
          <input type="password" name="new_password"
                 class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
        </div>
        <div>
          <label class="block text-gray-600 mb-1 font-medium">Confirm Password</label>
          <input type="password" name="confirm_password"
                 class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500" required>
        </div>

        <button type="submit" name="change_password"
                class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-lg transition">
          🔄 Update Password
        </button>
      </form>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>
