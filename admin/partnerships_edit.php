<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    header('Location: partnerships.php');
    exit;
}

// Fetch partnership
$query = "SELECT * FROM partnerships WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    header('Location: partnerships.php');
    exit;
}

$partner = $result->fetch_assoc();

// Handle Update
if (isset($_POST['update_partnership'])) {
    $name = trim($_POST['name']);
    $logo = $partner['logo'];
    
    // Create partnerships folder if it doesn't exist
    if (!is_dir("../uploads/partnerships")) {
        mkdir("../uploads/partnerships", 0755, true);
    }
    
    // Handle new logo upload
    if (!empty($_FILES['logo']['name'])) {
        // Delete old logo
        if (!empty($partner['logo'])) {
            $old_path = "../uploads/partnerships/" . $partner['logo'];
            if (file_exists($old_path)) {
                unlink($old_path);
            }
        }
        
        $logo = $_FILES['logo']['name'];
        $target = "../uploads/partnerships/" . basename($logo);
        
        if (!move_uploaded_file($_FILES['logo']['tmp_name'], $target)) {
            $msg = "⚠️ Failed to upload logo!";
            $logo = $partner['logo'];
        } else {
            $msg = "✅ Partnership updated successfully!";
        }
    } else {
        $msg = "✅ Partnership updated successfully!";
    }
    
    // Update in database
    $update_query = "UPDATE partnerships SET name = ?, logo = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('ssi', $name, $logo, $id);
    $update_stmt->execute();
    $update_stmt->close();
    
    // Refresh partner data
    $query = "SELECT * FROM partnerships WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $partner = $result->fetch_assoc();
}

include('../includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-gradient-to-r from-cyan-700 to-cyan-800 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Edit Partnership</h1>
  <p class="opacity-90">Update partnership details</p>
</section>

<!-- MAIN CONTENT -->
<section class="py-10 px-4 bg-gray-50 min-h-screen">
  <div class="max-w-4xl mx-auto">

    <!-- Back Link -->
    <div class="mb-6">
      <a href="partnerships.php" class="text-cyan-600 hover:text-cyan-700 font-semibold flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path></svg>
        Back to Partnerships
      </a>
    </div>

    <?php if (isset($msg)): ?>
      <div class="bg-green-100 border border-green-300 text-green-800 px-4 py-3 rounded mb-6 flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path></svg>
        <?= htmlspecialchars($msg) ?>
      </div>
    <?php endif; ?>

    <!-- Edit Form -->
    <div class="bg-white shadow-lg rounded-xl p-8 border-l-4 border-cyan-600">
      <h2 class="text-2xl font-bold text-gray-800 mb-8">Update Partnership Details</h2>

      <form method="POST" enctype="multipart/form-data">
        <div class="grid md:grid-cols-2 gap-8">
          
          <!-- Left: Form Fields -->
          <div>
            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Partnership Name</label>
              <input type="text" name="name" value="<?= htmlspecialchars($partner['name']) ?>" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500 focus:border-transparent" required>
            </div>

            <div class="mb-6">
              <label class="block text-gray-700 font-medium mb-2">Update Logo (Optional)</label>
              <input type="file" name="logo" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-cyan-500" accept="image/*">
              <p class="text-sm text-gray-500 mt-2">Leave empty to keep current logo</p>
            </div>

            <button type="submit" name="update_partnership" class="bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white w-full py-3 px-6 rounded-lg font-semibold transition shadow-md hover:shadow-lg flex items-center justify-center gap-2">
              <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path></svg>
              Update Partnership
            </button>
          </div>

          <!-- Right: Current Logo Preview -->
          <div>
            <label class="block text-gray-700 font-medium mb-2">Current Logo</label>
            <div class="bg-gray-100 rounded-lg p-6 h-80 flex items-center justify-center border-2 border-gray-200">
              <?php if (!empty($partner['logo'])): ?>
                <img src="../uploads/partnerships/<?= htmlspecialchars($partner['logo']) ?>" alt="<?= htmlspecialchars($partner['name']) ?>" class="h-full w-full object-contain">
              <?php else: ?>
                <span class="text-gray-400">No logo</span>
              <?php endif; ?>
            </div>
            <p class="text-xs text-gray-500 mt-3">Added: <?= date('M d, Y', strtotime($partner['created_at'])) ?></p>
          </div>

        </div>
      </form>
    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
