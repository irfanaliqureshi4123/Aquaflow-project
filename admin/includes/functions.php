<?php
// ===============================
// Global helper functions
// ===============================

function logActivity($conn, $username, $role, $action) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $stmt = $conn->prepare("INSERT INTO activity_log (username, role, action, ip_address) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $username, $role, $action, $ip);
    $stmt->execute();
}
?>

<?php
// ======================================
// Email Logging Helper Function
// ======================================

// Logs outgoing emails to the database
function log_email($conn, $recipient_email, $subject, $message, $type = 'system', $status = 'pending') {
    $recipient_email = mysqli_real_escape_string($conn, $recipient_email);
    $subject = mysqli_real_escape_string($conn, $subject);
    $message = mysqli_real_escape_string($conn, $message);
    $type = mysqli_real_escape_string($conn, $type);
    $status = mysqli_real_escape_string($conn, $status);

    $query = "INSERT INTO email_logs (recipient_email, subject, message, type, status, sent_at)
              VALUES ('$recipient_email', '$subject', '$message', '$type', '$status', NOW())";
    $conn->query($query);
}


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

// ==================================================
// 🔹 Send Email via Gmail SMTP + Log + Attachments
// ==================================================

function send_system_email(
    $conn,
    $to,
    $subject,
    $body,
    $type = 'system',
    $cc = null,
    $bcc = null,
    $attachmentPath = null
) {
    $mail = new PHPMailer(true);
    $status = 'failed';

    try {
        // SMTP configuration
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'yourcompany@gmail.com';   // 🔹 Your Gmail
        $mail->Password   = 'your_app_password';       // 🔹 App Password from Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Sender info
        $mail->setFrom('yourcompany@gmail.com', 'Your Company Name');
        $mail->addAddress($to);

        // CC / BCC support
        if (!empty($cc)) {
            if (is_array($cc)) {
                foreach ($cc as $addr) $mail->addCC($addr);
            } else {
                $mail->addCC($cc);
            }
        }

        if (!empty($bcc)) {
            if (is_array($bcc)) {
                foreach ($bcc as $addr) $mail->addBCC($addr);
            } else {
                $mail->addBCC($bcc);
            }
        }

        // Attachment support
        if (!empty($attachmentPath) && file_exists($attachmentPath)) {
            $mail->addAttachment($attachmentPath);
        }

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;

        // Send it
        $mail->send();
        $status = 'sent';
    } catch (Exception $e) {
        $status = 'failed';
        error_log("Email failed: {$mail->ErrorInfo}");
    }

    // Save email log
    $recipient_email = $to;
    $cc_field = is_array($cc) ? implode(',', $cc) : $cc;
    $bcc_field = is_array($bcc) ? implode(',', $bcc) : $bcc;
    $attachment_field = basename($attachmentPath);

    $stmt = $conn->prepare("
        INSERT INTO email_logs (recipient_email, subject, message, type, status, cc, bcc, attachment, sent_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('ssssssss', $recipient_email, $subject, $body, $type, $status, $cc_field, $bcc_field, $attachment_field);
    $stmt->execute();
    $stmt->close();

    return $status === 'sent';
}
?>
