<?php
require_once 'includes/db_connect.php';

echo "Database Migration\n";
echo "==================\n\n";

// Check if column exists
$result = $conn->query("DESCRIBE users");
$has_is_verified = false;
while ($row = $result->fetch_assoc()) {
    if ($row['Field'] === 'is_verified') {
        $has_is_verified = true;
        break;
    }
}

if (!$has_is_verified) {
    if ($conn->query("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0")) {
        echo "✅ Added is_verified column to users table\n";
    } else {
        echo "❌ Error: " . $conn->error . "\n";
        exit(1);
    }
} else {
    echo "✅ is_verified column already exists\n";
}

// Create email_verifications table if needed
$tableCheck = $conn->query("SHOW TABLES LIKE 'email_verifications'");
if ($tableCheck->num_rows === 0) {
    if ($conn->query("CREATE TABLE email_verifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(150) NOT NULL,
        token VARCHAR(255) NOT NULL,
        expires_at DATETIME NOT NULL,
        verified INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )")) {
        echo "✅ Created email_verifications table\n";
    } else {
        echo "❌ Error creating table: " . $conn->error . "\n";
    }
} else {
    echo "✅ email_verifications table already exists\n";
}

echo "\n✅ Database migration complete!\n";
$conn->close();
?>
