<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch users (only customers)
$users = $conn->query("SELECT id, name, email FROM users WHERE role='customer' ORDER BY name");

// Fetch all active membership plans
$plans = $conn->query("SELECT DISTINCT plan_name FROM memberships ORDER BY plan_name ASC");

// Search filter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$query = "
  SELECT m.id, u.name AS customer_name, u.email, m.plan_name, 
         m.delivery_frequency, m.bottles_per_delivery, 
         m.start_date, m.end_date, m.status
  FROM memberships m
  JOIN users u ON m.user_id = u.id
  WHERE 1
";
if (!empty($search)) {
  $query .= " AND (u.name LIKE '%$search%' OR m.plan_name LIKE '%$search%' OR u.email LIKE '%$search%')";
}
$query .= " ORDER BY m.start_date DESC";
$result = $conn->query($query);
?>

<section class="bg-cyan-700 text-white text-center py-12">
  <div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold">Manage User Memberships</h1>
    <p class="text-lg opacity-90 mt-2">Assign and manage customer subscription plans</p>
  </div>
</section>

<div class="container mx-auto px-4 py-10">
  <!-- Top Actions -->
  <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" name="search" placeholder="Search user or plan..."
        value="<?= htmlspecialchars($search); ?>"
        class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 w-full md:w-64">
      <button type="submit"
        class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium">Search</button>
    </form>

    <button onclick="openModal('addModal')"
      class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium flex items-center gap-2">
      <i class="fas fa-plus"></i> Assign Membership
    </button>
  </div>

  <!-- Membership Table -->
  <div class="overflow-x-auto bg-white rounded-xl shadow">
    <table class="min-w-full border border-gray-200 text-sm text-left">
      <thead class="bg-cyan-700 text-white">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">Customer</th>
          <th class="px-6 py-3">Email</th>
          <th class="px-6 py-3">Plan</th>
          <th class="px-6 py-3">Frequency</th>
          <th class="px-6 py-3">Bottles/Delivery</th>
          <th class="px-6 py-3">Start</th>
          <th class="px-6 py-3">End</th>
          <th class="px-6 py-3">Status</th>
          <th class="px-6 py-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): $i = 1; ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-6 py-4"><?= $i++; ?></td>
              <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['customer_name']); ?></td>
              <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['email']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['plan_name']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['delivery_frequency']); ?></td>
              <td class="px-6 py-4 text-center"><?= htmlspecialchars($row['bottles_per_delivery']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['start_date']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['end_date']); ?></td>
              <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs <?= $row['status'] == 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'; ?>">
                  <?= ucfirst($row['status']); ?>
                </span>
              </td>
              <td class="px-6 py-4 text-center space-x-2">
                <button onclick="openEditModal(<?= htmlspecialchars(json_encode($row)); ?>)"
                  class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
                <a href="delete_user_membership.php?id=<?= $row['id']; ?>"
                  class="text-red-600 hover:text-red-800 font-semibold"
                  onclick="return confirm('Are you sure you want to remove this membership?')">Delete</a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="10" class="text-center py-6 text-gray-500">No memberships found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Membership Modal -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
    <h2 class="text-xl font-bold mb-4 text-cyan-700">Assign New Membership</h2>
    <form method="POST" action="add_user_membership.php" class="space-y-4">
      <select name="user_id" required class="w-full border rounded-md px-4 py-2">
        <option value="">Select Customer</option>
        <?php while ($u = $users->fetch_assoc()): ?>
          <option value="<?= $u['id']; ?>"><?= htmlspecialchars($u['name']); ?> (<?= htmlspecialchars($u['email']); ?>)</option>
        <?php endwhile; ?>
      </select>

      <select name="plan_name" required class="w-full border rounded-md px-4 py-2">
        <option value="">Select Plan</option>
        <?php while ($p = $plans->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($p['plan_name']); ?>"><?= htmlspecialchars($p['plan_name']); ?></option>
        <?php endwhile; ?>
      </select>

      <input type="text" name="delivery_frequency" placeholder="Delivery Frequency (e.g. Weekly)" required class="w-full border rounded-md px-4 py-2">
      <input type="number" name="bottles_per_delivery" placeholder="Bottles per Delivery" required class="w-full border rounded-md px-4 py-2">
      <input type="date" name="start_date" required class="w-full border rounded-md px-4 py-2">
      <input type="date" name="end_date" required class="w-full border rounded-md px-4 py-2">
      <select name="status" required class="w-full border rounded-md px-4 py-2">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeModal('addModal')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md">Assign</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Membership Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
    <h2 class="text-xl font-bold mb-4 text-cyan-700">Edit Membership</h2>
    <form method="POST" action="update_user_membership.php" id="editForm" class="space-y-4">
      <input type="hidden" name="id" id="edit_id">
      <input type="text" name="plan_name" id="edit_plan_name" required class="w-full border rounded-md px-4 py-2">
      <input type="text" name="delivery_frequency" id="edit_delivery_frequency" required class="w-full border rounded-md px-4 py-2">
      <input type="number" name="bottles_per_delivery" id="edit_bottles_per_delivery" required class="w-full border rounded-md px-4 py-2">
      <input type="date" name="start_date" id="edit_start_date" required class="w-full border rounded-md px-4 py-2">
      <input type="date" name="end_date" id="edit_end_date" required class="w-full border rounded-md px-4 py-2">
      <select name="status" id="edit_status" required class="w-full border rounded-md px-4 py-2">
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>

      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeModal('editModal')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id) {
  document.getElementById(id).classList.remove('hidden');
  document.getElementById(id).classList.add('flex');
}
function closeModal(id) {
  document.getElementById(id).classList.add('hidden');
  document.getElementById(id).classList.remove('flex');
}
function openEditModal(data) {
  openModal('editModal');
  document.getElementById('edit_id').value = data.id;
  document.getElementById('edit_plan_name').value = data.plan_name;
  document.getElementById('edit_delivery_frequency').value = data.delivery_frequency;
  document.getElementById('edit_bottles_per_delivery').value = data.bottles_per_delivery;
  document.getElementById('edit_start_date').value = data.start_date;
  document.getElementById('edit_end_date').value = data.end_date;
  document.getElementById('edit_status').value = data.status;
}
</script>

<?php include('includes/footer.php'); ?>
