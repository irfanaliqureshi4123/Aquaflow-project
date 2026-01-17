<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Handle Add Partnership
if (isset($_POST['add_partnership'])) {
    $name = trim($_POST['name']);
    $logo = $_FILES['logo']['name'];
    $target = "../uploads/partnerships/" . basename($logo);
    
    // Create partnerships folder if it doesn't exist
    if (!is_dir("../uploads/partnerships")) {
        mkdir("../uploads/partnerships", 0755, true);
    }
    
    if (!empty($name)) {
        if (!empty($logo) && move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $query = "INSERT INTO partnerships (name, logo) VALUES (?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $name, $logo);
            if ($stmt->execute()) {
                $msg = "✅ Partnership added successfully!";
            } else {
                $msg = "⚠️ Error adding partnership!";
            }
            $stmt->close();
        } else if (empty($logo)) {
            $msg = "⚠️ Please select a logo image!";
        } else {
            $msg = "⚠️ Failed to upload logo!";
        }
    } else {
        $msg = "⚠️ Please enter partnership name!";
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    
    // Get logo filename to delete
    $query = "SELECT logo FROM partnerships WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $partner = $result->fetch_assoc();
    
    if ($partner && !empty($partner['logo'])) {
        $logo_path = "../uploads/partnerships/" . $partner['logo'];
        if (file_exists($logo_path)) {
            unlink($logo_path);
        }
    }
    
    // Delete from database
    $delete_query = "DELETE FROM partnerships WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    if ($delete_stmt->execute()) {
        $msg = "🗑️ Partnership deleted successfully!";
    } else {
        $msg = "⚠️ Error deleting partnership!";
    }
    $delete_stmt->close();
}

// Fetch All Partnerships
$result = $conn->query("SELECT * FROM partnerships ORDER BY id DESC");

include('../includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-gradient-to-r from-cyan-700 to-cyan-800 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Manage Partnerships</h1>
  <p class="opacity-90">Add, Edit, and Delete Partnership Companies</p>
</section>

<!-- MAIN CONTENT -->
<section class="py-10 px-4 bg-gray-50 min-h-screen">
  <div class="max-w-6xl mx-auto">

    <?php if (isset($msg)): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6 flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Add Partnership Form -->
    <div class="bg-white shadow-lg rounded-xl p-8 mb-10 border-l-4 border-cyan-600">
      <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-2">
        <svg class="w-6 h-6 text-cyan-600" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v4h8v-4zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
        ➕ Add New Partnership
      </h2>
      <form action="" method="POST" enctype="multipart/form-data" class="grid md:grid-cols-3 gap-4 items-end">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Partnership Name</label>
          <input type="text" name="name" placeholder="e.g., Company ABC" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" required>
        </div>

        <div>
          <label class="block text-gray-700 font-medium mb-2">Logo Image</label>
          <input type="file" name="logo" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500" accept="image/*" required>
        </div>

        <button type="submit" name="add_partnership" class="bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white py-3 px-6 rounded-lg font-semibold transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd"></path></svg>
          Add Partnership
        </button>
      </form>
    </div>

    <!-- Partnerships Grid -->
    <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if ($result && $result->num_rows > 0): ?>
        <?php while ($partner = $result->fetch_assoc()): ?>
          <div class="bg-white shadow-lg rounded-xl p-6 hover:shadow-xl transition border border-gray-100">
            <!-- Logo Preview -->
            <div class="mb-4 bg-gray-100 rounded-lg p-4 h-40 flex items-center justify-center border-2 border-gray-200">
              <?php if (!empty($partner['logo'])): ?>
                <img src="../uploads/partnerships/<?= htmlspecialchars($partner['logo']) ?>" alt="<?= htmlspecialchars($partner['name']) ?>" class="h-full w-full object-contain">
              <?php else: ?>
                <span class="text-gray-400 text-center">No logo</span>
              <?php endif; ?>
            </div>

            <!-- Partnership Name -->
            <h3 class="text-lg font-bold text-gray-800 mb-2 truncate"><?= htmlspecialchars($partner['name']) ?></h3>

            <!-- Meta Info -->
            <p class="text-xs text-gray-500 mb-4">
              Added: <?= date('M d, Y', strtotime($partner['created_at'])) ?>
            </p>

            <!-- Action Buttons -->
            <div class="flex gap-3">
              <a href="partnerships_edit.php?id=<?= $partner['id'] ?>" class="flex-1 bg-blue-500 hover:bg-blue-600 text-white py-2 px-4 rounded-lg font-medium transition text-center text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
                Edit
              </a>
              <a href="?delete=<?= $partner['id'] ?>" onclick="return confirm('Delete this partnership?')" class="flex-1 bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg font-medium transition text-center text-sm shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
                Delete
              </a>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <div class="md:col-span-2 lg:col-span-3 bg-white rounded-xl shadow p-12 text-center">
          <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v4h8v-4zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"></path></svg>
          <p class="text-xl font-semibold text-gray-600">No partnerships yet</p>
          <p class="text-gray-500 mt-2">Add your first partnership company above</p>
        </div>
      <?php endif; ?>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
