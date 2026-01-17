<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

if (!isset($_GET['id'])) {
  header("Location: products.php");
  exit;
}

$id = intval($_GET['id']);
$query = $conn->query("SELECT * FROM products WHERE id = $id");
$product = $query->fetch_assoc();

if (!$product) {
  echo "<div class='text-center py-20 text-gray-600 text-lg'>Product not found.</div>";
  include('../includes/footer.php');
  exit;
}

// Handle update
if (isset($_POST['update_product'])) {
  $name = trim($_POST['name']);
  $price = floatval($_POST['price']);
  $stock = intval($_POST['stock']);
  $description = isset($_POST['description']) ? trim($_POST['description']) : '';
  $size = isset($_POST['size']) ? trim($_POST['size']) : '';

  $image = $product['image']; // default old image
  if (!empty($_FILES['image']['name'])) {
    $image = $_FILES['image']['name'];
    $target = "../uploads/" . basename($image);
    move_uploaded_file($_FILES['image']['tmp_name'], $target);
  }

  $update_query = "UPDATE products 
                   SET name='$name', description='$description', size='$size', price='$price', stock='$stock', image='$image'
                   WHERE id=$id";

  if ($conn->query($update_query)) {
    $msg = "✅ Product updated successfully!";
    $query = $conn->query("SELECT * FROM products WHERE id = $id");
    $product = $query->fetch_assoc();
  } else {
    $msg = "⚠️ Error updating product: " . $conn->error;
  }
}
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Edit Product</h1>
  <p class="opacity-90">Update product details and image</p>
</section>

<!-- MAIN CONTENT -->
<section class="py-10 px-4 bg-gray-50 min-h-screen">
  <div class="max-w-3xl mx-auto bg-white shadow-md rounded-xl p-8">

    <?php if (isset($msg)): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6">
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="grid gap-5">
      <div>
        <label class="block font-medium text-gray-700 mb-1">Product Name</label>
        <input type="text" name="name" value="<?= htmlspecialchars($product['name']) ?>" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
      </div>

      <div>
        <label class="block font-medium text-gray-700 mb-1">Description</label>
        <textarea name="description" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" rows="3"><?= htmlspecialchars($product['description'] ?? '') ?></textarea>
      </div>

      <div>
        <label class="block font-medium text-gray-700 mb-1">Size</label>
        <input type="text" name="size" value="<?= htmlspecialchars($product['size'] ?? '') ?>" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500">
      </div>

      <div class="grid md:grid-cols-2 gap-5">
        <div>
          <label class="block font-medium text-gray-700 mb-1">Price (Rs)</label>
          <input type="number" step="0.01" name="price" value="<?= htmlspecialchars($product['price']) ?>" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
        </div>

        <div>
          <label class="block font-medium text-gray-700 mb-1">Stock Quantity</label>
          <input type="number" name="stock" value="<?= htmlspecialchars($product['stock']) ?>" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
        </div>
      </div>

      <div>
        <label class="block font-medium text-gray-700 mb-1">Product Image</label>
        <div class="flex items-center gap-6">
          <img src="../uploads/<?= htmlspecialchars($product['image']) ?>" alt="Product Image" class="w-24 h-24 rounded-lg object-cover border">
          <input type="file" name="image" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" accept="image/*">
        </div>
        <p class="text-sm text-gray-500 mt-1">Leave blank to keep existing image</p>
      </div>

      <div class="flex justify-between items-center mt-6">
        <a href="products.php" class="bg-gray-200 hover:bg-gray-300 text-gray-800 font-medium px-6 py-2 rounded-md transition">← Back</a>
        <button type="submit" name="update_product" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold px-8 py-2 rounded-md transition">Update Product</button>
      </div>
    </form>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
