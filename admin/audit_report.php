<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle CSV export
if (isset($_POST['export'])) {
    $start_date = $_POST['start_date'];
    $end_date = $_POST['end_date'];

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_report_' . date('Y-m-d') . '.csv"');

    $output = fopen("php://output", "w");
    fputcsv($output, ['ID', 'Username', 'Role', 'Action', 'IP Address', 'Date']);

    $query = "SELECT * FROM activity_log WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' ORDER BY created_at DESC";
    $result = $conn->query($query);

    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [$row['id'], $row['username'], $row['role'], $row['action'], $row['ip_address'], $row['created_at']]);
    }
    fclose($output);
    exit;
}

// Filter logs by date range
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

$query = "SELECT * FROM activity_log WHERE DATE(created_at) BETWEEN '$start_date' AND '$end_date' ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">📑 Audit Report</h1>

  <!-- Filter Form -->
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <form method="GET" class="flex flex-col md:flex-row items-center gap-4">
      <div>
        <label class="block text-gray-700 font-semibold mb-1">Start Date</label>
        <input type="date" name="start_date" value="<?= htmlspecialchars($start_date) ?>" class="border rounded-md px-3 py-2 focus:ring-2 focus:ring-cyan-500">
      </div>

      <div>
        <label class="block text-gray-700 font-semibold mb-1">End Date</label>
        <input type="date" name="end_date" value="<?= htmlspecialchars($end_date) ?>" class="border rounded-md px-3 py-2 focus:ring-2 focus:ring-cyan-500">
      </div>

      <button type="submit" class="mt-4 md:mt-7 bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-2 rounded-md transition">
        🔍 Generate Report
      </button>
    </form>
  </div>

  <!-- Export Button -->
  <div class="mb-4">
    <form method="POST" class="inline">
      <input type="hidden" name="start_date" value="<?= htmlspecialchars($start_date) ?>">
      <input type="hidden" name="end_date" value="<?= htmlspecialchars($end_date) ?>">
      <button type="submit" name="export" class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg">
        ⬇️ Download CSV
      </button>
    </form>
  </div>

  <!-- Report Table -->
  <div class="bg-white rounded-lg shadow-md overflow-x-auto">
    <table class="min-w-full text-sm text-gray-700">
      <thead class="bg-cyan-600 text-white">
        <tr>
          <th class="px-6 py-3">#</th>
          <th class="px-6 py-3">User</th>
          <th class="px-6 py-3">Role</th>
          <th class="px-6 py-3">Action</th>
          <th class="px-6 py-3">IP Address</th>
          <th class="px-6 py-3">Date</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-6 py-3"><?= $row['id'] ?></td>
              <td class="px-6 py-3 font-medium"><?= htmlspecialchars($row['username']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['role']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['action']) ?></td>
              <td class="px-6 py-3"><?= htmlspecialchars($row['ip_address']) ?></td>
              <td class="px-6 py-3 text-gray-500"><?= date("Y-m-d H:i", strtotime($row['created_at'])) ?></td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr>
            <td colspan="6" class="text-center text-gray-500 py-6">No records found for this period.</td>
          </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<?php include('includes/footer.php'); ?>
