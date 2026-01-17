<?php
/**
 * Membership Subscription Success Page
 * 
 * This page is displayed after successful membership payment processing.
 * 
 * Features:
 * - Payment confirmation details
 * - Membership activation confirmation
 * - Download receipt/invoice
 * - Next steps guidance
 * - Social sharing options
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';

// Access Control
require_customer();

// Verify subscription was completed
if (!isset($_SESSION['subscription_completed'])) {
    header('Location: ./membership.php');
    exit;
}

$subscription = $_SESSION['subscription_completed'];
include '../includes/header.php';

$user_id = $_SESSION['user_id'];

// Get user's latest membership details
$user_query = $conn->prepare("SELECT email, name FROM users WHERE id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

// Get latest membership details
$latest_membership = $conn->prepare("
    SELECT 
        um.id,
        um.start_date,
        um.end_date,
        um.status,
        m.name,
        m.bottles_per_week,
        m.price
    FROM user_memberships um
    JOIN memberships m ON um.membership_id = m.id
    WHERE um.user_id = ?
    ORDER BY um.id DESC
    LIMIT 1
");
$latest_membership->bind_param('i', $user_id);
$latest_membership->execute();
$membership_result = $latest_membership->get_result();
$membership = $membership_result->fetch_assoc();

// Clear session data
unset($_SESSION['subscription_completed']);
unset($_SESSION['membership_subscription']);
unset($_SESSION['csrf_token']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Confirmed - AquaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .success-container {
            max-width: 700px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .success-card {
            background: white;
            border-radius: 1rem;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            text-align: center;
        }
        .success-icon {
            display: inline-block;
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
        }
        .success-icon i {
            font-size: 2.5rem;
            color: white;
        }
        .success-title {
            font-size: 2rem;
            font-weight: bold;
            color: #10b981;
            margin-bottom: 0.5rem;
        }
        .success-subtitle {
            font-size: 1rem;
            color: #666;
            margin-bottom: 2rem;
        }
        .details-section {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.75rem;
            padding: 1.5rem;
            margin: 1.5rem 0;
            text-align: left;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: 600;
            color: #374151;
        }
        .detail-value {
            color: #06b6d4;
            font-weight: 500;
        }
        .benefits-section {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 0.375rem;
            text-align: left;
        }
        .benefits-section h3 {
            color: #065f46;
            margin: 0 0 1rem 0;
        }
        .benefits-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .benefits-list li {
            padding: 0.5rem 0;
            color: #047857;
            display: flex;
            align-items: center;
        }
        .benefits-list li:before {
            content: "✓";
            display: inline-block;
            width: 24px;
            height: 24px;
            background: #10b981;
            color: white;
            border-radius: 50%;
            text-align: center;
            line-height: 24px;
            margin-right: 0.75rem;
            font-weight: bold;
            font-size: 0.875rem;
        }
        .action-buttons {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin: 2rem 0;
        }
        @media (max-width: 640px) {
            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-primary {
            background: #06b6d4;
            color: white;
        }
        .btn-primary:hover {
            background: #0891b2;
        }
        .btn-secondary {
            background: #e5e7eb;
            color: #374151;
        }
        .btn-secondary:hover {
            background: #d1d5db;
        }
        .next-steps {
            background: #eff6ff;
            border-left: 4px solid #3b82f6;
            padding: 1.5rem;
            margin: 1.5rem 0;
            border-radius: 0.375rem;
            text-align: left;
        }
        .next-steps h3 {
            color: #1e40af;
            margin: 0 0 1rem 0;
        }
        .next-steps ol {
            margin: 0;
            padding-left: 1.5rem;
            color: #1e40af;
        }
        .next-steps li {
            margin-bottom: 0.5rem;
        }
        .status-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-weight: 600;
            margin: 0.5rem 0;
        }
        .status-badge.active {
            background: #d1fae5;
            color: #065f46;
        }
        .status-badge.pending {
            background: #fef3c7;
            color: #92400e;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <!-- Success Card -->
        <div class="success-card">
            <div class="success-icon">
                <i class="fas fa-check"></i>
            </div>

            <h1 class="success-title">Subscription Confirmed!</h1>
            <p class="success-subtitle">Your membership subscription has been successfully processed.</p>

            <!-- Payment Details -->
            <div class="details-section">
                <h3 style="margin-top: 0; color: #111827;">Payment Summary</h3>
                <div class="detail-row">
                    <span class="detail-label">Membership Plan:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($subscription['membership_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Amount:</span>
                    <span class="detail-value">Rs <?php echo number_format($subscription['amount'], 2); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Payment Method:</span>
                    <span class="detail-value">
                        <?php echo $subscription['payment_method'] === 'cod' ? 'Cash on Delivery' : 'Credit Card'; ?>
                    </span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Transaction ID:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($subscription['transaction_id']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Status:</span>
                    <span class="detail-value">
                        <span class="status-badge <?php echo htmlspecialchars($subscription['status']); ?>">
                            <?php echo ucfirst(str_replace('_', ' ', $subscription['status'])); ?>
                        </span>
                    </span>
                </div>
            </div>

            <!-- Membership Details -->
            <?php if ($membership): ?>
                <div class="details-section">
                    <h3 style="margin-top: 0; color: #111827;">Membership Details</h3>
                    <div class="detail-row">
                        <span class="detail-label">Bottles per Week:</span>
                        <span class="detail-value"><?php echo htmlspecialchars($membership['bottles_per_week']); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Start Date:</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($membership['start_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">End Date:</span>
                        <span class="detail-value"><?php echo date('M d, Y', strtotime($membership['end_date'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge <?php echo htmlspecialchars($membership['status']); ?>">
                                <?php echo ucfirst($membership['status']); ?>
                            </span>
                        </span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Benefits -->
            <div class="benefits-section">
                <h3>Your Membership Benefits</h3>
                <ul class="benefits-list">
                    <li><?php echo htmlspecialchars($membership['bottles_per_week']); ?> bottles delivered per week</li>
                    <li>Free delivery to your address</li>
                    <li>24/7 customer support</li>
                    <li>Quality guaranteed water</li>
                    <li>Flexible pause/resume options</li>
                    <li>Priority service</li>
                </ul>
            </div>

            <!-- Next Steps -->
            <div class="next-steps">
                <h3>What's Next?</h3>
                <ol>
                    <li>You will receive a confirmation email shortly at <?php echo htmlspecialchars($user['email']); ?></li>
                    <li>Your membership will be activated according to your payment status</li>
                    <li>Our delivery team will contact you to confirm your delivery schedule</li>
                    <li>Your first delivery will arrive within 2-3 business days</li>
                </ol>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="./membership.php" class="btn btn-primary">
                    <i class="fas fa-arrow-right"></i> View My Memberships
                </a>
                <a href="../index.php" class="btn btn-secondary">
                    <i class="fas fa-home"></i> Back to Home
                </a>
            </div>

            <!-- Contact Info -->
            <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid #e5e7eb;">
                <p style="color: #666; font-size: 0.875rem;">
                    Need help? Contact our support team at 
                    <a href="tel:+923001234567" style="color: #06b6d4; text-decoration: none;">+92 300-123-4567</a> 
                    or 
                    <a href="mailto:support@aquaflow.com" style="color: #06b6d4; text-decoration: none;">support@aquaflow.com</a>
                </p>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>
</body>
</html>

<?php
$conn->close();
?>
