<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

// Get message ID from URL
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id === 0) {
    header('Location: messages.php');
    exit;
}

// Fetch the message
$query = "SELECT * FROM contact_messages WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<div class='bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4'>";
    echo "Message not found.";
    echo "</div>";
    exit;
}

$message = $result->fetch_assoc();

// Mark message as read if status is new
if ($message['status'] === 'new') {
    $update_query = "UPDATE contact_messages SET status = 'read' WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('i', $id);
    $update_stmt->execute();
}

// Handle delete
if (isset($_POST['delete'])) {
    $delete_query = "DELETE FROM contact_messages WHERE id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param('i', $id);
    if ($delete_stmt->execute()) {
        header('Location: messages.php');
        exit;
    }
}

// Handle status update
if (isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $update_query = "UPDATE contact_messages SET status = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_query);
    $update_stmt->bind_param('si', $new_status, $id);
    $update_stmt->execute();
    // Reload the message to get updated data
    $query = "SELECT * FROM contact_messages WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $message = $result->fetch_assoc();
}

include('../includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-gradient-to-r from-cyan-700 to-cyan-800 text-white text-center py-10">
  <h1 class="text-3xl font-bold">View Message</h1>
  <p class="opacity-90">Details of customer inquiry</p>
</section>

<!-- MAIN CONTENT -->
<section class="py-10 px-4 bg-gray-50 min-h-screen">
  <div class="max-w-4xl mx-auto">

    <!-- Back Link -->
    <div class="mb-6">
      <a href="messages.php" class="text-cyan-600 hover:text-cyan-700 font-semibold flex items-center gap-2">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9.707 16.707a1 1 0 01-1.414 0l-6-6a1 1 0 010-1.414l6-6a1 1 0 011.414 1.414L5.414 9H17a1 1 0 110 2H5.414l4.293 4.293a1 1 0 010 1.414z" clip-rule="evenodd"></path></svg>
        Back to Messages
      </a>
    </div>

    <!-- Message Card -->
    <div class="bg-white shadow-lg rounded-xl p-8 mb-8">
      
      <!-- Header Info -->
      <div class="grid md:grid-cols-2 gap-6 mb-8 pb-8 border-b border-gray-200">
        <div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">From</h3>
          <p class="text-xl font-bold text-gray-800"><?= htmlspecialchars($message['name']) ?></p>
          <p class="text-sm text-gray-600"><?= htmlspecialchars($message['email']) ?></p>
        </div>

        <div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Status</h3>
          <div class="flex items-center gap-3">
            <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold <?= $message['status'] === 'new' ? 'bg-green-100 text-green-800' : ($message['status'] === 'read' ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800') ?>">
              <?= ucfirst($message['status']) ?>
            </span>
          </div>
        </div>
      </div>

      <!-- Contact Info -->
      <div class="grid md:grid-cols-2 gap-6 mb-8 pb-8 border-b border-gray-200">
        <div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Phone</h3>
          <p class="text-lg text-gray-800"><?= htmlspecialchars($message['phone'] ?? 'N/A') ?></p>
        </div>

        <div>
          <h3 class="text-gray-500 text-sm font-medium mb-1">Message Type</h3>
          <span class="inline-block px-4 py-2 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800">
            <?= ucfirst(str_replace('_', ' ', $message['message_type'] ?? 'General')) ?>
          </span>
        </div>
      </div>

      <!-- Date Info -->
      <div class="mb-8 pb-8 border-b border-gray-200">
        <h3 class="text-gray-500 text-sm font-medium mb-1">Received</h3>
        <p class="text-gray-800"><?= date('F d, Y \a\t h:i A', strtotime($message['created_at'])) ?></p>
      </div>

      <!-- Message Content -->
      <div class="mb-8">
        <h3 class="text-gray-500 text-sm font-medium mb-3">Message</h3>
        <div class="bg-gray-50 p-6 rounded-lg border border-gray-200">
          <p class="text-gray-800 whitespace-pre-wrap text-base leading-relaxed">
            <?= htmlspecialchars($message['message']) ?>
          </p>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-4 pt-6 border-t border-gray-200">
        
        <!-- Status Update Form -->
        <form method="POST" class="flex gap-2">
          <select name="status" class="border border-gray-300 rounded-lg px-4 py-2 text-sm focus:ring-2 focus:ring-cyan-500">
            <option value="new" <?= $message['status'] === 'new' ? 'selected' : '' ?>>New</option>
            <option value="read" <?= $message['status'] === 'read' ? 'selected' : '' ?>>Read</option>
            <option value="responded" <?= $message['status'] === 'responded' ? 'selected' : '' ?>>Responded</option>
            <option value="closed" <?= $message['status'] === 'closed' ? 'selected' : '' ?>>Closed</option>
          </select>
          <button type="submit" name="update_status" value="1" class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-2 rounded-lg font-medium transition shadow-md hover:shadow-lg">
            Update Status
          </button>
        </form>

        <!-- Delete Button -->
        <form method="POST" class="ml-auto">
          <button type="submit" name="delete" value="1" onclick="return confirm('Are you sure you want to delete this message?')" class="bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-medium transition shadow-md hover:shadow-lg inline-flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"></path></svg>
            Delete Message
          </button>
        </form>
      </div>

    </div>

  </div>
</section>

<?php include('../includes/footer.php'); ?>
