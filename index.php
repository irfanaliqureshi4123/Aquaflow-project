<?php
session_start();

// Include the database connection
include 'includes/db_connect.php';

// Check if the connection is established
if (!$conn) {
    die("Database connection not established.");
}

// Redirect logged-in non-customer users to their dashboards
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($role === 'manager') {
        header('Location: manager/dashboard.php');
        exit;
    } elseif ($role === 'staff' || $role === 'delivery') {
        header('Location: staff/dashboard.php');
        exit;
    }
    // customer role continues to see this page
}

// Fetch top-selling products
$result = $conn->query("SELECT id, name, size, price, image FROM products ORDER BY sales DESC LIMIT 3");
if (!$result) {
    die("Query failed: " . $conn->error);
}

// Redirect non-customer logged-in users to their dashboards
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    $role = $_SESSION['role'];
    if ($role === 'admin') {
        header('Location: admin/dashboard.php');
        exit;
    } elseif ($role === 'manager') {
        header('Location: manager/dashboard.php');
        exit;
    } elseif ($role === 'staff' || $role === 'delivery') {
        header('Location: staff/dashboard.php');
        exit;
    }
    // customer role continues to see this page
}
?>

<?php include 'includes/header.php'; ?>

<!-- Hero Section -->
<section class="relative h-[80vh] flex items-center justify-center text-white">
  <video autoplay loop muted playsinline class="absolute inset-0 w-full h-full object-cover z-0">
    <source src="<?php echo $asset_path; ?>img/hero-bg.mp4" type="video/mp4">
    Your browser does not support the video tag.
  </video>
  <div class="bg-cyan-900/60 absolute inset-0 z-1"></div>
  <div class="relative z-10 text-center max-w-2xl px-4">
    <h1 class="text-4xl sm:text-5xl font-extrabold mb-4">Pure, Fresh & Fast Water Delivery</h1>
    <p class="text-lg mb-6">Stay hydrated with AquaFlow — delivering mineral water bottles straight to your doorstep.</p>
    <div class="flex justify-center space-x-4">
      <a href="products.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">Order Now</a>
      <a href="membership.php" class="border border-white text-white px-6 py-3 rounded-md hover:bg-white hover:text-cyan-700 transition font-semibold">View Plans</a>
    </div>
  </div>
</section>

<!-- Products Preview -->
<section class="py-16 bg-gray-50">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-cyan-700 mb-8">Our Products</h2>
    <div class="grid gap-8 md:grid-cols-3 sm:grid-cols-2 grid-cols-1">
      <?php if ($result->num_rows > 0): ?>
        <?php while ($row = $result->fetch_assoc()): ?>
          <div class="bg-white rounded-lg shadow hover:shadow-lg transition flex flex-col">
            <div class="w-full h-72 overflow-hidden bg-gray-200 rounded-t-lg flex items-center justify-center">
              <img src="<?= !empty($row['image']) ? $base_url . 'uploads/' . htmlspecialchars($row['image']) : $asset_path . 'img/water-placeholder.jpg' ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="w-full h-full object-contain">
            </div>
            <div class="p-5 flex-grow">
              <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($row['name']) ?></h3>
              <p class="text-gray-500 mb-2"><?= htmlspecialchars($row['size']) ?></p>
              <p class="text-cyan-700 font-semibold mb-4">Rs <?= htmlspecialchars($row['price']) ?></p>
              
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p class="text-center text-gray-500 col-span-3">No products available yet.</p>
      <?php endif; ?>
    </div>
    <div class="text-center mt-8">
      <a href="products.php" class="inline-block border border-cyan-600 text-cyan-700 px-6 py-3 rounded-md font-semibold hover:bg-cyan-600 hover:text-white transition">View All Products</a>
    </div>
  </div>
</section>

<!-- Membership Plans -->
<section class="py-16 bg-white">
  <div class="container mx-auto px-4 text-center">
    <h2 class="text-3xl font-bold text-cyan-700 mb-10">Monthly Membership Plans</h2>
    <div class="grid md:grid-cols-2 gap-8 max-w-4xl mx-auto">
      <div class="p-8 rounded-xl shadow bg-gray-50 hover:shadow-lg transition">
        <h3 class="text-2xl font-semibold text-cyan-700 mb-2">Basic Plan</h3>
        <p class="text-gray-500 mb-3">2 Bottles / Week</p>
        <p class="text-4xl font-bold mb-4">Rs 1,200 <span class="text-base font-medium">/ month</span></p>
        <a href="membership.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">Subscribe</a>
      </div>
      <div class="p-8 rounded-xl shadow bg-gray-50 hover:shadow-lg transition">
        <h3 class="text-2xl font-semibold text-cyan-700 mb-2">Family Plan</h3>
        <p class="text-gray-500 mb-3">5 Bottles / Week</p>
        <p class="text-4xl font-bold mb-4">Rs 2,800 <span class="text-base font-medium">/ month</span></p>
        <a href="membership.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-6 py-3 rounded-md font-semibold transition">Subscribe</a>
      </div>
    </div>
  </div>
