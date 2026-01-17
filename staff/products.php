<?php
include '../includes/db_connect.php';

// Session is already started in header, no need to start again
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'staff') {
    header('Location: ../login.php');
    exit();
}

// Get available products (remove quantity column if it doesn't exist)
$products_query = "SELECT id, name, description, price, size, stock, image FROM products ORDER BY name";
$products_result = $conn->query($products_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Products & Memberships - Staff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-gray-50">
    <?php include '../includes/header.php'; ?>

    <main class="min-h-screen py-8">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Page Title -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Products & Memberships</h1>
                <p class="text-gray-600">View available products and active membership subscribers</p>
            </div>

            <!-- Available Products Section -->
            <div class="mb-12">
                <div class="flex items-center mb-6">
                    <i class="fas fa-box text-cyan-600 text-2xl mr-3"></i>
                    <h2 class="text-2xl font-bold text-gray-800">Available Products</h2>
                </div>

                <?php if ($products_result && $products_result->num_rows > 0): ?>
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <?php while ($product = $products_result->fetch_assoc()): ?>
                            <div class="bg-white rounded-lg shadow-md hover:shadow-lg transition overflow-hidden">
                                <!-- Product Image -->
                                <div class="h-48 bg-gray-200 overflow-hidden">
                                    <?php if (!empty($product['image']) && file_exists("../uploads/" . $product['image'])): ?>
                                        <img src="../../uploads/<?php echo htmlspecialchars($product['image']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <div class="w-full h-full flex items-center justify-center bg-gray-300">
                                            <i class="fas fa-image text-gray-500 text-4xl"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Product Info -->
                                <div class="p-6">
                                    <div class="flex justify-between items-start mb-3">
                                        <h3 class="text-lg font-bold text-gray-800"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded">In Stock</span>
                                    </div>
                                    
                                    <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars($product['description']); ?></p>
                                    
                                    <div class="border-t border-gray-200 pt-3 mt-3">
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-gray-700 font-medium">Price:</span>
                                            <span class="text-cyan-600 font-bold text-lg">Rs. <?php echo number_format($product['price'], 2); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center mb-2">
                                            <span class="text-gray-700 font-medium">Size:</span>
                                            <span class="text-gray-600"><?php echo htmlspecialchars($product['size']); ?></span>
                                        </div>
                                        <div class="flex justify-between items-center">
                                            <span class="text-gray-700 font-medium">Stock:</span>
                                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded font-bold"><?php echo intval($product['stock']); ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">
                        <i class="fas fa-info-circle text-yellow-600 text-2xl mb-3"></i>
                        <p class="text-yellow-800">No active products available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <?php include '../includes/footer.php'; ?>
</body>
</html>
