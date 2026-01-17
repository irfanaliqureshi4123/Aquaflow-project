<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle delete log action
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $conn->query("DELETE FROM activity_log WHERE id = $id");
  $success = "🗑️ Log deleted successfully!";
}

// Search and filter
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : '';

$query = "SELECT * FROM activity_log WHERE 1";
if ($search) {
  $query .= " AND (action LIKE '%$search%' OR username LIKE '%$search%' OR role LIKE '%$search%')";
}
if ($filter) {
  $query .= " AND role = '$filter'";
}
$query .= " ORDER BY created_at DESC";
$logs = $conn->query($query);
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">🧾 Activity Log</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>

  <!-- Filters -->
  <div class="bg-white p-4 rounded-lg shadow-md mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
    <form method="GET" class="flex gap-2">
      <input type="text" name="search" placeholder="🔍 Search logs..."
             value="<?= htmlspecialchars($search) ?>"
             class="border rounded-lg px-4 py-2 w-64 focus:ring-2 focus:ring-cyan-500">
      <select name="filter" class="border rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-500">
        <option value="">All Roles</option>
        <option value="Admin" <?= $filter == 'Admin' ? 'selected' : '' ?>>Admin</option>
        <option value="Staff" <?= $filter == 'Staff' ? 'selected' : '' ?>>Staff</option>
      </select>
      <button class="bg-cyan-600 hover:bg-cyan-700 text-white px-5 py-2 rounded-lg transition">
        Filter
      </button>
    </form>
    <a href="activity_log.php" class="text-sm text-gray-600 hover:underline">🔄 Reset Filters</a>
  </div>

  <!-- Log Table -->
  <div class="bg-white rounded-xl shadow-md overflow-x-auto">
    <table class="min-w-full text-sm text-gray-700">
      <thead class="bg-cyan-600 text-white text-left">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">User</th>
          <th class="px-6 py-3">Role</th>
          <th class="px-6 py-3">Action</th>
          <th class="px-6 py-3">IP Address</th>
          <th class="px-6 py-3">Date</th>
          <th class="px-6 py-3 text-right">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($logs->num_rows > 0): ?>
          <?php while ($row = $logs->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-6 py-3"><?= $row['id'] ?></td>
              <td class="px-6 py-3 font-medium"><?= htmlspecialchars($row['username']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['role']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['action']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['ip_address']) ?></td>
              <td class="px-6 py-3 text-gray-500"><?= date("Y-m-d H:i", strtotime($row['created_at'])) ?></td>
              <td class="px-6 py-3 text-right">
                <a href="?delete=<?= $row['id'] ?>"
                   onclick="return confirm('Delete this log entry?')"
                   class="text-red-600 hover:underline">🗑️ Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="7" class="text-center py-6 text-gray-500">No activity logs found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('includes/footer.php'); ?>
