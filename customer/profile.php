<?php
// Start the session first
session_start();

// Include the database connection
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Initialize message variables
$message = '';
$messageType = '';

// Include the header
include('../includes/header.php');

// Debugging: Check if database connection is initialized
if (!$conn) {
    die("Database connection not established.");
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$query = "SELECT name, email, phone, address FROM users WHERE id = ?";
$stmt = $conn->prepare($query);

if ($stmt) {
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
} else {
    die("Failed to prepare statement: " . $conn->error);
}

// Update user details
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];

    $update_query = "UPDATE users SET name = ?, phone = ?, address = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);

    if ($update_stmt) {
        $update_stmt->bind_param("sssi", $name, $phone, $address, $user_id);

        if ($update_stmt->execute()) {
            $message = "Profile updated successfully!";
            $messageType = "success";
            // Refresh user data
            $stmt->execute();
            $user = $stmt->get_result()->fetch_assoc();
        } else {
            $message = "Failed to update profile: " . $conn->error;
            $messageType = "error";
        }
    } else {
        die("Failed to prepare update statement: " . $conn->error);
    }
}
?>
<!-- HEADER -->
<section class="bg-teal-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">My Profile</h1>
  <p class="opacity-90">Manage your AquaFlow account details</p>
</section>

<!-- MAIN -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-xl mx-auto bg-white shadow-lg rounded-xl p-8">
    <?php if ($message): ?>
        <div class="<?= $messageType === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> p-4 mb-6 rounded-lg">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>
    <form method="POST" class="space-y-5">
      <div>
        <label class="block text-gray-700 font-semibold mb-2">Full Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" 
               class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none" required>
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-2">Email (readonly)</label>
        <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" readonly
               class="w-full border border-gray-200 bg-gray-100 rounded-md p-3 text-gray-600 cursor-not-allowed">
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-2">Phone</label>
        <input type="text" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>"
               class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none">
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-2">Address</label>
        <textarea name="address" rows="3"
                  class="w-full border border-gray-300 rounded-md p-3 focus:ring-2 focus:ring-teal-500 focus:outline-none"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
      </div>

      <div class="text-center">
        <button type="submit" class="bg-teal-600 text-white px-6 py-3 rounded-md font-semibold hover:bg-teal-700 transition">
          Save Changes
        </button>
      </div>
    </form>

    <div class="text-center mt-6">
      <a href="dashboard.php" class="text-teal-600 hover:underline">
        ← Back to Dashboard
      </a>
    </div>
  </div>
</section>

<?php include('../includes/footer.php'); ?>

<script>
    document.querySelector('form').addEventListener('submit', function (e) {
        const phone = document.querySelector('input[name="phone"]').value;
        if (phone && !/^\d{11}$/.test(phone)) {
            e.preventDefault();
            alert('Please enter a valid 11-digit phone number.');
            return;
        }
    });

    // Add visual feedback for form fields
    const inputs = document.querySelectorAll('input:not([readonly]), textarea');
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.classList.add('ring-2', 'ring-teal-500');
        });
        input.addEventListener('blur', function() {
            this.classList.remove('ring-2', 'ring-teal-500');
        });
    });
</script>