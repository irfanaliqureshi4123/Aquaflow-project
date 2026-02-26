<?php
// ===============================================
// generate_invoice.php
// Standalone backend to generate and email invoices
// ===============================================

error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';
require_once '../includes/PHPMailer/src/Exception.php';
require_once '../includes/PHPMailer/src/PHPMailer.php';
require_once '../includes/PHPMailer/src/SMTP.php';
require_once '../vendor/tecnickcom/tcpdf/tcpdf.php'; // Add this line to include TCPDF

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// ===============================================
// 🔹 PDF Invoice Generator Class
// ===============================================
class InvoiceGenerator {
    public static function generateInvoicePDF($order_id, $orderData, $items, $outputPath)
    {
    // Create new PDF document
    $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('AquaFlow');
    $pdf->SetAuthor('AquaFlow Water Supply');
    $pdf->SetTitle('Invoice #' . $order_id);
    $pdf->AddPage();
    
    // Set margins
    $pdf->SetMargins(15, 15, 15);
    $pdf->SetAutoPageBreak(TRUE, 15);

    // ============ HEADER SECTION ============
    // Logo and Company Info
    $pdf->SetFont('helvetica', 'B', 24);
    $pdf->SetTextColor(0, 102, 204); // Cyan color
    $pdf->Cell(0, 15, 'AQUAFLOW', 0, 1, 'C');
    
    $pdf->SetFont('helvetica', 'N', 10);
    $pdf->SetTextColor(100, 100, 100);
    $pdf->Cell(0, 5, 'PURE WATER SUPPLY', 0, 1, 'C');
    $pdf->SetDrawColor(0, 102, 204);
    $pdf->Line(15, $pdf->GetY() + 2, 195, $pdf->GetY() + 2);
    $pdf->Ln(5);

    // ============ INVOICE TITLE & DETAILS ============
    $pdf->SetFont('helvetica', 'B', 16);
    $pdf->SetTextColor(0, 0, 0);
    $pdf->Cell(100, 8, 'INVOICE', 0, 0);
    
    $pdf->SetFont('helvetica', 'N', 9);
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Cell(65, 4, 'Invoice #: ' . str_pad($order_id, 5, '0', STR_PAD_LEFT), 0, 1, 'L');
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Cell(65, 4, 'Invoice Date: ' . date('d/m/Y'), 0, 1, 'L');
    $pdf->SetXY(130, $pdf->GetY());
    $pdf->Cell(65, 4, 'Our Date: ' . date('d/m/Y'), 0, 1, 'L');
    
    $pdf->Ln(3);

    // ============ CUSTOMER & BILLING INFO ============
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFillColor(0, 102, 204);
    $pdf->Cell(50, 6, 'JOIN DETAILS', 0, 0, 'L', true);
    $pdf->SetX(105);
    $pdf->Cell(90, 6, 'SHIP TO', 0, 1, 'L', true);

    $pdf->SetFont('helvetica', 'N', 9);
    $pdf->SetTextColor(0, 0, 0);
    $nameLines = explode(' ', $orderData['customer_name'], 2);
    $pdf->Cell(50, 4, 'Name: ' . htmlspecialchars($nameLines[0]), 0, 0);
    $pdf->SetX(105);
    $pdf->Cell(90, 4, 'Name: ' . htmlspecialchars($orderData['customer_name']), 0, 1);
    
    $pdf->Cell(50, 4, 'Email: ' . htmlspecialchars(substr($orderData['email'], 0, 20)), 0, 0);
    $pdf->SetX(105);
    $pdf->Cell(90, 4, 'Phone: ' . htmlspecialchars($orderData['phone'] ?? 'N/A'), 0, 1);
    
    $pdf->Cell(50, 4, 'Address: ' . htmlspecialchars(substr($orderData['address'] ?? '', 0, 20)), 0, 0);
    $pdf->SetX(105);
    $pdf->Cell(90, 4, 'Address: ' . htmlspecialchars(substr($orderData['address'] ?? '', 0, 30)), 0, 1);
    
    $pdf->Ln(3);

    // ============ ORDER ITEMS TABLE ============
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFillColor(0, 102, 204);
    
    $pdf->Cell(60, 6, 'DESCRIPTION', 0, 0, 'L', true);
    $pdf->Cell(30, 6, 'QTY', 0, 0, 'C', true);
    $pdf->Cell(35, 6, 'UNIT PRICE', 0, 0, 'R', true);
    $pdf->Cell(35, 6, 'AMOUNT', 0, 1, 'R', true);

    // Table header line
    $pdf->SetDrawColor(200, 200, 200);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());

    $pdf->SetFont('helvetica', 'N', 9);
    $pdf->SetTextColor(0, 0, 0);
    
    $subtotal = 0;
    $itemCount = 0;
    
