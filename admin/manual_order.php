<?php
session_start();
require_once('../includes/db_connect.php');
require_once('../includes/access_control.php');

// Access Control: Only admin can access this page
require_admin();

include('../includes/header.php');

// Fetch all customers
$customers = $conn->query("SELECT id, name, email FROM users WHERE role = 'customer' ORDER BY name");

// Fetch all products
$products = $conn->query("SELECT id, name, price FROM products ORDER BY name");
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Create Manual Order</h1>
    <p class="text-lg opacity-90">Place an order on behalf of a customer</p>
  </div>
</section>

<!-- MANUAL ORDER FORM -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4 max-w-2xl">
    <div class="bg-white shadow-lg rounded-xl p-8">
      
      <form method="POST" action="manual_order_submit.php" class="space-y-6" id="manualOrderForm">
        <!-- CSRF Token -->
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token'] ?? ''); ?>">
        
        <!-- Customer Selection -->
        <div>
          <label class="block text-gray-700 font-bold mb-2">Customer *</label>
          <select name="customer_id" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
            <option value="">-- Select Customer --</option>
            <?php while ($customer = $customers->fetch_assoc()): ?>
              <option value="<?php echo $customer['id']; ?>">
                <?php echo htmlspecialchars($customer['name']) . ' (' . htmlspecialchars($customer['email']) . ')'; ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <!-- Products Section -->
        <div>
          <label class="block text-gray-700 font-bold mb-4">Order Items *</label>
          <div id="orderItems" class="space-y-3">
            <div class="orderItem flex gap-2 items-end">
              <div class="flex-1">
                <select name="product_id[]" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-600 product-select">
                  <option value="">-- Select Product --</option>
                  <?php 
                  $products->data_seek(0); // Reset pointer
                  while ($product = $products->fetch_assoc()): 
                  ?>
                    <option value="<?php echo $product['id']; ?>" data-price="<?php echo $product['price']; ?>">
                      <?php echo htmlspecialchars($product['name']) . ' - Rs ' . number_format($product['price'], 2); ?>
                    </option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div class="w-24">
                <input type="number" name="quantity[]" value="1" min="1" max="999" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-600 quantity-input">
              </div>
              <button type="button" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium removeItem" style="display:none;">Remove</button>
            </div>
          </div>
          <button type="button" id="addItem" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium">+ Add Item</button>
        </div>

        <!-- Order Total Preview -->
        <div class="bg-gray-100 p-4 rounded-lg">
          <div class="text-right">
            <p class="text-gray-600 text-sm mb-2">Order Subtotal:</p>
            <p class="text-3xl font-bold text-cyan-700">Rs <span id="orderTotal">0.00</span></p>
          </div>
        </div>

        <!-- Payment Method -->
        <div>
          <label class="block text-gray-700 font-bold mb-2">Payment Method *</label>
          <select name="payment_method" required class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
            <option value="">-- Select Payment Method --</option>
            <option value="cash_on_delivery">Cash on Delivery (COD)</option>
            <option value="credit_card">Credit Card</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="check">Check</option>
          </select>
        </div>

        <!-- Order Notes -->
        <div>
          <label class="block text-gray-700 font-bold mb-2">Order Notes (Optional)</label>
          <textarea name="notes" rows="3" class="w-full border border-gray-300 rounded-lg px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none" placeholder="Add any special instructions or notes..."></textarea>
        </div>

        <!-- Submit Button -->
        <div class="flex gap-3 pt-4">
          <button type="submit" class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-lg font-bold">
            <i class="fas fa-check mr-2"></i>Create Order
          </button>
          <a href="dashboard.php" class="flex-1 bg-gray-400 hover:bg-gray-500 text-white px-6 py-3 rounded-lg font-bold text-center">
            <i class="fas fa-times mr-2"></i>Cancel
          </a>
        </div>
      </form>

    </div>
  </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const orderItemsDiv = document.getElementById('orderItems');
  const addItemBtn = document.getElementById('addItem');
  
  function updateTotal() {
    let total = 0;
    document.querySelectorAll('.orderItem').forEach(item => {
      const select = item.querySelector('.product-select');
      const price = parseFloat(select.options[select.selectedIndex].dataset.price) || 0;
      const quantity = parseInt(item.querySelector('.quantity-input').value) || 0;
      total += price * quantity;
    });
    document.getElementById('orderTotal').textContent = total.toFixed(2);
  }
  
  function attachItemHandlers(item) {
    const productSelect = item.querySelector('.product-select');
    const quantityInput = item.querySelector('.quantity-input');
    const removeBtn = item.querySelector('.removeItem');
    
    productSelect.addEventListener('change', updateTotal);
    quantityInput.addEventListener('change', updateTotal);
    
    if (removeBtn) {
      removeBtn.addEventListener('click', function() {
        item.remove();
        updateTotal();
        updateRemoveButtons();
      });
    }
  }
  
  function updateRemoveButtons() {
    const items = document.querySelectorAll('.orderItem');
    items.forEach((item, index) => {
      const removeBtn = item.querySelector('.removeItem');
      if (removeBtn) {
        removeBtn.style.display = items.length > 1 ? 'block' : 'none';
      }
    });
  }
  
  // Initial setup
  document.querySelectorAll('.orderItem').forEach(attachItemHandlers);
  updateRemoveButtons();
  
  // Add item functionality
  addItemBtn.addEventListener('click', function() {
    const newItem = document.querySelector('.orderItem').cloneNode(true);
    newItem.querySelector('.product-select').value = '';
    newItem.querySelector('.quantity-input').value = '1';
    orderItemsDiv.appendChild(newItem);
    attachItemHandlers(newItem);
    updateRemoveButtons();
  });
});
</script>

<?php include('../includes/footer.php'); ?>
