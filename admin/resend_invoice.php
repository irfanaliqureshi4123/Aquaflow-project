<?php
// ======================================
// AquaFlow - Re-send Invoice Script
// ======================================
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Access Control: Only admin can access this page
require_admin();

require_once '../includes/functions.php';
require_once '../includes/invoice_email_template.php';
require_once '../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ✅ Gmail SMTP Configuration
define('SMTP_EMAIL', 'irfanprogrammer1@gmail.com');
define('SMTP_PASS', 'REDACTED'); // Gmail App Password
define('SMTP_NAME', 'AquaFlow Billing');

// ✅ Get invoice ID from request
$invoiceId = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($invoiceId <= 0) {
    die('Invalid invoice ID.');
}

// ✅ Fetch invoice data
$invoiceQuery = $conn->prepare("
    SELECT invoices.*, customers.name AS customer_name, customers.email AS customer_email
    FROM invoices
    JOIN customers ON invoices.customer_id = customers.id
    WHERE invoices.id = ?
");
$invoiceQuery->bind_param("i", $invoiceId);
$invoiceQuery->execute();
$result = $invoiceQuery->get_result();

if ($result->num_rows === 0) {
    die('Invoice not found.');
}

$invoice = $result->fetch_assoc();
$invoiceNumber  = $invoice['invoice_number'];
$orderId        = $invoice['order_id'];
$customerEmail  = $invoice['customer_email'];
$customerName   = $invoice['customer_name'];
$pdfPath        = "../storage/invoices/invoice_" . $orderId . ".pdf";
$orderTotal     = $invoice['amount'];
$invoiceDate    = $invoice['invoice_date'] ?? date('Y-m-d H:i:s');

// ✅ Check if file exists
if (!file_exists($pdfPath)) {
    die('Invoice file missing. Please generate it first.');
}

// ✅ Prepare Email Content
$subject = "Invoice #$invoiceNumber — AquaFlow Order #$orderId";
$paymentLink = "https://yourdomain.com/payments/invoice.php?id=" . $invoiceId;
$body = generateInvoiceEmailTemplate(
    $customerName,
    $invoiceNumber,
    $orderId,
    number_format($orderTotal, 2),
    date('F d, Y', strtotime($invoiceDate)),
    $paymentLink
);

// ✅ Optional CC/BCC
$cc = ['accounts@aquaflow.com'];
$bcc = ['admin@aquaflow.com'];

// ✅ Send email using PHPMailer
$mail = new PHPMailer(true);
$status = 'failed';

try {
    $mail->isSMTP();
    $mail->Host       = 'smtp.gmail.com';
    $mail->SMTPAuth   = true;
    $mail->Username   = SMTP_EMAIL;
    $mail->Password   = SMTP_PASS;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = 587;

    // Sender & Recipients
    $mail->setFrom(SMTP_EMAIL, SMTP_NAME);
    $mail->addAddress($customerEmail, $customerName);

    // Add CC and BCC
    foreach ($cc as $addr) $mail->addCC($addr);
    foreach ($bcc as $addr) $mail->addBCC($addr);

    // Embed AquaFlow logo
    $mail->AddEmbeddedImage('../assets/logo.png', 'company_logo', 'logo.png');

    // Attach invoice PDF
    $mail->addAttachment($pdfPath);

    // Content
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body    = $body;

    // ✅ Send
    $mail->send();
    $status = 'sent';
} catch (Exception $e) {
    error_log("Re-send Invoice Error: " . $mail->ErrorInfo);
    $status = 'failed';
}

// ✅ Log email into DB
$stmt = $conn->prepare("
    INSERT INTO email_logs (recipient_email, subject, message, type, status, cc, bcc, attachment, sent_at)
    VALUES (?, ?, ?, 'invoice', ?, ?, ?, ?, NOW())
");
$cc_field = implode(',', $cc);
$bcc_field = implode(',', $bcc);
$attachment = basename($pdfPath);
$stmt->bind_param('sssssss', $customerEmail, $subject, $body, $status, $cc_field, $bcc_field, $attachment);
$stmt->execute();

// ✅ Optional activity log
if (isset($_SESSION['username'])) {
    logActivity($conn, $_SESSION['username'], $_SESSION['role'], "Re-sent invoice #$invoiceNumber to $customerEmail");
}

// ✅ Cleanup
$stmt->close();
$conn->close();

// ✅ Redirect
if ($status === 'sent') {
    header("Location: invoices.php?resend=success&id=$invoiceId");
} else {
    header("Location: invoices.php?resend=fail&id=$invoiceId");
}
exit();
?>
