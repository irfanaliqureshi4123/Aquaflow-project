<?php
/**
 * Membership Email Notifications
 * 
 * Sends email notifications for membership events:
 * - Subscription initiated
 * - Payment confirmed
 * - Subscription activated
 * - Subscription renewed
 * - Subscription expiring
 * - Subscription cancelled
 * 
 * @author AquaWater Team
 * @version 1.0
 */

// Use PHPMailer for reliable email sending
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../vendor/autoload.php';

class MembershipEmailNotifier {
    
    private $mail;
    private $smtp_host;
    private $smtp_port;
    private $smtp_user;
    private $smtp_pass;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        require_once __DIR__ . '/../../includes/db_connect.php';
        
        // Load email configuration from environment
        $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
        $dotenv->load();
        
        $this->smtp_host = $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com';
        $this->smtp_port = $_ENV['SMTP_PORT'] ?? 587;
        $this->smtp_user = $_ENV['SMTP_USER'] ?? '';
        $this->smtp_pass = $_ENV['SMTP_PASS'] ?? '';
        $this->from_email = $_ENV['FROM_EMAIL'] ?? 'noreply@aquawater.com';
        $this->from_name = $_ENV['FROM_NAME'] ?? 'AquaWater';
        
        $this->initializePHPMailer();
    }
    
    private function initializePHPMailer() {
        $this->mail = new PHPMailer(true);
        
        try {
            // SMTP configuration
            $this->mail->isSMTP();
            $this->mail->Host = $this->smtp_host;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->smtp_user;
            $this->mail->Password = $this->smtp_pass;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = $this->smtp_port;
            
            // Set from
            $this->mail->setFrom($this->from_email, $this->from_name);
            
        } catch (Exception $e) {
            error_log("PHPMailer initialization error: " . $e->getMessage());
        }
    }
    
    /**
     * Send subscription initiated notification
     */
    public function sendSubscriptionInitiated($email, $name, $membership_name, $amount) {
        try {
            $subject = "Subscription Initiated - AquaWater";
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    <div style='background: #06b6d4; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0;'>Subscription Initiated</h1>
                    </div>
                    <div style='background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px;'>
                        <p>Hello <strong>$name</strong>,</p>
                        
                        <p>Thank you for choosing AquaWater! Your membership subscription has been initiated.</p>
                        
                        <div style='background: white; border: 2px solid #e5e7eb; padding: 15px; margin: 20px 0; border-radius: 8px;'>
                            <h3 style='margin-top: 0;'>Subscription Details</h3>
                            <p><strong>Plan:</strong> $membership_name</p>
                            <p><strong>Amount:</strong> Rs " . number_format($amount, 2) . "</p>
                            <p><strong>Status:</strong> <span style='background: #dbeafe; color: #0c4a6e; padding: 5px 10px; border-radius: 20px;'>Pending Payment</span></p>
                        </div>
                        
                        <p><strong>Next Step:</strong> Proceed to payment to activate your subscription.</p>
                        
                        <p style='color: #6b7280; font-size: 14px;'>If you did not initiate this subscription, please contact our support team.</p>
                        
                        <p>Best regards,<br><strong>AquaWater Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendEmail($email, $name, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending subscription initiated email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send payment confirmed notification
     */
    public function sendPaymentConfirmed($email, $name, $membership_name, $amount, $payment_method, $start_date, $end_date) {
        try {
            $subject = "Payment Confirmed - Your Membership is Active!";
            
            $method_text = $payment_method === 'cod' ? 'Cash on Delivery' : 'Credit Card';
            $start = date('M d, Y', strtotime($start_date));
            $end = date('M d, Y', strtotime($end_date));
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    <div style='background: #10b981; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0;'>Payment Confirmed! ✓</h1>
                    </div>
                    <div style='background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px;'>
                        <p>Hello <strong>$name</strong>,</p>
                        
                        <p>Great news! Your payment has been confirmed and your membership subscription is now <strong>ACTIVE</strong>.</p>
                        
                        <div style='background: white; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                            <h3 style='margin-top: 0;'>Your Active Subscription</h3>
                            <p><strong>Plan:</strong> $membership_name</p>
                            <p><strong>Amount Paid:</strong> Rs " . number_format($amount, 2) . "</p>
                            <p><strong>Payment Method:</strong> $method_text</p>
                            <p><strong>Start Date:</strong> $start</p>
                            <p><strong>End Date:</strong> $end</p>
                            <p><strong>Status:</strong> <span style='background: #d1fae5; color: #065f46; padding: 5px 10px; border-radius: 20px;'>Active</span></p>
                        </div>
                        
                        <p><strong>What's Next?</strong></p>
                        <ul>
                            <li>Your water delivery will start as per your membership schedule</li>
                            <li>Track your deliveries in your account dashboard</li>
                            <li>You can renew your subscription before it expires</li>
                        </ul>
                        
                        <p style='color: #6b7280; font-size: 14px; margin-top: 20px;'>Questions? Contact our support team at support@aquawater.com</p>
                        
                        <p>Best regards,<br><strong>AquaWater Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendEmail($email, $name, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending payment confirmed email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send subscription expiring soon notification
     */
    public function sendExpiringReminder($email, $name, $membership_name, $days_remaining, $end_date) {
        try {
            $subject = "Your Membership Expires Soon - Renew Now!";
            $end = date('M d, Y', strtotime($end_date));
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    <div style='background: #f59e0b; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0;'>Subscription Expiring Soon</h1>
                    </div>
                    <div style='background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px;'>
                        <p>Hello <strong>$name</strong>,</p>
                        
                        <p>Your membership subscription will expire in <strong>$days_remaining days</strong>.</p>
                        
                        <div style='background: white; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                            <h3 style='margin-top: 0;'>Current Subscription</h3>
                            <p><strong>Plan:</strong> $membership_name</p>
                            <p><strong>Expires On:</strong> $end</p>
                        </div>
                        
                        <p><strong>Don't miss a drop!</strong> Renew your membership now to ensure uninterrupted water delivery.</p>
                        
                        <p style='text-align: center; margin: 20px 0;'>
                            <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost') . "/customer/membership.php' 
                               style='background: #06b6d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                                Renew Your Membership
                            </a>
                        </p>
                        
                        <p style='color: #6b7280; font-size: 14px;'>Once your subscription expires, you won't receive deliveries. Renew today to continue enjoying fresh water!</p>
                        
                        <p>Best regards,<br><strong>AquaWater Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendEmail($email, $name, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending expiring reminder email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send subscription expired notification
     */
    public function sendExpiredNotification($email, $name, $membership_name, $end_date) {
        try {
            $subject = "Your Membership Has Expired";
            $end = date('M d, Y', strtotime($end_date));
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    <div style='background: #ef4444; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0;'>Membership Expired</h1>
                    </div>
                    <div style='background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px;'>
                        <p>Hello <strong>$name</strong>,</p>
                        
                        <p>Your membership subscription expired on <strong>$end</strong>.</p>
                        
                        <div style='background: white; border-left: 4px solid #ef4444; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                            <h3 style='margin-top: 0;'>Expired Plan</h3>
                            <p><strong>Plan:</strong> $membership_name</p>
                            <p><strong>Expired On:</strong> $end</p>
                        </div>
                        
                        <p>Your water delivery has been paused. To resume service, please renew your membership.</p>
                        
                        <p style='text-align: center; margin: 20px 0;'>
                            <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost') . "/customer/membership.php' 
                               style='background: #06b6d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                                Renew Now
                            </a>
                        </p>
                        
                        <p style='color: #6b7280; font-size: 14px;'>We'd love to continue serving you fresh, quality water. Renew your subscription today!</p>
                        
                        <p>Best regards,<br><strong>AquaWater Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendEmail($email, $name, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending expired notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Send subscription cancelled notification
     */
    public function sendCancellationNotification($email, $name, $membership_name, $cancellation_date) {
        try {
            $subject = "Membership Cancelled";
            $date = date('M d, Y H:i', strtotime($cancellation_date));
            
            $body = "
            <html>
            <body style='font-family: Arial, sans-serif; color: #333;'>
                <div style='max-width: 600px; margin: 0 auto;'>
                    <div style='background: #6b7280; color: white; padding: 20px; text-align: center; border-radius: 8px 8px 0 0;'>
                        <h1 style='margin: 0;'>Membership Cancelled</h1>
                    </div>
                    <div style='background: #f9fafb; padding: 20px; border-radius: 0 0 8px 8px;'>
                        <p>Hello <strong>$name</strong>,</p>
                        
                        <p>Your membership subscription has been cancelled as of <strong>$date</strong>.</p>
                        
                        <div style='background: white; border-left: 4px solid #6b7280; padding: 15px; margin: 20px 0; border-radius: 4px;'>
                            <h3 style='margin-top: 0;'>Cancelled Plan</h3>
                            <p><strong>Plan:</strong> $membership_name</p>
                            <p><strong>Cancelled On:</strong> $date</p>
                        </div>
                        
                        <p>Your water delivery service has been stopped. You will not be charged further.</p>
                        
                        <p style='text-align: center; margin: 20px 0;'>
                            <a href='" . ($_ENV['BASE_URL'] ?? 'http://localhost') . "/customer/membership.php' 
                               style='background: #06b6d4; color: white; padding: 10px 20px; text-decoration: none; border-radius: 6px; display: inline-block;'>
                                Browse Plans & Resubscribe
                            </a>
                        </p>
                        
                        <p>We value your business. If you cancelled due to any issues, please let us know how we can improve at support@aquawater.com</p>
                        
                        <p>Best regards,<br><strong>AquaWater Team</strong></p>
                    </div>
                </div>
            </body>
            </html>";
            
            return $this->sendEmail($email, $name, $subject, $body);
            
        } catch (Exception $e) {
            error_log("Error sending cancellation notification email: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Generic email sender
     */
    private function sendEmail($to_email, $to_name, $subject, $body) {
        try {
            $this->mail->addAddress($to_email, $to_name);
            $this->mail->Subject = $subject;
            $this->mail->isHTML(true);
            $this->mail->Body = $body;
            
            $result = $this->mail->send();
            
            // Log email sent
            error_log("Email sent to $to_email with subject: $subject");
            
            // Clear recipients for next email
            $this->mail->clearAddresses();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Failed to send email: " . $e->getMessage());
            return false;
        }
    }
}

// Usage example in other files:
/*
require_once '../includes/membership_emailer.php';

$emailer = new MembershipEmailNotifier();

// Send subscription initiated email
$emailer->sendSubscriptionInitiated($email, $name, $membership_name, $amount);

// Send payment confirmed email
$emailer->sendPaymentConfirmed($email, $name, $membership_name, $amount, $payment_method, $start_date, $end_date);

// Send expiring reminder
$emailer->sendExpiringReminder($email, $name, $membership_name, $days_remaining, $end_date);

// Send expired notification
$emailer->sendExpiredNotification($email, $name, $membership_name, $end_date);

// Send cancellation notification
$emailer->sendCancellationNotification($email, $name, $membership_name, $cancellation_date);
*/
?>
