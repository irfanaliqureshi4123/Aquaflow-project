<?php
session_start();

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Error handling and logging setup
ini_set('display_errors', 0); // Disable direct error output
error_reporting(E_ALL); // Still catch all errors

// Debug logging function
function debugLog($message, $type = 'info', $context = []) {
    if (!is_dir(__DIR__ . '/storage/logs')) {
        mkdir(__DIR__ . '/storage/logs', 0777, true);
    }
    
    $log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'type' => $type,
        'message' => $message,
        'context' => $context,
        'url' => $_SERVER['REQUEST_URI'],
        'ip' => $_SERVER['REMOTE_ADDR']
    ];
    
    error_log(
        json_encode($log, JSON_PRETTY_PRINT) . "\n",
        3,
        __DIR__ . '/storage/logs/products_' . date('Y-m-d') . '.log'
    );
}

// Custom error handler
function handleError($message, $context = []) {
    debugLog($message, 'error', $context);
    return [
        'error' => true,
        'message' => "An error occurred while loading products. Please try again later.",
        'debug_message' => $message
    ];
}

// Database connection with error handling
try {
    if (!file_exists(__DIR__ . '/includes/db_connect.php')) {
        throw new Exception('Database connection file not found!');
    }
    include __DIR__ . '/includes/db_connect.php';
    
    if (!$conn) {
        throw new Exception('Database connection failed!');
    }
    
    // Log successful connection
  debugLog('Database connection established successfully');
    
} catch (Exception $e) {
  $error = handleError('Database connection error: ' . $e->getMessage());
}

include('includes/header.php');
?>

<!-- PAGE HEADER -->
<section class="bg-cyan-700 text-white text-center py-16">
  <div class="container mx-auto px-4">
    <h1 class="text-4xl font-bold mb-2">Our Products</h1>
    <p class="text-lg opacity-90">Pure, safe, and refreshing water — delivered right to your doorstep.</p>
  </div>
</section>

