<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Access Control: Only admin can access this page
require_admin();

include '../includes/header.php';

// Check if the form is submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve form data
    $membership_id = $_POST['membership_id'];
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    $reason = $_POST['reason'];
    $logged_by = $_SESSION['admin_id']; // Assuming admin ID is stored in session

    // Validate inputs
    if (!empty($membership_id) && !empty($user_id) && !empty($status) && !empty($reason)) {
        // Prepare SQL query to insert data into membership history table
        $query = "INSERT INTO membership_history (membership_id, user_id, status, reason, logged_by, logged_at) 
                  VALUES (?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('iisss', $membership_id, $user_id, $status, $reason, $logged_by);

        // Execute query and check for success
        if ($stmt->execute()) {
            $success_message = "Membership history successfully recorded.";
        } else {
            $error_message = "Error recording membership history: " . $stmt->error;
        }

        $stmt->close();
    } else {
        $error_message = "All fields are required.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Membership History</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Add Membership History</h1>
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success"><?php echo $success_message; ?></div>
        <?php elseif (isset($error_message)): ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="membership_id">Membership ID:</label>
                <input type="number" id="membership_id" name="membership_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="user_id">User ID:</label>
                <input type="number" id="user_id" name="user_id" class="form-control" required>
            </div>
            <div class="form-group">
                <label for="status">Status:</label>
                <select id="status" name="status" class="form-control" required>
                    <option value="expired">Expired</option>
                    <option value="cancelled">Cancelled</option>
                </select>
            </div>
            <div class="form-group">
                <label for="reason">Reason:</label>
                <textarea id="reason" name="reason" class="form-control" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Record History</button>
        </form>
    </div>
</body>
</html>