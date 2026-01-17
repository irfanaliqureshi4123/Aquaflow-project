<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';

// 🔒 Access control
if (!isset($_SESSION['role']) || ($_SESSION['role'] != 1 && $_SESSION['role'] != 2)) {
    header("Location: ../login.php");
    exit();
}

// ✅ Handle bulk invoice generation trigger
if (isset($_GET['generated']) && $_GET['generated'] == 1) {
    $msg = "✅ All missing invoices were successfully generated!";
}

// ✅ Handle resend status messages
if (isset($_GET['resend'])) {
    $invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
    if ($_GET['resend'] === 'success') {
        $msg = "✅ Invoice #{$invoiceId} was successfully resent!";
    } else {
        $msg = "❌ Failed to resend invoice #{$invoiceId}. Please try again.";
    }
}

// Fetch all orders
$query = "
    SELECT o.id, o.customer_name, o.email, o.total_amount, o.created_at,
           IFNULL(el.status, 'not_sent') AS email_status
    FROM orders o
    LEFT JOIN email_logs el ON el.subject LIKE CONCAT('%Order #', o.id, '%')
    ORDER BY o.created_at DESC
";
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Invoices | Admin Panel</title>
  <link rel="stylesheet" href="../assets/css/admin.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f8f9fa; }
    .container { margin-top: 40px; }
    .card { border-radius: 15px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
    .card-header { background: #007bff; color: white; border-radius: 15px 15px 0 0; }
    .btn-sm { border-radius: 20px; }
    .status-sent { color: green; font-weight: bold; }
    .status-failed { color: red; font-weight: bold; }
    .status-not_sent { color: #777; font-style: italic; }
  </style>
</head>
<body>

<div class="container">
  <div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
      <h4><i class="bi bi-receipt"></i> Invoice Management</h4>
      <div>
        <a href="generate_invoice.php?bulk=1" class="btn btn-warning btn-sm me-2">
          <i class="bi bi-gear"></i> Generate All Missing Invoices
        </a>
        <a href="dashboard.php" class="btn btn-light btn-sm">← Dashboard</a>
      </div>
    </div>

    <div class="card-body">
      <?php if (isset($msg)): ?>
        <div class="alert alert-success"><?= $msg ?></div>
      <?php endif; ?>

      <p class="text-muted mb-3">View, download, or resend invoices. You can also generate all missing ones at once.</p>

      <table class="table table-bordered table-striped align-middle">
        <thead>
          <tr>
            <th>#</th>
            <th>Customer</th>
            <th>Email</th>
            <th>Total (Rs)</th>
            <th>Date</th>
            <th>Status</th>
            <th>Actions</th>
          </tr>
        </thead>
        <tbody>
        <?php if ($result->num_rows > 0): ?>
          <?php while ($row = $result->fetch_assoc()):
            $invoicePath = "../storage/invoices/invoice_" . $row['id'] . ".pdf";
            $exists = file_exists($invoicePath);
          ?>
          <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['customer_name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= number_format($row['total_amount'], 2) ?></td>
            <td><?= date('d M Y, h:i A', strtotime($row['created_at'])) ?></td>
            <td>
              <span class="status-<?= $row['email_status'] ?>">
                <?= ucfirst($row['email_status']) ?>
              </span>
            </td>
            <td>
              <?php if ($exists): ?>
                <a href="<?= $invoicePath ?>" class="btn btn-success btn-sm me-2" target="_blank">
                  <i class="bi bi-download"></i> Download
                </a>
                <a href="resend_invoice.php?id=<?= $row['id'] ?>" 
                   class="btn btn-primary btn-sm">
                   <i class="bi bi-envelope"></i> Resend
                </a>
              <?php else: ?>
                <button class="btn btn-secondary btn-sm" disabled>
                  <i class="bi bi-file-earmark-x"></i> Missing
                </button>
              <?php endif; ?>

              <a href="generate_invoice.php?order_id=<?= $row['id'] ?>" class="btn btn-primary btn-sm">
                <i class="bi bi-envelope"></i> Re-Send
              </a>
            </td>
          </tr>
          <?php endwhile; ?>
        <?php else: ?>
          <tr><td colspan="7" class="text-center text-muted">No orders found.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Bootstrap -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

</body>
</html>
