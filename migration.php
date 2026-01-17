<?php
require_once "includes/db_connect.php";

echo "<h2>Database Migration</h2>";

// Check current users table structure
$result = $conn->query("DESCRIBE users");
$columns = [];
while ($row = $result->fetch_assoc()) {
    $columns[$row["Field"]] = $row["Type"];
}

// Add is_verified column if missing
if (!isset($columns["is_verified"])) {
    if ($conn->query("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0")) {
        echo " Added is_verified column<br>";
    } else {
        echo " Error adding is_verified: " . $conn->error . "<br>";
    }
} else {
    echo "✅ is_verified column already exists<br>";
}

// Check current structure
$result = $conn->query("DESCRIBE users");
echo "<h3>Current users table structure:</h3>";
echo "<table border=\"1\" cellpadding=\"5\">";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
while ($row = $result->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row["Field"] . "</td>";
    echo "<td>" . $row["Type"] . "</td>";
    echo "<td>" . $row["Null"] . "</td>";
    echo "<td>" . $row["Key"] . "</td>";
    echo "<td>" . ($row["Default"] ?? "NULL") . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
