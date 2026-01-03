<?php
require_once '../config.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header('Location: ../seller_login.php');
    exit;
}

$conn = getDBConnection();
$seller_id = $_SESSION['seller_id'];
$errors = [];

// Get categories
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");

// Get brands
$brands = $conn->query("SELECT * FROM brands ORDER BY name");

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = $_POST['price'];
    $category_id = $_POST['category_id'];
    $brand_id = $_POST['brand_id'] ?? null;
    $model_id = $_POST['model_id'] ?? null;
    $location = trim($_POST['location']);
    $user_name = trim($_POST['user_name']);
    $user_phone = trim($_POST['user_phone']);
    $user_email = trim($_POST['user_email']);
    $status = $_POST['status'];
    
    // Validation
    if (empty($title)) $errors[] = "Title is required";
    if (empty($price) || $price < 0) $errors[] = "Valid price is required";
    if (empty($category_id)) $errors[] = "Category is required";
    if (empty($location)) $errors[] = "Location is required";
    if (empty($user_name)) $errors[] = "Contact name is required";
    if (empty($user_phone)) $errors[] = "Contact phone is required";
    
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, brand_id, model_id, title, description, price, location, user_name, user_phone, user_email, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiissdssssss", $seller_id, $category_id, $brand_id, $model_id, $title, $description, $price, $location, $user_name, $user_phone, $user_email, $status);
        
        if ($stmt->execute()) {
            header('Location: ../seller_dashboard.php?added=success');
            exit;
        } else {
            $errors[] = "Database error: " . $stmt->error;
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post New Ad</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .back-link {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s;
        }
        .back-link:hover { color: #764ba2; }
        h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .subtitle {
            color: #666;
            margin-bottom: 30px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .required { color: #dc3545; }
        input, select, textarea {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea {
            min-height: 120px;
            resize: vertical;
        }
        .btn {
            padding: 14px 32px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(86, 171, 47, 0.4);
        }
        .btn-secondary {
            background: #6c757d;
            color: white;
            text-decoration: none;
            display: inline-block;
        }
        .errors {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #dc3545;
        }
        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
        }
        /* Custom SweetAlert2 styling */
        .swal2-popup { border-radius: 20px !important; }
        .swal2-confirm { border-radius: 10px !important; padding: 12px 30px !important; }
    </style>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
</head>
<body>
    <div class="container">
        <a href="../seller_dashboard.php" class="back-link">← Back to Dashboard</a>
        
        <h1> Post New Ad</h1>
        <p class="subtitle">Fill in the details to create your classified ad</p>

        <?php if (!empty($errors)): ?>
            <div class="errors">
                <strong>Please fix the following errors:</strong>
                <ul style="margin-left: 20px; margin-top: 10px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return validateForm(event)">
            <div class="form-group full-width">
                <label>Product Title <span class="required">*</span></label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required placeholder="e.g., Samsung Galaxy S24 Ultra">
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category_id" id="category" required>
                        <option value="">Select Category</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (isset($_POST['category_id']) && $_POST['category_id'] == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand_id" id="brand">
                        <option value="">Select Brand</option>
                        <?php while($brand = $brands->fetch_assoc()): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo (isset($_POST['brand_id']) && $_POST['brand_id'] == $brand['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description" placeholder="Describe your product..."><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price (₹) <span class="required">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" required placeholder="0.00">
                </div>

                <div class="form-group">
                    <label>Location <span class="required">*</span></label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? ''); ?>" required placeholder="City, State">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Name <span class="required">*</span></label>
                    <input type="text" name="user_name" value="<?php echo htmlspecialchars($_POST['user_name'] ?? $_SESSION['seller_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact Phone <span class="required">*</span></label>
                    <input type="tel" name="user_phone" value="<?php echo htmlspecialchars($_POST['user_phone'] ?? ''); ?>" required placeholder="+91 9876543210">
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="user_email" value="<?php echo htmlspecialchars($_POST['user_email'] ?? ''); ?>" placeholder="your@email.com">
                </div>

                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status">
                        <option value="active" selected>Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">✓ Post Ad</button>
                <a href="../seller_dashboard.php" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
    
    <script>
        function validateForm(e) {
            // Additional client-side validation can be added here
            return true;
        }
    </script>
</body>
</html>