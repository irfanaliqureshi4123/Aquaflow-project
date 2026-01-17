<?php
/**
 * Membership Payment Processing
 * 
 * This file processes the membership subscription payment through Stripe or COD.
 * 
 * Features:
 * - CSRF token validation
 * - Stripe card payment processing
 * - COD payment handling
 * - Payment status updates
 * - Error handling and rollback
 * - Email notifications
 * - Activity logging
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';
require_once '../vendor/autoload.php';

// Access Control
require_customer();

// Load membership emailer
require_once '../includes/membership_emailer.php';

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Verify CSRF token
if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    http_response_code(403);
    $_SESSION['error'] = 'Security validation failed. Please try again.';
    header('Location: ./membership_checkout.php');
    exit;
}

// Initialize Stripe
$stripe_secret_key = $_ENV['STRIPE_SECRET_KEY'] ?? '';
if (empty($stripe_secret_key)) {
    error_log("Stripe secret key not configured");
    $_SESSION['error'] = 'Payment processing configuration error.';
    header('Location: ./membership_checkout.php');
    exit;
}

\Stripe\Stripe::setApiKey($stripe_secret_key);

$user_id = $_SESSION['user_id'];
$user_membership_id = isset($_POST['user_membership_id']) ? (int)$_POST['user_membership_id'] : 0;
$membership_id = isset($_POST['membership_id']) ? (int)$_POST['membership_id'] : 0;
$amount = isset($_POST['amount']) ? (float)$_POST['amount'] : 0;
$payment_method = isset($_POST['payment_method']) ? sanitize_input($_POST['payment_method']) : '';

// Validation
if (!$user_membership_id || !$amount || !$payment_method) {
    $_SESSION['error'] = 'Invalid payment information.';
    header('Location: ./membership_checkout.php');
    exit;
}

// Start transaction
$conn->begin_transaction();

try {
    // Get membership details and subscription dates
    $membership_query = $conn->prepare("
        SELECT m.name, um.start_date, um.end_date
        FROM memberships m
        JOIN user_memberships um ON um.membership_id = m.id
        WHERE m.id = ? AND um.id = ?
    ");
    $membership_query->execute([$membership_id, $user_membership_id]);
    $membership_result = $membership_query->get_result();
    $membership = $membership_result->fetch_assoc();
    $membership_name = $membership['name'] ?? 'Membership';
    $start_date = $membership['start_date'] ?? date('Y-m-d');
    $end_date = $membership['end_date'] ?? date('Y-m-d', strtotime('+30 days'));

    // Get user details
    $user_query = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
    $user_query->bind_param('i', $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user = $user_result->fetch_assoc();

    if ($payment_method === 'card') {
        // Process Stripe Card Payment
        $stripe_payment_method_id = isset($_POST['stripe_payment_method_id']) ? sanitize_input($_POST['stripe_payment_method_id']) : '';

        if (empty($stripe_payment_method_id) || !preg_match('/^pm_[a-zA-Z0-9]{24,}$/', $stripe_payment_method_id)) {
            throw new Exception("Invalid payment method format.");
        }

        // Convert amount to cents for Stripe (USD)
        $amount_usd = $amount * 0.0030; // Approximate PKR to USD conversion
        $amount_cents = round($amount_usd * 100);

        // Create Stripe PaymentIntent
        try {
            $intent = \Stripe\PaymentIntent::create([
                'amount' => max($amount_cents, 50), // Minimum $0.50
                'currency' => 'usd',
                'payment_method' => $stripe_payment_method_id,
                'confirmation_method' => 'manual',
                'confirm' => true,
                'return_url' => $_ENV['BASE_URL'] . '/customer/membership_success.php',
                'metadata' => [
                    'user_id' => $user_id,
                    'user_membership_id' => $user_membership_id,
                    'type' => 'membership_subscription'
                ]
            ]);

            if ($intent->status === 'succeeded') {
                $payment_status = 'completed';
                $transaction_id = $intent->id;
            } elseif ($intent->status === 'requires_action') {
                $payment_status = 'pending';
                $transaction_id = $intent->id;
                $_SESSION['requires_action'] = true;
                $_SESSION['client_secret'] = $intent->client_secret;
            } else {
                throw new Exception("Payment failed with status: " . $intent->status);
            }
        } catch (\Stripe\Exception\CardException $e) {
            throw new Exception("Card declined: " . $e->getMessage());
        } catch (\Stripe\Exception\ApiErrorException $e) {
            throw new Exception("Stripe error: " . $e->getMessage());
        }

        // Update payment record
        $update_payment = $conn->prepare("
            UPDATE user_memberships 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update_payment->bind_param('si', $payment_status, $user_membership_id);
        $update_payment->execute();

    } elseif ($payment_method === 'cod') {
        // Process Cash on Delivery
        $payment_status = 'pending';
        $transaction_id = 'COD-' . time();

        // Update payment record
        $update_payment = $conn->prepare("
            UPDATE user_memberships 
            SET status = 'pending', updated_at = NOW()
            WHERE id = ?
        ");
        $update_payment->bind_param('i', $user_membership_id);
        $update_payment->execute();

    } else {
        throw new Exception("Invalid payment method.");
    }

    // Update user membership status based on payment status
    if ($payment_status === 'completed') {
        $membership_status = 'active';
    } else {
        $membership_status = 'pending';
    }

    $update_membership = $conn->prepare("
        UPDATE user_memberships 
        SET status = ?, payment_method = ?, updated_at = NOW()
        WHERE id = ? AND user_id = ?
    ");
    $update_membership->bind_param('ssii', $membership_status, $payment_method, $user_membership_id, $user_id);
    $update_membership->execute();

    // Log activity (optional - activity_log may have limitations)
    $activity_description = "Membership payment processed - {$membership_name} - {$payment_method} - Status: {$payment_status}";
    $log_query = $conn->prepare("
        INSERT INTO activity_log (action, created_at) 
        VALUES (?, NOW())
    ");
    if ($log_query) {
        $log_query->bind_param('s', $activity_description);
        @$log_query->execute(); // Suppress errors if table has issues
    }

    // Commit transaction
    $conn->commit();

    // Send email notification
    try {
        $emailer = new MembershipEmailNotifier();
        
        if ($payment_status === 'completed') {
            $emailer->sendPaymentConfirmed(
                $user['email'], 
                $user['name'], 
                $membership_name, 
                $amount, 
                $payment_method,
                $start_date,
                $end_date
            );
        } else {
            // Send initiated notification for pending payments
            $emailer->sendSubscriptionInitiated(
                $user['email'], 
                $user['name'], 
                $membership_name, 
                $amount
            );
        }
    } catch (Exception $e) {
        error_log("Email notification error: " . $e->getMessage());
        // Don't fail the payment process if email fails
    }

    // Set success message and redirect
    $_SESSION['success'] = 'Payment processed successfully! Your membership will be activated shortly.';
    $_SESSION['subscription_completed'] = [
        'membership_name' => $membership_name,
        'amount' => $amount,
        'payment_method' => $payment_method,
        'transaction_id' => $transaction_id,
        'status' => $payment_status
    ];

    header('Location: ./membership_success.php');
    exit;

} catch (Exception $e) {
    // Rollback transaction
    $conn->rollback();

    error_log("Membership payment processing error: " . $e->getMessage());

    // Update payment status to failed
    $conn->begin_transaction();
    try {
        $failed_status = 'cancelled';
        $update_failed = $conn->prepare("
            UPDATE user_memberships 
            SET status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $update_failed->bind_param('si', $failed_status, $user_membership_id);
        $update_failed->execute();

        $conn->commit();
    } catch (Exception $rollback_error) {
        $conn->rollback();
    }

    $_SESSION['error'] = 'Payment failed: ' . $e->getMessage();
    header('Location: ./membership_checkout.php');
    exit;
}

/**
 * Sanitize user input
 */
function sanitize_input($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

$conn->close();
?>
