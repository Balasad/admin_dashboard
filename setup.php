<?php
require_once 'config.php';

// Check if admin already exists
$conn = getDBConnection();
$result = $conn->query("SELECT COUNT(*) as cnt FROM admin_users");
$count = $result->fetch_assoc()['cnt'];

if ($count > 0) {
    die("Setup already completed! Admin user already exists. <br><a href='admin_login.php'>Go to Login</a>");
}

// Create admin user
$username = 'admin';
$password = 'admin123';
$email = 'admin@example.com';
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $username, $hashed_password, $email);

if ($stmt->execute()) {
    echo "✓ Setup completed successfully!<br><br>";
    echo "<strong>Admin Credentials:</strong><br>";
    echo "Username: <strong>admin</strong><br>";
    echo "Password: <strong>admin123</strong><br><br>";
    echo "<a href='admin_login.php' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>Go to Admin Login</a><br><br>";
    echo "<small style='color: #666;'>Note: Delete this setup.php file after first login for security!</small>";
} else {
    echo "Error creating admin user: " . $conn->error;
}

$stmt->close();
$conn->close();
?>