<!-- FILTERS -->
<section class="py-8 bg-gray-50 border-b border-gray-200">
  <div class="container mx-auto px-4">
    <!-- Get category counts -->
    <?php
    function getCategoryCounts($conn) {
        // First check if table is empty
        $result = $conn->query("SELECT COUNT(*) as count FROM products");
        $total = $result->fetch_assoc()['count'];
        
        if ($total === 0) {
            return ['all' => 0, 'home' => 0, 'office' => 0, 'wholesale' => 0];
        }
        
        // Get all counts in a single query
        $query = "SELECT 
            COUNT(*) as total,
            SUM(CASE 
                WHEN description LIKE '%home%' OR description LIKE '%residential%' 
                OR CAST(REGEXP_REPLACE(size, '[^0-9]', '') AS UNSIGNED) <= 20 
                THEN 1 ELSE 0 
            END) as home,
            SUM(CASE 
                WHEN description LIKE '%office%' OR description LIKE '%business%' 
                OR description LIKE '%commercial%' 
                THEN 1 ELSE 0 
            END) as office,
            SUM(CASE 
                WHEN description LIKE '%wholesale%' OR description LIKE '%bulk%' 
                THEN 1 ELSE 0 
            END) as wholesale
            FROM products";
            
        $result = $conn->query($query);
        $counts = $result->fetch_assoc();
        
        return [
            'all' => (int)$counts['total'],
            'home' => (int)$counts['home'],
            'office' => (int)$counts['office'],
            'wholesale' => (int)$counts['wholesale']
        ];
    }
    
    $categoryCounts = getCategoryCounts($conn);
    ?>

    <form method="GET" class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
      <div class="flex flex-wrap items-center gap-4">
        <!-- Category Filter -->
        <div class="flex items-center gap-3">
          <label for="category" class="font-medium text-gray-700">Filter by:</label>
          <select name="category" id="category" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
            <option value="all" <?= (!isset($_GET['category']) || $_GET['category'] === 'all') ? 'selected' : '' ?>>
              All Categories (<?= $categoryCounts['all'] ?>)
            </option>
            <option value="home" <?= isset($_GET['category']) && $_GET['category'] === 'home' ? 'selected' : '' ?>>
              Home Use (<?= $categoryCounts['home'] ?>)
            </option>
            <option value="office" <?= isset($_GET['category']) && $_GET['category'] === 'office' ? 'selected' : '' ?>>
              Office (<?= $categoryCounts['office'] ?>)
            </option>
            <option value="wholesale" <?= isset($_GET['category']) && $_GET['category'] === 'wholesale' ? 'selected' : '' ?>>
              Wholesale (<?= $categoryCounts['wholesale'] ?>)
            </option>
          </select>
        </div>

        <!-- Sort Options -->
        <div class="flex items-center gap-3">
          <label for="sort" class="font-medium text-gray-700">Sort by:</label>
          <select name="sort" id="sort" class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
            <option value="newest" <?= (!isset($_GET['sort']) || $_GET['sort'] === 'newest') ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= isset($_GET['sort']) && $_GET['sort'] === 'oldest' ? 'selected' : '' ?>>Oldest First</option>
            <option value="name_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'name_asc' ? 'selected' : '' ?>>Name (A-Z)</option>
            <option value="name_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'name_desc' ? 'selected' : '' ?>>Name (Z-A)</option>
            <option value="price_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'price_asc' ? 'selected' : '' ?>>Price (Low to High)</option>
            <option value="price_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'price_desc' ? 'selected' : '' ?>>Price (High to Low)</option>
            <option value="stock_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'stock_asc' ? 'selected' : '' ?>>Stock (Low to High)</option>
            <option value="stock_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'stock_desc' ? 'selected' : '' ?>>Stock (High to Low)</option>
            <option value="size_asc" <?= isset($_GET['sort']) && $_GET['sort'] === 'size_asc' ? 'selected' : '' ?>>Size (Small to Large)</option>
            <option value="size_desc" <?= isset($_GET['sort']) && $_GET['sort'] === 'size_desc' ? 'selected' : '' ?>>Size (Large to Small)</option>
          </select>
        </div>
      </div>

      <div class="flex items-center gap-2">
        <!-- Search Input -->
        <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" 
               placeholder="Search products..." class="border border-gray-300 rounded-md px-4 py-2 focus:ring-2 focus:ring-cyan-600 focus:outline-none">
        
        <!-- Submit and Clear Buttons -->
        <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-md font-medium">
          Search
        </button>
        <a href="products.php" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md font-medium">
          Clear
        </a>
      </div>
    </form>
  </div>
</section>

