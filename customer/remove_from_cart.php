<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $cart_id = intval($_POST['cart_id']);
    $user_id = $_SESSION['user_id'];

    // Delete only if the cart item belongs to the logged-in user
    $remove_query = $conn->prepare("
        DELETE FROM cart 
        WHERE id = ? AND user_id = ?
    ");
    $remove_query->bind_param('ii', $cart_id, $user_id);
    
    if ($remove_query->execute() && $remove_query->affected_rows > 0) {
        $_SESSION['cart_success'] = 'Item removed from cart successfully!';
    }

    header('Location: cart.php');
    exit;
}

// Redirect if accessed directly without POST
header('Location: cart.php');
exit;
?>
