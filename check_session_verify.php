<?php
session_start();

echo "<h1>Session Verification</h1>";
echo "<pre>";

if (isset($_SESSION['test_value'])) {
    echo "✅ ✅ ✅ SUCCESS! Session is working! ✅ ✅ ✅\n\n";
    echo "Test value: " . $_SESSION['test_value'] . "\n";
    echo "This means sessions ARE persisting between pages.\n";
    echo "\nThe CSRF problem is something else.\n";
} else {
    echo "❌ ❌ ❌ FAILED! Session not persisting! ❌ ❌ ❌\n\n";
    echo "This means sessions are BROKEN.\n";
    echo "\nPossible causes:\n";
    echo "1. Session save path doesn't exist\n";
    echo "2. No write permission to session folder\n";
    echo "3. Cookies are blocked\n";
    echo "4. Session.auto_start disabled\n";
}

echo "\nSession ID: " . session_id() . "\n";
echo "Session save path: " . session_save_path() . "\n\n";

echo "All session data:\n";
print_r($_SESSION);

echo "</pre>";
?>

<a href="check_session.php" style="background: #2196F3; color: white; padding: 15px 30px; text-decoration: none; display: inline-block;">
    ← Test Again
</a>
