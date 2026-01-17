<?php
/**
 * Validate CSRF Token Debug
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

echo "<h1>🔍 CSRF Validation Debug</h1>";
echo "<pre style='background: #f0f0f0; padding: 20px; border-radius: 5px;'>";

echo "=== VALIDATION PROCESS ===\n\n";

// Step 1: Check POST
echo "Step 1: POST Check\n";
echo "Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "POST data exists: " . (!empty($_POST) ? "✅ Yes" : "❌ No") . "\n\n";

// Step 2: Check SESSION token
echo "Step 2: SESSION Token Check\n";
echo "SESSION token exists: " . (isset($_SESSION['csrf_token']) ? "✅ Yes" : "❌ No") . "\n";
if (isset($_SESSION['csrf_token'])) {
    echo "SESSION token value: " . $_SESSION['csrf_token'] . "\n";
    echo "SESSION token length: " . strlen($_SESSION['csrf_token']) . " chars\n";
} else {
    echo "❌ SESSION token is MISSING!\n";
}
echo "\n";

// Step 3: Check POST token
echo "Step 3: POST Token Check\n";
echo "POST token exists: " . (isset($_POST['csrf_token']) ? "✅ Yes" : "❌ No") . "\n";
if (isset($_POST['csrf_token'])) {
    echo "POST token value: " . $_POST['csrf_token'] . "\n";
    echo "POST token length: " . strlen($_POST['csrf_token']) . " chars\n";
} else {
    echo "❌ POST token is MISSING!\n";
}
echo "\n";

// Step 4: Compare tokens
echo "Step 4: Token Comparison\n";
if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token'])) {
    echo "Both tokens present: ✅\n";
    echo "Tokens match: " . (hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']) ? "✅ YES" : "❌ NO") . "\n";
    
    if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        echo "\n⚠️  TOKENS DON'T MATCH!\n";
        echo "POST:    " . $_POST['csrf_token'] . "\n";
        echo "SESSION: " . $_SESSION['csrf_token'] . "\n";
        
        // Character comparison
        echo "\nCharacter-by-character comparison:\n";
        $post_len = strlen($_POST['csrf_token']);
        $sess_len = strlen($_SESSION['csrf_token']);
        echo "POST length: $post_len\n";
        echo "SESSION length: $sess_len\n";
    } else {
        echo "✅ TOKENS MATCH PERFECTLY!\n";
        echo "✅ CSRF VALIDATION WOULD PASS!\n";
    }
} else {
    echo "❌ One or both tokens missing!\n";
    echo "Cannot compare.\n";
}
echo "\n";

// Step 5: Full POST data
echo "Step 5: All POST Data\n";
echo "Complete POST array:\n";
print_r($_POST);
echo "\n";

// Step 6: Full SESSION data
echo "Step 6: All SESSION Data\n";
echo "Complete SESSION array:\n";
print_r($_SESSION);
echo "\n";

echo "=== CONCLUSION ===\n";
if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    echo "✅ ✅ ✅ VALIDATION SUCCESSFUL! ✅ ✅ ✅\n";
    echo "The CSRF token system is working correctly!\n";
} else {
    echo "❌ ❌ ❌ VALIDATION FAILED ❌ ❌ ❌\n";
    echo "There is an issue with the CSRF token.\n";
    echo "\nPossible causes:\n";
    if (!isset($_SESSION['csrf_token'])) echo "- SESSION token not generated\n";
    if (!isset($_POST['csrf_token'])) echo "- POST token not included in form\n";
    if (isset($_POST['csrf_token']) && isset($_SESSION['csrf_token']) && $_POST['csrf_token'] !== $_SESSION['csrf_token']) echo "- Tokens don't match (different values)\n";
}

echo "</pre>";

echo '<br><a href="debug_csrf.php" style="background: #2196F3; color: white; padding: 10px 20px; text-decoration: none; display: inline-block;">← Back to Debug Page</a>';
?>