<!-- PRODUCT GRID -->
<section class="py-16 bg-white">
  <div class="container mx-auto px-4">
    <div id="productGrid" class="grid gap-8 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4">
      <?php
      // Get filter parameters
      $filter = isset($_GET['category']) ? $_GET['category'] : 'all';
      $search = isset($_GET['search']) ? $_GET['search'] : '';
      $sort = isset($_GET['sort']) ? $_GET['sort'] : 'name_asc';
      
      // Calculate pagination
      $items_per_page = 12; // Number of products per page
      $current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
      $offset = ($current_page - 1) * $items_per_page;
      
      // Initialize variables
  $query = "";
      $base_query = "FROM products";
      $where_conditions = [];
      $total_items = 0;
      $total_pages = 0;
      $result = false;
      $no_results_reason = '';
  $skip_queries = false;
      
      // Start query execution time tracking
      $start_time = microtime(true);
          
          // Check if table is empty first
          $check_empty = $conn->query("SELECT 1 FROM products LIMIT 1");
          if (!$check_empty) {
              throw new Exception("Failed to check if products table is empty: " . $conn->error);
          }
          
      if ($check_empty->num_rows === 0) {
        $no_results_reason = 'empty_db';
        debugLog('Products table is empty');
        $skip_queries = true;
      }
      
      // Add category filter based on description keywords
      if ($filter !== 'all') {
          switch($filter) {
              case 'home':
                  $where_conditions[] = "description LIKE '%home%' OR description LIKE '%residential%'";
                  break;
              case 'office':
                  $where_conditions[] = "description LIKE '%office%' OR description LIKE '%business%' OR description LIKE '%commercial%'";
                  break;
              case 'wholesale':
                  $where_conditions[] = "description LIKE '%wholesale%' OR description LIKE '%bulk%'";
                  break;
          }
      }
      
      // Add search filter if provided
      if (!empty($search)) {
          $search = $conn->real_escape_string($search);
          $where_conditions[] = "(name LIKE '%$search%' OR description LIKE '%$search%')";
      }
      
      // Combine where conditions
      if (!empty($where_conditions)) {
          $base_query .= " WHERE " . implode(" AND ", $where_conditions);
      }
      
      // Get total count for pagination
      $count_query = "SELECT COUNT(*) as total " . $base_query;
      $total_result = $conn->query($count_query);
      $total_items = $total_result->fetch_assoc()['total'];
      $total_pages = ceil($total_items / $items_per_page);
      
      try {
          // Only proceed with queries if we have data
          if ($check_empty->num_rows > 0) {
        // Simplified sorting without using PHP 8-only constructs
        // Use switch for broader PHP compatibility
        switch ($sort) {
          case 'newest':
            $order_by = "created_at DESC";
            break;
          case 'oldest':
            $order_by = "created_at ASC";
            break;
          case 'name_desc':
            $order_by = "name DESC";
            break;
          case 'price_asc':
            $order_by = "price ASC";
            break;
          case 'price_desc':
            $order_by = "price DESC";
            break;
          case 'stock_asc':
            $order_by = "stock ASC";
            break;
          case 'stock_desc':
            $order_by = "stock DESC";
            break;
          case 'size_asc':
            $order_by = "size ASC";
            break;
          case 'size_desc':
            $order_by = "size DESC";
            break;
          case 'name_asc':
          default:
            $order_by = "name ASC";
        }
              
              // Get total count for pagination
              $count_query = "SELECT COUNT(*) as total " . $base_query;
              debugLog('Executing count query', 'query', ['sql' => $count_query]);
              
              $total_result = $conn->query($count_query);
              if (!$total_result) {
                  throw new Exception("Count query failed: " . $conn->error);
              }
              
              $total_items = $total_result->fetch_assoc()['total'];
              $total_pages = ceil($total_items / $items_per_page);
              
              if ($total_items === 0) {
                  $no_results_reason = 'no_matches';
                  debugLog('No products match the current filters', 'info', [
                      'filter' => $filter,
                      'search' => $search,
                      'sort' => $sort
                  ]);
              } else {
                  // Execute main query
                  $query = "SELECT * " . $base_query . " ORDER BY " . $order_by . " LIMIT $items_per_page OFFSET $offset";
                  debugLog('Executing main query', 'query', ['sql' => $query]);
                  
                  $result = $conn->query($query);
                  if (!$result) {
                      throw new Exception("Main query failed: " . $conn->error);
                  }
                  
                  // Log query performance
                  $execution_time = microtime(true) - $start_time;
                  debugLog('Query execution completed', 'performance', [
                      'execution_time' => round($execution_time * 1000, 2) . 'ms',
                      'total_results' => $total_items,
                      'page' => $current_page,
                      'filter' => $filter
                  ]);
              }
          }
      } catch (Exception $e) {
          $error = handleError($e->getMessage(), [
              'query' => $query ?? 'No query executed',
              'filter' => $filter,
              'search' => $search
          ]);
      }
      
    // If queries are not skipped (e.g., empty DB) and a query was built, run it
    if (!$skip_queries && !empty($query)) {
      $result = $conn->query($query);
    }

    if ($result && $result->num_rows > 0):
        while ($row = $result->fetch_assoc()):
      ?>
      <div class="bg-gray-50 rounded-xl shadow hover:shadow-lg transition overflow-hidden flex flex-col">
        <div class="w-full h-80 overflow-hidden bg-gray-200 rounded-t-lg flex items-center justify-center">
          <img src="<?= !empty($row['image']) ? $base_url . 'uploads/' . htmlspecialchars($row['image']) : $asset_path . 'img/water-placeholder.jpg' ?>" alt="<?= htmlspecialchars($row['name']) ?>" class="w-full h-full object-contain">
        </div>
        <div class="p-5 flex-grow flex flex-col">
          <h3 class="text-lg font-bold text-gray-800 mb-1"><?= htmlspecialchars($row['name']) ?></h3>
          <p class="text-gray-500 mb-2"><?= htmlspecialchars($row['size']) ?></p>
          <p class="text-cyan-700 font-semibold mb-4">Rs <?= htmlspecialchars($row['price']) ?></p>
          <form method="POST" action="customer/cart_add.php" class="text-center mt-auto">
            <input type="hidden" name="product_id" value="<?= $row['id'] ?>">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="number" name="quantity" value="1" min="1" max="99" class="border rounded-lg p-2 w-full mb-4">
            <button type="submit" class="bg-cyan-600 hover:bg-cyan-700 text-white w-full py-2 rounded-md font-medium transition">
              Add to Cart
            </button>
          </form>
        </div>
      </div>
      <?php endwhile; else: ?>
          <div class="col-span-4 text-center py-8">
            <?php if (isset($error)): ?>
              <div class="bg-red-100 border border-red-300 text-red-800 px-4 py-3 rounded mb-4">
                <p class="font-medium"><?= htmlspecialchars($error['message']) ?></p>
                <?php if (defined('ENVIRONMENT') && constant('ENVIRONMENT') === 'development'): ?>
                  <p class="text-sm mt-2"><?= htmlspecialchars($error['debug_message']) ?></p>
                <?php endif; ?>
              </div>
            <?php else: ?>
              <?php switch($no_results_reason): 
                    case 'empty_db': ?>
                      <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 100-2 1 1 0 000 2zm7-1a1 1 0 11-2 0 1 1 0 012 0zm-7.536 5.879a1 1 0 001.415 0 3 3 0 014.242 0 1 1 0 001.415-1.415 5 5 0 00-7.072 0 1 1 0 000 1.415z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-xl font-semibold">No Products Yet</p>
                        <p class="text-gray-400 mt-2">The product catalog is currently empty.</p>
                      </div>
                      <?php break; ?>
                    case 'no_matches': ?>
                      <div class="text-gray-500">
                        <svg class="w-16 h-16 mx-auto mb-4" fill="currentColor" viewBox="0 0 20 20">
                          <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-xl font-semibold">No Matches Found</p>
                        <p class="text-gray-400 mt-2">Try adjusting your search or filter criteria.</p>
                        <?php if ($filter !== 'all' || !empty($search)): ?>
                          <a href="products.php" class="inline-block mt-4 text-cyan-600 hover:text-cyan-700">
                            Clear all filters
                          </a>
                        <?php endif; ?>
                      </div>
                      <?php break; ?>
                    default: ?>
                      <p class="text-gray-500 text-lg">No products found.</p>
              <?php endswitch; ?>
            <?php endif; ?>
          </div>
      <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="mt-8 flex justify-center gap-2">
      <?php if ($current_page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page - 1])) ?>" 
           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
          Previous
        </a>
      <?php endif; ?>

      <?php
      $start_page = max(1, $current_page - 2);
      $end_page = min($total_pages, $current_page + 2);
      
      if ($start_page > 1) {
          echo '<span class="px-4 py-2">...</span>';
      }
      
      for ($i = $start_page; $i <= $end_page; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" 
           class="px-4 py-2 rounded-md <?= $i === $current_page ? 'bg-cyan-600 text-white' : 'bg-gray-200 text-gray-700 hover:bg-gray-300' ?>">
          <?= $i ?>
        </a>
      <?php endfor;

      if ($end_page < $total_pages) {
          echo '<span class="px-4 py-2">...</span>';
      }
      ?>

      <?php if ($current_page < $total_pages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $current_page + 1])) ?>" 
           class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
          Next
        </a>
      <?php endif; ?>
    </div>
    <?php endif; ?>
  </div>
</section>


<?php include('includes/footer.php'); ?>
