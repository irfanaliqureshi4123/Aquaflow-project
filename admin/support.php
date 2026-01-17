<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Handle delete message
if (isset($_GET['delete'])) {
  $id = intval($_GET['delete']);
  $conn->query("DELETE FROM support_messages WHERE id = $id");
  $success = "Message deleted successfully.";
}

// Handle reply (simple email + database log)
if (isset($_POST['reply_message'])) {
  $msg_id = intval($_POST['msg_id']);
  $reply = mysqli_real_escape_string($conn, $_POST['reply']);
  $email = mysqli_real_escape_string($conn, $_POST['email']);

  // Store reply in DB (optional log)
  $conn->query("UPDATE support_messages SET reply = '$reply', replied_at = NOW() WHERE id = $msg_id");

  // Send email (for now just simulate)
  // mail($email, "Reply from AquaFlow Support", $reply);

  $success = "Reply sent to $email successfully!";
}

// Fetch all support messages
$messages = $conn->query("SELECT * FROM support_messages ORDER BY created_at DESC");
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">💬 Customer Support Messages</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>

  <div class="bg-white rounded-xl shadow overflow-hidden">
    <table class="min-w-full border border-gray-200 text-sm">
      <thead class="bg-cyan-600 text-white text-left">
        <tr>
          <th class="py-3 px-4">ID</th>
          <th class="py-3 px-4">Name</th>
          <th class="py-3 px-4">Email</th>
          <th class="py-3 px-4">Subject</th>
          <th class="py-3 px-4">Message</th>
          <th class="py-3 px-4">Status</th>
          <th class="py-3 px-4">Action</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($messages->num_rows > 0): ?>
          <?php while ($msg = $messages->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="py-3 px-4 font-medium text-gray-700">#<?= $msg['id'] ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($msg['name']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($msg['email']) ?></td>
              <td class="py-3 px-4"><?= htmlspecialchars($msg['subject']) ?></td>
              <td class="py-3 px-4 truncate w-64"><?= htmlspecialchars($msg['message']) ?></td>
              <td class="py-3 px-4">
                <?php if (!empty($msg['reply'])): ?>
                  <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">Replied</span>
                <?php else: ?>
                  <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">Pending</span>
                <?php endif; ?>
              </td>
              <td class="py-3 px-4 flex flex-wrap gap-2">
                <button 
                  onclick="openReplyModal(<?= $msg['id'] ?>, '<?= htmlspecialchars($msg['email']) ?>')" 
                  class="bg-cyan-600 hover:bg-cyan-700 text-white px-3 py-1 rounded-md text-sm">
                  Reply
                </button>
                <a href="?delete=<?= $msg['id'] ?>" 
                   onclick="return confirm('Delete this message?')" 
                   class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded-md text-sm">
                  Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center py-5 text-gray-500">No support messages found.</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Reply Modal -->
<div id="replyModal" class="hidden fixed inset-0 bg-gray-800 bg-opacity-50 flex justify-center items-center z-50">
  <div class="bg-white p-6 rounded-xl shadow-lg w-96">
    <h2 class="text-lg font-semibold mb-4">Reply to Customer</h2>
    <form method="POST">
      <input type="hidden" name="msg_id" id="msg_id">
      <input type="hidden" name="email" id="reply_email">
      <textarea name="reply" rows="4" placeholder="Type your reply here..." required
        class="w-full border-gray-300 rounded-lg focus:ring-2 focus:ring-cyan-500 mb-3"></textarea>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeReplyModal()" 
                class="bg-gray-300 px-4 py-2 rounded-md hover:bg-gray-400">Cancel</button>
        <button type="submit" name="reply_message" 
                class="bg-cyan-600 text-white px-4 py-2 rounded-md hover:bg-cyan-700">Send</button>
      </div>
    </form>
  </div>
</div>

<script>
function openReplyModal(id, email) {
  document.getElementById('replyModal').classList.remove('hidden');
  document.getElementById('msg_id').value = id;
  document.getElementById('reply_email').value = email;
}
function closeReplyModal() {
  document.getElementById('replyModal').classList.add('hidden');
}
</script>

<?php include('includes/footer.php'); ?>
