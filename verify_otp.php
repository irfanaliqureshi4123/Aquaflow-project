<?php
session_start();
require_once 'includes/db_connect.php';
require_once 'includes/header.php';

$message = "";
$success = false;

// Get email from session or URL parameter
$email = isset($_SESSION['verify_email']) ? $_SESSION['verify_email'] : (isset($_GET['email']) ? trim($_GET['email']) : '');

// Handle OTP verification
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $otp = trim($_POST['otp']);

    if (empty($email) || empty($otp)) {
        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Please enter both email and OTP.</div>";
    } else {
        // Check if OTP exists, is not expired, and matches
        $stmt = $conn->prepare("SELECT email FROM email_verifications WHERE email = ? AND otp = ? AND expires_at > NOW() AND verified = 0");
        if (!$stmt) {
            error_log("Prepare failed: " . $conn->error);
            $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Database error. Please try again.</div>";
        } else {
            $stmt->bind_param("ss", $email, $otp);
            if (!$stmt->execute()) {
                error_log("Execute failed: " . $stmt->error);
                $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Database error. Please try again.</div>";
            } else {
                $stmt->store_result();
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($verified_email);
                    $stmt->fetch();
                    $stmt->close();

                    // Update user as verified
                    $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
                    $update->bind_param("s", $email);

                    if ($update->execute()) {
                        // Mark OTP as used
                        $markUsed = $conn->prepare("UPDATE email_verifications SET verified = 1 WHERE email = ? AND otp = ?");
                        $markUsed->bind_param("ss", $email, $otp);
                        $markUsed->execute();
                        $markUsed->close();

                        $message = "✅ Email verified successfully! You can now <a href='login.php' class='underline font-bold text-cyan-600'>login</a> to your account.";
                        $success = true;
                        
                        // Clear the session email
                        unset($_SESSION['verify_email']);
                    } else {
                        $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Something went wrong. Please try again later.</div>";
                    }
                    $update->close();
                } else {
                    $message = "<div class='bg-red-100 text-red-700 p-3 sm:p-4 rounded-md mb-4 sm:mb-5'>❌ Invalid or expired OTP. Please <a href='resend_verification.php' class='underline font-bold'>request a new OTP</a>.</div>";
                    $stmt->close();
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12 overflow-y-auto">
    <div class="w-full max-w-md mx-auto">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 md:p-10 border border-gray-200">
            <!-- Icon & Title -->
            <div class="text-center mb-6 sm:mb-8">
                <?php if ($success): ?>
                    <div class="text-5xl sm:text-6xl mb-4 text-cyan-600">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Email Verified!</h1>
                    <p class="text-sm sm:text-base text-gray-600">Your account is now active and ready to use</p>
                <?php else: ?>
                    <div class="text-5xl sm:text-6xl mb-4 text-cyan-600">
                        <i class="fas fa-key"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Verify Your Email</h1>
                    <p class="text-sm sm:text-base text-gray-600">Enter the OTP sent to your email</p>
                <?php endif; ?>
            </div>

            <!-- Message -->
            <?php if ($message): ?>
                <?php if ($success): ?>
                    <div class="bg-green-100 text-green-700 border-green-300 p-4 rounded-lg border mb-6">
                        <p class="text-sm sm:text-base"><?php echo $message; ?></p>
                    </div>
                <?php else: ?>
                    <div class="mb-4 sm:mb-6">
                        <?php echo $message; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if (!$success): ?>
                <!-- OTP Verification Form -->
                <form method="POST" action="">
                    <!-- Email Input -->
                    <div class="mb-4 sm:mb-5">
                        <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                            <i class="fas fa-envelope text-cyan-600 mr-2"></i>Email Address
                        </label>
                        <input type="email" name="email" required
                            value="<?php echo htmlspecialchars($email); ?>"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                            placeholder="your@email.com">
                    </div>

                    <!-- OTP Input -->
                    <div class="mb-5 sm:mb-6">
                        <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                            <i class="fas fa-lock text-cyan-600 mr-2"></i>OTP Code
                        </label>
                        <input type="text" name="otp" required maxlength="6"
                            class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base text-center tracking-widest text-2xl"
                            placeholder="000000">
                        <p class="text-xs text-gray-500 mt-1">Enter 6-digit OTP</p>
                    </div>

                    <!-- Verify Button -->
                    <button type="submit"
                        class="w-full bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl text-sm sm:text-base">
                        <i class="fas fa-check mr-2"></i>Verify OTP
                    </button>
                </form>

                <!-- Divider -->
                <div class="flex items-center my-4 sm:my-6">
                    <div class="flex-grow border-t border-gray-300"></div>
                    <span class="px-2 sm:px-3 text-gray-500 text-xs sm:text-sm">or</span>
                    <div class="flex-grow border-t border-gray-300"></div>
                </div>

                <!-- Resend Link -->
                <a href="resend_verification.php" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                    <i class="fas fa-redo mr-2"></i>Resend OTP
                </a>
            <?php else: ?>
                <!-- Success Actions -->
                <div class="flex gap-3">
                    <a href="login.php" class="flex-1 text-center bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                        <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                    </a>
                    <a href="index.php" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                        <i class="fas fa-home mr-2"></i>Go Home
                    </a>
                </div>
            <?php endif; ?>

            <!-- Info Box -->
            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-cyan-50 border border-cyan-200 rounded-lg text-center text-xs sm:text-sm text-gray-700">
                <i class="fas fa-info-circle text-cyan-600 mr-2"></i>
                <span>OTP expires in 1 hour. If expired, request a new one.</span>
            </div>
        </div>
    </div>

</body>

</html>
