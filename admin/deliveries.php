<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-12">
  <div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold">Delivery Management</h1>
    <p class="text-lg opacity-90 mt-2">Manage and track all delivery schedules efficiently</p>
  </div>
</section>

<div class="container mx-auto px-4 py-10">
  <!-- Top Controls -->
  <div class="flex flex-col md:flex-row justify-between items-center gap-4 mb-6">
    <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" name="search" placeholder="Search by customer or order ID..."
        class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 w-full md:w-72" />
      <button type="submit"
        class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium">Search</button>
    </form>

    <button onclick="openModal('addModal')"
      class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md font-medium flex items-center gap-2">
      <i class="fas fa-plus"></i> Schedule Delivery
    </button>
  </div>

  <!-- Delivery Table -->
  <div class="overflow-x-auto bg-white rounded-xl shadow">
    <table class="min-w-full border border-gray-200 text-sm text-left">
      <thead class="bg-cyan-700 text-white">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">Order ID</th>
          <th class="px-6 py-3">Customer</th>
          <th class="px-6 py-3">Address</th>
          <th class="px-6 py-3">Date</th>
          <th class="px-6 py-3">Status</th>
          <th class="px-6 py-3 text-center">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $query = "SELECT * FROM deliveries WHERE 1";
        if (!empty($search)) $query .= " AND (customer_name LIKE '%$search%' OR order_id LIKE '%$search%')";
        $query .= " ORDER BY delivery_date DESC";

        $result = $conn->query($query);
        if ($result && $result->num_rows > 0):
          while ($row = $result->fetch_assoc()):
        ?>
        <tr class="border-b hover:bg-gray-50">
          <td class="px-6 py-4"><?= $row['id']; ?></td>
          <td class="px-6 py-4 font-semibold text-cyan-700">#<?= htmlspecialchars($row['order_id']); ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['customer_name']); ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['address']); ?></td>
          <td class="px-6 py-4"><?= htmlspecialchars($row['delivery_date']); ?></td>
          <td class="px-6 py-4">
            <span
              class="px-3 py-1 rounded-full text-xs font-medium 
                <?= $row['status'] == 'Delivered' ? 'bg-green-100 text-green-700' : 
                  ($row['status'] == 'On the Way' ? 'bg-yellow-100 text-yellow-800' : 
                  ($row['status'] == 'Cancelled' ? 'bg-red-100 text-red-700' : 'bg-gray-100 text-gray-700')); ?>">
              <?= htmlspecialchars($row['status']); ?>
            </span>
          </td>
          <td class="px-6 py-4 text-center space-x-2">
            <button onclick='openEditModal(<?= json_encode($row); ?>)'
              class="text-blue-600 hover:text-blue-800 font-semibold">Edit</button>
            <a href="delete_delivery.php?id=<?= $row['id']; ?>"
              class="text-red-600 hover:text-red-800 font-semibold"
              onclick="return confirm('Are you sure you want to delete this delivery?')">Delete</a>
          </td>
        </tr>
        <?php endwhile; else: ?>
        <tr><td colspan="7" class="text-center py-6 text-gray-500">No deliveries found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- ADD DELIVERY MODAL -->
<div id="addModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
    <h2 class="text-xl font-bold mb-4 text-cyan-700">Schedule New Delivery</h2>
    <form method="POST" action="add_delivery.php" class="space-y-4">
      <input type="text" name="order_id" placeholder="Order ID" required class="w-full border rounded-md px-4 py-2">
      <input type="text" name="customer_name" placeholder="Customer Name" required class="w-full border rounded-md px-4 py-2">
      <textarea name="address" placeholder="Delivery Address" required class="w-full border rounded-md px-4 py-2"></textarea>
      <input type="date" name="delivery_date" required class="w-full border rounded-md px-4 py-2">
      <select name="status" required class="w-full border rounded-md px-4 py-2">
        <option value="Pending">Pending</option>
        <option value="On the Way">On the Way</option>
        <option value="Delivered">Delivered</option>
        <option value="Cancelled">Cancelled</option>
      </select>
      <div class="flex justify-end gap-3">
        <button type="button" onclick="closeModal('addModal')" class="bg-gray-300 hover:bg-gray-400 text-gray-800 px-4 py-2 rounded-md">Cancel</button>
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- EDIT DELIVERY MODAL -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg p-6 w-full max-w-lg">
    <h2 class="text-xl font-bold mb-4 text-cyan-700">Edit Delivery</h2>
    <form method="POST" action="update_delivery.php" id="editForm" class="space-y-4">
      <input type="hidden" name="id" id="edit_id">
      <input type="text" name="order_id" id="edit_order_id" required class="w-full border rounded-md px-4 py-2">
      <input type="text" name="customer_name" id="edit_customer_name" required class="w-full border rounded-md px-4 py-2">
      <textarea name="address" id="edit_address" required class="w-full border rounded-md px-4 py-2"></textarea>
      <input type="date" name="delivery_date" id="edit_delivery_date" required class="w-full border rounded-md px-4 py-2">
      <select name="status" id="edit_status" required class="w-full border rounded-md px-4 py-2">
        <option value="Pending">Pending</option>
        <option value="On the Way">On the Way</option>
        <option value="Delivered">Delivered</option>
        <option value="Cancelled">Cancelled</option>
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
    document.getElementById('edit_order_id').value = data.order_id;
    document.getElementById('edit_customer_name').value = data.customer_name;
    document.getElementById('edit_address').value = data.address;
    document.getElementById('edit_delivery_date').value = data.delivery_date;
    document.getElementById('edit_status').value = data.status;
  }
</script>

<?php include('includes/footer.php'); ?>
