<?php
ob_start();
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');
require_once('../vendor/autoload.php');

// Access Control: Only customers can access this page
require_customer();

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

if (!$order_id) { die("Invalid order ID"); }

// Fetch Order
$order_query = $conn->prepare("
    SELECT id, total_amount, status, order_date, customer_email
    FROM orders 
    WHERE id=? AND user_id=?
");
$order_query->bind_param("ii", $order_id, $user_id);
$order_query->execute();
$order_result = $order_query->get_result();

if ($order_result->num_rows == 0) die("Order not found");

$order = $order_result->fetch_assoc();

// Fallback values
$order['status'] = $order['status'] ?: 'Pending';

// Fetch customer email if missing
if (empty($order['customer_email'])) {
    $email_q = $conn->prepare("SELECT email FROM users WHERE id=?");
    $email_q->bind_param("i", $user_id);
    $email_q->execute();
    $order['customer_email'] = $email_q->get_result()->fetch_assoc()['email'] ?? 'N/A';
}

// Fetch Items
$items = $conn->prepare("
    SELECT p.name, oi.quantity, oi.price
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    WHERE oi.order_id=?
");
$items->bind_param("i", $order_id);
$items->execute();
$items_result = $items->get_result();

// ---------------------------------------
// PDF CLASS WITH ROTATION
// ---------------------------------------
class PDFWithRotation extends TCPDF {
    function RotatedText($x, $y, $txt, $angle) {
        $this->StartTransform();
        $this->Rotate($angle, $x, $y);
        $this->Text($x, $y, $txt);
        $this->StopTransform();
    }
}

$pdf = new PDFWithRotation();
$pdf->AddPage();
$pdf->SetMargins(15, 15, 15);

// Modern Colors
$primary = [0, 140, 100];
$dark = [30, 30, 30];
$muted = [80, 80, 80];

// -------------------------------------------
// COMPANY HEADER
// -------------------------------------------
$pdf->SetFillColor(245, 245, 245);
$pdf->Rect(10, 10, 190, 35, "F");

$pdf->SetFont('helvetica', 'B', 22);
$pdf->SetTextColor($primary[0], $primary[1], $primary[2]);
$pdf->SetXY(15, 12);
$pdf->Cell(0, 10, 'AquaFlow - Pure Water Supply', 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor($muted[0], $muted[1], $muted[2]);
$pdf->SetXY(15, 25);
$pdf->Cell(0, 5, "Fortress Stadium, Lahore, Pakistan", 0, 1);
$pdf->SetXY(15, 29);
$pdf->Cell(0, 5, "Phone: +92 300 1234567 | Email: support@aquaflow.com", 0, 1);

// QR Code (top right)
$qrData = "https://yourdomain.com/verify-order.php?order_id=" . $order['id'];
$pdf->write2DBarcode($qrData, 'QRCODE,H', 165, 13, 30, 30);

// -------------------------------------------
// PAID / PENDING STAMP
// -------------------------------------------
if (strtolower($order['status']) == 'paid') {
    $pdf->SetTextColor(0, 160, 40);
    $pdf->SetFont('helvetica', 'B', 32);
    $pdf->RotatedText(150, 100, 'PAID', 30);
} else {
    $pdf->SetTextColor(200, 0, 0);
    $pdf->SetFont('helvetica', 'B', 32);
    $pdf->RotatedText(150, 100, 'PENDING', 30);
}

$pdf->Ln(40);

// -------------------------------------------
// INVOICE INFO BLOCKS
// -------------------------------------------
$pdf->SetFont('helvetica', 'B', 13);
$pdf->SetTextColor($primary[0], $primary[1], $primary[2]);
$pdf->Cell(95, 8, "Invoice Details", 0, 0);
$pdf->Cell(95, 8, "Bill To", 0, 1);

$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

$pdf->Cell(95, 6, "Invoice #: " . $order['id'], 0, 0);
$pdf->Cell(95, 6, "Name: " . ($_SESSION['name'] ?? 'Customer'), 0, 1);

$pdf->Cell(95, 6, "Date: " . date("F d, Y", strtotime($order['order_date'])), 0, 0);
$pdf->Cell(95, 6, "Email: " . $order['customer_email'], 0, 1);

$pdf->Cell(95, 6, "Status: " . ucfirst($order['status']), 0, 0);
$pdf->Cell(95, 6, "", 0, 1);

$pdf->Ln(10);

// -------------------------------------------
// TABLE HEADER
// -------------------------------------------
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(0, 140, 100);
$pdf->SetTextColor(255, 255, 255);

$pdf->Cell(80, 8, "Product", 1, 0, "L", true);
$pdf->Cell(25, 8, "Qty", 1, 0, "C", true);
$pdf->Cell(40, 8, "Price (PKR)", 1, 0, "R", true);
$pdf->Cell(40, 8, "Total (PKR)", 1, 1, "R", true);

// -------------------------------------------
// TABLE BODY
// -------------------------------------------
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);

while ($item = $items_result->fetch_assoc()) {
    $pdf->Cell(80, 8, $item['name'], 1);
    $pdf->Cell(25, 8, $item['quantity'], 1, 0, "C");
    $pdf->Cell(40, 8, number_format($item['price']), 1, 0, "R");
    $pdf->Cell(40, 8, number_format($item['price'] * $item['quantity']), 1, 1, "R");
}

// -------------------------------------------
// TOTAL SECTION
// -------------------------------------------
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(240, 255, 240);

$pdf->Cell(145, 9, "TOTAL", 1, 0, "R", true);
$pdf->Cell(40, 9, number_format($order['total_amount']), 1, 1, "R", true);

// -------------------------------------------
// FOOTER
// -------------------------------------------
$pdf->Ln(10);
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(90, 90, 90);

$pdf->MultiCell(0, 5,
"Thank you for your order!
For any help, contact support@aquaflow.com
Website: www.aquaflow.com",
0, 'C'
);

$filename = "Invoice_" . $order['id'] . "_" . date("Y-m-d") . ".pdf";
$pdf->Output($filename, "D");
exit;
?>
