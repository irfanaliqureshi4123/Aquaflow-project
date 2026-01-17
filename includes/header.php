<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Define base URL dynamically based on the current request
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
$host = $_SERVER['HTTP_HOST'];
$base_url = $protocol . $host . '/aquaWater/';

$asset_path = $base_url . 'assets/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>AquaFlow | Pure Water Supply</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <link href="<?php echo $asset_path; ?>css/style.css" rel="stylesheet">
</head>
<body class="text-gray-800 bg-white font-sans">

<!-- Navbar -->
<header class="bg-white shadow sticky top-0 z-50">
  <div class="container mx-auto px-4 py-3 flex items-center justify-between">
    <a href="<?php echo $base_url; ?>index.php" class="flex items-center space-x-2">
      <img src="<?php echo $asset_path; ?>img/logo.png" alt="AquaFlow logo - pure water supply company" class="w-10 h-10">
     <span class="text-2xl font-bold text-cyan-700">AquaFlow</span>
    </a>
    <button id="menu-toggle" class="lg:hidden text-cyan-700 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
    <nav id="menu" class="hidden lg:flex space-x-6 text-gray-700">
      <?php if (!isset($_SESSION['user_id'])): ?>
        <!-- Logged-out visitors -->
        <a href="<?php echo $base_url; ?>index.php" class="hover:text-cyan-600 font-medium">Home</a>
        <a href="<?php echo $base_url; ?>products.php" class="hover:text-cyan-600 font-medium">Products</a>
        <a href="<?php echo $base_url; ?>membership.php" class="hover:text-cyan-600 font-medium">Membership</a>
        <a href="<?php echo $base_url; ?>contact.php" class="hover:text-cyan-600 font-medium">Contact</a>
        <a href="<?php echo $base_url; ?>login.php" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-semibold">Login</a>
      <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
        <!-- Logged-in customers -->
        <a href="<?php echo $base_url; ?>index.php" class="hover:text-cyan-600 font-medium">Home</a>
        <a href="<?php echo $base_url; ?>products.php" class="hover:text-cyan-600 font-medium">Products</a>
        <a href="<?php echo $base_url; ?>customer/cart.php" class="hover:text-cyan-600 font-medium">Cart</a>
        <a href="<?php echo $base_url; ?>customer/orders.php" class="hover:text-cyan-600 font-medium">Orders</a>
        <a href="<?php echo $base_url; ?>customer/membership.php" class="hover:text-cyan-600 font-medium">Membership</a>
        <a href="<?php echo $base_url; ?>contact.php" class="hover:text-cyan-600 font-medium">Contact</a>
        <a href="<?php echo $base_url; ?>customer/profile.php" class="hover:text-cyan-600 font-medium">Profile</a>
        <a href="<?php echo $base_url; ?>logout.php" class="text-red-600 hover:text-red-700 font-medium">Logout</a>
      <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
        <!-- Admin features in navbar -->
        <a href="<?php echo $base_url; ?>admin/dashboard.php" class="hover:text-cyan-600 font-medium">Admin Dashboard</a>
        <a href="<?php echo $base_url; ?>admin/products.php" class="hover:text-cyan-600 font-medium">Manage Products</a>
        <a href="<?php echo $base_url; ?>admin/orders.php" class="hover:text-cyan-600 font-medium">View Orders</a>
        <a href="<?php echo $base_url; ?>admin/customers.php" class="hover:text-cyan-600 font-medium">View Customers</a>
        <a href="<?php echo $base_url; ?>admin/staff.php" class="hover:text-cyan-600 font-medium">Manage Staff</a>
        <a href="<?php echo $base_url; ?>admin/manual_order.php" class="hover:text-cyan-600 font-medium">Create Order</a>
        <a href="<?php echo $base_url; ?>admin/messages.php" class="hover:text-cyan-600 font-medium">Messages</a>
        <a href="<?php echo $base_url; ?>logout.php" class="text-red-600 hover:text-red-700 font-medium">Logout</a>
      <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
        <!-- Staff features in navbar -->
        <a href="<?php echo $base_url; ?>staff/dashboard.php" class="hover:text-cyan-600 font-medium">Dashboard</a>
        <a href="<?php echo $base_url; ?>staff/products.php" class="hover:text-cyan-600 font-medium">Products</a>
        <a href="<?php echo $base_url; ?>staff/membership.php" class="hover:text-cyan-600 font-medium">Membership</a>
        <a href="<?php echo $base_url; ?>staff/orders.php" class="hover:text-cyan-600 font-medium">Manage Orders</a>
        <a href="<?php echo $base_url; ?>staff/deliveries.php" class="hover:text-cyan-600 font-medium">Track Deliveries</a>
        <a href="<?php echo $base_url; ?>logout.php" class="text-red-600 hover:text-red-700 font-medium">Logout</a>
      <?php else: ?>
        <div class="relative dropdown-container">
          <button class="flex items-center space-x-1 hover:text-cyan-600 font-medium dropdown-button" aria-expanded="false">
            <span><?php echo htmlspecialchars($_SESSION['name'] ?? 'Account'); ?></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
            </svg>
          </button>
          <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 dropdown-menu opacity-0 invisible transform scale-95 transition-all duration-200 ease-in-out z-50">
            <!-- Role Badge -->
            <div class="px-4 py-2 border-b border-gray-200">
              <span class="text-xs font-bold px-2 py-1 rounded inline-block
                <?php 
                  $role = $_SESSION['role'] ?? '';
                  switch($role) {
                    case 'admin': echo 'bg-red-100 text-red-800'; break;
                    case 'manager': echo 'bg-purple-100 text-purple-800'; break;
                    case 'staff': echo 'bg-blue-100 text-blue-800'; break;
                    case 'delivery': echo 'bg-orange-100 text-orange-800'; break;
                    case 'customer': echo 'bg-green-100 text-green-800'; break;
                    default: echo 'bg-gray-100 text-gray-800';
                  }
                ?>">
                <?php echo ucfirst($role); ?>
              </span>
            </div>
            <!-- Role-Specific Links -->
            <?php 
            $role = $_SESSION['role'] ?? '';
            if ($role === 'manager'): ?>
              <a href="<?php echo $base_url; ?>manager/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-tachometer-alt mr-2"></i>Manager Dashboard</a>
              <a href="<?php echo $base_url; ?>staff/orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-box mr-2"></i>Orders</a>
              <a href="<?php echo $base_url; ?>staff/deliveries.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-truck mr-2"></i>Deliveries</a>
            <?php elseif ($role === 'staff' || $role === 'delivery'): ?>
              <a href="<?php echo $base_url; ?>staff/dashboard.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-tachometer-alt mr-2"></i>Dashboard</a>
              <a href="<?php echo $base_url; ?>staff/orders.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-box mr-2"></i>Orders</a>
              <a href="<?php echo $base_url; ?>staff/deliveries.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100"><i class="fas fa-truck mr-2"></i>Deliveries</a>
            <?php endif; ?>
            <div class="border-t border-gray-200">
              <a href="<?php echo $base_url; ?>logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-gray-100"><i class="fas fa-sign-out-alt mr-2"></i>Logout</a>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </nav>
  </div>
  <!-- Mobile Menu -->
  <div id="mobile-menu" class="lg:hidden hidden bg-white border-t border-gray-200">
    <?php if (!isset($_SESSION['user_id'])): ?>
      <!-- Logged-out visitors mobile menu -->
      <a href="<?php echo $base_url; ?>index.php" class="block px-4 py-2 hover:bg-gray-100">Home</a>
      <a href="<?php echo $base_url; ?>products.php" class="block px-4 py-2 hover:bg-gray-100">Products</a>
      <a href="<?php echo $base_url; ?>membership.php" class="block px-4 py-2 hover:bg-gray-100">Membership</a>
      <a href="<?php echo $base_url; ?>contact.php" class="block px-4 py-2 hover:bg-gray-100">Contact</a>
      <a href="<?php echo $base_url; ?>login.php" class="block px-4 py-2 text-cyan-700 font-semibold">Login</a>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'customer'): ?>
      <!-- Logged-in customers mobile menu -->
      <a href="<?php echo $base_url; ?>index.php" class="block px-4 py-2 hover:bg-gray-100">Home</a>
      <a href="<?php echo $base_url; ?>products.php" class="block px-4 py-2 hover:bg-gray-100">Products</a>
      <a href="<?php echo $base_url; ?>customer/cart.php" class="block px-4 py-2 hover:bg-gray-100">Cart</a>
      <a href="<?php echo $base_url; ?>customer/orders.php" class="block px-4 py-2 hover:bg-gray-100">Orders</a>
      <a href="<?php echo $base_url; ?>customer/membership.php" class="block px-4 py-2 hover:bg-gray-100">Membership</a>
      <a href="<?php echo $base_url; ?>contact.php" class="block px-4 py-2 hover:bg-gray-100">Contact</a>
      <a href="<?php echo $base_url; ?>customer/profile.php" class="block px-4 py-2 hover:bg-gray-100">Profile</a>
      <a href="<?php echo $base_url; ?>logout.php" class="block px-4 py-2 text-red-600">Logout</a>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
      <!-- Admin features mobile menu -->
      <a href="<?php echo $base_url; ?>admin/dashboard.php" class="block px-4 py-2 hover:bg-gray-100">Admin Dashboard</a>
      <a href="<?php echo $base_url; ?>admin/products.php" class="block px-4 py-2 hover:bg-gray-100">Manage Products</a>
      <a href="<?php echo $base_url; ?>admin/orders.php" class="block px-4 py-2 hover:bg-gray-100">View Orders</a>
      <a href="<?php echo $base_url; ?>admin/customers.php" class="block px-4 py-2 hover:bg-gray-100">View Customers</a>
      <a href="<?php echo $base_url; ?>admin/staff.php" class="block px-4 py-2 hover:bg-gray-100">Manage Staff</a>
      <a href="<?php echo $base_url; ?>admin/manual_order.php" class="block px-4 py-2 hover:bg-gray-100">Create Order</a>
      <a href="<?php echo $base_url; ?>admin/messages.php" class="block px-4 py-2 hover:bg-gray-100">Messages</a>
      <a href="<?php echo $base_url; ?>logout.php" class="block px-4 py-2 text-red-600">Logout</a>
    <?php elseif (isset($_SESSION['role']) && $_SESSION['role'] === 'staff'): ?>
      <!-- Staff features mobile menu -->
      <a href="<?php echo $base_url; ?>staff/dashboard.php" class="block px-4 py-2 hover:bg-gray-100">Dashboard</a>
      <a href="<?php echo $base_url; ?>staff/products.php" class="block px-4 py-2 hover:bg-gray-100">Products</a>
      <a href="<?php echo $base_url; ?>staff/membership.php" class="block px-4 py-2 hover:bg-gray-100">Membership</a>
      <a href="<?php echo $base_url; ?>staff/orders.php" class="block px-4 py-2 hover:bg-gray-100">Manage Orders</a>
      <a href="<?php echo $base_url; ?>staff/deliveries.php" class="block px-4 py-2 hover:bg-gray-100">Track Deliveries</a>
      <a href="<?php echo $base_url; ?>logout.php" class="block px-4 py-2 text-red-600">Logout</a>
    <?php else: ?>
      <!-- Non-customer, non-admin logged-in users (manager, staff, delivery) -->
      <div class="border-t border-gray-200">
        <div class="px-4 py-3">
          <span class="text-xs font-bold px-2 py-1 rounded inline-block
            <?php 
              $role = $_SESSION['role'] ?? '';
              switch($role) {
                case 'manager': echo 'bg-purple-100 text-purple-800'; break;
                case 'staff': echo 'bg-blue-100 text-blue-800'; break;
                case 'delivery': echo 'bg-orange-100 text-orange-800'; break;
                default: echo 'bg-gray-100 text-gray-800';
              }
            ?>">
            <?php echo ucfirst($role); ?>
          </span>
        </div>
        <?php 
        $role = $_SESSION['role'] ?? '';
        if ($role === 'manager'): ?>
          <a href="<?php echo $base_url; ?>manager/dashboard.php" class="block px-4 py-2 hover:bg-gray-100">Manager Dashboard</a>
          <a href="<?php echo $base_url; ?>staff/orders.php" class="block px-4 py-2 hover:bg-gray-100">Orders</a>
          <a href="<?php echo $base_url; ?>staff/deliveries.php" class="block px-4 py-2 hover:bg-gray-100">Deliveries</a>
        <?php elseif ($role === 'staff' || $role === 'delivery'): ?>
          <a href="<?php echo $base_url; ?>staff/dashboard.php" class="block px-4 py-2 hover:bg-gray-100">Dashboard</a>
          <a href="<?php echo $base_url; ?>staff/orders.php" class="block px-4 py-2 hover:bg-gray-100">Orders</a>
          <a href="<?php echo $base_url; ?>staff/deliveries.php" class="block px-4 py-2 hover:bg-gray-100">Deliveries</a>
        <?php endif; ?>
        <a href="<?php echo $base_url; ?>logout.php" class="block px-4 py-2 text-red-600">Logout</a>
      </div>
    <?php endif; ?>
  </div>
