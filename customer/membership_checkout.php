<?php
/**
 * Membership Payment Checkout Page
 * 
 * This page displays the payment checkout form for membership subscriptions.
 * 
 * Features:
 * - Displays membership details and pricing
 * - Multiple payment method options
 * - Stripe card integration
 * - COD (Cash on Delivery) option
 * - Order summary
 * - CSRF token protection
 * - Responsive design
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
require_once '../includes/db_connect.php';
require_once '../includes/access_control.php';
require_once '../vendor/autoload.php';

// Access Control
require_customer();

// Load environment variables
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$stripe_publishable_key = $_ENV['STRIPE_PUBLISHABLE_KEY'] ?? '';

if (empty($stripe_publishable_key)) {
    die("Stripe configuration error. Please contact administrator.");
}

// Verify subscription is in session
if (!isset($_SESSION['membership_subscription'])) {
    $_SESSION['error'] = 'No membership subscription found. Please start again.';
    header('Location: ./membership.php');
    exit;
}

$subscription = $_SESSION['membership_subscription'];
$user_id = $_SESSION['user_id'];
$user_membership_id = $subscription['user_membership_id'];
$membership_name = $subscription['membership_name'];
$price = $subscription['price'];
$bottles_per_week = $subscription['bottles_per_week'];
$duration_days = $subscription['duration_days'];
$start_date = $subscription['start_date'];
$end_date = $subscription['end_date'];

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Fetch user information
$user_query = $conn->prepare("SELECT email, phone, address FROM users WHERE id = ?");
$user_query->bind_param('i', $user_id);
$user_query->execute();
$user_result = $user_query->get_result();
$user = $user_result->fetch_assoc();

include '../includes/header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Membership Checkout - AquaFlow</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script src="https://js.stripe.com/v3/"></script>
    <style>
        .checkout-container {
            max-width: 900px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .checkout-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .checkout-grid {
                grid-template-columns: 1fr;
            }
        }
        .order-summary {
            background: #f9fafb;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }
        .summary-item {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
        }
        .summary-divider {
            border-top: 2px solid #e5e7eb;
            margin: 1rem 0;
        }
        .summary-total {
            font-size: 1.5rem;
            font-weight: bold;
            color: #06b6d4;
            display: flex;
            justify-content: space-between;
        }
        .payment-form {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
        }
        .payment-method-option {
            display: flex;
            align-items: center;
            padding: 1rem;
            border: 2px solid #e5e7eb;
            border-radius: 0.5rem;
            cursor: pointer;
            margin-bottom: 1rem;
            transition: all 0.3s;
        }
        .payment-method-option:hover {
            border-color: #06b6d4;
            background: #f0f9ff;
        }
        .payment-method-option.active {
            border-color: #06b6d4;
            background: #ecf9ff;
        }
        .payment-method-option input[type="radio"] {
            margin-right: 1rem;
            cursor: pointer;
        }
        .payment-method-content {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f9fafb;
            border-radius: 0.5rem;
        }
        .payment-method-content.active {
            display: block;
        }
        .stripe-element {
            border: 1px solid #ccc;
            padding: 0.75rem;
            border-radius: 0.375rem;
            background: white;
        }
        .StripeElement {
            box-sizing: border-box;
            height: 40px;
            padding: 10px 12px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            background-color: white;
            box-shadow: 0 1px 3px 0 #e6ebf1;
            -webkit-transition: box-shadow 150ms ease;
            transition: box-shadow 150ms ease;
        }
        .StripeElement--focus {
            box-shadow: 0 1px 3px 0 #cfd7df;
            border-color: #80bdff;
        }
        .StripeElement--invalid {
            border-color: #fa755a;
        }
        .error-message {
            color: #dc3545;
            font-size: 0.875rem;
            margin-top: 0.5rem;
        }
        .submit-button {
            width: 100%;
            background: #06b6d4;
            color: white;
            padding: 0.75rem 1rem;
            border: none;
            border-radius: 0.5rem;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s;
            margin-top: 1rem;
        }
        .submit-button:hover:not(:disabled) {
            background: #0891b2;
        }
        .submit-button:disabled {
            background: #cbd5e1;
            cursor: not-allowed;
        }
        .membership-benefits {
            background: #ecfdf5;
            border-left: 4px solid #10b981;
            padding: 1rem;
            margin-bottom: 1rem;
            border-radius: 0.375rem;
        }
        .membership-benefits h4 {
            margin: 0 0 0.5rem 0;
            color: #065f46;
        }
        .membership-benefits ul {
            margin: 0;
            padding-left: 1.5rem;
        }
        .membership-benefits li {
            color: #047857;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
    </style>
</head>
<body>
    <div class="checkout-container">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-cyan-700 mb-2">Membership Checkout</h1>
            <p class="text-gray-600">Complete your membership subscription</p>
        </div>

        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="bg-green-50 border border-green-200 text-green-700 px-4 py-3 rounded-md mb-6">
                <?php echo htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-md mb-6">
                <?php echo htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <!-- Checkout Grid -->
        <div class="checkout-grid">
            <!-- Payment Form Section -->
            <div>
                <div class="payment-form">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Select Payment Method</h2>

                    <form id="payment-form" method="POST" action="./process_membership_payment.php">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                        <input type="hidden" name="user_membership_id" value="<?php echo htmlspecialchars($user_membership_id); ?>">
                        <input type="hidden" name="membership_id" value="<?php echo htmlspecialchars($subscription['membership_id']); ?>">
                        <input type="hidden" name="amount" value="<?php echo htmlspecialchars($price); ?>">

                        <!-- Credit Card Option -->
                        <label class="payment-method-option active">
                            <input type="radio" name="payment_method" value="card" checked>
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">Credit/Debit Card</h3>
                                <p class="text-sm text-gray-600">Visa, Mastercard, or other card</p>
                            </div>
                        </label>

                        <div id="card-payment" class="payment-method-content active">
                            <div id="card-element" class="StripeElement mb-4"></div>
                            <div id="card-errors" class="error-message"></div>
                            <input type="hidden" id="stripe_payment_method_id" name="stripe_payment_method_id">
                        </div>

                        <!-- COD Option -->
                        <label class="payment-method-option">
                            <input type="radio" name="payment_method" value="cod">
                            <div class="flex-1">
                                <h3 class="font-semibold text-gray-800">Cash on Delivery (COD)</h3>
                                <p class="text-sm text-gray-600">Pay when membership is activated</p>
                            </div>
                        </label>

                        <div id="cod-payment" class="payment-method-content">
                            <p class="text-sm text-gray-700">
                                <strong>Note:</strong> Please prepare the payment in cash when our representative delivers. 
                                Make sure you keep the receipt for your records.
                            </p>
                        </div>

                        <!-- Submit Button -->
                        <button type="submit" class="submit-button" id="submit-button">
                            <span id="button-text">Complete Payment - Rs <?php echo number_format($price, 2); ?></span>
                            <span id="spinner" style="display:none;">
                                <i class="fas fa-spinner fa-spin"></i> Processing...
                            </span>
                        </button>
                    </form>
                </div>

                <!-- Cancel Option -->
                <div class="mt-4 text-center">
                    <a href="./membership.php" class="text-cyan-600 hover:text-cyan-700 font-medium">
                        ← Back to Memberships
                    </a>
                </div>
            </div>

            <!-- Order Summary Section -->
            <div class="order-summary">
                <h2 class="text-lg font-bold text-gray-800 mb-4">Order Summary</h2>

                <!-- Membership Benefits -->
                <div class="membership-benefits">
                    <h4>Your Benefits</h4>
                    <ul>
                        <li>✓ <?php echo htmlspecialchars($bottles_per_week); ?> bottles per week</li>
                        <li>✓ <?php echo htmlspecialchars($duration_days); ?> days access</li>
                        <li>✓ Free delivery included</li>
                        <li>✓ Customer support</li>
                    </ul>
                </div>

                <!-- Membership Details -->
                <div class="summary-item font-semibold text-gray-800">
                    <span><?php echo htmlspecialchars($membership_name); ?></span>
                </div>

                <div class="summary-item text-sm text-gray-600">
                    <span>Duration:</span>
                    <span><?php echo htmlspecialchars($duration_days); ?> days</span>
                </div>

                <div class="summary-item text-sm text-gray-600">
                    <span>Start Date:</span>
                    <span><?php echo date('M d, Y', strtotime($start_date)); ?></span>
                </div>

                <div class="summary-item text-sm text-gray-600">
                    <span>End Date:</span>
                    <span><?php echo date('M d, Y', strtotime($end_date)); ?></span>
                </div>

                <div class="summary-divider"></div>

                <!-- Pricing -->
                <div class="summary-item text-sm text-gray-600">
                    <span>Subtotal:</span>
                    <span>Rs <?php echo number_format($price, 2); ?></span>
                </div>

                <div class="summary-item text-sm text-gray-600">
                    <span>Delivery Charges:</span>
                    <span class="text-green-600">Free</span>
                </div>

                <div class="summary-item text-sm text-gray-600">
                    <span>Tax:</span>
                    <span>Calculated at checkout</span>
                </div>

                <div class="summary-divider"></div>

                <div class="summary-total">
                    <span>Total:</span>
                    <span>Rs <?php echo number_format($price, 2); ?></span>
                </div>

                <!-- User Email -->
                <div class="summary-item text-xs text-gray-500 mt-4 pt-4 border-t">
                    <span>Confirmation will be sent to:</span>
                </div>
                <div class="summary-item text-sm text-gray-700 font-medium">
                    <span><?php echo htmlspecialchars($user['email']); ?></span>
                </div>
            </div>
        </div>
    </div>

    <!-- Stripe JavaScript -->
    <script>
        const stripe = Stripe('<?php echo htmlspecialchars($stripe_publishable_key); ?>');
        const elements = stripe.elements();
        const cardElement = elements.create('card', {
            style: {
                base: {
                    fontSize: '16px',
                    color: '#424770',
                    fontFamily: 'system-ui, -apple-system, sans-serif'
                }
            }
        });

        cardElement.mount('#card-element');

        // Handle card errors
        cardElement.addEventListener('change', function(event) {
            const displayError = document.getElementById('card-errors');
            if (event.error) {
                displayError.textContent = event.error.message;
            } else {
                displayError.textContent = '';
            }
        });

        // Payment method switching
        document.querySelectorAll('input[name="payment_method"]').forEach(radio => {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.payment-method-option').forEach(el => {
                    el.classList.remove('active');
                });
                this.closest('.payment-method-option').classList.add('active');

                document.querySelectorAll('.payment-method-content').forEach(el => {
                    el.classList.remove('active');
                });

                if (this.value === 'card') {
                    document.getElementById('card-payment').classList.add('active');
                } else if (this.value === 'cod') {
                    document.getElementById('cod-payment').classList.add('active');
                }
            });
        });

        // Form submission
        const form = document.getElementById('payment-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();

            const paymentMethod = document.querySelector('input[name="payment_method"]:checked').value;

            if (paymentMethod === 'card') {
                // Create payment method with Stripe
                const { paymentMethod: pm, error } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: cardElement
                });

                if (error) {
                    document.getElementById('card-errors').textContent = error.message;
                    return;
                }

                // Store payment method ID in hidden field
                document.getElementById('stripe_payment_method_id').value = pm.id;
            }

            // Disable submit button and show loading
            const submitButton = document.getElementById('submit-button');
            submitButton.disabled = true;
            document.getElementById('button-text').style.display = 'none';
            document.getElementById('spinner').style.display = 'inline';

            // Submit form
            form.submit();
        });
    </script>
</body>
</html>

<?php
$conn->close();
?>
