<?php
/**
 * Stripe Webhook Handler
 * 
 * This script handles Stripe webhook events for payment processing.
 * 
 * Events Handled:
 * - payment_intent.succeeded: Payment completed successfully
 * - payment_intent.payment_failed: Payment failed
 * - charge.refunded: Refund processed
 * 
 * Security:
 * - Verifies webhook signature using Stripe secret
 * - Only processes signed events
 * - Logs all webhook activities
 * - Updates order status atomically
 * 
 * Setup:
 * Configure this URL in Stripe Dashboard:
 * https://yourdomain.com/customer/webhook.php
 * 
 * @author AquaWater Team
 * @version 1.0
 */

require_once('../vendor/autoload.php');
include('../includes/db_connect.php');

// Retrieve raw POST data
$input = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? '';

// SECURITY: Get Stripe webhook secret from environment
$stripe_webhook_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? '';

if (empty($stripe_webhook_secret)) {
    error_log("Stripe webhook secret is not configured.");
    http_response_code(400);
    exit();
}

// Set Stripe API key
\Stripe\Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY'] ?? '');

$event = null;

try {
    // SECURITY: Verify webhook signature to ensure it's from Stripe
    $event = \Stripe\Webhook::constructEvent(
        $input,
        $sig_header,
        $stripe_webhook_secret
    );
} catch (\UnexpectedValueException $e) {
    // Invalid payload
    error_log("Webhook error: Invalid payload - " . $e->getMessage());
    http_response_code(400);
    exit();
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    // Invalid signature
    error_log("Webhook error: Invalid signature - " . $e->getMessage());
    http_response_code(400);
    exit();
}

// Log webhook event
error_log("Webhook received: " . $event->type . " - ID: " . $event->id);

// Handle different webhook events
switch ($event->type) {
    case 'payment_intent.succeeded':
        handlePaymentSucceeded($event, $conn);
        break;
        
    case 'payment_intent.payment_failed':
        handlePaymentFailed($event, $conn);
        break;
        
    case 'charge.refunded':
        handleRefund($event, $conn);
        break;
        
    default:
        error_log("Unhandled event type: " . $event->type);
}

// Return success response to Stripe
http_response_code(200);
exit();

/**
 * Handle successful payment
 * Updates order status to confirmed and payment status to paid
 * 
 * @param object $event Stripe webhook event
 * @param mysqli $conn Database connection
 */
