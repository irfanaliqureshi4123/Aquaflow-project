<?php
session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';
require_once '../includes/functions.php';
require_once '../vendor/autoload.php';

// Access Control: Only admin can access this page
require_admin(); // for PDF/PHPMailer/Stripe etc.

// ✅ Helper to generate unique invoice number
function generateInvoiceNumber($orderId) {
    return 'INV-' . date('Ymd') . '-' . $orderId;
}

// ✅ Bulk generation mode (Generate All Missing Invoices)
if (isset($_GET['bulk']) && $_GET['bulk'] == 1) {
    $query = "SELECT id FROM orders";
    $result = $conn->query($query);

    $count = 0;
    while ($order = $result->fetch_assoc()) {
        $pdfPath = "../storage/invoices/invoice_" . $order['id'] . ".pdf";
        if (!file_exists($pdfPath)) {
            $_GET['order_id'] = $order['id'];
            include __FILE__; // recursively generate
            $count++;
        }
    }

    header("Location: invoices.php?generated=1&count=$count");
    exit();
}

// ✅ Get order ID
$orderId = isset($_POST['order_id']) ? intval($_POST['order_id']) : (isset($_GET['order_id']) ? intval($_GET['order_id']) : 0);
if ($orderId <= 0) {
    die('Invalid order ID');
}

// ✅ Fetch order details
$orderStmt = $conn->prepare("SELECT * FROM orders WHERE id = ?");
$orderStmt->bind_param("i", $orderId);
$orderStmt->execute();
$orderResult = $orderStmt->get_result();
if ($orderResult->num_rows === 0) {
    die('Order not found');
}
$order = $orderResult->fetch_assoc();

// ✅ Invoice number and data
$invoiceNumber = generateInvoiceNumber($orderId);
$invoiceDate = date('Y-m-d H:i:s');
$customerId = $order['customer_id'];
$orderTotal = $order['total_amount'];

// ✅ Fetch customer details
$custStmt = $conn->prepare("SELECT name, email FROM customers WHERE id = ?");
$custStmt->bind_param("i", $customerId);
$custStmt->execute();
$customer = $custStmt->get_result()->fetch_assoc();
$customerEmail = $customer['email'] ?? '';

// ✅ Fetch order items
$itemStmt = $conn->prepare("SELECT product_name, quantity, price FROM order_items WHERE order_id = ?");
$itemStmt->bind_param("i", $orderId);
$itemStmt->execute();
$itemResult = $itemStmt->get_result();

// ✅ Create invoice PDF
require_once '../vendor/setasign/fpdf/fpdf.php';
$pdf = new FPDF();
$pdf->AddPage();

// ============ HEADER ============
$pdf->SetFont('Arial', 'B', 24);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(0, 10, 'AQUAFLOW', 0, 1, 'C');

$pdf->SetFont('Arial', '', 10);
$pdf->SetTextColor(100, 100, 100);
$pdf->Cell(0, 5, 'PURE WATER SUPPLY', 0, 1, 'C');
$pdf->SetDrawColor(0, 102, 204);
$pdf->Line(10, $pdf->GetY() + 2, 200, $pdf->GetY() + 2);
$pdf->Ln(5);

// ============ INVOICE TITLE ============
$pdf->SetFont('Arial', 'B', 16);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(100, 8, 'INVOICE', 0, 0);

$pdf->SetFont('Arial', '', 9);
$pdf->SetX(130);
$pdf->Cell(70, 4, 'Invoice #: ' . str_pad($orderId, 5, '0', STR_PAD_LEFT), 0, 1);
$pdf->SetX(130);
$pdf->Cell(70, 4, 'Invoice Date: ' . date('d/m/Y'), 0, 1);
$pdf->SetX(130);
$pdf->Cell(70, 4, 'Order Date: ' . date('d/m/Y', strtotime($order['order_date'])), 0, 1);
$pdf->Ln(3);

// ============ CUSTOMER INFO ============
$pdf->SetFont('Arial', 'B', 10);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(0, 102, 204);
$pdf->Cell(50, 6, 'BILL TO', 0, 0, 'L', true);
$pdf->SetX(105);
$pdf->Cell(95, 6, 'SHIP TO', 0, 1, 'L', true);

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);
$pdf->Cell(50, 4, 'Customer: ' . htmlspecialchars($customer['name'] ?? 'N/A'), 0, 0);
$pdf->SetX(105);
$pdf->Cell(95, 4, 'Name: ' . htmlspecialchars($customer['name'] ?? 'N/A'), 0, 1);

