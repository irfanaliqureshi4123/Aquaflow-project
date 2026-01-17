<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Path to system log file (adjust if your path differs)
$logFile = '../storage/logs/system.log';

// Create log folder/file if missing
if (!file_exists(dirname($logFile))) {
    mkdir(dirname($logFile), 0777, true);
}
if (!file_exists($logFile)) {
    file_put_contents($logFile, "System log initialized on " . date('Y-m-d H:i:s') . "\n");
}

// Handle clear log action
if (isset($_POST['clear_log'])) {
    file_put_contents($logFile, "Log cleared on " . date('Y-m-d H:i:s') . "\n");
    $success = "🧹 Log file cleared successfully!";
}

// Read log content
$logContent = file_get_contents($logFile);
$logLines = explode("\n", trim($logContent));
$logLines = array_reverse($logLines); // Show newest first
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">🧾 System Logs</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>

  <!-- Log Info -->
  <div class="bg-white rounded-lg shadow-md p-6 mb-6">
    <p class="text-gray-700 mb-4">
      View or download system and PHP error logs directly from your admin dashboard. Useful for debugging and monitoring.
    </p>

    <form method="POST" class="flex gap-3 flex-wrap">
      <button type="submit" name="clear_log" class="bg-red-600 hover:bg-red-700 text-white px-5 py-2 rounded-lg">
        🧹 Clear Log
      </button>
      <a href="<?= $logFile ?>" download class="bg-green-600 hover:bg-green-700 text-white px-5 py-2 rounded-lg">
        ⬇️ Download Log
      </a>
    </form>
  </div>

  <!-- Log Viewer -->
  <div class="bg-white rounded-lg shadow-md p-6 overflow-x-auto">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">📋 Recent Log Entries</h2>
    <div class="border border-gray-200 rounded-lg max-h-[600px] overflow-y-scroll font-mono text-sm bg-gray-50">
      <?php if (count($logLines) > 0): ?>
        <?php foreach ($logLines as $line): ?>
          <div class="px-4 py-2 border-b border-gray-200 text-gray-700">
            <?= htmlspecialchars($line) ?>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="text-center text-gray-500 py-6">No log entries found.</div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>
