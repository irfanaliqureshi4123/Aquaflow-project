<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Fetch all users
$result = $conn->query("SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Manage Users - AquaFlow Admin</title>
  <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50 min-h-screen">
  <!-- Header -->
  <section class="bg-blue-700 text-white py-6 text-center shadow">
    <h1 class="text-3xl font-bold">👥 Manage Users</h1>
    <p class="opacity-80">Add, Edit, or Remove system users</p>
  </section>

  <div class="max-w-6xl mx-auto mt-10 p-6 bg-white rounded-xl shadow-lg">
    <!-- Top Bar -->
    <div class="flex justify-between items-center mb-6">
      <h2 class="text-xl font-semibold text-gray-700">All Registered Users</h2>
      <button onclick="openModal()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-semibold shadow">
        + Add User
      </button>
    </div>

    <!-- User Table -->
    <div class="overflow-x-auto">
      <table class="min-w-full border border-gray-200 rounded-lg overflow-hidden">
        <thead class="bg-gray-100">
          <tr>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">#</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Name</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Email</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Role</th>
            <th class="px-4 py-2 text-left text-sm font-semibold text-gray-700">Created</th>
            <th class="px-4 py-2 text-center text-sm font-semibold text-gray-700">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <?php if ($result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="px-4 py-2 text-gray-700"><?= $row['id'] ?></td>
                <td class="px-4 py-2 font-medium text-gray-800"><?= htmlspecialchars($row['name']) ?></td>
                <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                <td class="px-4 py-2">
                  <?php
                  $roleColors = [
                    'admin' => 'bg-red-100 text-red-800',
                    'staff' => 'bg-yellow-100 text-yellow-800',
                    'customer' => 'bg-green-100 text-green-800'
                  ];
                  ?>
                  <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $roleColors[$row['role']] ?>">
                    <?= ucfirst($row['role']) ?>
                  </span>
                </td>
                <td class="px-4 py-2 text-gray-500 text-sm"><?= $row['created_at'] ?></td>
                <td class="px-4 py-2 text-center space-x-2">
                  <button onclick="editUser(<?= htmlspecialchars(json_encode($row)) ?>)"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white px-3 py-1 rounded text-sm">Edit</button>
                  <button onclick="deleteUser(<?= $row['id'] ?>)"
                    class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm">Delete</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="text-center py-6 text-gray-500">No users found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- User Modal -->
  <div id="userModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-md">
      <h3 id="modalTitle" class="text-xl font-semibold mb-4">Add User</h3>
      <form id="userForm" action="../backend/save_user.php" method="POST">
        <input type="hidden" name="id" id="userId">
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Name</label>
          <input type="text" name="name" id="userName" required class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-200">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
          <input type="email" name="email" id="userEmail" required class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-200">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
          <input type="password" name="password" id="userPassword" class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-200">
        </div>
        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
          <select name="role" id="userRole" class="w-full border rounded px-3 py-2 focus:ring focus:ring-blue-200">
            <option value="customer">Customer</option>
            <option value="staff">Staff</option>
            <option value="admin">Admin</option>
          </select>
        </div>
        <div class="flex justify-end gap-3">
          <button type="button" onclick="closeModal()" class="bg-gray-300 px-4 py-2 rounded hover:bg-gray-400">Cancel</button>
          <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Save</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    function openModal() {
      document.getElementById('userModal').classList.remove('hidden');
      document.getElementById('userForm').reset();
      document.getElementById('modalTitle').innerText = "Add User";
      document.getElementById('userId').value = "";
    }
    function closeModal() {
      document.getElementById('userModal').classList.add('hidden');
    }
    function editUser(user) {
      openModal();
      document.getElementById('modalTitle').innerText = "Edit User";
      document.getElementById('userId').value = user.id;
      document.getElementById('userName').value = user.name;
      document.getElementById('userEmail').value = user.email;
      document.getElementById('userRole').value = user.role;
    }
    function deleteUser(id) {
      Swal.fire({
        title: 'Delete User?',
        text: "This action cannot be undone!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
      }).then((result) => {
        if (result.isConfirmed) {
          window.location.href = '../backend/delete_user.php?id=' + id;
        }
      });
    }
  </script>
</body>
</html>
