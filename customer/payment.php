<?php
/**
 * Payment Form Display & Processing
 * 
 * This page displays the payment form for customers.
 * 
 * Features:
 * - Displays cart summary
 * - Multiple payment method options (Credit Card, PayPal, COD)
 * - Stripe card element integration
 * - CSRF token protection
 * - Real-time card validation
 * - Responsive design with Tailwind CSS
 * 
 * Security:
 * - Session verification
 * - CSRF token generation and validation
 * - HTML escaping for user content
 * - Environment variable configuration
 * 
 * @author AquaWater Team
 * @version 2.0
 */

session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');
require_once('../vendor/autoload.php');

// Access Control: Only customers can access this page
require_customer();

// Load environment variables for secure API keys
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// SECURITY: Verify Stripe configuration
if (!isset($_ENV['STRIPE_PUBLISHABLE_KEY']) || empty($_ENV['STRIPE_PUBLISHABLE_KEY'])) {
    die("Stripe configuration error. Please contact administrator.");
}

// Stripe Publishable Key (safe to expose in frontend)
$stripe_publishable_key = $_ENV['STRIPE_PUBLISHABLE_KEY'];

// SECURITY: Verify user is authenticated
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

// SECURITY: Generate CSRF token for form protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$user_id = $_SESSION['user_id'];

