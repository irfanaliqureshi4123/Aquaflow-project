<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Set JSON header
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id']) && isset($_POST['type'])) {

    $cart_id = intval($_POST['id']);
    $type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);
    $user_id = $_SESSION['user_id'];
    
    // Validate type
    if (!in_array($type, ['plus', 'minus'])) {
        echo json_encode(['success' => false, 'msg' => 'Invalid type']);
        exit;
    }

    // Fetch current quantity + price
    $query = $conn->prepare("
        SELECT c.quantity, p.price 
        FROM cart c 
        JOIN products p ON c.product_id = p.id 
        WHERE c.id = ? AND c.user_id = ?
    ");
    $query->bind_param("ii", $cart_id, $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'msg' => 'Cart item not found']);
        exit;
    }

    $row = $result->fetch_assoc();
    $quantity = $row['quantity'];
    $price = $row['price'];

    // Change quantity
    if ($type === "plus") {
        $quantity++;
        // Optional: Add max quantity limit
        if ($quantity > 99) {
            $quantity = 99;
        }
    } elseif ($type === "minus") {
        if ($quantity > 1) {
            $quantity--;
        } else {
            echo json_encode(['success' => false, 'msg' => 'Minimum quantity is 1']);
            exit;
        }
    }

    // Update in database
    $update = $conn->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
    $update->bind_param("iii", $quantity, $cart_id, $user_id);
    
    if (!$update->execute()) {
        echo json_encode(['success' => false, 'msg' => 'Failed to update quantity']);
        exit;
    }

    // New total
    $total_price = $quantity * $price;

    echo json_encode([
        'success' => true,
        'qty' => $quantity,
        'total' => number_format($total_price, 2)
    ]);
    exit;
}

echo json_encode(['success' => false, 'msg' => 'Invalid request']);
exit;
