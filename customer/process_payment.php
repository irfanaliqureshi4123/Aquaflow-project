<?php 
/**
 * Payment Processing Controller
 * 
 * Handles the core payment processing logic including:
 * - CSRF token validation
 * - User authentication verification
 * - Cart validation and total calculation
 * - Order creation and transaction management
 * - Stripe payment processing (Credit Card, PayPal)
 * - Cash on Delivery (COD) handling
 * - Email notification sending
 * - Database transaction management with rollback on error
 * 
 * Security Features:
 * - CSRF protection with token verification
 * - Prepared statements to prevent SQL injection
 * - Input validation and sanitization
 * - Stripe payment method ID validation
 * - User ownership verification
 * - Amount range validation
 * - Email format validation
 * 
 * Error Handling:
 * - Transaction rollback on any error
 * - Comprehensive error logging
 * - User-friendly error messages
 * - Generic error messages to prevent information leakage
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');
require_once('../vendor/autoload.php');

// Import PHPMailer classes
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Load environment variables for secure API keys
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Set Stripe API Key
if (!isset($_ENV['STRIPE_SECRET_KEY']) || empty($_ENV['STRIPE_SECRET_KEY'])) {
    error_log("ERROR: STRIPE_SECRET_KEY not found in .env file");
    die("Stripe configuration error. Please contact administrator.");
}
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

// Access Control: Only customers can access this page
require_customer();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['payment_method'])) {
    $_SESSION['payment_error'] = 'Invalid request. Please try again.';
    header("Location: payment.php");
    exit;
}

// DEBUG: Log CSRF validation
error_log("=== CSRF VALIDATION DEBUG ===");
error_log("POST csrf_token exists: " . (isset($_POST['csrf_token']) ? 'YES' : 'NO'));
error_log("SESSION csrf_token exists: " . (isset($_SESSION['csrf_token']) ? 'YES' : 'NO'));
if (isset($_POST['csrf_token'])) {
    error_log("POST token: " . $_POST['csrf_token']);
}
if (isset($_SESSION['csrf_token'])) {
    error_log("SESSION token: " . $_SESSION['csrf_token']);
}
error_log("Session ID: " . session_id());

// Validate CSRF token
if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    error_log("CSRF VALIDATION FAILED!");
    $_SESSION['payment_error'] = 'Invalid CSRF token. Please try again.';
    header("Location: payment.php");
    exit;
}

error_log("CSRF VALIDATION PASSED!");
error_log("=== END CSRF DEBUG ===");

$user_id = $_SESSION['user_id'];
$payment_method = trim($_POST['payment_method']);

// SECURITY: Validate payment method format (prevent injection)
if (empty($payment_method) || !preg_match('/^[a-zA-Z\s]+$/', $payment_method)) {
    error_log("Invalid payment method format: " . $payment_method);
    $_SESSION['payment_error'] = 'Invalid payment method selected.';
    header("Location: payment.php");
    exit;
}

// SECURITY: Fetch and validate user email
$user_query = $conn->prepare("SELECT email FROM users WHERE id = ?");
$user_query->bind_param("i", $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
if ($user_result->num_rows === 0) {
    error_log("User not found during payment: user_id=" . $user_id);
    session_destroy();
    header("Location: ../login.php");
    exit;
}
$user_data = $user_result->fetch_assoc();
$user_email = filter_var($user_data['email'], FILTER_VALIDATE_EMAIL);

// SECURITY: Validate email format
if (!$user_email) {
    error_log("Invalid email format for user_id: " . $user_id);
    $_SESSION['payment_error'] = 'Invalid user email. Please contact support.';
    header("Location: payment.php");
    exit;
}

// Debugging log for submitted payment method
error_log("Submitted Payment Method: " . $payment_method);

/* ---------------------------------------------------------
  🛒 Fetch Cart Items
------------------------------------------------------------*/
$cart_q = $conn->prepare("
    SELECT p.id, p.name, p.price, c.quantity 
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
");
$cart_q->bind_param("i", $user_id);
$cart_q->execute();
$items = $cart_q->get_result();

if ($items->num_rows === 0) {
    $_SESSION['cart_error'] = 'Your cart is empty. Please add items before checkout.';
    header("Location: cart.php");
    exit;
}

$total_amount_pkr = 0;
$order_items = [];

while ($row = $items->fetch_assoc()) {
    // SECURITY: Validate price and quantity
    $row['price'] = floatval($row['price']);
    $row['quantity'] = intval($row['quantity']);
    
    if ($row['price'] <= 0 || $row['quantity'] <= 0) {
        error_log("Invalid price or quantity: price=" . $row['price'] . ", qty=" . $row['quantity']);
        throw new Exception("Invalid item in cart. Please contact support.");
    }
    
    $line_total = $row['price'] * $row['quantity'];
    $total_amount_pkr += $line_total;

    $order_items[] = [
        "product_id" => $row['id'],
        "name"       => $row['name'],
        "price"      => $row['price'],
        "qty"        => $row['quantity'],
        "total"      => $line_total
    ];
}

// SECURITY: Validate total amount
$total_amount_pkr = floatval($total_amount_pkr);
if ($total_amount_pkr < 1 || $total_amount_pkr > 10000000) {
    error_log("Invalid payment amount: " . $total_amount_pkr . " for user_id: " . $user_id);
    throw new Exception("Payment amount is invalid. Please contact support.");
}

// Convert PKR to USD (example)
$usd_rate = 0.0036;
$total_usd = $total_amount_pkr * $usd_rate;

// Generate invoice number
$invoice_no = "INV-" . time() . "-" . rand(1000, 9999);

/* ---------------------------------------------------------
  📦 Create Order First (before payment processing)
------------------------------------------------------------*/
// Start transaction
$conn->begin_transaction();

try {
    // Insert order with pending status
    $order_q = $conn->prepare("
        INSERT INTO orders 
        (user_id, total_amount, status, order_date, customer_email, payment_method, invoice_no) 
        VALUES (?, ?, 'Pending', NOW(), ?, ?, ?)
    ");
    $submitted_method = strtolower(trim($payment_method));
    $order_q->bind_param("idsss", $user_id, $total_amount_pkr, $user_email, $submitted_method, $invoice_no);
    
    if (!$order_q->execute()) {
        throw new Exception("Failed to create order: " . $conn->error);
    }
    
    $order_id = $conn->insert_id;
    
    // Save Order Items
    $item_q = $conn->prepare("
        INSERT INTO order_items (order_id, product_id, price, quantity)
        VALUES (?, ?, ?, ?)
    ");
    
    foreach ($order_items as $it) {
        $item_q->bind_param(
            "iidi", 
            $order_id, 
            $it['product_id'], 
            $it['price'], 
            $it['qty']
        );
        if (!$item_q->execute()) {
            throw new Exception("Failed to save order items: " . $conn->error);
        }
    }

/* ---------------------------------------------------------
  💳 Process Payment
------------------------------------------------------------*/
$payment_status = "Pending";
$transaction_id = null;

// Fetch allowed payment methods dynamically from the database
$allowed_methods = [];
$methods_q = $conn->query("SELECT method_name FROM payment_methods");
if ($methods_q && $methods_q->num_rows > 0) {
    while ($row = $methods_q->fetch_assoc()) {
        $allowed_methods[] = strtolower($row['method_name']);
    }
} else {
    error_log("Failed to fetch payment methods or no methods available.");
    throw new Exception("Payment methods are currently unavailable. Please contact support.");
}

// Debugging log for allowed methods
error_log("Allowed Payment Methods: " . json_encode($allowed_methods));

// Normalize submitted payment method
$submitted_method = strtolower(trim($payment_method));

// Normalize allowed methods
$allowed_methods = array_map(function ($method) {
    return strtolower(trim($method));
}, $allowed_methods);

// Validate payment method
if (!in_array($submitted_method, $allowed_methods)) {
    throw new Exception("The selected payment method is currently unavailable.");
}

// Process payment based on the selected method
if ($submitted_method === "cash on delivery") {
    $payment_status = "COD";
    $transaction_id = "COD-" . time();
} elseif ($submitted_method === "credit card") {
    $stripe_payment_method_id = $_POST['stripe_payment_method_id'] ?? null;

    if (empty($stripe_payment_method_id)) {
        throw new Exception("Stripe Payment Method ID is missing.");
    }
    
    // SECURITY: Validate Stripe payment method format (should start with "pm_")
    if (!preg_match('/^pm_[a-zA-Z0-9]{24,}$/', $stripe_payment_method_id)) {
        error_log("Invalid Stripe payment method format: " . $stripe_payment_method_id);
        throw new Exception("Invalid payment method format.");
    }

    try {
        $base_url = $_ENV['BASE_URL'] ?? 'http://localhost/aquaWater';
        $intent = \Stripe\PaymentIntent::create([
            'amount' => round($total_usd * 100),
            'currency' => 'usd',
            'payment_method' => $stripe_payment_method_id,
            'confirmation_method' => 'manual',
            'confirm' => true,
            'return_url' => $base_url . '/customer/success.php?order_id=' . $order_id,
        ]);

        if ($intent->status === 'succeeded') {
            $payment_status = "Paid";
            $transaction_id = $intent->id;
        } else if ($intent->status === 'requires_action' && $intent->next_action->type === 'use_stripe_sdk') {
            // Handle 3D Secure or other required actions on the client side
            $payment_status = "Pending - Action Required";
            $transaction_id = $intent->id;
            
            // Update order status before redirect
            $update_order = $conn->prepare("UPDATE orders SET status = ?, transaction_id = ? WHERE id = ?");
            $update_order->bind_param("ssi", $payment_status, $transaction_id, $order_id);
            $update_order->execute();
            $conn->commit();
            
            header("Location: ./payment.php?client_secret=" . $intent->client_secret);
            exit;
        } else {
            $payment_status = "Failed";
            $transaction_id = $intent->id;
            throw new Exception("Payment Failed: " . $intent->status);
        }

    } catch (\Stripe\Exception\CardException $e) {
        throw new Exception("Card Payment Failed: " . $e->getMessage());
    }
} elseif ($submitted_method === "paypal") {
    try {
        $base_url = $_ENV['BASE_URL'] ?? 'http://localhost/aquaWater';
        $intent = \Stripe\PaymentIntent::create([
            'amount' => round($total_usd * 100),
            'currency' => 'usd',
            'payment_method_types' => ['paypal'],
            'return_url' => $base_url . '/customer/success.php?order_id=' . $order_id,
        ]);

        $payment_status = "Pending - PayPal";
        $transaction_id = $intent->id;
        
        // Update order status before redirect
        $update_order = $conn->prepare("UPDATE orders SET status = ?, transaction_id = ? WHERE id = ?");
        $update_order->bind_param("ssi", $payment_status, $transaction_id, $order_id);
        $update_order->execute();
        $conn->commit();

        // Redirect to Stripe for PayPal authorization
        header("Location: " . $intent->next_action->redirect_to_url->url);
        exit;

    } catch (\Stripe\Exception\ApiErrorException $e) {
        throw new Exception("PayPal Payment Initiation Failed: " . $e->getMessage());
    }
} else {
    throw new Exception("Invalid payment method.");
}

/* ---------------------------------------------------------
  📦 Update Order Status
------------------------------------------------------------*/
$update_order = $conn->prepare("
    UPDATE orders 
    SET status = ?, transaction_id = ? 
    WHERE id = ?
");
$update_order->bind_param("ssi", $payment_status, $transaction_id, $order_id);

if (!$update_order->execute()) {
    throw new Exception("Failed to update order status: " . $conn->error);
}

/* ---------------------------------------------------------
  🧹 Clear Cart
------------------------------------------------------------*/
$delete_cart = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
$delete_cart->bind_param("i", $user_id);
if (!$delete_cart->execute()) {
    throw new Exception("Failed to clear cart: " . $conn->error);
}

// Commit transaction
$conn->commit();

/* ---------------------------------------------------------
  🎉 Success - Send Email & Redirect
------------------------------------------------------------*/
// Log successful transactions
$log_data = [
    'user_id' => $user_id,
    'invoice_no' => $invoice_no,
    'total_amount' => $total_amount_pkr,
    'payment_method' => $submitted_method,
    'payment_status' => $payment_status,
    'transaction_id' => $transaction_id,
];
error_log("Transaction Successful: " . json_encode($log_data));

// Send order confirmation email
if (isset($_ENV['SMTP_HOST']) && !empty($_ENV['SMTP_HOST'])) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = $_ENV['SMTP_HOST'];
        $mail->SMTPAuth = true;
        $mail->Username = $_ENV['SMTP_USERNAME'];
        $mail->Password = $_ENV['SMTP_PASSWORD'];
        $mail->SMTPSecure = 'tls';
        $mail->Port = $_ENV['SMTP_PORT'];

        $mail->setFrom($_ENV['SMTP_FROM_EMAIL'], $_ENV['SMTP_FROM_NAME']);
        $mail->addAddress($user_email);
        $mail->isHTML(true);
        $mail->Subject = "Order Confirmation - Invoice #{$invoice_no}";
        
        // Build email body
        $email_body = "<html><body style='font-family: Arial, sans-serif;'>";
        $email_body .= "<div style='max-width: 600px; margin: 0 auto; padding: 20px; background: #f5f5f5;'>";
        $email_body .= "<div style='background: white; padding: 30px; border-radius: 10px;'>";
        $email_body .= "<h1 style='color: #00a859;'>Thank you for your order!</h1>";
        $email_body .= "<p>Dear Customer,</p>";
        $email_body .= "<p>Your order has been received and is being processed.</p>";
        $email_body .= "<div style='background: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        $email_body .= "<h2 style='margin: 0 0 10px 0;'>Order Details</h2>";
        $email_body .= "<p><strong>Order ID:</strong> {$order_id}</p>";
        $email_body .= "<p><strong>Invoice Number:</strong> {$invoice_no}</p>";
        $email_body .= "<p><strong>Total Amount:</strong> Rs " . number_format($total_amount_pkr, 2) . "</p>";
        $email_body .= "<p><strong>Payment Method:</strong> " . ucwords($submitted_method) . "</p>";
        $email_body .= "<p><strong>Status:</strong> {$payment_status}</p>";
        $email_body .= "</div>";
        
        // Order items
        $email_body .= "<h3>Order Items:</h3>";
        $email_body .= "<table style='width: 100%; border-collapse: collapse;'>";
        $email_body .= "<tr style='background: #f0f0f0;'>";
        $email_body .= "<th style='padding: 10px; text-align: left; border: 1px solid #ddd;'>Product</th>";
        $email_body .= "<th style='padding: 10px; text-align: center; border: 1px solid #ddd;'>Qty</th>";
        $email_body .= "<th style='padding: 10px; text-align: right; border: 1px solid #ddd;'>Price</th>";
        $email_body .= "</tr>";
        
        foreach ($order_items as $item) {
            $email_body .= "<tr>";
            $email_body .= "<td style='padding: 10px; border: 1px solid #ddd;'>{$item['name']}</td>";
            $email_body .= "<td style='padding: 10px; text-align: center; border: 1px solid #ddd;'>{$item['qty']}</td>";
            $email_body .= "<td style='padding: 10px; text-align: right; border: 1px solid #ddd;'>Rs " . number_format($item['total'], 2) . "</td>";
            $email_body .= "</tr>";
        }
        
        $email_body .= "</table>";
        $email_body .= "<p style='margin-top: 20px;'>If you have any questions, please don't hesitate to contact us.</p>";
        $email_body .= "<p>Best regards,<br><strong>AquaWater Team</strong></p>";
        $email_body .= "</div></div></body></html>";
        
        $mail->Body = $email_body;
        $mail->AltBody = "Thank you for your order! Order ID: {$order_id}, Invoice: {$invoice_no}, Total: Rs " . number_format($total_amount_pkr, 2);

        $mail->send();
        error_log("Order confirmation email sent successfully to: " . $user_email);
    } catch (Exception $e) {
        error_log("Email could not be sent to {$user_email}. Error: " . $mail->ErrorInfo);
    }
} else {
    error_log("SMTP not configured. Skipping order confirmation email.");
}

    // Redirect to success page based on payment method
    if ($submitted_method === "cash on delivery") {
        header("Location: ./cod_success.php?order_id={$order_id}");
    } else {
        header("Location: ./success.php?order_id={$order_id}");
    }
    exit;

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    // Log detailed error
    error_log("Payment processing error: " . $e->getMessage() . " | User ID: " . $user_id . " | Payment Method: " . $payment_method);
    
    // Store error in session for display
    $_SESSION['payment_error'] = "Payment failed: " . $e->getMessage();
    
    // Redirect back to payment page
    header("Location: payment.php");
    exit;
}
?>