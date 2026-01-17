<?php
require_once('../includes/db_connect.php');

// Collect form data
$id = $_POST['id'] ?? '';
$name = trim($_POST['name']);
$email = trim($_POST['email']);
$password = $_POST['password'] ?? '';
$role = $_POST['role'] ?? 'customer';

// Prevent duplicate emails
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $id);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {
    echo "<script>alert('⚠️ Email already exists!'); window.history.back();</script>";
    exit;
}

if ($id) {
    // --- Update existing user ---
    if (!empty($password)) {
        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, password=?, role=? WHERE id=?");
        $stmt->bind_param("ssssi", $name, $email, $hashed, $role, $id);
    } else {
        $stmt = $conn->prepare("UPDATE users SET name=?, email=?, role=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $email, $role, $id);
    }
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('✅ User updated successfully!'); window.location='../admin/users.php';</script>";

} else {
    // --- Add new user ---
    if (empty($password)) {
        echo "<script>alert('⚠️ Password required for new users!'); window.history.back();</script>";
        exit;
    }
    $hashed = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashed, $role);
    $stmt->execute();
    $stmt->close();
    echo "<script>alert('✅ New user added successfully!'); window.location='../admin/users.php';</script>";
}

$conn->close();
?>
