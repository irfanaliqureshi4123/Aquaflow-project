<?php
/**
 * Membership Subscription Initiation
 * 
 * This file handles the initial membership subscription request.
 * It validates the membership, creates a pending record, and redirects to checkout.
 * 
 * Flow:
 * 1. Validate membership plan exists and is available
 * 2. Check if user already has active subscription to this plan
 * 3. Create pending user_membership record
 * 4. Redirect to payment checkout page
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Only logged-in customers can subscribe
require_customer();

$user_id = $_SESSION['user_id'];
$membership_id = isset($_POST['membership_id']) ? (int)$_POST['membership_id'] : 0;

if (!$membership_id) {
    $_SESSION['error'] = 'Invalid membership plan.';
    header('Location: ./membership.php');
    exit;
}

// Verify membership exists
$verify_query = "
    SELECT id, name, price, bottles_per_week, duration_days 
    FROM memberships 
    WHERE id = ?
";
$verify_stmt = $conn->prepare($verify_query);
$verify_stmt->bind_param('i', $membership_id);
$verify_stmt->execute();
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['error'] = 'Membership plan not found.';
    header('Location: ./membership.php');
    exit;
}

$membership = $verify_result->fetch_assoc();
$price = $membership['price'];
$membership_name = $membership['name'];

// Check if user already has an active subscription to this membership
$check_active = $conn->prepare("
    SELECT id FROM user_memberships 
    WHERE user_id = ? AND membership_id = ? 
    AND status = 'active' AND end_date >= CURDATE()
");
$check_active->bind_param('ii', $user_id, $membership_id);
$check_active->execute();
$check_result = $check_active->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['warning'] = 'You already have an active subscription to this membership plan.';
    header('Location: ./membership.php');
    exit;
}

// Start transaction to create subscription records
$conn->begin_transaction();

try {
    // Create a pending user membership record
    $start_date = date('Y-m-d');
    $end_date = date('Y-m-d', strtotime('+' . $membership['duration_days'] . ' days'));
    
    $insert_query = "
        INSERT INTO user_memberships 
        (user_id, membership_id, start_date, end_date, status) 
        VALUES (?, ?, ?, ?, 'pending')
    ";
    $insert_stmt = $conn->prepare($insert_query);
    if (!$insert_stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $insert_stmt->bind_param('iiss', $user_id, $membership_id, $start_date, $end_date);
    if (!$insert_stmt->execute()) {
        throw new Exception("Execute failed: " . $insert_stmt->error);
    }
    
    $user_membership_id = $conn->insert_id;
    
    if (!$user_membership_id) {
        throw new Exception("Failed to get inserted ID");
    }
    
    // Commit the transaction
    $conn->commit();
    
    // Store subscription info in session for checkout page
    $_SESSION['membership_subscription'] = [
        'user_membership_id' => $user_membership_id,
        'membership_id' => $membership_id,
        'membership_name' => $membership_name,
        'price' => $price,
        'bottles_per_week' => $membership['bottles_per_week'],
        'duration_days' => $membership['duration_days'],
        'start_date' => $start_date,
        'end_date' => $end_date
    ];
    
    // Log activity (optional - activity_log may have limitations)
    $activity_description = "Initiated subscription to {$membership_name} membership";
    $log_query = "
        INSERT INTO activity_log (action, created_at) 
        VALUES (?, NOW())
    ";
    $log_stmt = $conn->prepare($log_query);
    if ($log_stmt) {
        $log_stmt->bind_param('s', $activity_description);
        @$log_stmt->execute(); // Suppress errors if table has issues
    }
    
    // Redirect to checkout page
    header('Location: ./membership_checkout.php');
    exit;
    
} catch (Exception $e) {
    $conn->rollback();
    error_log("Subscription initiation error - " . $e->getMessage() . " - File: " . __FILE__ . " Line: " . __LINE__);
    $_SESSION['error'] = 'Failed to initialize subscription: ' . $e->getMessage();
    header('Location: ./membership.php');
    exit;
}
