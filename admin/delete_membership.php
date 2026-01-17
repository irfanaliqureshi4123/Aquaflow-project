<?php
/**
 * Delete Membership Plan
 * 
 * Admin action to delete a membership/subscription plan.
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);

    // Check if membership has active subscriptions
    $check_stmt = $conn->prepare("SELECT COUNT(*) as count FROM user_memberships WHERE membership_id = ? AND status IN ('active', 'pending')");
    $check_stmt->execute([$id]);
    $check_result = $check_stmt->get_result();
    $check_row = $check_result->fetch_assoc();

    if ($check_row['count'] > 0) {
        header("Location: memberships.php?tab=plans&error=Cannot delete membership with active subscriptions");
        exit;
    }

    $check_stmt->close();

    // Delete the membership
    $stmt = $conn->prepare("DELETE FROM memberships WHERE id = ?");
    
    if ($stmt->execute([$id])) {
        header("Location: memberships.php?tab=plans&success=Membership deleted successfully");
        exit;
    } else {
        header("Location: memberships.php?tab=plans&error=Error deleting membership");
        exit;
    }

    $stmt->close();
} else {
    header("Location: memberships.php?tab=plans");
    exit;
}

$conn->close();
?>
