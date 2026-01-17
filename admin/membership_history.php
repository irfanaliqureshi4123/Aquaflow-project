<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// ✅ Filters
$filter = $_GET['filter'] ?? '';
$search = $_GET['search'] ?? '';

// ✅ Base query
$query = "SELECT m.id, u.name AS user_name, m.plan_name, m.start_date, m.end_date, m.status, m.delivery_frequency, m.bottles_per_delivery
          FROM user_memberships m
          JOIN users u ON m.user_id = u.id
          WHERE m.status IN ('expired', 'cancelled')";

if (!empty($filter)) {
    $query .= " AND m.status = '" . $conn->real_escape_string($filter) . "'";
}
if (!empty($search)) {
    $query .= " AND (u.name LIKE '%" . $conn->real_escape_string($search) . "%' 
                OR m.plan_name LIKE '%" . $conn->real_escape_string($search) . "%')";
}

$query .= " ORDER BY m.end_date DESC";
$result = $conn->query($query);
?>

<section class="bg-cyan-700 text-white text-center py-12">
  <div class="container mx-auto px-4">
    <h1 class="text-3xl font-bold">Membership History</h1>
    <p class="text-lg opacity-90 mt-2">Review expired or canceled AquaFlow memberships</p>
  </div>
</section>

<div class="container mx-auto px-4 py-10">
  <!-- Filters -->
  <div class="flex flex-col md:flex-row justify-between items-center mb-6 gap-4">
    <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search by name or plan..."
        class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 w-full md:w-64" />
      <select name="filter" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600">
        <option value="">All</option>
        <option value="expired" <?= $filter == 'expired' ? 'selected' : '' ?>>Expired</option>
        <option value="cancelled" <?= $filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
      </select>
      <button type="submit"
        class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium">Filter</button>
    </form>
  </div>

  <!-- Table -->
  <div class="overflow-x-auto bg-white rounded-xl shadow">
    <table class="min-w-full border border-gray-200 text-sm text-left">
      <thead class="bg-cyan-700 text-white">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">Customer</th>
          <th class="px-6 py-3">Plan</th>
          <th class="px-6 py-3">Delivery</th>
          <th class="px-6 py-3">Bottles</th>
          <th class="px-6 py-3">Start Date</th>
          <th class="px-6 py-3">End Date</th>
          <th class="px-6 py-3">Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result && $result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-6 py-4"><?= $row['id']; ?></td>
              <td class="px-6 py-4 font-medium"><?= htmlspecialchars($row['user_name']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['plan_name']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['delivery_frequency']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['bottles_per_delivery']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['start_date']); ?></td>
              <td class="px-6 py-4"><?= htmlspecialchars($row['end_date']); ?></td>
              <td class="px-6 py-4">
                <span class="px-3 py-1 rounded-full text-xs 
                    <?= $row['status'] === 'expired' ? 'bg-yellow-100 text-yellow-700' : 'bg-red-100 text-red-700'; ?>">
                  <?= ucfirst($row['status']); ?>
                </span>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="8" class="text-center py-6 text-gray-500">No historical memberships found.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('includes/footer.php'); ?>
