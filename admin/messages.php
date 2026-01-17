<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Pagination settings
$messages_per_page = 5;
$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($current_page - 1) * $messages_per_page;

// Get total number of messages
$total_query = "SELECT COUNT(*) as total FROM contact_messages";
$total_result = $conn->query($total_query);
$total_messages = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_messages / $messages_per_page);

// Fetch messages for current page
$query = "SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT $messages_per_page OFFSET $offset";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<!-- HEADER -->
<section class="bg-cyan-700 text-white text-center py-10">
  <h1 class="text-3xl font-bold">Customer Messages</h1>
  <p class="opacity-90">Manage customer inquiries and bulk orders</p>
</section>

<!-- MESSAGES TABLE -->
<section class="py-10 bg-gray-50 px-4 min-h-screen">
  <div class="max-w-7xl mx-auto">
    <!-- Stats -->
    <div class="grid md:grid-cols-4 gap-6 mb-8">
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-gray-500 text-sm">Total Messages</h3>
        <p class="text-3xl font-bold text-cyan-700"><?= $result->num_rows ?></p>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-gray-500 text-sm">New Messages</h3>
        <p class="text-3xl font-bold text-green-600"><?= $conn->query("SELECT COUNT(*) AS count FROM contact_messages WHERE status='new'")->fetch_assoc()['count'] ?></p>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-gray-500 text-sm">Bulk Orders</h3>
        <p class="text-3xl font-bold text-orange-600"><?= $conn->query("SELECT COUNT(*) AS count FROM contact_messages WHERE message_type='bulk_order'")->fetch_assoc()['count'] ?></p>
      </div>
      
      <div class="bg-white shadow rounded-lg p-6">
        <h3 class="text-gray-500 text-sm">Complaints</h3>
        <p class="text-3xl font-bold text-red-600"><?= $conn->query("SELECT COUNT(*) AS count FROM contact_messages WHERE message_type='complaint'")->fetch_assoc()['count'] ?></p>
      </div>
    </div>

    <!-- Messages Table -->
    <div class="bg-white shadow rounded-lg overflow-hidden">
      <table class="w-full">
        <thead class="bg-gray-200 border-b">
          <tr>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Name</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Email</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Type</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Subject</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Status</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Date</th>
            <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php if ($result->num_rows > 0): ?>
            <?php while ($msg = $result->fetch_assoc()): ?>
              <tr class="border-b hover:bg-gray-50">
                <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars($msg['name']) ?></td>
                <td class="px-6 py-4 text-sm text-gray-600"><?= htmlspecialchars($msg['email']) ?></td>
                <td class="px-6 py-4 text-sm">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold
                    <?php
                      if ($msg['message_type'] === 'bulk_order') echo 'bg-orange-100 text-orange-800';
                      elseif ($msg['message_type'] === 'complaint') echo 'bg-red-100 text-red-800';
                      elseif ($msg['message_type'] === 'partnership') echo 'bg-purple-100 text-purple-800';
                      else echo 'bg-blue-100 text-blue-800';
                    ?>">
                    <?= ucwords(str_replace('_', ' ', $msg['message_type'])) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-800"><?= htmlspecialchars(substr($msg['subject'], 0, 30)) ?>...</td>
                <td class="px-6 py-4 text-sm">
                  <span class="px-3 py-1 rounded-full text-xs font-semibold
                    <?php
                      if ($msg['status'] === 'new') echo 'bg-green-100 text-green-800';
                      elseif ($msg['status'] === 'read') echo 'bg-yellow-100 text-yellow-800';
                      elseif ($msg['status'] === 'replied') echo 'bg-blue-100 text-blue-800';
                      else echo 'bg-gray-100 text-gray-800';
                    ?>">
                    <?= ucfirst($msg['status']) ?>
                  </span>
                </td>
                <td class="px-6 py-4 text-sm text-gray-600"><?= date('M d, Y', strtotime($msg['created_at'])) ?></td>
                <td class="px-6 py-4 text-sm">
                  <a href="view_message.php?id=<?= $msg['id'] ?>" class="text-cyan-600 hover:text-cyan-700 font-semibold">View</a>
                </td>
              </tr>
            <?php endwhile; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="px-6 py-8 text-center text-gray-500">No messages yet.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
      <div class="mt-8 flex justify-center items-center gap-2">
        <!-- Previous Button -->
        <?php if ($current_page > 1): ?>
          <a href="?page=1" class="px-4 py-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700 transition font-medium">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
          </a>
          <a href="?page=<?= $current_page - 1 ?>" class="px-4 py-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700 transition font-medium">Previous</a>
        <?php endif; ?>

        <!-- Page Numbers -->
        <div class="flex gap-2">
          <?php 
            $start_page = max(1, $current_page - 2);
            $end_page = min($total_pages, $current_page + 2);
            
            if ($start_page > 1) echo '<span class="px-2 py-2">...</span>';
            
            for ($i = $start_page; $i <= $end_page; $i++): 
          ?>
            <a href="?page=<?= $i ?>" class="px-4 py-2 rounded-lg font-medium transition <?= $i === $current_page ? 'bg-cyan-700 text-white' : 'bg-gray-200 text-gray-800 hover:bg-gray-300' ?>">
              <?= $i ?>
            </a>
          <?php endfor; 
            if ($end_page < $total_pages) echo '<span class="px-2 py-2">...</span>';
          ?>
        </div>

        <!-- Next Button -->
        <?php if ($current_page < $total_pages): ?>
          <a href="?page=<?= $current_page + 1 ?>" class="px-4 py-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700 transition font-medium">Next</a>
          <a href="?page=<?= $total_pages ?>" class="px-4 py-2 rounded-lg bg-cyan-600 text-white hover:bg-cyan-700 transition font-medium">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 010-1.414L10.586 10 7.293 6.707a1 1 0 011.414-1.414l4 4a1 1 0 010 1.414l-4 4a1 1 0 01-1.414 0z" clip-rule="evenodd"></path></svg>
          </a>
        <?php endif; ?>
      </div>

      <!-- Page Info -->
      <div class="mt-4 text-center text-gray-600 text-sm">
        Showing page <strong><?= $current_page ?></strong> of <strong><?= $total_pages ?></strong> 
        (<?= $result->num_rows ?> of <?= $total_messages ?> messages)
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include('../includes/footer.php'); ?>
