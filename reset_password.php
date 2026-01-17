<?php
session_start();
include('includes/db_connect.php');

$token = $_GET['token'] ?? '';
$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = $_POST['token'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password !== $confirm_password) {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Passwords do not match.</div>";
    } else {
        // Validate token
        $stmt = $conn->prepare("SELECT email, expires_at FROM password_resets WHERE token = ?");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $reset = $result->fetch_assoc();

        if ($reset && strtotime($reset['expires_at']) > time()) {
            $email = $reset['email'];
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);

            // Update password
            $update = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $update->bind_param("ss", $hashed, $email);
            $update->execute();

            // Remove used token
            $conn->query("DELETE FROM password_resets WHERE email='$email'");

            $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>✅ Password updated successfully. <a href='login.php' class='underline'>Login now</a>.</div>";
        } else {
            $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>⚠️ Invalid or expired token.</div>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Reset Password - AquaFlow</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    .strength-bar {
      height: 6px;
      border-radius: 4px;
      margin-top: 6px;
    }
  </style>
</head>
<body class="bg-gray-100">

  <section class="flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-xl shadow-lg w-full max-w-md">
      <div class="text-center mb-6">
        <img src="/aquaWater/assets/img/logo.png" alt="AquaFlow Logo" class="mx-auto w-16 mb-2">
        <h1 class="text-2xl font-bold text-cyan-700">Reset Password</h1>
        <p class="text-gray-600 text-sm">Create a strong new password below</p>
      </div>

      <?= $message ?>

      <form method="POST" onsubmit="return confirmReset();" autocomplete="off">
        <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">

        <label class="block mb-2 text-gray-700 font-semibold">New Password</label>
        <input type="password" id="new_password" name="new_password" required
               class="w-full border border-gray-300 p-3 rounded-md mb-2 focus:ring-2 focus:ring-cyan-700">
        <div id="strength-bar" class="strength-bar bg-gray-200"></div>
        <p id="strength-text" class="text-sm text-gray-500 mt-1 mb-4"></p>

        <label class="block mb-2 text-gray-700 font-semibold">Confirm Password</label>
        <input type="password" name="confirm_password" required
               class="w-full border border-gray-300 p-3 rounded-md mb-4 focus:ring-2 focus:ring-cyan-700">

        <button type="submit"
          class="w-full bg-cyan-700 text-white p-3 rounded-md hover:bg-cyan-800 transition">
          Update Password
        </button>
      </form>

      <p class="text-center mt-4">
        <a href="login.php" class="text-cyan-700 hover:underline">← Back to Login</a>
      </p>
    </div>
  </section>

  <script>
    // 🔹 Password strength indicator
    const passwordInput = document.getElementById('new_password');
    const bar = document.getElementById('strength-bar');
    const text = document.getElementById('strength-text');

    passwordInput.addEventListener('input', () => {
      const value = passwordInput.value;
      let strength = 0;

      if (value.length >= 8) strength++;
      if (/[A-Z]/.test(value)) strength++;
      if (/[a-z]/.test(value)) strength++;
      if (/[0-9]/.test(value)) strength++;
      if (/[^A-Za-z0-9]/.test(value)) strength++;

      let color = 'bg-gray-200';
      let message = 'Weak password';

      if (strength <= 2) {
        color = 'bg-red-500';
        message = 'Weak';
      } else if (strength === 3) {
        color = 'bg-yellow-500';
        message = 'Medium';
      } else if (strength >= 4) {
        color = 'bg-green-500';
        message = 'Strong';
      }

      bar.className = `strength-bar ${color}`;
      text.textContent = `Password strength: ${message}`;
    });

    // 🔹 Confirm before submission
    function confirmReset() {
      return confirm("Are you sure you want to reset your password?");
    }
  </script>

</body>
</html>
