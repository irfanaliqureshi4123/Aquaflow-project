<?php
session_start();
include('../includes/db_connect.php');

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// Fetch product details
$product_id = isset($_GET['product']) ? intval($_GET['product']) : 0;
$product_query = $conn->prepare("SELECT * FROM products WHERE id = ?");
$product_query->bind_param('i', $product_id);
$product_query->execute();
$product_result = $product_query->get_result();

if ($product_result->num_rows === 0) {
    die("Product not found.");
}

$product = $product_result->fetch_assoc();

// Handle add to cart submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $quantity = intval($_POST['quantity']);
    $user_id = $_SESSION['user_id'];

    $cart_query = $conn->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
    $cart_query->bind_param('iii', $user_id, $product_id, $quantity);

    if ($cart_query->execute()) {
        echo "<p>Product added to cart successfully!</p>";
    } else {
        echo "<p>Failed to add product to cart. Please try again.</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add to Cart</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 50px auto;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 20px;
        }
        h2 {
            font-size: 20px;
            color: #555;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            color: #666;
            margin-bottom: 20px;
        }
        form {
            display: flex;
            flex-direction: column;
        }
        label {
            font-size: 14px;
            color: #333;
            margin-bottom: 5px;
        }
        input[type="number"] {
            padding: 10px;
            font-size: 16px;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        button {
            padding: 10px;
            font-size: 16px;
            color: #fff;
            background-color: #007BFF;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        button:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Add to Cart</h1>
        <h2><?= htmlspecialchars($product['name']) ?></h2>
        <p>Price: Rs <?= htmlspecialchars($product['price']) ?></p>
        <form method="POST" action="cart_add.php">
            <label for="quantity">Quantity:</label>
            <input type="number" name="quantity" id="quantity" value="1" min="1" max="99" required>
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['id']) ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <button type="submit">Add to Cart</button>
        </form>
    </div>
</body>
</html>