// Fetch cart items
$cart_query = $conn->prepare("
    SELECT p.name, p.price, SUM(c.quantity) AS qty, (p.price * SUM(c.quantity)) AS total
    FROM cart c 
    JOIN products p ON c.product_id = p.id 
    WHERE c.user_id = ?
    GROUP BY c.product_id
");
$cart_query->bind_param('i', $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();

// Check if cart is empty
if ($cart_result->num_rows === 0) {
    $_SESSION['cart_error'] = 'Your cart is empty. Please add items before checking out.';
    header('Location: cart.php');
    exit;
}

// Calculate total amount
$total_amount = 0;
$cart_items = [];
while ($row = $cart_result->fetch_assoc()) {
    $total_amount += $row['total'];
    $cart_items[] = $row;
}

// Minimum order validation
if ($total_amount < 1) {
    $_SESSION['cart_error'] = 'Invalid order total.';
    header('Location: cart.php');
    exit;
}

// Fetch payment methods
$payment_methods_query = $conn->prepare("
    SELECT id, method_name 
    FROM payment_methods 
    WHERE is_active = 1 
    ORDER BY method_name ASC
");
$payment_methods_query->execute();
$payment_methods = $payment_methods_query->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Payment - AquaWater</title>
<script src="https://cdn.tailwindcss.com"></script>
<script src="https://js.stripe.com/v3/"></script>
</head>

<body class="bg-gray-100 min-h-screen">

<div class="max-w-4xl mx-auto mt-12 p-6 bg-white shadow-lg rounded-2xl">

    <!-- Back to Cart Link -->
    <a href="cart.php" class="inline-flex items-center text-blue-600 hover:text-blue-800 mb-4">
        ← Back to Cart
    </a>

    <h1 class="text-3xl font-bold text-center text-gray-800 mb-8">💳 Secure Checkout</h1>

    <!-- Error Messages -->
    <?php if (isset($_SESSION['payment_error'])): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded-lg">
        <div class="flex items-center">
            <svg class="h-5 w-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
            </svg>
            <strong>Error:</strong> <?php echo htmlspecialchars($_SESSION['payment_error']); ?>
        </div>
    </div>
    <?php unset($_SESSION['payment_error']); endif; ?>

    <!-- TOTAL AMOUNT -->
    <div class="text-center mb-6">
        <span class="text-xl font-semibold text-gray-700">
            Total Amount: 
        </span>
        <span class="text-2xl font-bold text-green-600">
            Rs <?php echo number_format($total_amount, 2); ?>
        </span>
    </div>

    <!-- CART ITEMS -->
    <div class="bg-gray-50 rounded-xl p-6 shadow-inner mb-8">
        <h2 class="text-2xl font-bold mb-4 text-gray-700">🛒 Cart Summary</h2>

        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200 text-gray-700 text-sm uppercase">
                    <th class="py-3 px-4 text-left">Product</th>
                    <th class="py-3 px-4 text-center">Price</th>
                    <th class="py-3 px-4 text-center">Qty</th>
                    <th class="py-3 px-4 text-right">Total</th>
                </tr>
            </thead>

            <tbody>
                <?php foreach ($cart_items as $row): ?>
                <tr class="border-b">
                    <td class="py-3 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="py-3 px-4 text-center">Rs <?php echo number_format($row['price'], 2); ?></td>
                    <td class="py-3 px-4 text-center"><?php echo $row['qty']; ?></td>
                    <td class="py-3 px-4 text-right font-semibold">Rs <?php echo number_format($row['total'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Security Notice -->
    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
        <div class="flex">
            <div class="flex-shrink-0">
                <svg class="h-5 w-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/>
                </svg>
            </div>
            <div class="ml-3">
                <p class="text-sm text-blue-700">
                    <strong>Secure Payment:</strong> Your payment information is encrypted and secure. We never store your card details.
                </p>
            </div>
        </div>
    </div>


    <!-- PAYMENT FORM -->
    <form action="process_payment.php" method="POST" class="space-y-6" id="payment-form">
        
        <!-- Payment Method -->
        <div>
            <label class="block text-gray-700 font-semibold mb-2">
                Select Payment Method <span class="text-red-500">*</span>
            </label>
            <select name="payment_method" id="payment_method" required
                    class="w-full border px-4 py-3 rounded-lg focus:ring-green-400 focus:border-green-500">
                <?php 
                $payment_methods_array = [];
                while($pm = $payment_methods->fetch_assoc()): 
                    $payment_methods_array[] = $pm;
                ?>
                    <option value="<?php echo htmlspecialchars($pm['method_name']); ?>">
                        <?php echo htmlspecialchars($pm['method_name']); ?>
                    </option>
                <?php endwhile; ?>
            </select>
            <?php if (empty($payment_methods_array)): ?>
                <p class="text-red-500 text-sm mt-2">No payment methods available. Please contact support.</p>
            <?php endif; ?>
        </div>

        <!-- CREDIT CARD DETAILS -->
        <div id="card_section" class="hidden space-y-4 p-4 bg-gray-50 rounded-xl shadow-inner">
            <h3 class="font-bold text-gray-700 text-lg">Card Details</h3>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-600 mb-1">Cardholder Name</label>
                    <input type="text" id="billing_name" 
                           class="w-full border px-4 py-2 rounded-lg"
                           placeholder="John Doe">
                </div>
                <div>
                    <label class="block text-sm text-gray-600 mb-1">ZIP/Postal Code</label>
                    <input type="text" id="billing_address_zip"
                           class="w-full border px-4 py-2 rounded-lg"
                           placeholder="12345">
                </div>
            </div>
        
            <div id="card-element" class="p-3 border rounded-lg bg-white"></div>
            <div id="card-errors" class="text-red-500 text-sm mt-2" role="alert"></div>
        </div>

        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">

        <!-- SUBMIT BTN -->
        <button type="submit" id="submit-button"
            class="w-full bg-green-600 hover:bg-green-700 text-white py-4 rounded-xl text-lg font-semibold shadow-lg transition disabled:opacity-50 disabled:cursor-not-allowed">
            <span id="button-text">Pay Rs <?php echo number_format($total_amount, 2); ?> →</span>
            <span id="spinner" class="hidden">
                <svg class="animate-spin inline h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Processing...
            </span>
        </button>

        <!-- Terms Notice -->
        <p class="text-xs text-gray-500 text-center mt-4">
            By completing this purchase, you agree to our Terms of Service and Privacy Policy.
        </p>

    </form>

</div>


<!-- SCRIPT -->
<script>
    const stripe = Stripe('<?php echo $stripe_publishable_key; ?>');
    const elements = stripe.elements();
    
    // Style for Stripe card element
    const cardStyle = {
        base: {
            fontSize: '16px',
            color: '#32325d',
            fontFamily: '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif',
            '::placeholder': {
                color: '#aab7c4'
            }
        },
        invalid: {
            color: '#fa755a',
            iconColor: '#fa755a'
        }
    };
    
    const card = elements.create('card', { style: cardStyle });
    card.mount('#card-element');

    const form = document.getElementById('payment-form');
    const paymentMethodSelect = document.getElementById('payment_method');
    const cardSection = document.getElementById('card_section');
    const cardErrors = document.getElementById('card-errors');
    const submitButton = document.getElementById('submit-button');
    const buttonText = document.getElementById('button-text');
    const spinner = document.getElementById('spinner');

    function toggleCardSection() {
        if (paymentMethodSelect.value.toLowerCase().includes('card')) {
            cardSection.classList.remove('hidden');
            card.update({disabled: false});
        } else {
            cardSection.classList.add('hidden');
            card.update({disabled: true});
            cardErrors.textContent = ''; // Clear any previous errors
        }
    }

    // Initial check on page load
    toggleCardSection();

    // Listen for changes in the payment method select
    paymentMethodSelect.addEventListener('change', toggleCardSection);

    // Update form submission handler with better error handling
    form.addEventListener('submit', async function(event) {
        event.preventDefault();
        
        // DEBUG: Check if CSRF token exists
        const csrfInput = form.querySelector('input[name="csrf_token"]');
        console.log('=== FORM SUBMISSION DEBUG ===');
        console.log('CSRF token input exists:', csrfInput ? 'YES' : 'NO');
        console.log('CSRF token value:', csrfInput ? csrfInput.value : 'NOT FOUND');
        console.log('Payment method:', paymentMethodSelect.value);
        
        // Show loading state
        submitButton.disabled = true;
        buttonText.classList.add('hidden');
        spinner.classList.remove('hidden');
        cardErrors.textContent = '';
    
        try {
            if (paymentMethodSelect.value.toLowerCase().includes('card')) {
                const billingName = document.getElementById('billing_name');
                const billingZip = document.getElementById('billing_address_zip');
        
                // Validate fields only for card payment
                if (!billingName.value.trim()) {
                    throw new Error('Cardholder name is required');
                }
                if (!billingZip.value.trim()) {
                    throw new Error('Postal code is required');
                }
                
                // Validate card input
                const cardElement = elements.getElement('card');
                if (!cardElement._complete) {
                    throw new Error('Please enter complete card details');
                }
    
                const { paymentMethod, error } = await stripe.createPaymentMethod({
                    type: 'card',
                    card: card,
                    billing_details: {
                        name: billingName.value,
                        address: {
                            postal_code: billingZip.value
                        }
                    }
                });
    
                if (error) throw error;
                
                // Hidden input for payment method
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'stripe_payment_method_id';
                hiddenInput.value = paymentMethod.id;
                form.appendChild(hiddenInput);
            }
    
            form.submit();
        } catch (error) {
            cardErrors.textContent = error.message;
            
            // Reset button state
            submitButton.disabled = false;
            buttonText.classList.remove('hidden');
            spinner.classList.add('hidden');
            
            // Scroll to error
            cardErrors.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    });

    // Display card errors
    card.addEventListener('change', function(event) {
        if (event.error) {
            cardErrors.textContent = event.error.message;
        } else {
            cardErrors.textContent = '';
        }
    });
</script>

</body>
</html>