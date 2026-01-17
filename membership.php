<?php
include 'includes/db_connect.php';

// Fetch all membership plans
$query = "SELECT id, name, description, bottles_per_week, price, duration_days FROM memberships ORDER BY price ASC";
$result = $conn->query($query);

if (!$result) {
    die("Query failed: " . $conn->error);
}
?>

<?php include 'includes/header.php'; ?>

<!-- Membership Plans Section -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4">
    <div class="text-center mb-12">
      <h1 class="text-4xl font-bold text-cyan-700 mb-4">Monthly Membership Plans</h1>
      <p class="text-lg text-gray-600">Choose the perfect plan for your hydration needs</p>
    </div>

    <!-- Plans Grid -->
    <div class="grid md:grid-cols-3 gap-8 max-w-6xl mx-auto">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($plan = $result->fetch_assoc()): ?>
          <div class="bg-white rounded-lg shadow-lg hover:shadow-2xl transition p-8">
            <h3 class="text-2xl font-bold text-cyan-700 mb-2"><?= htmlspecialchars($plan['name']) ?></h3>
            <p class="text-gray-600 text-sm mb-4"><?= htmlspecialchars($plan['description']) ?></p>
            
            <div class="mb-6">
              <p class="text-gray-700 mb-2">
                <span class="font-semibold"><?= htmlspecialchars($plan['bottles_per_week']) ?></span> Bottles / Week
              </p>
              <p class="text-gray-700 mb-4">
                <span class="font-semibold"><?= htmlspecialchars($plan['duration_days']) ?></span> Days Duration
              </p>
            </div>

            <div class="mb-6 border-t border-b border-gray-200 py-4">
              <p class="text-4xl font-bold text-cyan-700">Rs <?= number_format($plan['price'], 2) ?></p>
              <p class="text-gray-600 text-sm">per month</p>
            </div>

            <?php if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
              <!-- Logged-in customers can subscribe -->
              <form method="POST" action="/aquaWater/customer/subscribe_membership.php" class="mb-4">
                <input type="hidden" name="membership_id" value="<?= $plan['id'] ?>">
                <button type="submit" class="w-full bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">
                  Subscribe Now
                </button>
              </form>
            <?php else: ?>
              <!-- Non-logged-in or non-customers see login prompt -->
              <a href="login.php" class="w-full block text-center bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">
                Login to Subscribe
              </a>
            <?php endif; ?>

            <div class="mt-4 pt-4 border-t border-gray-200">
              <ul class="text-sm text-gray-700 space-y-2">
                <li class="flex items-center">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Free Cancellation
                </li>
                <li class="flex items-center">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Home Delivery
                </li>
                <li class="flex items-center">
                  <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                  </svg>
                  Customer Support
                </li>
              </ul>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center text-gray-500 col-span-3">No membership plans available yet.</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<!-- Why Choose Our Plans Section -->
<section class="py-16 bg-white">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-cyan-700 mb-12">Why Choose AquaFlow?</h2>
    
    <div class="grid md:grid-cols-3 gap-8 max-w-5xl mx-auto">
      <div class="text-center">
        <div class="mb-4">
          <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-cyan-700" fill="currentColor" viewBox="0 0 20 20">
              <path d="M10.894 2.553a1 1 0 00-1.788 0l-7 14a1 1 0 001.169 1.409l5.951-1.429 5.951 1.429a1 1 0 001.169-1.409l-7-14z"/>
            </svg>
          </div>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">100% Pure Water</h3>
        <p class="text-gray-600">Certified pure mineral water for your health</p>
      </div>

      <div class="text-center">
        <div class="mb-4">
          <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-cyan-700" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10.854 7.276a.5.5 0 00-.708 0L8.146 9.414a.5.5 0 10.708.708l1.293-1.293V14a.5.5 0 001 0v-4.171l1.293 1.293a.5.5 0 10.708-.708l-2.146-2.138zM1 10a9 9 0 1118 0 9 9 0 01-18 0zm2 0a7 7 0 1114 0 7 7 0 01-14 0z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Fast Delivery</h3>
        <p class="text-gray-600">Quick and reliable delivery to your doorstep</p>
      </div>

      <div class="text-center">
        <div class="mb-4">
          <div class="w-16 h-16 bg-cyan-100 rounded-full flex items-center justify-center mx-auto">
            <svg class="w-8 h-8 text-cyan-700" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
          </div>
        </div>
        <h3 class="text-xl font-bold text-gray-800 mb-2">Flexible Plans</h3>
        <p class="text-gray-600">Easy to upgrade, downgrade, or cancel anytime</p>
      </div>
    </div>
  </div>
</section>

<!-- FAQ Section -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-cyan-700 mb-12">Frequently Asked Questions</h2>
    
    <div class="max-w-3xl mx-auto space-y-4">
      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Can I cancel my membership anytime?</h3>
        <p class="text-gray-600">Yes! You can cancel your membership anytime without any penalty or additional charges.</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">How often will I receive delivery?</h3>
        <p class="text-gray-600">Delivery schedule depends on your chosen plan. Most plans offer weekly delivery with flexible scheduling.</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">Can I upgrade my membership?</h3>
        <p class="text-gray-600">Absolutely! You can upgrade to a higher plan anytime, and we'll adjust your billing accordingly.</p>
      </div>

      <div class="bg-white rounded-lg shadow p-6">
        <h3 class="text-lg font-bold text-gray-800 mb-2">What if I miss a delivery?</h3>
        <p class="text-gray-600">Contact our customer support team to reschedule your delivery. We're here to help!</p>
      </div>
    </div>
  </div>
</section>

<?php include('includes/footer.php'); ?>