    foreach ($items as $item) {
        $lineTotal = $item['quantity'] * $item['price'];
        $subtotal += $lineTotal;
        $itemCount++;
        
        $itemName = substr($item['product_name'], 0, 35);
        $pdf->Cell(60, 5, $itemName, 0, 0, 'L');
        $pdf->Cell(30, 5, $item['quantity'], 0, 0, 'C');
        $pdf->Cell(35, 5, 'Rs ' . number_format($item['price'], 2), 0, 0, 'R');
        $pdf->Cell(35, 5, 'Rs ' . number_format($lineTotal, 2), 0, 1, 'R');
    }

    // Line separator
    $pdf->SetDrawColor(0, 102, 204);
    $pdf->Line(15, $pdf->GetY(), 195, $pdf->GetY());
    $pdf->Ln(2);

    // ============ TOTALS SECTION ============
    $tax = 0; // You can modify this if tax is applicable
    $total = $subtotal + $tax;
    
    $pdf->SetFont('helvetica', 'N', 9);
    $pdf->SetX(105);
    $pdf->Cell(55, 5, 'SUBTOTAL', 0, 0, 'L');
    $pdf->Cell(35, 5, 'Rs ' . number_format($subtotal, 2), 0, 1, 'R');
    
    $pdf->SetX(105);
    $pdf->Cell(55, 5, 'TAX', 0, 0, 'L');
    $pdf->Cell(35, 5, 'Rs ' . number_format($tax, 2), 0, 1, 'R');
    
    // Total line
    $pdf->SetFont('helvetica', 'B', 11);
    $pdf->SetFillColor(240, 240, 240);
    $pdf->SetX(105);
    $pdf->Cell(55, 7, 'TOTAL', 0, 0, 'L', true);
    $pdf->SetTextColor(0, 102, 204);
    $pdf->Cell(35, 7, 'Rs ' . number_format($total, 2), 0, 1, 'R', true);
    
    $pdf->Ln(5);

    // ============ FOOTER ============
    $pdf->SetFont('helvetica', 'I', 8);
    $pdf->SetTextColor(150, 150, 150);
    $pdf->Cell(0, 4, 'Thank you for your business!', 0, 1, 'C');
    $pdf->Cell(0, 4, 'Generated on ' . date('d-m-Y H:i'), 0, 1, 'C');

    $pdf->Output($outputPath, 'F'); // Save PDF to file
    }
}

// ===============================================
// 🔹 Generate & Send Invoice
// ===============================================
if (isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);

    // Fetch order details
    $orderQuery = $conn->prepare("SELECT * FROM orders WHERE id = ?");
    $orderQuery->bind_param("i", $order_id);
    $orderQuery->execute();
    $orderData = $orderQuery->get_result()->fetch_assoc();

    if (!$orderData) {
        die("Order not found!");
    }

    // Fetch ordered items
    $itemsQuery = $conn->prepare("SELECT * FROM order_items WHERE order_id = ?");
    $itemsQuery->bind_param("i", $order_id);
    $itemsQuery->execute();
    $items = $itemsQuery->get_result()->fetch_all(MYSQLI_ASSOC);

    // Generate Invoice PDF
    $invoiceDir = '../storage/invoices/';
    if (!is_dir($invoiceDir)) mkdir($invoiceDir, 0777, true);

    $invoicePath = $invoiceDir . 'invoice_' . $order_id . '.pdf';
    
    // Generate the invoice using the InvoiceGenerator class
    InvoiceGenerator::generateInvoicePDF($order_id, $orderData, $items, $invoicePath);

    // Send Email with Invoice Attachment
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = getenv('SMTP_USERNAME') ?: getenv('SMTP_FROM_EMAIL');
        $mail->Password = getenv('SMTP_PASSWORD');
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $smtpFromEmail = getenv('SMTP_USERNAME') ?: getenv('SMTP_FROM_EMAIL');
        $smtpFromName = getenv('SMTP_FROM_NAME') ?: 'Aqua Cool Water Store';
        $mail->setFrom($smtpFromEmail, $smtpFromName);
        $mail->addAddress($orderData['email'], $orderData['customer_name']);
        $mail->addAttachment($invoicePath);

        $mail->isHTML(true);
        $mail->Subject = 'Your Invoice - Aqua Cool Order #' . $order_id;
        $mail->Body = '<p>Dear ' . htmlspecialchars($orderData['customer_name']) . ',</p>
                       <p>Thank you for your order! Please find your invoice attached.</p>
                       <p>Best regards,<br>Aqua Cool Team</p>';

        $mail->send();

        // Log email
        log_email(
            $conn,
            $orderData['email'],
            'Invoice for Order #' . $order_id,
            'Invoice sent successfully',
            'invoice',
            'sent'
        );

        echo "<h3>✅ Invoice generated and emailed successfully!</h3>";
        echo "<a href='$invoicePath' target='_blank'>Download Invoice</a>";

    } catch (Exception $e) {
        log_email(
            $conn,
            $orderData['email'],
            'Invoice for Order #' . $order_id,
            'Email sending failed: ' . $mail->ErrorInfo,
            'invoice',
            'failed'
        );
        echo "❌ Failed to send invoice email: {$mail->ErrorInfo}";
    }

    logActivity($conn, $_SESSION['username'], $_SESSION['role'], "Generated invoice for order ID: $order_id");
}
?>
