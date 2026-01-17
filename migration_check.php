<?php
require_once 'includes/db_connect.php';

echo "<h2>Database Migration Report</h2>";

// Check if users table has is_verified column
$checkColumn = $conn->query("DESCRIBE users");
$hasIsVerified = false;
$hasEmailToken = false;

while ($row = $checkColumn->fetch_assoc()) {
    if ($row['Field'] === 'is_verified') {
        $hasIsVerified = true;
    }
    if ($row['Field'] === 'email_token') {
        $hasEmailToken = true;
    }
}

// Add is_verified column if it doesn't exist
if (!$hasIsVerified) {
    $alter1 = $conn->query("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0 AFTER role");
    if ($alter1) {
        echo "✅ Added 'is_verified' column to users table<br>";
    } else {
        echo "❌ Failed to add 'is_verified' column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ 'is_verified' column already exists<br>";
}

// Add email_token column if it doesn't exist
if (!$hasEmailToken) {
    $alter2 = $conn->query("ALTER TABLE users ADD COLUMN email_token VARCHAR(255) DEFAULT NULL AFTER is_verified");
    if ($alter2) {
        echo "✅ Added 'email_token' column to users table<br>";
    } else {
        echo "❌ Failed to add 'email_token' column: " . $conn->error . "<br>";
    }
} else {
    echo "✅ 'email_token' column already exists<br>";
}

// Create email_verifications table if it doesn't exist
$checkTable = $conn->query("SHOW TABLES LIKE 'email_verifications'");
if ($checkTable->num_rows === 0) {
    $createTable = $conn->query("CREATE TABLE IF NOT EXISTS email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        verified INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    if ($createTable) {
        echo "✅ Created 'email_verifications' table<br>";
    } else {
        echo "❌ Failed to create 'email_verifications' table: " . $conn->error . "<br>";
    }
} else {
    echo "✅ 'email_verifications' table already exists<br>";
}

echo "<h3>Current users table structure:</h3>";
$describe = $conn->query("DESCRIBE users");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
while ($row = $describe->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['Field'] . "</td>";
    echo "<td>" . $row['Type'] . "</td>";
    echo "<td>" . $row['Null'] . "</td>";
    echo "<td>" . $row['Key'] . "</td>";
    echo "<td>" . $row['Default'] . "</td>";
    echo "<td>" . $row['Extra'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<br><h3>Sample users:</h3>";
$users = $conn->query("SELECT id, name, email, role, is_verified FROM users LIMIT 5");
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Verified</th></tr>";
while ($row = $users->fetch_assoc()) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['name'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "<td>" . ($row['is_verified'] ? '✅ Yes' : '❌ No') . "</td>";
    echo "</tr>";
}
echo "</table>";

$conn->close();
?>
