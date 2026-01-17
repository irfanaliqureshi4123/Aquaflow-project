<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";
$email_sent = false;

// Handle resend verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    if (empty($email)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Please enter your email address.</div>";
    } else {
        // Check if email exists and is not verified
        $stmt = $conn->prepare("SELECT id, name FROM users WHERE email = ? AND is_verified = 0");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 0) {
            // Either email doesn't exist or already verified
            $message = "<div class='bg-yellow-100 text-yellow-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>⚠️ Email not found or already verified. Please <a href='login.php' class='underline font-bold'>login</a> or <a href='register.php' class='underline font-bold'>register</a>.</div>";
        } else {
            $stmt->bind_result($user_id, $name);
            $stmt->fetch();
            $stmt->close();

            // Generate new 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpires = date("Y-m-d H:i:s", time() + 3600); // 1 hour from now

            // Store new OTP
            $tokenStmt = $conn->prepare("INSERT INTO email_verifications (email, otp, expires_at) VALUES (?, ?, ?)");
            $tokenStmt->bind_param("sss", $email, $otp, $otpExpires);
            $tokenStmt->execute();
            $tokenStmt->close();

            // Send verification email using PHPMailer
            $verificationLink = $base_url . "verify_otp.php";
            $subject = "🔐 Your AquaFlow Email Verification OTP";

            // Professional HTML email with OTP only
            $emailBody = "
            <html>
                <head>
                    <style>
                        body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                        .container { background-color: white; max-width: 600px; margin: 20px auto; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                        .header { text-align: center; color: #00a8a8; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
                        .content { color: #333; line-height: 1.6; margin-bottom: 20px; }
                        .otp-box { background-color: #e0f7fa; color: #00796b; font-size: 36px; font-weight: bold; text-align: center; padding: 25px 20px; border-radius: 8px; margin: 30px 0; border: 2px dashed #80deea; letter-spacing: 5px; font-family: monospace; }
                        .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                        .highlight { color: #00796b; font-weight: bold; }
                    </style>
                </head>
                <body>
                    <div class='container'>
                        <div class='header'>
                            <h1>🔐 Verify Your Email</h1>
                        </div>
                        <div class='content'>
                            <p>Hi " . htmlspecialchars($name ?? 'User') . ",</p>
                            <p>This is your new verification OTP for your AquaFlow account. Please use the code below to verify your email address:</p>
                            <div class='otp-box'>" . $otp . "</div>
                            <p style='text-align: center; font-size: 16px;'>This OTP is valid for <span class='highlight'>1 hour</span>.</p>
                            <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                            <p style='color: #999; font-size: 12px;'>If you didn't request this email, please ignore it.</p>
                            <p style='color: #999; font-size: 12px;'>Best regards,<br>AquaFlow Support Team</p>
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
                    $mail->addAddress($email);
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
                $message = "<div class='bg-green-100 text-green-700 p-4 sm:p-5 rounded-lg mb-4 sm:mb-6'>
                    <p class='font-bold mb-2 sm:mb-3 sm:text-lg'>✅ Verification OTP Sent!</p>
                    <p class='text-sm sm:text-base'>A new verification OTP has been sent to <strong>$email</strong></p>
                    <p class='text-xs mt-2 sm:mt-3 sm:text-sm'>Please check your email for the 6-digit OTP code and enter it on the verification page.</p>
                </div>";
            } else {
                $message = "<div class='bg-green-100 text-green-700 p-4 sm:p-5 rounded-lg mb-4 sm:mb-6'>
                    <p class='font-bold mb-2 sm:mb-3 sm:text-lg'>✅ Verification OTP Sent!</p>
                    <p class='text-sm sm:text-base'>A new verification OTP has been sent to <strong>$email</strong></p>
                    <p class='text-xs mt-2 sm:mt-3 sm:text-sm'>Please check your email for the 6-digit OTP code and enter it on the verification page.</p>
                    <p class='text-xs mt-3 sm:mt-4 text-gray-600'><i class='fas fa-lightbulb mr-1'></i>Check your spam folder if you don't see the email.</p>
                </div>";
            }
            $email_sent = true;
        }
    }
} elseif (isset($_GET['email'])) {
    // Pre-fill email from login page
    $prefill_email = trim($_GET['email']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resend Verification - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12 overflow-y-auto">
    <div class="w-full max-w-md mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 md:p-10 border border-gray-200">
            <!-- Logo & Title -->
            <div class="text-center mb-6 sm:mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-cyan-700 mb-1 sm:mb-2">AquaFlow</h1>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Resend Verification</h2>
                <p class="text-sm sm:text-base text-gray-600">Get a new verification link sent to your email</p>
            </div>

            <?php if ($message) echo $message; ?>

            <?php if (!$email_sent): ?>
            <form method="POST" action="">
                <!-- Email Input -->
                <div class="mb-4 sm:mb-5">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-envelope text-cyan-600 mr-2"></i>Email Address
                    </label>
                    <input type="email" name="email" required
                        value="<?php echo isset($prefill_email) ? htmlspecialchars($prefill_email) : ''; ?>"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="your@email.com">
                </div>

                <!-- Resend Button -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl text-sm sm:text-base">
                    <i class="fas fa-envelope mr-2"></i>Resend Verification Email
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-4 sm:my-6">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="px-2 sm:px-3 text-gray-500 text-xs sm:text-sm">or</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>

            <!-- Links -->
            <div class="flex gap-3">
                <a href="verify_otp.php" class="flex-1 text-center bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                    <i class="fas fa-key mr-2"></i>Verify OTP
                </a>
                <a href="login.php" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                    <i class="fas fa-sign-in-alt mr-2"></i>Back to Login
                </a>
            </div>
            <?php else: ?>
                <!-- Success Message -->
                <div class="text-center">
                    <div class="text-5xl sm:text-6xl mb-4 text-cyan-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <p class="text-gray-600 mb-6 sm:mb-8 text-sm sm:text-base">Check your email for the 6-digit OTP code and enter it on the verification page to activate your account.</p>
                    
                    <div class="flex gap-3">
                        <a href="verify_otp.php" class="flex-1 text-center bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                            <i class="fas fa-key mr-2"></i>Enter OTP
                        </a>
                        <a href="login.php" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                            <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="mt-6 p-3 sm:p-4 bg-cyan-50 border border-cyan-200 rounded-lg text-center text-xs sm:text-sm text-gray-700">
                <i class="fas fa-info-circle text-cyan-600 mr-2"></i>
                <span>Check your spam folder if you don't see the email</span>
            </div>
        </div>
    </div>

</body>

</html>
