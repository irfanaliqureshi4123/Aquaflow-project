<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Access Control: Only admin can access this page
require_admin();

require '../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require '../vendor/phpmailer/phpmailer/src/SMTP.php';
require '../vendor/phpmailer/phpmailer/src/Exception.php';

// Fetch unpaid invoices older than X days
$days = 7;
$query = $conn->prepare("SELECT invoices.invoice_number, invoices.amount, invoices.invoice_date, customers.email, customers.name
                         FROM invoices
                         JOIN customers ON invoices.customer_id = customers.id
                         WHERE invoices.invoice_status = 'Pending' AND invoices.invoice_date < DATE_SUB(NOW(), INTERVAL ? DAY)");
$query->bind_param("i", $days);
$query->execute();
$result = $query->get_result();

while ($row = $result->fetch_assoc()) {
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    $mail->isSMTP();
    $mail->Host = 'smtp.example.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'your@email.com';
    $mail->Password = 'yourpassword';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('your@email.com', 'AquaWater');
    $mail->addAddress($row['email'], $row['name']);
    $mail->Subject = 'Invoice Payment Reminder';
    $mail->Body = "Dear {$row['name']},\n\nThis is a reminder that your invoice #{$row['invoice_number']} for \${$row['amount']} is still pending. Please pay securely using the Stripe link below:\n\n" . $stripeUrl . "\n\nThank you!";

    $mail->send();
}

$query->close();
$conn->close();
?>