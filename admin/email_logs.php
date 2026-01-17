<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle filters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$type = isset($_GET['type']) ? $_GET['type'] : 'all';

$query = "SELECT * FROM email_logs WHERE 1";
if ($type != 'all') $query .= " AND type = '$type'";
if (!empty($search)) $query .= " AND (recipient_email LIKE '%$search%' OR subject LIKE '%$search%')";
$query .= " ORDER BY sent_at DESC";

$result = $conn->query($query);
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-12">
  <h1 class="text-3xl font-bold mb-2">Email Logs</h1>
  <p class="opacity-90 text-lg">Monitor all outgoing system emails (orders, support, notifications).</p>
</section>

<!-- FILTER & SEARCH -->
<section class="py-6 bg-gray-50 border-b border-gray-200">
  <div class="container mx-auto px-4 flex flex-col md:flex-row justify-between items-center gap-4">
    <form method="GET" class="flex items-center gap-2 w-full md:w-auto">
      <input type="text" name="search" placeholder="Search by recipient or subject" value="<?= htmlspecialchars($search) ?>" class="border border-gray-300 rounded-md px-4 py-2 w-full md:w-72 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
      <select name="type" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
        <option value="all">All Types</option>
        <option value="order" <?= $type=='order'?'selected':'' ?>>Order</option>
        <option value="support" <?= $type=='support'?'selected':'' ?>>Support</option>
        <option value="newsletter" <?= $type=='newsletter'?'selected':'' ?>>Newsletter</option>
        <option value="system" <?= $type=='system'?'selected':'' ?>>System</option>
      </select>
      <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium">Filter</button>
    </form>
  </div>
</section>

<!-- EMAIL LOGS TABLE -->
<section class="py-10">
  <div class="container mx-auto px-4">
    <div class="overflow-x-auto bg-white shadow rounded-lg">
      <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-100 text-gray-700 uppercase text-sm font-semibold">
          <tr>
            <th class="px-6 py-3 text-left">Recipient</th>
            <th class="px-6 py-3 text-left">Subject</th>
            <th class="px-6 py-3 text-left">Type</th>
            <th class="px-6 py-3 text-left">Status</th>
            <th class="px-6 py-3 text-left">Sent At</th>
            <th class="px-6 py-3 text-center">Actions</th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 text-gray-700">
          <?php if ($result && $result->num_rows > 0): ?>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr class="hover:bg-gray-50">
                <td class="px-6 py-3"><?= htmlspecialchars($row['recipient_email']) ?></td>
                <td class="px-6 py-3"><?= htmlspecialchars($row['subject']) ?></td>
                <td class="px-6 py-3 capitalize"><?= htmlspecialchars($row['type']) ?></td>
                <td class="px-6 py-3">
                  <?php
                    $statusColor = $row['status'] == 'sent' ? 'bg-green-100 text-green-700' : ($row['status'] == 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
                  ?>
                  <span class="px-3 py-1 rounded-full text-sm font-medium <?= $statusColor ?>">
                    <?= ucfirst($row['status']) ?>
                  </span>
                </td>
                <td class="px-6 py-3"><?= date('d M Y, h:i A', strtotime($row['sent_at'])) ?></td>
                <td class="px-6 py-3 text-center">
                  <button onclick="viewEmail(<?= $row['id'] ?>)" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md text-sm font-medium">View</button>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr><td colspan="6" class="px-6 py-6 text-center text-gray-500">No email logs found.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<!-- MODAL: EMAIL DETAILS -->
<div id="emailModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
  <div class="bg-white rounded-lg shadow-lg w-11/12 md:w-1/2 p-6 relative">
    <h2 class="text-2xl font-bold mb-4 text-cyan-700">Email Details</h2>
    <div id="emailContent" class="text-gray-700 space-y-2"></div>
    <button onclick="closeModal()" class="absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-xl">&times;</button>
  </div>
</div>

<script>
  function viewEmail(id) {
    fetch('view_email.php?id=' + id)
      .then(res => res.text())
      .then(html => {
        document.getElementById('emailContent').innerHTML = html;
        document.getElementById('emailModal').classList.remove('hidden');
      });
  }

  function closeModal() {
    document.getElementById('emailModal').classList.add('hidden');
  }
</script>

<?php include('includes/footer.php'); ?>
