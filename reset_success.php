<?php
session_start();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Password Reset Successful - AquaFlow</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">

  <div class="bg-white p-8 rounded-2xl shadow-lg text-center w-full max-w-md">
    <img src="/aquaWater/assets/img/logo.png" alt="AquaFlow Logo" class="mx-auto w-20 mb-4">

    <h1 class="text-3xl font-bold text-cyan-700 mb-2">Password Reset Successful</h1>
    <p class="text-gray-600 mb-6">
      Your password has been updated securely. You can now log in to your AquaFlow account using your new credentials.
    </p>

    <a href="login.php"
       class="bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold hover:bg-cyan-800 transition duration-300">
      Go to Login
    </a>

    <div class="mt-8 text-sm text-gray-500">
      <hr class="my-4">
      <p>Need help? <a href="contact.php" class="text-cyan-700 hover:underline">Contact AquaFlow Support</a></p>
      <p class="mt-2">&copy; <?= date('Y') ?> AquaFlow Water Delivery</p>
    </div>
  </div>

</body>
</html>
