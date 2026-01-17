<?php
function generatePasswordResetEmail($resetLink) {
    return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>AquaFlow Password Reset</title>
<style>
  body {
    font-family: 'Segoe UI', Arial, sans-serif;
    background-color: #f8fafc;
    margin: 0;
    padding: 0;
    color: #333;
  }
  .email-container {
    max-width: 600px;
    margin: 30px auto;
    background: #ffffff;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 4px 12px rgba(0,0,0,0.05);
  }
  .header {
    background-color: #0284c7;
    color: #fff;
    text-align: center;
    padding: 25px;
  }
  .header img {
    width: 120px;
    margin-bottom: 10px;
  }
  .header h1 {
    font-size: 22px;
    margin: 5px 0;
    text-transform: uppercase;
  }
  .body {
    padding: 30px 25px;
    line-height: 1.6;
  }
  .body h2 {
    color: #0284c7;
    font-size: 18px;
    margin-top: 0;
  }
  .reset-btn {
    display: inline-block;
    background-color: #0284c7;
    color: #fff;
    text-decoration: none;
    padding: 12px 28px;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.3s;
  }
  .reset-btn:hover {
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
    .body, .header {
      padding: 20px;
    }
  }
</style>
</head>
<body>
  <div class="email-container">
    <div class="header">
      <img src="cid:company_logo" alt="AquaFlow Logo">
      <h1>AquaFlow Water Company</h1>
    </div>
    <div class="body">
      <h2>🔐 Password Reset Request</h2>
      <p>Hello,</p>
      <p>We received a request to reset your AquaFlow account password. If this was you, please click the button below to reset your password securely.</p>
      
      <p style="text-align:center; margin: 25px 0;">
        <a href="$resetLink" class="reset-btn" target="_blank">Reset Password</a>
      </p>

      <p>This link will expire in <strong>1 hour</strong>. If you did not request a password reset, please ignore this message.</p>

      <p>Thank you,<br><strong>The AquaFlow Support Team</strong></p>
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
