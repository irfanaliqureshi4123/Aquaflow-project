<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

$id = intval($_GET['id']);
$result = $conn->query("SELECT * FROM email_logs WHERE id=$id");
if ($row = $result->fetch_assoc()):
?>
  <p><strong>To:</strong> <?= htmlspecialchars($row['recipient_email']) ?></p>
  <p><strong>Subject:</strong> <?= htmlspecialchars($row['subject']) ?></p>
  <p><strong>Status:</strong> <?= htmlspecialchars(ucfirst($row['status'])) ?></p>
  <p><strong>Sent At:</strong> <?= date('d M Y, h:i A', strtotime($row['sent_at'])) ?></p>
  <hr class="my-2">
  <div class="whitespace-pre-wrap"><?= nl2br(htmlspecialchars($row['message'])) ?></div>
<?php else: ?>
  <p class="text-red-500">Email not found.</p>
<?php endif; ?>
