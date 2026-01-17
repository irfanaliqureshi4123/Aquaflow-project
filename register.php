<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/header.php';
require_once 'vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$message = "";

// Handle registration form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm  = trim($_POST['confirm_password']);
    $role     = 'customer';

    // ✅ Basic validation
    if (empty($name) || empty($email) || empty($password)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ All fields are required.</div>";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Invalid email format.</div>";
    } elseif ($password !== $confirm) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Passwords do not match.</div>";
    } elseif (strlen($password) < 6) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Password must be at least 6 characters.</div>";
    } else {
        // ✅ Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Email already registered. Try logging in.</div>";
        } else {
            // ✅ Hash password
            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            
            // ✅ Generate 6-digit OTP
            $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otpExpires = date("Y-m-d H:i:s", time() + 3600); // 1 hour from now

            // ✅ Create email verifications table if it doesn't exist
            $conn->query("CREATE TABLE IF NOT EXISTS email_verifications (
                id INT AUTO_INCREMENT PRIMARY KEY,
                email VARCHAR(150) NOT NULL,
                otp VARCHAR(6), 
                token VARCHAR(255),
                expires_at DATETIME NOT NULL,
                verified INT DEFAULT 0,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY unique_email_otp (email, otp)
            )");
            
            // Ensure otp column exists
            $result = $conn->query("SHOW COLUMNS FROM email_verifications LIKE 'otp'");
            if ($result->num_rows === 0) {
                $conn->query("ALTER TABLE email_verifications ADD COLUMN otp VARCHAR(6) AFTER email");
            }

            // ✅ Insert user with is_verified = 0
            $insert = $conn->prepare("INSERT INTO users (name, email, password, role, is_verified) VALUES (?, ?, ?, ?, 0)");
            $insert->bind_param("ssss", $name, $email, $hashedPassword, $role);

            if ($insert->execute()) {
                // ✅ Store OTP
                $tokenStmt = $conn->prepare("INSERT INTO email_verifications (email, otp, expires_at) VALUES (?, ?, ?)");
                $tokenStmt->bind_param("sss", $email, $otp, $otpExpires);
                $tokenStmt->execute();
                $tokenStmt->close();

                // ✅ Send verification email using PHPMailer
                $subject = "🔐 Your AquaFlow Email Verification OTP";
                
                $verificationLink = $base_url . "verify_otp.php";
                $emailBody = "
                <html>
                    <head>
                        <style>
                            body { font-family: Arial, sans-serif; background-color: #f5f5f5; }
                            .container { background-color: white; max-width: 600px; margin: 20px auto; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                            .header { text-align: center; color: #00a8a8; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 1px solid #eee; }
                            .content { color: #333; line-height: 1.6; margin-bottom: 20px; }
                            .otp-box { background-color: #e0f7fa; color: #00796b; font-size: 32px; font-weight: bold; text-align: center; padding: 20px; border-radius: 8px; margin: 25px 0; border: 2px dashed #80deea; letter-spacing: 4px; font-family: monospace; }
                            .footer { text-align: center; color: #999; font-size: 12px; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
                        </style>
                    </head>
                    <body>
                        <div class='container'>
                            <div class='header'>
                                <h1>🔐 Verify Your AquaFlow Account</h1>
                            </div>
                            <div class='content'>
                                <p>Hi " . htmlspecialchars($name) . ",</p>
                                <p>Thank you for registering with AquaFlow! To complete your registration, please use the One-Time Password (OTP) below to verify your email address:</p>
                                <div class='otp-box'>" . $otp . "</div>
                                <p>This OTP is valid for <strong>1 hour</strong>. After that, you'll need to request a new one.</p>
                                <p>Enter this OTP at: <a href='" . $verificationLink . "'>" . $verificationLink . "</a></p>
                                <hr style='border: none; border-top: 1px solid #eee; margin: 20px 0;'>
                                <p style='color: #999; font-size: 12px;'>If you didn't create this account, please ignore this email.</p>
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

                // Show success message with OTP verification prompt
                $message = "<div class='bg-green-100 text-green-700 p-4 sm:p-5 rounded-lg mb-4 sm:mb-6'>
                    <p class='font-bold mb-2 sm:mb-3 sm:text-lg'>✅ Registration Successful!</p>
                    <p class='text-sm sm:text-base'>Your account has been created with email <strong>" . htmlspecialchars($email) . "</strong></p>";
                
                if ($emailSent) {
                    $message .= "<p class='text-xs sm:text-sm mt-3'>✉️ A verification email with your OTP has been sent to your inbox.</p>";
                } else {
                    $message .= "<p class='text-xs sm:text-sm mt-3'>Your OTP is: <span class='font-bold text-lg'>" . $otp . "</span></p>";
                }
                
                $message .= "</div>";
                
                // Store email in session for pre-fill on verify_otp page
                $_SESSION['verify_email'] = $email;
            } else {
                $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Something went wrong. Please try again later.</div>";
            }

            $insert->close();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12 overflow-y-auto">
    <!-- Registration Card Container -->
    <div class="w-full max-w-md mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 md:p-10 border border-gray-200">
            <!-- Logo & Title -->
            <div class="text-center mb-6 sm:mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-cyan-700 mb-1 sm:mb-2">AquaFlow</h1>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Create Account</h2>
                <p class="text-sm sm:text-base text-gray-600">Join our water delivery service</p>
            </div>

            <?php if ($message) echo $message; ?>

            <?php if (strpos($message, "Registration Successful") === false): ?>
            <!-- Registration Form -->
            <form method="POST" action="">
                <!-- Full Name Input -->
                <div class="mb-4 sm:mb-5">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-user text-cyan-600 mr-2"></i>Full Name
                    </label>
                    <input type="text" name="name" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="John Doe">
                </div>

                <!-- Email Input -->
                <div class="mb-4 sm:mb-5">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-envelope text-cyan-600 mr-2"></i>Email Address
                    </label>
                    <input type="email" name="email" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="your@email.com">
                </div>

                <!-- Password Input -->
                <div class="mb-4 sm:mb-5">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-lock text-cyan-600 mr-2"></i>Password
                    </label>
                    <input type="password" name="password" required minlength="6"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="••••••••">
                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                </div>

                <!-- Confirm Password Input -->
                <div class="mb-5 sm:mb-6">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-lock text-cyan-600 mr-2"></i>Confirm Password
                    </label>
                    <input type="password" name="confirm_password" required minlength="6"
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="••••••••">
                </div>

                <!-- Register Button -->
                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl text-sm sm:text-base">
                    <i class="fas fa-user-plus mr-2"></i>Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="flex items-center my-4 sm:my-6">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="px-2 sm:px-3 text-gray-500 text-xs sm:text-sm">Already have an account?</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>

            <!-- Login Link -->
            <a href="login.php" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                <i class="fas fa-sign-in-alt mr-2"></i>Login to Account
            </a>
            <?php else: ?>
            <!-- Verification Pending Message -->
            <div class="text-center">
                <div class="text-5xl sm:text-6xl mb-4 text-cyan-600">
                    <i class="fas fa-envelope"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-bold text-gray-800 mb-4">Check Your Email for OTP</h3>
                <p class="text-sm sm:text-base text-gray-600 mb-6">We've sent a verification OTP to your email. Use it to verify your account.</p>
                
                <div class="bg-cyan-50 p-4 sm:p-5 rounded-lg border border-cyan-200 mb-6">
                    <p class="text-xs sm:text-sm text-gray-700">
                        <i class="fas fa-lightbulb text-cyan-600 mr-2"></i>
                        <strong>Tip:</strong> Check your spam folder if you don't see the email. OTP expires in 1 hour.
                    </p>
                </div>

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
            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-cyan-50 border border-cyan-200 rounded-lg text-center text-xs sm:text-sm text-gray-700">
                <i class="fas fa-info-circle text-cyan-600 mr-2"></i>
                <span>Your data is safe and secure with us</span>
            </div>
        </div>
    </div>

</body>
</html>
