<?php
session_start();
require_once 'includes/db_connect.php';

$message = "";
$success = false;

// Get token from URL
$token = isset($_GET['token']) ? trim($_GET['token']) : '';

if (empty($token)) {
    $message = "❌ Invalid verification link. Please try again or <a href='register.php' class='underline font-bold text-cyan-600'>register</a>.";
} else {
    // Check if token exists and is not expired
    $stmt = $conn->prepare("SELECT email FROM email_verifications WHERE token = ? AND expires_at > NOW() AND verified = 0");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $stmt->bind_result($email);
        $stmt->fetch();
        $stmt->close();

        // Update user as verified
        $update = $conn->prepare("UPDATE users SET is_verified = 1 WHERE email = ?");
        $update->bind_param("s", $email);

        if ($update->execute()) {
            // Mark token as used
            $markUsed = $conn->prepare("UPDATE email_verifications SET verified = 1 WHERE token = ?");
            $markUsed->bind_param("s", $token);
            $markUsed->execute();
            $markUsed->close();

            $message = "✅ Email verified successfully! You can now <a href='login.php' class='underline font-bold text-cyan-600'>login</a> to your account.";
            $success = true;
        } else {
            $message = "❌ Something went wrong. Please try again later.";
        }
        $update->close();
    } else {
        $message = "❌ Invalid or expired verification link. Please <a href='register.php' class='underline font-bold text-cyan-600'>register</a> again or request a new verification email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify Email - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center justify-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12">
    <div class="w-full max-w-md">
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
                    <div class="text-5xl sm:text-6xl mb-4 text-yellow-600">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-2">Email Verification</h1>
                    <p class="text-sm sm:text-base text-gray-600">Processing your verification request</p>
                <?php endif; ?>
            </div>

            <!-- Message -->
            <div class="<?php echo $success ? 'bg-green-100 text-green-700 border-green-300' : 'bg-yellow-100 text-yellow-700 border-yellow-300'; ?> p-4 rounded-lg border mb-6">
                <p class="text-sm sm:text-base">
                    <?php echo $message; ?>
                </p>
            </div>

            <!-- Action Button -->
            <div class="flex gap-3">
                <a href="login.php" class="flex-1 text-center bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                    <i class="fas fa-sign-in-alt mr-2"></i>Go to Login
                </a>
                <a href="index.php" class="flex-1 text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                    <i class="fas fa-home mr-2"></i>Go Home
                </a>
            </div>

            <!-- Help Text -->
            <div class="mt-6 p-3 sm:p-4 bg-gray-50 border border-gray-300 rounded-lg text-center text-xs sm:text-sm text-gray-600">
                <p><i class="fas fa-question-circle text-gray-500 mr-2"></i>Didn't receive the email?</p>
                <a href="resend_verification.php" class="text-cyan-600 hover:text-cyan-700 font-bold underline">Resend verification link</a>
            </div>
        </div>
    </div>

</body>

</html>
