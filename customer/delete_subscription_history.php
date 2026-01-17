<?php
/**
 * Delete Subscription History Record
 * 
 * Allows customers to delete individual subscription records from their history.
 * Only allows deletion of expired or cancelled subscriptions.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Only logged-in customers can delete their own history
require_customer();

$user_id = $_SESSION['user_id'];
$subscription_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$subscription_id) {
    $_SESSION['error'] = 'Invalid subscription record.';
    header('Location: ./membership_history.php');
    exit;
}

// Verify subscription exists and belongs to the user
$verify_stmt = $conn->prepare("
    SELECT id, status, user_id
    FROM user_memberships
    WHERE id = ? AND user_id = ?
");
$verify_stmt->execute([$subscription_id, $user_id]);
$verify_result = $verify_stmt->get_result();

if ($verify_result->num_rows === 0) {
    $_SESSION['error'] = 'Subscription record not found.';
    header('Location: ./membership_history.php');
    exit;
}

$subscription = $verify_result->fetch_assoc();
$verify_stmt->close();

// Only allow deletion of expired or cancelled subscriptions
if ($subscription['status'] !== 'expired' && $subscription['status'] !== 'cancelled') {
    $_SESSION['error'] = 'You can only delete expired or cancelled subscription records.';
    header('Location: ./membership_history.php');
    exit;
}

// Delete the subscription record
$delete_stmt = $conn->prepare("
    DELETE FROM user_memberships
    WHERE id = ? AND user_id = ?
");

if ($delete_stmt->execute([$subscription_id, $user_id])) {
    $_SESSION['success'] = 'Subscription record deleted from your history.';
    
    // Log the deletion
    $log_stmt = $conn->prepare("
        INSERT INTO activity_log (action, created_at)
        VALUES (?, NOW())
    ");
    if ($log_stmt) {
        $log_message = "Customer deleted subscription record - Subscription ID: {$subscription_id}";
        $log_stmt->execute([$log_message]);
    }
} else {
    $_SESSION['error'] = 'Error deleting subscription record: ' . $delete_stmt->error;
}

$delete_stmt->close();
$conn->close();

header('Location: ./membership_history.php');
exit;
?>