$pdf->Cell(50, 4, 'Email: ' . htmlspecialchars(substr($customer['email'] ?? '', 0, 25)), 0, 0);
$pdf->SetX(105);
$pdf->Cell(95, 4, 'Phone: N/A', 0, 1);

$pdf->Cell(50, 4, 'Order ID: ' . $orderId, 0, 0);
$pdf->SetX(105);
$pdf->Cell(95, 4, 'Address: N/A', 0, 1);
$pdf->Ln(3);

// ============ ORDER ITEMS TABLE ============
$pdf->SetFont('Arial', 'B', 9);
$pdf->SetTextColor(255, 255, 255);
$pdf->SetFillColor(0, 102, 204);
$pdf->Cell(60, 6, 'DESCRIPTION', 0, 0, 'L', true);
$pdf->Cell(30, 6, 'QTY', 0, 0, 'C', true);
$pdf->Cell(35, 6, 'UNIT PRICE', 0, 0, 'R', true);
$pdf->Cell(35, 6, 'AMOUNT', 0, 1, 'R', true);

$pdf->SetDrawColor(200, 200, 200);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());

$pdf->SetFont('Arial', '', 9);
$pdf->SetTextColor(0, 0, 0);

$subtotal = 0;
while ($item = $itemResult->fetch_assoc()) {
    $lineTotal = $item['quantity'] * $item['price'];
    $subtotal += $lineTotal;

    $itemName = substr($item['product_name'], 0, 35);
    $pdf->Cell(60, 5, $itemName, 0, 0, 'L');
    $pdf->Cell(30, 5, $item['quantity'], 0, 0, 'C');
    $pdf->Cell(35, 5, 'Rs ' . number_format($item['price'], 2), 0, 0, 'R');
    $pdf->Cell(35, 5, 'Rs ' . number_format($lineTotal, 2), 0, 1, 'R');
}

// ============ TOTALS ============
$pdf->SetDrawColor(0, 102, 204);
$pdf->Line(10, $pdf->GetY(), 200, $pdf->GetY());
$pdf->Ln(2);

$tax = 0;
$totalAmount = $subtotal + $tax;

$pdf->SetFont('Arial', '', 9);
$pdf->SetX(130);
$pdf->Cell(55, 5, 'SUBTOTAL', 0, 0, 'L');
$pdf->Cell(35, 5, 'Rs ' . number_format($subtotal, 2), 0, 1, 'R');

$pdf->SetX(130);
$pdf->Cell(55, 5, 'TAX', 0, 0, 'L');
$pdf->Cell(35, 5, 'Rs ' . number_format($tax, 2), 0, 1, 'R');

// Total
$pdf->SetFont('Arial', 'B', 11);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetX(130);
$pdf->Cell(55, 7, 'TOTAL', 0, 0, 'L', true);
$pdf->SetTextColor(0, 102, 204);
$pdf->Cell(35, 7, 'Rs ' . number_format($totalAmount, 2), 0, 1, 'R', true);

$pdf->Ln(5);

// ============ FOOTER ============
$pdf->SetFont('Arial', 'I', 8);
$pdf->SetTextColor(150, 150, 150);
$pdf->Cell(0, 4, 'Thank you for your business!', 0, 1, 'C');
$pdf->Cell(0, 4, 'Generated on ' . date('d-m-Y H:i'), 0, 1, 'C');

// ✅ Save file
if (!is_dir('../storage/invoices')) {
    mkdir('../storage/invoices', 0777, true);
}
$pdfPath = "../storage/invoices/invoice_" . $orderId . ".pdf";
$pdf->Output('F', $pdfPath);

// ✅ Store in DB if not exists
$check = $conn->prepare("SELECT id FROM invoices WHERE order_id = ?");
$check->bind_param("i", $orderId);
$check->execute();
$exists = $check->get_result()->num_rows > 0;

if (!$exists) {
    $insert = $conn->prepare("INSERT INTO invoices (invoice_number, order_id, customer_id, amount, invoice_date) VALUES (?, ?, ?, ?, ?)");
    $insert->bind_param("siids", $invoiceNumber, $orderId, $customerId, $totalAmount, $invoiceDate);
    $insert->execute();
}

echo "✅ Invoice generated successfully for Order #$orderId (Invoice #$invoiceNumber)<br>";

// Close resources
$orderStmt->close();
$custStmt->close();
$itemStmt->close();
$conn->close();
?>
