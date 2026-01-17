<?php
session_start();

// Set a test value
$_SESSION['test_value'] = 'Hello_' . time();

echo "<h1>Session Test</h1>";
echo "<pre>";

echo "✅ Test value set: " . $_SESSION['test_value'] . "\n";
echo "✅ Session ID: " . session_id() . "\n";
echo "✅ Session save path: " . session_save_path() . "\n\n";

echo "All session data:\n";
print_r($_SESSION);

echo "</pre>";
?>

<a href="check_session_verify.php" style="background: #4CAF50; color: white; padding: 15px 30px; text-decoration: none; display: inline-block; font-size: 18px;">
    ➡️ Click Here To Verify Session Works
</a>

<hr style="margin: 30px 0;">

<p><strong>What should happen:</strong></p>
<p>When you click the button above, if session is working, you should see the same test value.</p>
