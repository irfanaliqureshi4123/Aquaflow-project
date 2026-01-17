<?php
require_once 'includes/db_connect.php';

echo "<h2>Database Verification</h2>";

// Check users table
$result = $conn->query("DESCRIBE users");
echo "<h3>Users Table Structure:</h3>";
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td><strong>" . $row['Field'] . "</strong></td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . ($row['Key'] ?: '-') . "</td>";
    echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check email_verifications table
echo "<h3>Email Verifications Table:</h3>";
$result = $conn->query("DESCRIBE email_verifications");
if ($result && $result->num_rows > 0) {
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td><strong>" . $row['Field'] . "</strong></td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . ($row['Key'] ?: '-') . "</td>";
        echo "<td>" . ($row['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p style='color: green; font-weight: bold;'>✅ All tables configured correctly!</p>";
} else {
    echo "<p style='color: red;'>❌ Email verifications table not found</p>";
}

$conn->close();
?>
