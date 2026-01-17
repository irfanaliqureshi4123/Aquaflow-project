<?php
session_start();
include('includes/db_connect.php');
require 'vendor/autoload.php'; // PHPMailer
require 'email_templates/reset_password_email.php'; // Include our HTML email template

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = trim($_POST['email']);

    // Check if email exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows > 0) {
        // Generate token
        $token = bin2hex(random_bytes(32));
        $expires = date("Y-m-d H:i:s", strtotime('+1 hour'));

        // Ensure table exists
        $conn->query("CREATE TABLE IF NOT EXISTS password_resets (
            id INT AUTO_INCREMENT PRIMARY KEY,
            email VARCHAR(150) NOT NULL,
            token VARCHAR(255) NOT NULL,
            expires_at DATETIME NOT NULL
        )");

        // Remove old tokens
        $delete = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
        $delete->bind_param("s", $email);
        $delete->execute();

        // Store new token
        $stmt = $conn->prepare("INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $email, $token, $expires);
        $stmt->execute();

        // Send reset email
        $resetLink = "http://localhost/reset_password.php?token=$token";
        
        // Use PHP's built-in mail() function instead of PHPMailer
        $to = $email;
        $subject = "🔐 Reset Your AquaFlow Password";
        
        // Create HTML email
        $emailBody = "
        <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                    .container { background-color: white; max-width: 600px; margin: 20px auto; padding: 30px; border-radius: 10px; }
                    .header { text-align: center; color: #00a8a8; margin-bottom: 20px; }
                    .content { color: #333; line-height: 1.6; }
                    .button { display: inline-block; background-color: #00a8a8; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>AquaFlow 💧</h1>
                    </div>
                    <div class='content'>
                        <p>Hello,</p>
                        <p>We received a request to reset your password. Click the button below to set a new password:</p>
                        <center>
                            <a href='$resetLink' class='button'>Reset Password</a>
                        </center>
                        <p>Or copy and paste this link in your browser:</p>
                        <p><a href='$resetLink'>$resetLink</a></p>
                        <p>This link will expire in <strong>1 hour</strong>.</p>
                        <p>If you didn't request this, please ignore this email.</p>
                        <p>Best regards,<br>AquaFlow Support Team</p>
                    </div>
                    <div class='footer'>
                        <p>&copy; 2025 AquaFlow. All rights reserved.</p>
                    </div>
                </div>
            </body>
        </html>";

        // Try to send email via Gmail SMTP
        $gmailEmail = getenv('SMTP_USERNAME') ?: 'your-gmail@gmail.com';
        $gmailPassword = getenv('SMTP_PASSWORD') ?: 'your-app-password';
        
        $mail = new PHPMailer(true);
        $emailSent = false;
        
        try {
            // Only attempt to send if credentials are configured
            if ($gmailEmail && $gmailEmail !== 'your-gmail@gmail.com' && $gmailPassword && $gmailPassword !== 'your-app-password') {
                $mail->isSMTP();
                $mail->Host = 'smtp.gmail.com';
                $mail->SMTPAuth = true;
                $mail->Username = $gmailEmail;
                $mail->Password = $gmailPassword;
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = 587;
                $mail->setFrom($gmailEmail, 'AquaFlow Support');
                $mail->addAddress($to);
                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $emailBody;
                $mail->send();
                $emailSent = true;
            }
        } catch (Exception $e) {
            $emailSent = false;
        }

        if ($emailSent) {
            $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>✅ Password reset email sent successfully. Check your email inbox.</div>";
        } else {
            $message = "<div class='bg-green-100 text-green-700 p-3 rounded-md mb-4'>
                <p class='font-bold'>✅ Reset Link Generated!</p>
                <p class='text-sm mt-2'><strong>Click below to reset your password:</strong></p>
                <p class='mt-2'><a href='reset_password.php?token=$resetToken' class='inline-block px-4 py-2 bg-green-600 text-white rounded font-bold hover:bg-green-700'>🔑 Reset Password Now</a></p>
                <p class='text-xs mt-2 text-gray-600'>Or copy this link: <code class='bg-gray-200 px-1 rounded'>reset_password.php?token=$resetToken</code></p>
            </div>";
        }
    } else {
        $message = "<div class='bg-red-100 text-red-700 p-3 rounded-md mb-4'>❌ Email not found in our system.</div>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
  <title>Forgot Password - AquaFlow</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center justify-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12">
  <div class="w-full max-w-md">

  <!-- Form Container -->
  <div class="w-full max-w-md">
    <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 md:p-10 border border-gray-200">
      <!-- Header -->
      <div class="text-center mb-6 sm:mb-8">
        <h1 class="text-3xl sm:text-4xl font-bold text-cyan-700 mb-2">AquaFlow</h1>
        <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-2">Reset Password</h2>
        <p class="text-sm sm:text-base text-gray-600">Enter your email to receive reset link</p>
      </div>

      <?php if ($message) echo $message; ?>

      <form method="POST" class="space-y-4 sm:space-y-5">
        <!-- Email Input -->
        <div>
          <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
            <i class="fas fa-envelope text-cyan-600 mr-2"></i>Registered Email
          </label>
          <input type="email" name="email" required 
            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base" 
            placeholder="your@email.com">
          <p class="text-xs sm:text-sm text-gray-500 mt-1.5">We'll send a password reset link to this email</p>
        </div>

        <!-- Submit Button -->
        <button type="submit" class="w-full bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl mt-6 sm:mt-8 text-sm sm:text-base">
          <i class="fas fa-envelope-circle-check mr-2"></i>Send Reset Link
        </button>
      </form>

      <!-- Divider -->
      <div class="flex items-center my-4 sm:my-6">
        <div class="flex-grow border-t border-gray-300"></div>
        <span class="px-2 sm:px-3 text-gray-500 text-xs sm:text-sm">Need help?</span>
        <div class="flex-grow border-t border-gray-300"></div>
      </div>

      <!-- Back to Login -->
      <a href="login.php" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
        <i class="fas fa-arrow-left mr-2"></i>Back to Login
      </a>

      <!-- Info Box -->
      <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-blue-50 border border-blue-200 rounded-lg text-center text-xs sm:text-sm text-gray-700">
        <i class="fas fa-shield-alt text-blue-600 mr-2"></i>
        <span>Reset link expires in 1 hour</span>
      </div>
    </div>
  </div>
</body>
</html>