</header>

<style>
  .dropdown-menu.active {
    opacity: 1;
    visibility: visible;
    transform: scale(100%);
  }
  .dropdown-button[aria-expanded="true"] svg {
    transform: rotate(180deg);
  }
</style>

<script>
  // Mobile menu toggle
  const menuToggle = document.getElementById('menu-toggle');
  const mobileMenu = document.getElementById('mobile-menu');
  menuToggle.addEventListener('click', () => {
    mobileMenu.classList.toggle('hidden');
  });

  // Dropdown functionality
  document.querySelectorAll('.dropdown-container').forEach(dropdown => {
    const button = dropdown.querySelector('.dropdown-button');
    const menu = dropdown.querySelector('.dropdown-menu');
    let timeoutId;

    // Show menu
    dropdown.addEventListener('mouseenter', () => {
      clearTimeout(timeoutId);
      button.setAttribute('aria-expanded', 'true');
      menu.classList.add('active');
    });

    // Hide menu with delay
    dropdown.addEventListener('mouseleave', () => {
      timeoutId = setTimeout(() => {
        button.setAttribute('aria-expanded', 'false');
        menu.classList.remove('active');
      }, 500); // 500ms delay before hiding
    });

    // Handle touch events for mobile
    button.addEventListener('click', (e) => {
      e.preventDefault();
      const isExpanded = button.getAttribute('aria-expanded') === 'true';
      button.setAttribute('aria-expanded', !isExpanded);
      menu.classList.toggle('active');
    });

    // Close dropdown when clicking outside
    document.addEventListener('click', (e) => {
      if (!dropdown.contains(e.target)) {
        button.setAttribute('aria-expanded', 'false');
        menu.classList.remove('active');
      }
    });
  });
</script>
