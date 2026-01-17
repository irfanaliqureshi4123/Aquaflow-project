<?php
function generateInvoiceEmailTemplate($customerName, $invoiceNumber, $orderId, $orderTotal, $invoiceDate, $paymentLink) {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Invoice #$invoiceNumber - AquaFlow Water Company</title>
  <style>
    body {
      font-family: 'Segoe UI', Arial, sans-serif;
      margin: 0;
      padding: 0;
      background-color: #f8fafc;
      color: #333;
    }
    .email-container {
      max-width: 600px;
      margin: 20px auto;
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 10px rgba(0,0,0,0.05);
    }
    .email-header {
      background-color: #0284c7;
      color: #fff;
      text-align: center;
      padding: 30px 20px;
    }
    .email-header img {
      width: 120px;
      margin-bottom: 10px;
    }
    .email-header h1 {
      font-size: 22px;
      margin: 5px 0 0;
      text-transform: uppercase;
    }
    .email-body {
      padding: 30px 25px;
      line-height: 1.6;
    }
    .email-body h2 {
      color: #0284c7;
      font-size: 18px;
      margin-top: 0;
    }
    .invoice-summary {
      background: #f1f5f9;
      border-radius: 8px;
      padding: 15px 20px;
      margin: 20px 0;
    }
    .invoice-summary p {
      margin: 8px 0;
    }
    .btn {
      display: inline-block;
      background-color: #0284c7;
      color: #fff;
      text-decoration: none;
      padding: 12px 24px;
      border-radius: 6px;
      font-weight: 600;
      transition: background 0.3s;
    }
    .btn:hover {
      background-color: #0369a1;
    }
    .footer {
      background-color: #f1f5f9;
      text-align: center;
      padding: 15px;
      font-size: 13px;
      color: #555;
    }
    @media only screen and (max-width: 600px) {
      .email-body, .email-header {
        padding: 20px;
      }
      .btn {
        width: 100%;
        text-align: center;
      }
    }
  </style>
</head>
<body>
  <div class="email-container">
    <div class="email-header">
      <img src="cid:company_logo" alt="AquaFlow Logo" />
      <h1>AquaFlow Water Company</h1>
    </div>
    <div class="email-body">
      <p>Dear <strong>$customerName</strong>,</p>
      <p>Thank you for choosing <strong>AquaFlow Water Company</strong>! Your invoice has been generated and is ready for review.</p>

      <div class="invoice-summary">
        <h2>Invoice Details</h2>
        <p><strong>Invoice Number:</strong> $invoiceNumber</p>
        <p><strong>Order ID:</strong> #$orderId</p>
        <p><strong>Date:</strong> $invoiceDate</p>
        <p><strong>Total Amount:</strong> Rs $orderTotal</p>
      </div>

      <p>You can securely pay or view your invoice using the button below:</p>

      <p style="text-align:center;">
        <a href="$paymentLink" class="btn" target="_blank">View / Pay Invoice</a>
      </p>

      <p>If you have any questions, simply reply to this email — our support team will be happy to help.</p>

      <p>Best regards,<br>
      <strong>The AquaFlow Billing Team</strong></p>
    </div>
    <div class="footer">
      <p>&copy; 2025 AquaFlow Water Company. All rights reserved.</p>
      <p>123 AquaFlow Street, Karachi, Pakistan</p>
      <p><a href="mailto:support@aquaflow.com">support@aquaflow.com</a></p>
    </div>
  </div>
</body>
</html>
HTML;
}
?>
