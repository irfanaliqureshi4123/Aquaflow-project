<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Function to export DB as .sql file
function backupDatabase($host, $user, $pass, $dbname) {
    $tables = [];
    $conn = new mysqli($host, $user, $pass, $dbname);
    $result = $conn->query("SHOW TABLES");

    while ($row = $result->fetch_row()) {
        $tables[] = $row[0];
    }

    $output = "-- Database Backup: $dbname\n-- Date: " . date('Y-m-d H:i:s') . "\n\n";
    $conn->query("SET NAMES 'utf8'");

    foreach ($tables as $table) {
        $res = $conn->query("SHOW CREATE TABLE $table");
        $row2 = $res->fetch_row();
        $output .= "\n\nDROP TABLE IF EXISTS `$table`;\n" . $row2[1] . ";\n\n";

        $res = $conn->query("SELECT * FROM $table");
        $numFields = $res->field_count;

        while ($row3 = $res->fetch_row()) {
            $output .= "INSERT INTO `$table` VALUES(";
            for ($i = 0; $i < $numFields; $i++) {
                $row3[$i] = $row3[$i] ? addslashes($row3[$i]) : 'NULL';
                $row3[$i] = str_replace("\n", "\\n", $row3[$i]);
                $output .= '"' . $row3[$i] . '"';
                if ($i < ($numFields - 1)) {
                    $output .= ',';
                }
            }
            $output .= ");\n";
        }
        $output .= "\n\n";
    }

    $backupFile = '../backups/backup_' . $dbname . '_' . date('Y-m-d_H-i-s') . '.sql';
    file_put_contents($backupFile, $output);
    return $backupFile;
}

// Handle backup request
if (isset($_POST['backup'])) {
    $filePath = backupDatabase($servername, $username, $password, $dbname);
    $success = "✅ Backup created successfully!";
}
?>

<div class="p-6 bg-gray-100 min-h-screen">
  <h1 class="text-3xl font-bold text-gray-800 mb-6">🗄️ Database Backup</h1>

  <?php if (!empty($success)): ?>
    <div class="bg-green-100 text-green-700 p-3 rounded-lg mb-4"><?= $success ?></div>
  <?php endif; ?>

  <!-- Backup Info -->
  <div class="bg-white shadow-md rounded-lg p-6 mb-6">
    <p class="text-gray-700 mb-4">
      Create a full SQL backup of your database. You can download the generated file for safekeeping or migration.
    </p>
    <form method="POST">
      <button type="submit" name="backup" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">
        💾 Generate Backup
      </button>
    </form>
  </div>

  <!-- Available Backups -->
  <div class="bg-white shadow-md rounded-lg p-6">
    <h2 class="text-xl font-semibold text-gray-800 mb-4">📂 Available Backups</h2>
    <div class="overflow-x-auto">
      <table class="min-w-full text-sm text-gray-700">
        <thead class="bg-cyan-600 text-white">
          <tr>
            <th class="px-6 py-3 text-left">File Name</th>
            <th class="px-6 py-3 text-left">Date Created</th>
            <th class="px-6 py-3 text-right">Action</th>
          </tr>
        </thead>
        <tbody>
          <?php
          $backupDir = '../backups/';
          if (!is_dir($backupDir)) mkdir($backupDir, 0777, true);

          $files = glob($backupDir . "*.sql");
          if ($files):
            foreach (array_reverse($files) as $file):
          ?>
          <tr class="border-b hover:bg-gray-50">
            <td class="px-6 py-3"><?= basename($file) ?></td>
            <td class="px-6 py-3"><?= date("Y-m-d H:i:s", filemtime($file)) ?></td>
            <td class="px-6 py-3 text-right">
              <a href="<?= $file ?>" download class="text-cyan-600 hover:underline">⬇️ Download</a>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr>
            <td colspan="3" class="text-center py-6 text-gray-500">No backups found.</td>
          </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php include('includes/footer.php'); ?>
