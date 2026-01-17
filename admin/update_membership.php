<?php
/**
 * Update Membership Plan (Legacy Handler)
 * 
 * This file handles direct POST updates from forms.
 * For full membership management, use edit_membership.php
 * 
 * @author AquaWater Team
 * @version 1.0
 */

session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = intval($_POST['id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = floatval($_POST['price'] ?? 0);
    $duration_days = intval($_POST['duration_days'] ?? 30);
    $bottles_per_week = intval($_POST['bottles_per_week'] ?? 4);

    if ($id > 0 && !empty($name)) {
        $stmt = $conn->prepare("
            UPDATE memberships 
            SET name = ?, description = ?, price = ?, duration_days = ?, bottles_per_week = ? 
            WHERE id = ?
        ");
        
        if ($stmt->execute([$name, $description, $price, $duration_days, $bottles_per_week, $id])) {
            header("Location: memberships.php?tab=plans&success=Membership updated successfully");
            exit;
        } else {
            header("Location: edit_membership.php?id=$id&error=Error updating membership");
            exit;
        }

        $stmt->close();
    } else {
        header("Location: memberships.php?tab=plans");
        exit;
    }
}

$conn->close();
?>
