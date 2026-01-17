<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// ✅ Get membership ID from URL
$id = $_GET['id'] ?? 0;

if (!$id || !is_numeric($id)) {
    die("<div style='margin:100px auto;text-align:center;font-family:sans-serif;'>⚠️ Invalid Membership ID</div>");
}

// ✅ Check if record exists before deleting
$check = $conn->prepare("SELECT * FROM memberships WHERE id = ?");
$check->bind_param("i", $id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows === 0) {
    die("<div style='margin:100px auto;text-align:center;font-family:sans-serif;'>❌ No membership found with this ID.</div>");
}

// ✅ Delete record securely
$delete = $conn->prepare("DELETE FROM memberships WHERE id = ?");
$delete->bind_param("i", $id);

if ($delete->execute()) {
    // Redirect with success message
    header("Location: user_memberships.php?deleted=1");
    exit();
} else {
    echo "<div style='margin:100px auto;text-align:center;font-family:sans-serif;'>❌ Error deleting membership: " . $conn->error . "</div>";
}
?>