</section>

<!-- Partnership Companies Section -->
<section class="py-16 bg-gradient-to-r from-cyan-50 to-blue-50">
  <div class="container mx-auto px-4">
    <h2 class="text-3xl font-bold text-center text-cyan-700 mb-12">Our Partners</h2>
    
    <!-- Logo Slider -->
    <div class="relative bg-white rounded-xl shadow-lg p-12 overflow-hidden">
      <!-- Slider Container with mask gradient -->
      <div class="relative">
        <div class="absolute left-0 top-0 bottom-0 w-12 bg-gradient-to-r from-white to-transparent z-10"></div>
        <div class="absolute right-0 top-0 bottom-0 w-12 bg-gradient-to-l from-white to-transparent z-10"></div>
        
        <!-- Scrolling wrapper -->
        <div class="overflow-hidden">
          <div class="flex gap-16 animate-scroll-continuous">
            <?php
              // Fetch partnerships from database
              $partnerships_query = "SELECT id, name, logo FROM partnerships ORDER BY id ASC";
              $partnerships_result = $conn->query($partnerships_query);
              $partnerships = [];
              
              if ($partnerships_result && $partnerships_result->num_rows > 0) {
                while ($partner = $partnerships_result->fetch_assoc()) {
                  $partnerships[] = $partner;
                }
              }
              
              // Show each partnership twice for continuous scroll
              $display_count = count($partnerships) > 0 ? count($partnerships) * 2 : 10;
              
              for ($i = 0; $i < $display_count; $i++):
                if (count($partnerships) > 0) {
                  $partner = $partnerships[$i % count($partnerships)];
                  $logo_path = !empty($partner['logo']) ? $base_url . 'uploads/partnerships/' . htmlspecialchars($partner['logo']) : $asset_path . 'img/water-placeholder.jpg';
                  $partner_name = htmlspecialchars($partner['name']);
                } else {
                  $logo_path = $asset_path . 'img/water-placeholder.jpg';
                  $partner_name = 'Partner ' . ($i + 1);
                }
            ?>
              <div class="flex items-center justify-center min-w-max flex-shrink-0">
                <div class="text-center">
                  <div class="bg-gray-50 rounded-lg p-6 h-32 w-48 flex flex-col items-center justify-center shadow-md hover:shadow-lg transition border border-gray-100 group cursor-pointer">
                    <img src="<?= $logo_path ?>" alt="<?= $partner_name ?>" class="h-20 w-20 object-contain mb-3 group-hover:scale-110 transition">
                    <span class="text-sm font-semibold text-gray-800 text-center line-clamp-2"><?= $partner_name ?></span>
                  </div>
                </div>
              </div>
            <?php endfor; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- CTA Section -->
    <div class="mt-12 bg-white rounded-xl shadow-lg p-8 text-center">
      <h3 class="text-2xl font-bold text-gray-800 mb-4">Want to Partner With Us?</h3>
      <p class="text-gray-600 mb-6 max-w-2xl mx-auto">
        We're always looking for strategic partnerships to expand our reach and deliver better services. Join our growing network of partners today!
      </p>
      <a href="contact.php" class="inline-block bg-gradient-to-r from-cyan-600 to-cyan-700 hover:from-cyan-700 hover:to-cyan-800 text-white px-8 py-3 rounded-lg font-semibold transition shadow-lg hover:shadow-xl">
        <span class="flex items-center gap-2">
          <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"></path><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"></path></svg>
          Get in Touch
        </span>
      </a>
    </div>
  </div>
</section>

<!-- CSS for Smooth Continuous Scroll Animation -->
<style>
  @keyframes scroll-continuous {
    0% {
      transform: translateX(0);
    }
    100% {
      transform: translateX(-50%);
    }
  }

  .animate-scroll-continuous {
    animation: scroll-continuous 60s linear infinite;
    will-change: transform;
  }

  .animate-scroll-continuous:hover {
    animation-play-state: paused;
  }
</style>

<?php include('includes/footer.php'); ?>
