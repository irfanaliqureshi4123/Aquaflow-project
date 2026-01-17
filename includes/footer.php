<!-- CTA Section - Hidden for Admin Users -->
<?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
<section class="bg-cyan-700 text-white text-center py-12">
  <div class="container mx-auto px-4">
    <h3 class="text-2xl font-bold mb-3">Need bulk supply or have questions?</h3>
    <p class="mb-6 text-lg">We provide special pricing for wholesalers and offices.</p>
    <a href="<?= $base_url ?>contact.php" class="border border-white px-6 py-3 rounded-md hover:bg-white hover:text-cyan-700 font-semibold transition">Contact Us</a>
  </div>
</section>
<?php endif; ?>

<!-- Footer -->
<footer class="bg-gray-900 text-gray-300 py-8 md:py-12">
  <div class="container mx-auto px-4 max-w-7xl">
    <!-- Footer Content Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 md:gap-8 mb-8">
      <!-- About Us -->
      <div class="text-center sm:text-left">
        <h4 class="text-white font-bold text-base md:text-lg mb-4">About AquaFlow</h4>
        <p class="text-xs md:text-sm leading-relaxed">AquaFlow is your trusted partner for pure, fresh mineral water delivery. Quality and customer satisfaction are our priorities.</p>
        <div class="mt-4 flex justify-center sm:justify-start space-x-4">
          <a href="#" class="text-gray-400 hover:text-cyan-400 transition duration-300" title="Facebook">
            <i class="fab fa-facebook-f text-lg md:text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-cyan-400 transition duration-300" title="Twitter">
            <i class="fab fa-twitter text-lg md:text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-cyan-400 transition duration-300" title="Instagram">
            <i class="fab fa-instagram text-lg md:text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-cyan-400 transition duration-300" title="LinkedIn">
            <i class="fab fa-linkedin-in text-lg md:text-xl"></i>
          </a>
          <a href="#" class="text-gray-400 hover:text-cyan-400 transition duration-300" title="WhatsApp">
            <i class="fab fa-whatsapp text-lg md:text-xl"></i>
          </a>
        </div>
      </div>

      <!-- Quick Links -->
      <div class="text-center sm:text-left">
        <h4 class="text-white font-bold text-base md:text-lg mb-4">Quick Links</h4>
        <ul class="space-y-2 text-xs md:text-sm">
          <li><a href="<?= $base_url ?>products.php" class="hover:text-cyan-400 transition duration-300">Products</a></li>
          <li><a href="<?= $base_url ?>membership.php" class="hover:text-cyan-400 transition duration-300">Membership Plans</a></li>
          <?php if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin'): ?>
          <li><a href="<?= $base_url ?>contact.php" class="hover:text-cyan-400 transition duration-300">Contact Us</a></li>
          <?php endif; ?>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">FAQ</a></li>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Blog</a></li>
        </ul>
      </div>

      <!-- Customer Service -->
      <div class="text-center sm:text-left">
        <h4 class="text-white font-bold text-base md:text-lg mb-4">Customer Service</h4>
        <ul class="space-y-2 text-xs md:text-sm">
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Order Tracking</a></li>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Return Policy</a></li>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Shipping Info</a></li>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Support</a></li>
          <li><a href="#" class="hover:text-cyan-400 transition duration-300">Privacy Policy</a></li>
        </ul>
      </div>

      <!-- Contact Info -->
      <div class="text-center sm:text-left">
        <h4 class="text-white font-bold text-base md:text-lg mb-4">Contact Info</h4>
        <ul class="space-y-2 text-xs md:text-sm">
          <li>
            <span class="font-semibold text-white">Phone:</span><br>
            <a href="tel:+923001234567" class="hover:text-cyan-400 transition duration-300">+92 300 1234567</a>
          </li>
          <li>
            <span class="font-semibold text-white">Email:</span><br>
            <a href="mailto:info@aquaflow.pk" class="hover:text-cyan-400 transition duration-300">info@aquaflow.pk</a>
          </li>
          <li>
            <span class="font-semibold text-white">Address:</span><br>
            <span>Karachi, Pakistan</span>
          </li>
        </ul>
      </div>
    </div>

    <!-- Footer Divider -->
    <div class="border-t border-gray-700 pt-6 md:pt-8">
      <!-- Footer Bottom -->
      <div class="flex flex-col sm:flex-row justify-center sm:justify-between items-center gap-4">
        <div class="text-xs md:text-sm text-center sm:text-left">
          <p>&copy; <?= date('Y'); ?> AquaFlow Water Supply. All rights reserved.</p>
          <p class="mt-2">Developed by 💧 AquaFlow Team</p>
        </div>
        
        <!-- Footer Links -->
        <div class="flex flex-wrap justify-center sm:justify-end gap-3 md:gap-6 text-xs md:text-sm">
          <a href="#" class="hover:text-cyan-400 transition duration-300">Terms & Conditions</a>
          <a href="#" class="hover:text-cyan-400 transition duration-300">Privacy Policy</a>
          <a href="#" class="hover:text-cyan-400 transition duration-300">Sitemap</a>
        </div>
      </div>
    </div>
  </div>
</footer>

</body>
</html>
