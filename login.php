<?php
session_start();
require_once "includes/db_connect.php";

$message = "";

if (isset($_SESSION["user_id"])) {
    switch ($_SESSION["role"]) {
        case "admin":
            header("Location: admin/dashboard.php");
            break;
        case "staff":
            header("Location: staff/dashboard.php");
            break;
        default:
            header("Location: customer/dashboard.php");
    }
    exit;
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $email = trim($_POST["email"]);
    $password = trim($_POST["password"]);

    if (empty($email) || empty($password)) {
        $message = "<p class=\"text-red-600\">Please fill in all fields.</p>";
    } else {
        $stmt = $conn->prepare("SELECT id, name, password, role, is_verified FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        $login_success = false;

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $name, $hashedPassword, $role, $is_verified);
            if ($stmt->fetch() && $hashedPassword !== null) {
                if (password_verify($password, $hashedPassword)) {
                    if (!$is_verified) {
                        $message = "<div class=\"bg-yellow-100 text-yellow-700 p-3 rounded-md mb-4 text-sm sm:text-base\">
                            <i class=\"fas fa-envelope mr-2\"></i><strong>Email Not Verified</strong><br>
                            <span class=\"text-xs sm:text-sm\">Please check your email and click the verification link to activate your account.</span><br>
                            <a href=\"resend_verification.php?email=" . urlencode($email) . "\" class=\"text-yellow-800 font-bold underline mt-2 inline-block text-xs sm:text-sm\">Resend Verification Email</a>
                        </div>";
                    } else {
                        $_SESSION["user_id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = $role;
                        $login_success = true;
                    }
                } else {
                    $message = "<p class=\"text-red-600\">Incorrect password. Please try again.</p>";
                }
            }
        } else {
            $stmt2 = $conn->prepare("SELECT id, name, password, role FROM staff WHERE email = ?");
            $stmt2->bind_param("s", $email);
            $stmt2->execute();
            $stmt2->store_result();

            if ($stmt2->num_rows === 1) {
                $stmt2->bind_result($id, $name, $hashedPassword, $role);
                if ($stmt2->fetch() && $hashedPassword !== null) {
                    if (password_verify($password, $hashedPassword)) {
                        $_SESSION["user_id"] = $id;
                        $_SESSION["name"] = $name;
                        $_SESSION["role"] = $role;
                        $login_success = true;
                    } else {
                        $message = "<p class=\"text-red-600\">Incorrect password. Please try again.</p>";
                    }
                }
            } else {
                $message = "<p class=\"text-red-600\">No account found with that email.</p>";
            }
            $stmt2->close();
        }

        if ($login_success) {
            $role = $_SESSION["role"];
            if ($role == "admin") {
                header("Location: admin/dashboard.php");
            } elseif ($role == "manager") {
                header("Location: manager/dashboard.php");
            } elseif ($role == "staff" || $role == "delivery") {
                header("Location: staff/dashboard.php");
            } else {
                header("Location: customer/dashboard.php");
            }
            exit();
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
    <title>User Login - AquaFlow</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-gray-100 flex items-center justify-center px-4 sm:px-6 md:px-8 py-6 sm:py-8 md:py-12">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl p-6 sm:p-8 md:p-10 border border-gray-200">
            <div class="text-center mb-6 sm:mb-8">
                <h1 class="text-3xl sm:text-4xl font-bold text-cyan-700 mb-1 sm:mb-2">AquaFlow</h1>
                <h2 class="text-xl sm:text-2xl font-bold text-gray-800 mb-1 sm:mb-2">Welcome Back</h2>
                <p class="text-sm sm:text-base text-gray-600">Login to your account</p>
            </div>

            <?php if ($message) echo "<div class=\"mb-4 p-3 sm:p-4 bg-red-50 border border-red-200 rounded-lg text-red-700 text-center text-sm sm:text-base\">$message</div>"; ?>

            <form method="POST" action="">
                <div class="mb-4 sm:mb-5">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-envelope text-cyan-600 mr-2"></i>Email Address
                    </label>
                    <input type="email" name="email" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="your@email.com">
                </div>

                <div class="mb-5 sm:mb-6">
                    <label class="block text-gray-700 font-medium mb-1.5 sm:mb-2 text-sm sm:text-base">
                        <i class="fas fa-lock text-cyan-600 mr-2"></i>Password
                    </label>
                    <input type="password" name="password" required
                        class="w-full px-3 sm:px-4 py-2.5 sm:py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 focus:border-transparent outline-none transition text-sm sm:text-base"
                        placeholder="">
                </div>

                <button type="submit"
                    class="w-full bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white font-bold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 shadow-lg hover:shadow-xl text-sm sm:text-base">
                    <i class="fas fa-sign-in-alt mr-2"></i>Login
                </button>

                <div class="text-center mt-3 sm:mt-4">
                    <a href="forgot_password.php" class="text-cyan-600 hover:text-cyan-700 text-xs sm:text-sm font-medium transition">
                        Forgot your password?
                    </a>
                </div>
            </form>

            <div class="flex items-center my-4 sm:my-6">
                <div class="flex-grow border-t border-gray-300"></div>
                <span class="px-2 sm:px-3 text-gray-500 text-xs sm:text-sm">New to AquaFlow?</span>
                <div class="flex-grow border-t border-gray-300"></div>
            </div>

            <a href="register.php" class="block w-full text-center bg-gray-100 hover:bg-gray-200 text-gray-800 font-semibold py-2.5 sm:py-3 px-4 rounded-lg transition duration-300 text-sm sm:text-base">
                <i class="fas fa-user-plus mr-2"></i>Create an Account
            </a>

            <div class="mt-4 sm:mt-6 p-3 sm:p-4 bg-cyan-50 border border-cyan-200 rounded-lg text-center text-xs sm:text-sm text-gray-700">
                <i class="fas fa-info-circle text-cyan-600 mr-2"></i>
                <span>Premium water delivery at your doorstep</span>
            </div>
        </div>
    </div>
</body>
</html>
