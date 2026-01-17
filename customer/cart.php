<?php
session_start();
include('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only customers can access this page
require_customer();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Remove cart item
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cart_id'])) {
    // Validate CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        die('Invalid CSRF token');
    }
    
    $cart_id = intval($_POST['cart_id']);
    $user_id = $_SESSION['user_id'];
    
    // Verify cart item belongs to current user before deleting
    $verify_query = $conn->prepare("SELECT user_id FROM cart WHERE id = ?");
    $verify_query->bind_param('i', $cart_id);
    $verify_query->execute();
    $result = $verify_query->get_result();
    
    if ($result->num_rows > 0) {
        $cart_item = $result->fetch_assoc();
        if ($cart_item['user_id'] == $user_id) {
            $remove_query = $conn->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
            $remove_query->bind_param('ii', $cart_id, $user_id);
            $remove_query->execute();
        }
    }
    
    header('Location: cart.php');
    exit;
}

// Fetch cart
$user_id = $_SESSION['user_id'];
$cart_query = $conn->prepare("
    SELECT c.id, p.name, p.price, c.quantity, (p.price * c.quantity) AS total_price, p.image
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
");
$cart_query->bind_param('i', $user_id);
$cart_query->execute();
$cart_result = $cart_query->get_result();

// Calculate grand total
$grand_total = 0;
$cart_items = [];
while ($row = $cart_result->fetch_assoc()) {
    $grand_total += $row['total_price'];
    $cart_items[] = $row;
}
$cart_count = count($cart_items);
?>
<?php include '../includes/header.php'; ?>

<div class="min-h-screen bg-gradient-to-br from-gray-50 via-white to-cyan-50/30">
    <!-- CART CONTAINER -->
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 md:py-12">

        <!-- Back Button -->
        <div class="mb-8 flex items-center justify-between">
            <a href="../products.php" class="inline-flex items-center gap-2 text-cyan-600 hover:text-cyan-700 font-semibold transition group">
                <svg class="w-5 h-5 group-hover:-translate-x-1 transition" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Back to Products
            </a>
            <span class="inline-block bg-cyan-100 text-cyan-700 px-4 py-2 rounded-full text-sm font-semibold">
                <?php echo $cart_count; ?> item(s)
            </span>
        </div>

        <!-- Page Title -->
        <div class="mb-8">
            <h1 class="text-4xl md:text-5xl font-bold text-gray-800 mb-2">
                <i class="fas fa-shopping-cart text-cyan-600 mr-3"></i>Shopping Cart
            </h1>
            <p class="text-gray-600">Review your items before checkout</p>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['cart_success'])): ?>
        <div class="mb-6 p-4 rounded-lg text-cyan-800 bg-cyan-100 border border-cyan-200 flex items-center gap-3 animate-pulse">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
            </svg>
            <span><?php echo htmlspecialchars($_SESSION['cart_success']); ?></span>
            <?php unset($_SESSION['cart_success']); ?>
        </div>
        <?php endif; ?>
        
        <div id="cart-message" class="hidden mb-6 p-4 rounded-lg text-cyan-800 bg-cyan-100 border border-cyan-200 flex items-center gap-3">
            <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
            </svg>
            <span id="message-text"></span>
        </div>

        <?php if ($cart_count > 0): ?>
        <!-- Cart Table -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden mb-8">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gradient-to-r from-cyan-50 to-cyan-100 border-b border-cyan-200">
                            <th class="py-4 px-6 text-left text-sm font-bold text-cyan-900 uppercase tracking-wider">Product</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-cyan-900 uppercase tracking-wider">Unit Price</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-cyan-900 uppercase tracking-wider">Quantity</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-cyan-900 uppercase tracking-wider">Total Price</th>
                            <th class="py-4 px-6 text-center text-sm font-bold text-cyan-900 uppercase tracking-wider">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($cart_items as $row): ?>
                        <tr id="row-<?php echo $row['id']; ?>" class="hover:bg-cyan-50/50 transition duration-200">
                            <!-- PRODUCT -->
                            <td class="py-5 px-6">
                                <div class="flex items-center gap-4">
                                    <div class="flex-shrink-0">
                                        <img src="../uploads/<?php echo htmlspecialchars($row['image']); ?>" 
                                             alt="<?php echo htmlspecialchars($row['name']); ?>"
                                             class="w-16 h-16 rounded-lg border border-gray-200 object-cover shadow-sm">
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 text-base">
                                            <?php echo htmlspecialchars($row['name']); ?>
                                        </p>
                                        <p class="text-xs text-gray-500 mt-1">Product ID: <?php echo $row['id']; ?></p>
                                    </div>
                                </div>
                            </td>

                            <!-- PRICE -->
                            <td class="py-5 px-6 text-center">
                                <span class="font-semibold text-gray-900 text-base">
                                    Rs. <?php echo number_format($row['price'], 2); ?>
                                </span>
                            </td>

                            <!-- QUANTITY -->
                            <td class="py-5 px-6">
                                <div class="flex justify-center items-center gap-3">
                                    <button class="qty-btn minus bg-gray-100 hover:bg-cyan-500 text-gray-700 hover:text-white w-9 h-9 rounded-lg font-bold transition"
                                            data-id="<?php echo $row['id']; ?>" title="Decrease quantity">
                                        <i class="fas fa-minus text-sm"></i>
                                    </button>

                                    <span id="qty-<?php echo $row['id']; ?>" 
                                          class="text-lg font-bold text-gray-900 min-w-[40px] text-center">
                                        <?php echo $row['quantity']; ?>
                                    </span>

                                    <button class="qty-btn plus bg-gray-100 hover:bg-cyan-500 text-gray-700 hover:text-white w-9 h-9 rounded-lg font-bold transition"
                                            data-id="<?php echo $row['id']; ?>" title="Increase quantity">
                                        <i class="fas fa-plus text-sm"></i>
                                    </button>
                                </div>
                            </td>

                            <!-- TOTAL PRICE -->
                            <td class="py-5 px-6 text-center">
                                <span class="font-bold text-lg text-cyan-600 bg-cyan-100 px-3 py-1 rounded-lg inline-block"
                                      id="total-<?php echo $row['id']; ?>">
                                    Rs. <?php echo number_format($row['total_price'], 2); ?>
                                </span>
                            </td>

                            <!-- REMOVE -->
                            <td class="py-5 px-6 text-center">
                                <form method="POST" class="remove-form inline" data-id="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="cart_id" value="<?php echo $row['id']; ?>">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <button type="submit"
                                            class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg font-semibold transition flex items-center gap-2"
                                            title="Remove item">
                                        <i class="fas fa-trash text-sm"></i>
                                        Remove
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Order Summary - Centered and Attractive -->
        <div class="flex justify-center mt-12 mb-8">
            <div class="w-full max-w-md">
                <!-- Summary Card with Beautiful Design -->
                <div class="bg-gradient-to-br from-cyan-600 via-cyan-500 to-blue-600 rounded-2xl shadow-2xl p-1">
                    <!-- Inner white card with padding -->
                    <div class="bg-white rounded-2xl p-8">
                        <!-- Header -->
                        <div class="text-center mb-8">
                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-cyan-100 to-blue-100 rounded-full mb-4">
                                <i class="fas fa-receipt text-2xl text-cyan-600"></i>
                            </div>
                            <h3 class="text-3xl font-bold text-gray-900">Order Summary</h3>
                            <p class="text-gray-500 text-sm mt-2">Final amount to pay</p>
                        </div>
                        
                        <!-- Price Breakdown -->
                        <div class="space-y-4 mb-8 pb-8 border-b-2 border-gray-200">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Subtotal:</span>
                                <span class="text-lg font-bold text-gray-900">Rs. <?php echo number_format($grand_total, 2); ?></span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Shipping Fee:</span>
                                <span class="text-lg font-bold text-gray-900">Rs. 0.00</span>
                            </div>
                            <div class="flex justify-between items-center">
                                <span class="text-gray-600 font-medium">Tax (0%):</span>
                                <span class="text-lg font-bold text-gray-900">Rs. 0.00</span>
                            </div>
                        </div>
                        
                        <!-- Grand Total -->
                        <div class="bg-gradient-to-r from-cyan-50 to-blue-50 rounded-xl p-6 mb-8 border-2 border-cyan-200">
                            <p class="text-gray-600 text-sm font-semibold uppercase tracking-wide mb-2">Amount to Pay</p>
                            <p class="text-5xl font-bold bg-gradient-to-r from-cyan-600 to-blue-600 bg-clip-text text-transparent">
                                Rs. <?php echo number_format($grand_total, 2); ?>
                            </p>
                        </div>

                        <!-- Checkout Button -->
                        <a href="payment.php"
                           class="block w-full bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white text-center 
                                  font-bold py-4 px-6 rounded-xl text-lg shadow-lg hover:shadow-2xl transition duration-300 flex items-center justify-center gap-3 mb-3 transform hover:scale-105">
                            <i class="fas fa-lock"></i>
                            Proceed to Secure Payment
                        </a>

                        <!-- Continue Shopping -->
                        <a href="../products.php"
                           class="block w-full bg-gray-100 hover:bg-gray-200 text-gray-800 text-center 
                                  font-semibold py-3 px-6 rounded-xl transition duration-200 flex items-center justify-center gap-2">
                            <i class="fas fa-shopping-bag"></i>
                            Continue Shopping
                        </a>

                        <!-- Security Badge -->
                        <div class="text-center mt-6 pt-6 border-t border-gray-200">
                            <div class="flex items-center justify-center gap-2 text-green-600">
                                <i class="fas fa-shield-alt"></i>
                                <span class="text-sm font-semibold">Secure Payment Gateway</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <?php else: ?>
        <!-- EMPTY CART MESSAGE -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-200 py-16 px-8 text-center">
            <div class="mb-6">
                <svg class="mx-auto h-32 w-32 text-cyan-200" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M7 4V3a1 1 0 011-1h8a1 1 0 011 1v1h4a1 1 0 011 1v2a1 1 0 01-.293.707l-1.414 1.414A1 1 0 0019 8v10a2 2 0 01-2 2H7a2 2 0 01-2-2V8a1 1 0 01-.293-.707L3.293 5.879A1 1 0 013 5V3a1 1 0 011-1h2zm2 4a1 1 0 100 2 1 1 0 000-2zm6 0a1 1 0 100 2 1 1 0 000-2z"/>
                </svg>
            </div>
            <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-3">Your Cart is Empty</h2>
            <p class="text-gray-600 text-lg mb-8">No items in your cart yet. Let's start shopping!</p>
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="../products.php" class="inline-block bg-gradient-to-r from-cyan-600 to-blue-600 hover:from-cyan-700 hover:to-blue-700 text-white px-8 py-4 rounded-xl font-bold transition shadow-lg hover:shadow-xl flex items-center justify-center gap-2">
                    <i class="fas fa-shopping-bag"></i>
                    Browse Products
                </a>
                <a href="../index.php" class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-800 px-8 py-4 rounded-xl font-bold transition flex items-center justify-center gap-2">
                    <i class="fas fa-home"></i>
                    Back to Home
                </a>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include '../includes/footer.php'; ?>



