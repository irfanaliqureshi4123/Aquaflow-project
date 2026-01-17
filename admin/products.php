<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle Product Addition
if (isset($_POST['add_product'])) {
  $name = trim($_POST['name']);
  $price = floatval($_POST['price']);
  $stock = intval($_POST['stock']);
  $description = isset($_POST['description']) ? trim($_POST['description']) : '';
  $size = isset($_POST['size']) ? trim($_POST['size']) : '';
  $image = $_FILES['image']['name'];
  $target = "../uploads/" . basename($image);

  if (move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
    $query = "INSERT INTO products (name, description, size, price, stock, image, created_at) VALUES ('$name', '$description', '$size', '$price', '$stock', '$image', NOW())";
    $conn->query($query);
    $msg = "✅ Product added successfully!";
  } else {
    $msg = "⚠️ Failed to upload image!";
  }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    $conn->query("DELETE FROM products WHERE id = $id");
    $msg = "🗑️ Product deleted successfully!";
}

// Fetch All Products
$result = $conn->query("SELECT * FROM products ORDER BY id DESC");
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Manage Products</h1>
  <p class="opacity-90">Add, Edit, and Delete Inventory</p>
</section>

<!-- MAIN CONTENT -->
<section class="py-10 px-4 bg-gray-50 min-h-screen">
  <div class="max-w-6xl mx-auto">

    <?php if (isset($msg)): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Add Product Form -->
    <div class="bg-white shadow-md rounded-xl p-6 mb-10">
      <h2 class="text-xl font-semibold text-gray-800 mb-4">➕ Add New Product</h2>
      <form action="" method="POST" enctype="multipart/form-data" class="grid md:grid-cols-2 gap-4">
        <input type="text" name="name" placeholder="Product Name" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500" required>
        <input type="number" name="price" placeholder="Price" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500" required>

        <!-- Description (spans full width) -->
        <textarea name="description" placeholder="Short product description" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500 md:col-span-2" rows="3"></textarea>

        <input type="number" name="stock" placeholder="Stock Quantity" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500" required>
        <input type="text" name="size" placeholder="Size (e.g. 1L, 5L)" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500">
        <input type="file" name="image" class="border rounded-lg p-2 focus:ring-2 focus:ring-cyan-500" accept="image/*" required>
        <button type="submit" name="add_product" class="bg-cyan-600 hover:bg-cyan-700 text-white py-2 px-4 rounded-lg font-medium transition">Add Product</button>
      </form>
    </div>

    <!-- Products Table -->
    <div class="bg-white shadow-lg rounded-xl p-6 overflow-x-auto">
      <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center">
        <svg class="w-6 h-6 mr-2 text-cyan-600" fill="currentColor" viewBox="0 0 20 20"><path d="M3 1a1 1 0 000 2h1.22l.305 1.222a.997.997 0 00.01.042l1.358 5.43-.893.892C3.74 11.846 4.632 14 6.414 14H15a1 1 0 000-2H6.414l1-1H14a1 1 0 00.894-.553l3-6A1 1 0 0017 6H6.28l-.31-1.243A1 1 0 005 4H3a1 1 0 000 2h1.64L7.757 15H5a1 1 0 100 2h12a1 1 0 100-2H7.757L5.413 6H3z"></path></svg>
        📦 Product Inventory
      </h2>

      <table class="w-full">
        <thead>
          <tr class="bg-gradient-to-r from-cyan-600 to-cyan-700 text-white shadow-md">
            <th class="px-6 py-4 text-left font-semibold text-sm">Image</th>
            <th class="px-6 py-4 text-left font-semibold text-sm">Product Name</th>
            <th class="px-6 py-4 text-left font-semibold text-sm">Size</th>
            <th class="px-6 py-4 text-center font-semibold text-sm">Price (Rs)</th>
            <th class="px-6 py-4 text-center font-semibold text-sm">Stock</th>
            <th class="px-6 py-4 text-center font-semibold text-sm">Action</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-200">
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="hover:bg-cyan-50 transition duration-200 border-b border-gray-100">
              <td class="px-6 py-4">
                <div class="w-20 h-20 bg-gray-100 rounded-lg overflow-hidden shadow hover:shadow-lg transition flex items-center justify-center border-2 border-gray-200">
                  <img src="../uploads/<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="w-full h-full object-contain p-2">
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($row['name']) ?></span>
              </td>
              <td class="px-6 py-4">
                <span class="inline-block bg-blue-100 text-blue-800 px-3 py-1 rounded-full text-xs font-medium"><?= htmlspecialchars($row['size']) ?></span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="font-bold text-cyan-600 text-lg">Rs <?= number_format($row['price'], 2) ?></span>
              </td>
              <td class="px-6 py-4 text-center">
                <span class="inline-block px-4 py-2 rounded-lg font-semibold text-sm <?= $row['stock'] > 500 ? 'bg-green-100 text-green-800' : ($row['stock'] > 100 ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800') ?>">
                  <?= $row['stock'] ?>
                </span>
              </td>
              <td class="px-6 py-4 text-center">
                <div class="flex justify-center gap-3">
                  <a href="products_edit.php?id=<?= $row['id'] ?>" class="inline-flex items-center gap-1 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                    Edit
                  </a>
                  <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this product?')" class="inline-flex items-center gap-1 bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-medium text-sm transition shadow-md hover:shadow-lg">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                    Delete
                  </a>
                </div>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