function handlePaymentSucceeded($event, $conn) {
    $payment_intent = $event->data->object;
    $transaction_id = $payment_intent->id;
    
    error_log("Processing successful payment: " . $transaction_id);
    
    try {
        // TRANSACTION: Update order status atomically
        $conn->begin_transaction();
        
        // Find order by transaction ID
        $order_stmt = $conn->prepare("
            SELECT id, user_id, total_amount FROM orders 
            WHERE transaction_id = ?
        ");
        $order_stmt->bind_param("s", $transaction_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            error_log("Order not found for transaction: " . $transaction_id);
            $conn->rollback();
            return;
        }
        
        $order = $order_result->fetch_assoc();
        $order_id = $order['id'];
        $user_id = $order['user_id'];
        
        // Update order status to confirmed
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET status = 'confirmed', payment_status = 'paid' 
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status: " . $conn->error);
        }
        
        // Log payment success
        $log_stmt = $conn->prepare("
            INSERT INTO payment_logs (order_id, user_id, event_type, data) 
            VALUES (?, ?, 'payment_intent.succeeded', ?)
        ");
        $log_data = json_encode([
            'transaction_id' => $transaction_id,
            'amount' => $payment_intent->amount / 100,
            'currency' => $payment_intent->currency,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $log_stmt->bind_param("iis", $order_id, $user_id, $log_data);
        $log_stmt->execute();
        
        $conn->commit();
        
        error_log("Payment succeeded and order updated: Order ID = " . $order_id);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error handling successful payment: " . $e->getMessage());
    }
}

/**
 * Handle failed payment
 * Updates order status to show payment failure
 * 
 * @param object $event Stripe webhook event
 * @param mysqli $conn Database connection
 */
function handlePaymentFailed($event, $conn) {
    $payment_intent = $event->data->object;
    $transaction_id = $payment_intent->id;
    
    error_log("Processing failed payment: " . $transaction_id);
    
    try {
        $conn->begin_transaction();
        
        // Find order by transaction ID
        $order_stmt = $conn->prepare("
            SELECT id, user_id FROM orders 
            WHERE transaction_id = ?
        ");
        $order_stmt->bind_param("s", $transaction_id);
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows === 0) {
            error_log("Order not found for failed payment: " . $transaction_id);
            $conn->rollback();
            return;
        }
        
        $order = $order_result->fetch_assoc();
        $order_id = $order['id'];
        $user_id = $order['user_id'];
        
        // Update order status to failed
        $update_stmt = $conn->prepare("
            UPDATE orders 
            SET payment_status = 'failed' 
            WHERE id = ?
        ");
        $update_stmt->bind_param("i", $order_id);
        
        if (!$update_stmt->execute()) {
            throw new Exception("Failed to update order status: " . $conn->error);
        }
        
        // Get error message if available
        $error_message = $payment_intent->last_payment_error->message ?? 'Unknown error';
        
        // Log payment failure
        $log_stmt = $conn->prepare("
            INSERT INTO payment_logs (order_id, user_id, event_type, data) 
            VALUES (?, ?, 'payment_intent.payment_failed', ?)
        ");
        $log_data = json_encode([
            'transaction_id' => $transaction_id,
            'error' => $error_message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        $log_stmt->bind_param("iis", $order_id, $user_id, $log_data);
        $log_stmt->execute();
        
        $conn->commit();
        
        error_log("Payment failed for order: " . $order_id . " - Error: " . $error_message);
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error handling failed payment: " . $e->getMessage());
    }
}

/**
 * Handle refund processing
 * Updates order status when refund is issued
 * 
 * @param object $event Stripe webhook event
 * @param mysqli $conn Database connection
 */
function handleRefund($event, $conn) {
    $charge = $event->data->object;
    $refund_id = $charge->refunds->data[0]->id ?? null;
    
    if (!$refund_id) {
        error_log("Refund ID not found in webhook");
        return;
    }
    
    error_log("Processing refund: " . $refund_id);
    
    try {
        $conn->begin_transaction();
        
        // Find order by charge ID
        $order_stmt = $conn->prepare("
            SELECT id, user_id FROM orders 
            WHERE transaction_id = (
                SELECT transaction_id FROM orders 
                WHERE status IN ('confirmed', 'delivered')
                LIMIT 1
            )
        ");
        $order_stmt->execute();
        $order_result = $order_stmt->get_result();
        
        if ($order_result->num_rows > 0) {
            $order = $order_result->fetch_assoc();
            $order_id = $order['id'];
            $user_id = $order['user_id'];
            
            // Update order status to refunded
            $update_stmt = $conn->prepare("
                UPDATE orders 
                SET status = 'cancelled', payment_status = 'refunded' 
                WHERE id = ?
            ");
            $update_stmt->bind_param("i", $order_id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update order status: " . $conn->error);
            }
            
            // Log refund
            $log_stmt = $conn->prepare("
                INSERT INTO payment_logs (order_id, user_id, event_type, data) 
                VALUES (?, ?, 'charge.refunded', ?)
            ");
            $log_data = json_encode([
                'refund_id' => $refund_id,
                'amount' => $charge->refunds->data[0]->amount / 100,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            $log_stmt->bind_param("iis", $order_id, $user_id, $log_data);
            $log_stmt->execute();
            
            $conn->commit();
            
            error_log("Refund processed for order: " . $order_id);
        }
        
    } catch (Exception $e) {
        $conn->rollback();
        error_log("Error handling refund: " . $e->getMessage());
    }
}
?>