<!-- JAVASCRIPT -->
<script>
// Remove animation
document.querySelectorAll(".remove-form").forEach(form => {
    form.addEventListener("submit", function(e) {
        e.preventDefault();
        let id = this.dataset.id;
        let row = document.getElementById("row-" + id);

        row.classList.add("opacity-0", "transition", "duration-500");

        setTimeout(() => this.submit(), 500);
    });
});

// Quantity update AJAX
document.querySelectorAll(".qty-btn").forEach(btn => {
    btn.addEventListener("click", function() {
        let id = this.dataset.id;
        let type = this.classList.contains("plus") ? "plus" : "minus";

        fetch("update_quantity.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + id + "&type=" + type
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                document.getElementById("qty-" + id).textContent = data.qty;
                document.getElementById("total-" + id).textContent = "Rs. " + data.total;
                showMessage("Quantity updated successfully!");
            } else {
                showMessage("Error updating quantity");
            }
        })
        .catch(err => showMessage("Connection error"));
    });
});

// Success message popup
function showMessage(msg) {
    let box = document.getElementById("cart-message");
    let textEl = document.getElementById("message-text");
    textEl.textContent = msg;
    box.classList.remove("hidden");

    setTimeout(() => box.classList.add("hidden"), 3000);
}
</script>

</body>
</html>
