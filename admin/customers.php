<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Search and sort logic
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'latest';

// Use prepared statement to prevent SQL injection
$query = "SELECT u.id, u.name, u.email, u.phone, u.address, u.created_at 
          FROM users u 
          WHERE u.role = 'customer'";

if (!empty($search)) {
    $query .= " AND (u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
}

if ($sort == 'name') {
    $query .= " ORDER BY u.name ASC";
} else {
    $query .= " ORDER BY u.created_at DESC";
}

$stmt = $conn->prepare($query);

if (!empty($search)) {
    $searchParam = '%' . $search . '%';
    $stmt->bind_param('sss', $searchParam, $searchParam, $searchParam);
}

$stmt->execute();
$result = $stmt->get_result();
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Registered Customers</h1>
    <p class="text-gray-600">Manage and view all AquaFlow customers</p>
  </div>

  <!-- SEARCH & FILTERS -->
  <div class="bg-white rounded-lg shadow-md p-4 mb-6">
    <div class="flex flex-col md:flex-row justify-between items-center gap-4">
    <form method="GET" class="flex gap-2 w-full md:w-auto">
      <input 
        type="text" 
        name="search" 
        value="<?= htmlspecialchars($search) ?>" 
        placeholder="Search customers..."
        class="w-full md:w-72 border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none"
      >
      <button 
        type="submit"
        class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium"
      >
        Search
      </button>
    </form>

    <div>
      <form method="GET" class="flex items-center gap-2">
        <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
        <label for="sort" class="text-gray-700 font-medium">Sort by:</label>
        <select 
          id="sort" 
          name="sort" 
          onchange="this.form.submit()" 
          class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-blue-500 focus:outline-none"
        >
          <option value="latest" <?= $sort == 'latest' ? 'selected' : '' ?>>Latest</option>
          <option value="name" <?= $sort == 'name' ? 'selected' : '' ?>>Name (A-Z)</option>
        </select>
      </form>
    </div>
    </div>
  </div>

  <!-- CUSTOMER TABLE -->
  <div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="overflow-x-auto">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-blue-600 text-white">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">#</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Email</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Phone</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Address</th>
            <th class="px-6 py-3 text-left text-sm font-semibold uppercase tracking-wider">Registered On</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 bg-white">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php $i = 1; while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50 transition">
                <td class="px-6 py-4 text-gray-700"><?= $i++ ?></td>
                <td class="px-6 py-4 font-semibold text-gray-800"><?= htmlspecialchars($row['name']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                <td class="px-6 py-4 text-gray-600"><?= htmlspecialchars($row['phone']) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= htmlspecialchars($row['address']) ?></td>
                <td class="px-6 py-4 text-gray-500"><?= date('d M Y', strtotime($row['created_at'])) ?></td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="6" class="px-6 py-6 text-center text-gray-500">No customers found.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include('../includes/footer.php'); ?>
