<?php
/**
 * DEBUG CSRF Token Issue
 * This will help us see what's going wrong
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>🔍 CSRF Debug Information</h1>";
echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";

// Test 1: Session
echo "=== SESSION CHECK ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() === PHP_SESSION_ACTIVE ? "✅ Active" : "❌ Not Active") . "\n\n";

// Test 2: Generate CSRF Token
echo "=== GENERATING CSRF TOKEN ===\n";
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    echo "✅ New token generated\n";
} else {
    echo "✅ Token already exists\n";
}

echo "Token exists: " . (isset($_SESSION['csrf_token']) ? "✅ Yes" : "❌ No") . "\n";
echo "Token value: " . (isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : "NOT SET") . "\n";
echo "Token length: " . (isset($_SESSION['csrf_token']) ? strlen($_SESSION['csrf_token']) : 0) . " chars\n\n";

// Test 3: Full Session Contents
echo "=== FULL SESSION DATA ===\n";
echo "Session contents:\n";
print_r($_SESSION);
echo "\n";

// Test 4: POST Data (if form was submitted)
echo "=== POST DATA (if any) ===\n";
if (!empty($_POST)) {
    echo "POST data received:\n";
    print_r($_POST);
} else {
    echo "No POST data (this page hasn't been submitted yet)\n";
}
echo "\n";

// Test 5: Cookie Check
echo "=== COOKIE CHECK ===\n";
echo "Session cookie name: " . session_name() . "\n";
echo "Session cookie exists: " . (isset($_COOKIE[session_name()]) ? "✅ Yes" : "❌ No") . "\n\n";

// Test 6: Server Configuration
echo "=== SERVER CONFIGURATION ===\n";
echo "PHP Version: " . phpversion() . "\n";
echo "Session Save Path: " . session_save_path() . "\n";
echo "Session Cookie Params:\n";
print_r(session_get_cookie_params());
echo "\n";

echo "</pre>";

// Now show a test form
?>

<h2>📝 Test Form</h2>
<p>Submit this form to test if the token passes correctly:</p>

<form action="debug_csrf_validate.php" method="POST" style="background: #fff; border: 2px solid #4CAF50; padding: 20px; max-width: 500px;">
    <input type="hidden" name="csrf_token" value="<?php echo isset($_SESSION['csrf_token']) ? $_SESSION['csrf_token'] : 'NOT_SET'; ?>">
    
    <div style="margin-bottom: 15px;">
        <label><strong>Test Input:</strong></label><br>
        <input type="text" name="test_data" value="Test submission" style="width: 100%; padding: 8px; border: 1px solid #ddd;">
    </div>
    
    <button type="submit" style="background: #4CAF50; color: white; padding: 12px 24px; border: none; cursor: pointer; font-size: 16px;">
        🚀 Submit Test Form
    </button>
</form>

<hr style="margin: 30px 0;">

<h2>🔗 Quick Links</h2>
<ul>
    <li><a href="customer/payment.php">Go to Payment Page</a></li>
    <li><a href="customer/cart.php">Go to Cart</a></li>
    <li><a href="debug_csrf.php">Refresh This Page</a></li>
</ul>

<hr style="margin: 30px 0;">

<h2>📋 What To Check:</h2>
<ol>
    <li>Is Session Status "Active"? Should be ✅</li>
    <li>Does Token exist? Should be ✅</li>
    <li>Is Token length 64 characters? Should be ✅</li>
    <li>Does Session cookie exist? Should be ✅</li>
</ol>

<p><strong>Copy the output above and show me if you see any ❌ marks!</strong></p>
