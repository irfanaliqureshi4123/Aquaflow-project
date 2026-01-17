<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $conn->query("DELETE FROM staff WHERE id = $id");
  header("Location: staff.php");
  exit;
}

include('../includes/header.php');

// Handle Add Staff
if (isset($_POST['add_staff'])) {
  $name = trim($_POST['name']);
  $email = trim($_POST['email']);
  $role = trim($_POST['role']);
  $password = isset($_POST['password']) ? password_hash($_POST['password'], PASSWORD_DEFAULT) : password_hash('default123', PASSWORD_DEFAULT);

  // Check if password column exists
  $check_col = $conn->query("SHOW COLUMNS FROM staff LIKE 'password'");
  
  if ($check_col && $check_col->num_rows > 0) {
    // Password column exists
    $query = "INSERT INTO staff (name, email, role, password) VALUES ('$name', '$email', '$role', '$password')";
  } else {
    // Password column doesn't exist, add it first
    $conn->query("ALTER TABLE staff ADD COLUMN password VARCHAR(255) DEFAULT NULL");
    $query = "INSERT INTO staff (name, email, role, password) VALUES ('$name', '$email', '$role', '$password')";
  }
  
  $conn->query($query);
}

// Fetch staff
$result = $conn->query("SELECT * FROM staff ORDER BY id DESC");
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Manage Staff</h1>
  <p class="opacity-90">Add, Edit or Remove Staff Accounts</p>
</section>

<!-- CONTENT -->
<section class="py-10 bg-gray-50 min-h-screen">
  <div class="max-w-6xl mx-auto bg-white shadow-md rounded-xl p-6">

    <!-- Add Staff Button -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-2xl font-semibold text-gray-800">Staff List</h2>
      <button onclick="openModal()" class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2 rounded-lg font-medium shadow">
        + Add Staff
      </button>
    </div>

    <!-- STAFF TABLE -->
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-cyan-700 text-white">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Role</th>
            <th class="px-6 py-3 text-right text-sm font-semibold uppercase tracking-wider">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-gray-700"><?= $i++ ?></td>
                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['name']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                <td class="px-6 py-4 text-gray-600 capitalize"><?= htmlspecialchars($row['role']) ?></td>
                <td class="px-6 py-4 text-right">
                  <button 
                    onclick="openEditModal(<?= $row['id'] ?>, '<?= htmlspecialchars($row['name']) ?>', '<?= htmlspecialchars($row['email']) ?>', '<?= htmlspecialchars($row['role']) ?>')"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded-md text-sm font-medium mr-2">
                    Edit
                  </button>
                  <a href="?delete=<?= $row['id'] ?>" onclick="return confirm('Are you sure you want to delete this staff?')"
                    class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm font-medium">
                    Delete
                  </a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="5" class="text-center py-6 text-gray-500">No staff found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- ADD / EDIT MODAL -->
<div id="staffModal" class="fixed inset-0 bg-black bg-opacity-40 hidden justify-center items-center z-50">
  <div class="bg-white rounded-xl shadow-xl w-96 p-6">
    <h2 id="modalTitle" class="text-xl font-semibold mb-4">Add Staff</h2>

    <form id="staffForm" action="" method="POST" class="grid gap-4">
      <input type="hidden" name="staff_id" id="staff_id">

      <div>
        <label class="block text-gray-700 font-medium mb-1">Name</label>
        <input type="text" name="name" id="name" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="email" name="email" id="email" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Role</label>
        <select name="role" id="role" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
          <option value="staff">Staff</option>
          <option value="manager">Manager</option>
          <option value="delivery">Delivery</option>
        </select>
      </div>

      <div id="passwordSection">
        <label class="block text-gray-700 font-medium mb-1">Password</label>
        <input type="password" name="password" id="password" placeholder="Enter password" class="border rounded-lg p-2 w-full focus:ring-2 focus:ring-cyan-500" required>
      </div>

      <div class="flex justify-between items-center mt-4">
        <button type="button" onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 text-gray-800 font-medium px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" name="add_staff" id="submitBtn" class="bg-cyan-600 hover:bg-cyan-700 text-white font-semibold px-6 py-2 rounded-md">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
  const modal = document.getElementById('staffModal');
  const modalTitle = document.getElementById('modalTitle');
  const passwordSection = document.getElementById('passwordSection');
  const staffForm = document.getElementById('staffForm');

  function openModal() {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modalTitle.textContent = 'Add Staff';
    staffForm.reset();
    passwordSection.style.display = 'block';
  }

  function openEditModal(id, name, email, role) {
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    modalTitle.textContent = 'Edit Staff';
    document.getElementById('staff_id').value = id;
    document.getElementById('name').value = name;
    document.getElementById('email').value = email;
    document.getElementById('role').value = role;
    passwordSection.style.display = 'none'; // password not editable here
  }

  function closeModal() {
    modal.classList.add('hidden');
  }
</script>

<?php include('../includes/footer.php'); ?>
