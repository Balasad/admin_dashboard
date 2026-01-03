<?php
require_once 'config.php';
$conn = getDBConnection();
$username = 'admin';
$new_password = 'admin123';
$hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $conn->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $stmt = $conn->prepare("UPDATE admin_users SET password = ? WHERE username = ?");
    $stmt->bind_param("ss", $hashed_password, $username);
    $stmt->execute();
    $message = "Password Reset Successfully!";
} else {
    $email = 'admin@example.com';
    $stmt = $conn->prepare("INSERT INTO admin_users (username, password, email) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $hashed_password, $email);
    $stmt->execute();
    $message = "Admin User Created Successfully!";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Setup Complete</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .box { background: white; border-radius: 20px; padding: 50px; max-width: 500px; text-align: center; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        h2 { color: #28a745; margin-bottom: 30px; font-size: 28px; }
        .credentials { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; border-radius: 15px; margin: 30px 0; }
        .credentials p { margin: 15px 0; font-size: 18px; }
        .credentials strong { font-size: 24px; display: block; margin-top: 5px; }
        a { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px 40px; text-decoration: none; border-radius: 50px; font-weight: bold; margin-top: 20px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); transition: transform 0.2s; }
        a:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        .warning { background: #fff3cd; color: #856404; padding: 20px; border-radius: 10px; margin-top: 30px; font-size: 14px; border: 2px solid #ffc107; }
        .icon { font-size: 60px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <div class="icon">✅</div>
        <h2><?php echo $message; ?></h2>
        <div class="credentials">
            <p>Username<br><strong>admin</strong></p>
            <p>Password<br><strong>admin123</strong></p>
        </div>
        <a href="admin_login.php">Go to Admin Login →</a>
        <div class="warning">⚠️ Delete reset_password.php after login!</div>
    </div>
</body>
</html>
<?php $stmt->close(); $conn->close(); ?>
