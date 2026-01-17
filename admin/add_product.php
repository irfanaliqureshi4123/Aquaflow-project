<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

if (isset($_POST['add_product'])) {
  $name = trim($_POST['name']);
  $price = floatval($_POST['price']);
  $stock = intval($_POST['stock']);
  $description = isset($_POST['description']) ? trim($_POST['description']) : '';
  $size = isset($_POST['size']) ? trim($_POST['size']) : '';

  $image = '';
  if (!empty($_FILES['image']['name'])) {
    $image = 'uploads/' . basename($_FILES['image']['name']);
    $target = "../" . $image;
    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target)) {
      $msg = "⚠️ Failed to upload image.";
    }
  }

  $query = "INSERT INTO products (name, description, size, price, stock, image, created_at) 
            VALUES ('$name', '$description', '$size', '$price', '$stock', '$image', NOW())";

  if ($conn->query($query)) {
    $msg = "✅ Product added successfully!";
  } else {
    $msg = "⚠️ Error: " . $conn->error;
  }
}
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Add New Product</h1>
  <p class="opacity-90">Create a new product entry for AquaFlow Inventory</p>
</section>

<!-- ADD PRODUCT FORM -->
<section class="py-10 bg-gray-50 min-h-screen">
  <div class="max-w-3xl mx-auto bg-white shadow-md rounded-xl p-8">

    <?php if (isset($msg)): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="grid gap-5">
      <!-- Product Name -->
      <div>
        <label class="block font-medium text-gray-700 mb-1">Product Name</label>
        <input 
          type="text" 
          name="name" 
          placeholder="Enter product name"
          class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" 
          required>
      </div>

      <!-- Description -->
      <div>
        <label class="block font-medium text-gray-700 mb-1">Description</label>
        <textarea
          name="description"
          placeholder="Short product description"
          class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500"
          rows="3"></textarea>
      </div>

      <!-- Size -->
      <div>
        <label class="block font-medium text-gray-700 mb-1">Size</label>
        <input
          type="text"
          name="size"
          placeholder="e.g. 1L, 5L, 20L"
          class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500">
      </div>

      <!-- Price & Stock -->
      <div class="grid md:grid-cols-2 gap-5">
        <div>
          <label class="block font-medium text-gray-700 mb-1">Price (Rs)</label>
          <input 
            type="number" 
            step="0.01" 
            name="price" 
            placeholder="Enter price"
            class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" 
            required>
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1">Stock Quantity</label>
          <input 
            type="number" 
            name="stock" 
            placeholder="Enter stock quantity"
            class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" 
            required>
        </div>
      </div>

      <!-- Product Image -->
      <div>
        <label class="block font-medium text-gray-700 mb-1">Product Image</label>
        <input 
          type="file" 
          name="image" 
          accept="image/*"
          class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500"
          required>
      </div>

      <!-- Submit -->
      <div class="flex justify-between items-center mt-6">
        <a href="products.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-2 rounded-md transition">
          ← Back
        </a>
        <button 
          type="submit" 
          name="add_product"
          class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold px-8 py-2 rounded-md transition">
          Add Product
        </button>
      </div>
    </form>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
