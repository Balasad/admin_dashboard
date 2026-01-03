<?php
require_once 'config.php';

// Check if already logged in
if (isset($_SESSION['seller_id'])) {
    header('Location: seller_dashboard.php');
    exit;
}

$error = '';
$success = '';

// Handle Login
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $conn = getDBConnection();
    $stmt = $conn->prepare("SELECT id, name, password FROM sellers WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($seller = $result->fetch_assoc()) {
        if (password_verify($password, $seller['password'])) {
            $_SESSION['seller_id'] = $seller['id'];
            $_SESSION['seller_name'] = $seller['name'];
            header('Location: seller_dashboard.php');
            exit;
        }
    }
    
    $error = 'Invalid email or password';
    $stmt->close();
    $conn->close();
}

// Handle Registration
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);
    $location = trim($_POST['location']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($password !== $confirm_password) {
        $error = 'Passwords do not match';
    } else {
        $conn = getDBConnection();
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT id FROM sellers WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        if ($stmt->get_result()->num_rows > 0) {
            $error = 'Email already registered';
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("INSERT INTO sellers (name, email, phone, location, password) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("sssss", $name, $email, $phone, $location, $hashed_password);
            
            if ($stmt->execute()) {
                $success = 'Registration successful! Please login.';
            } else {
                $error = 'Registration failed. Please try again.';
            }
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seller Login - ClassifiedAds</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; justify-content: center; align-items: center; padding: 20px; }
        .container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 900px; width: 100%; overflow: hidden; display: grid; grid-template-columns: 1fr 1fr; }
        .left-panel { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 60px 40px; display: flex; flex-direction: column; justify-content: center; }
        .logo { font-size: 48px; margin-bottom: 20px; }
        .left-panel h1 { font-size: 36px; margin-bottom: 20px; }
        .left-panel p { font-size: 18px; opacity: 0.9; line-height: 1.6; }
        .feature { display: flex; align-items: center; gap: 15px; margin: 15px 0; }
        .feature-icon { font-size: 32px; }
        .right-panel { padding: 60px 40px; }
        .tabs { display: flex; gap: 20px; margin-bottom: 30px; border-bottom: 2px solid #e0e0e0; }
        .tab { padding: 15px 0; cursor: pointer; font-weight: 600; color: #999; border-bottom: 3px solid transparent; transition: all 0.3s; }
        .tab.active { color: #667eea; border-bottom-color: #667eea; }
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        h2 { font-size: 28px; margin-bottom: 30px; color: #333; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #555; }
        input { width: 100%; padding: 14px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 15px; transition: all 0.3s; }
        input:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
        .btn { width: 100%; padding: 15px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 10px; font-size: 16px; font-weight: bold; cursor: pointer; transition: all 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4); }
        .error { background: #fee; color: #c33; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #fcc; }
        .success { background: #efe; color: #3c3; padding: 15px; border-radius: 10px; margin-bottom: 20px; border: 2px solid #cfc; }
        .back-link { text-align: center; margin-top: 20px; }
        .back-link a { color: #667eea; text-decoration: none; font-weight: 600; }
        @media (max-width: 768px) {
            .container { grid-template-columns: 1fr; }
            .left-panel { padding: 40px 30px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-panel">
            <div class="logo"></div>
            <h1>Seller Portal</h1>
            <p>Join thousands of sellers on ClassifiedAds</p>
            
            <div style="margin-top: 40px;">
                <div class="feature">
                    <div class="feature-icon">📱</div>
                    <div>
                        <strong>Easy Listing</strong><br>
                        <small>Post ads in minutes</small>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon"></div>
                    <div>
                        <strong>Track Performance</strong><br>
                        <small>View stats & analytics</small>
                    </div>
                </div>
                <div class="feature">
                    <div class="feature-icon"></div>
                    <div>
                        <strong>Sell Faster</strong><br>
                        <small>Reach millions of buyers</small>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="right-panel">
            <div class="tabs">
                <div class="tab active" onclick="switchTab('login')">Login</div>
                <div class="tab" onclick="switchTab('register')">Register</div>
            </div>
            
            <?php if ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="success"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            
            <!-- Login Form -->
            <div id="login" class="tab-content active">
                <h2>Welcome Back!</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Enter your password">
                    </div>
                    <button type="submit" name="login" class="btn">Login to Dashboard</button>
                </form>
            </div>
            
            <!-- Register Form -->
            <div id="register" class="tab-content">
                <h2>Create Account</h2>
                <form method="POST">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="name" required placeholder="Your full name">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required placeholder="your@email.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="tel" name="phone" required placeholder="Your phone number">
                    </div>
                    <div class="form-group">
                        <label>Location</label>
                        <input type="text" name="location" required placeholder="City, State">
                    </div>
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Create a password">
                    </div>
                    <div class="form-group">
                        <label>Confirm Password</label>
                        <input type="password" name="confirm_password" required placeholder="Confirm password">
                    </div>
                    <button type="submit" name="register" class="btn">Create Account</button>
                </form>
            </div>
            
            <div class="back-link">
                <a href="index.php">← Back to Main Site</a> | 
                <a href="admin_login.php">Admin Login</a>
            </div>
        </div>
    </div>
    
    <script>
        function switchTab(tab) {
            document.querySelectorAll('.tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
            event.target.classList.add('active');
            document.getElementById(tab).classList.add('active');
        }
    </script>
</body>
</html>


