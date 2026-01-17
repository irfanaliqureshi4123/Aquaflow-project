<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only delivery staff can update status
if (!has_role('delivery')) {
    $_SESSION['error'] = 'Only delivery staff can update delivery status.';
    header('Location: deliveries.php');
    exit;
}

// Validate input
$delivery_id = intval($_GET['id'] ?? 0);
$new_status = trim($_GET['status'] ?? '');

// Validate delivery ID
if ($delivery_id <= 0) {
    $_SESSION['error'] = 'Invalid delivery ID.';
    header('Location: deliveries.php');
    exit;
}

// Validate status
$valid_statuses = ['Pending', 'On the Way', 'Delivered', 'Cancelled'];
if (!in_array($new_status, $valid_statuses)) {
    $_SESSION['error'] = 'Invalid delivery status.';
    header('Location: deliveries.php');
    exit;
}

try {
    // Fetch delivery to verify it exists
    $deliveryStmt = $conn->prepare("SELECT id, status FROM deliveries WHERE id = ?");
    $deliveryStmt->bind_param('i', $delivery_id);
    $deliveryStmt->execute();
    $delivery = $deliveryStmt->get_result()->fetch_assoc();
    
    if (!$delivery) {
        $_SESSION['error'] = 'Delivery not found.';
        header('Location: deliveries.php');
        exit;
    }
    
    // Prevent status downgrade (no going backwards)
    $status_order = ['Pending' => 1, 'On the Way' => 2, 'Delivered' => 3];
    $current_order = $status_order[$delivery['status']] ?? 0;
    $new_order = $status_order[$new_status] ?? 0;
    
    if ($new_order < $current_order) {
        $_SESSION['error'] = 'Cannot move delivery to a previous status.';
        header('Location: deliveries.php');
        exit;
    }
    
    // Update delivery status
    $updateStmt = $conn->prepare("UPDATE deliveries SET status = ?, updated_at = NOW() WHERE id = ?");
    $updateStmt->bind_param('si', $new_status, $delivery_id);
    
    if ($updateStmt->execute()) {
        $_SESSION['success'] = "Delivery #$delivery_id status updated to $new_status.";
        
        // Log activity
        $staff_id = $_SESSION['user_id'];
        $logStmt = $conn->prepare("INSERT INTO activity_log (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        $action = "Delivery Status Updated";
        $details = "Delivery #$delivery_id status updated to $new_status by Staff ID $staff_id";
        $logStmt->bind_param('iss', $staff_id, $action, $details);
        $logStmt->execute();
    } else {
        $_SESSION['error'] = 'Failed to update delivery status.';
    }
    
} catch (Exception $e) {
    $_SESSION['error'] = 'Error updating delivery: ' . $e->getMessage();
}

header('Location: deliveries.php');
exit;
?>
