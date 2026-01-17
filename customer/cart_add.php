<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Handle add to cart
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }

    if (!isset($_POST['product_id']) || !isset($_POST['quantity'])) {
        die("Invalid request.");
    }

    $product_id = intval($_POST['product_id']);
    $quantity   = intval($_POST['quantity']);

    // Validate quantity
    if ($quantity < 1 || $quantity > 99) {
        die("Invalid quantity. Must be between 1 and 99.");
    }

    // Check if product exists
    $product_query = $conn->prepare("SELECT id FROM products WHERE id = ?");
    $product_query->bind_param("i", $product_id);
    $product_query->execute();
    $product_result = $product_query->get_result();

    if ($product_result->num_rows === 0) {
        die("Product not found.");
    }

    // Check if product already in cart
    $existing_cart_query = $conn->prepare("
        SELECT id 
        FROM cart 
        WHERE user_id = ? AND product_id = ?
    ");
    $existing_cart_query->bind_param("ii", $user_id, $product_id);
    $existing_cart_query->execute();
    $existing_cart_result = $existing_cart_query->get_result();

    if ($existing_cart_result->num_rows > 0) {
        // Update quantity (add to existing)
        $row = $existing_cart_result->fetch_assoc();
        $cart_id = $row['id'];

        $update_query = $conn->prepare("
            UPDATE cart 
            SET quantity = quantity + ?, updated_at = NOW() 
            WHERE id = ? AND user_id = ?
        ");
        $update_query->bind_param("iii", $quantity, $cart_id, $user_id);
        
        if (!$update_query->execute()) {
            die("Failed to update cart.");
        }

    } else {
        // Add new cart item
        $insert_query = $conn->prepare("
            INSERT INTO cart (user_id, product_id, quantity, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
        ");
        $insert_query->bind_param("iii", $user_id, $product_id, $quantity);
        
        if (!$insert_query->execute()) {
            die("Failed to add to cart.");
        }
    }

    // Redirect to cart with success message
    $_SESSION['cart_success'] = 'Product added to cart successfully!';
    header("Location: cart.php");
    exit;
}

// Redirect if accessed directly
header("Location: ../products.php");
exit;
?>
