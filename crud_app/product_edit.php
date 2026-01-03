<?php
require_once '../config.php';

// Check authentication
$is_admin = isAdminLoggedIn();
$is_seller = isset($_SESSION['seller_id']);

if (!$is_admin && !$is_seller) {
    header('Location: ../seller_login.php');
    exit;
}

$conn = getDBConnection();
$product_id = $_GET['id'] ?? null;
$errors = [];

if (!$product_id) {
    header('Location: ../seller_dashboard.php');
    exit;
}

// Get product
if ($is_admin) {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->bind_param("i", $product_id);
} else {
    $stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $_SESSION['seller_id']);
}
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if (!$product) {
    header('Location: ../seller_dashboard.php');
    exit;
}

// Get categories and brands
$categories = $conn->query("SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name");
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
        $stmt = $conn->prepare("UPDATE products SET category_id=?, brand_id=?, model_id=?, title=?, description=?, price=?, location=?, user_name=?, user_phone=?, user_email=?, status=? WHERE id=?");
        $stmt->bind_param("iiissdsssssi", $category_id, $brand_id, $model_id, $title, $description, $price, $location, $user_name, $user_phone, $user_email, $status, $product_id);
        
        if ($stmt->execute()) {
            $redirect = $is_admin ? '../admin_dashboard.php' : '../seller_dashboard.php';
            header("Location: $redirect?updated=1");
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
    <title>Edit Product</title>
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
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 167, 38, 0.4);
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
        .info-badge {
            background: #e3f2fd;
            color: #1976d2;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2196f3;
        }
    </style>
</head>
<body>
    <div class="container">
        <?php 
        $back_url = $is_admin ? '../admin_dashboard.php' : '../seller_dashboard.php';
        ?>
        <a href="<?php echo $back_url; ?>" class="back-link">← Back to Dashboard</a>
    
        <h1> Edit Product</h1>
        <p class="subtitle">Update your product details</p>

        <div class="info-badge">
             Editing Product ID: <strong>#<?php echo htmlspecialchars($product['id']); ?></strong>
        </div>

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

        <form method="POST">
            <div class="form-group full-width">
                <label>Product Title <span class="required">*</span></label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($_POST['title'] ?? $product['title']); ?>" required>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Category <span class="required">*</span></label>
                    <select name="category_id" required>
                        <option value="">Select Category</option>
                        <?php while($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo (($_POST['category_id'] ?? $product['category_id']) == $cat['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label>Brand</label>
                    <select name="brand_id">
                        <option value="">Select Brand</option>
                        <?php while($brand = $brands->fetch_assoc()): ?>
                            <option value="<?php echo $brand['id']; ?>" <?php echo (($_POST['brand_id'] ?? $product['brand_id']) == $brand['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($brand['name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Description</label>
                <textarea name="description"><?php echo htmlspecialchars($_POST['description'] ?? $product['description']); ?></textarea>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Price (₹) <span class="required">*</span></label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo htmlspecialchars($_POST['price'] ?? $product['price']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Location <span class="required">*</span></label>
                    <input type="text" name="location" value="<?php echo htmlspecialchars($_POST['location'] ?? $product['location']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Name <span class="required">*</span></label>
                    <input type="text" name="user_name" value="<?php echo htmlspecialchars($_POST['user_name'] ?? $product['user_name']); ?>" required>
                </div>

                <div class="form-group">
                    <label>Contact Phone <span class="required">*</span></label>
                    <input type="tel" name="user_phone" value="<?php echo htmlspecialchars($_POST['user_phone'] ?? $product['user_phone']); ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label>Contact Email</label>
                    <input type="email" name="user_email" value="<?php echo htmlspecialchars($_POST['user_email'] ?? $product['user_email']); ?>">
                </div>

                <div class="form-group">
                    <label>Status <span class="required">*</span></label>
                    <select name="status">
                        <option value="active" <?php echo (($_POST['status'] ?? $product['status']) == 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="inactive" <?php echo (($_POST['status'] ?? $product['status']) == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                        <option value="sold" <?php echo (($_POST['status'] ?? $product['status']) == 'sold') ? 'selected' : ''; ?>>Sold</option>
                    </select>
                </div>
            </div>

            <div class="button-group">
                <button type="submit" class="btn btn-primary">✓ Update Product</button>
                <a href="<?php echo $back_url; ?>" class="btn btn-secondary">Cancel</a>
            </div>
        </form>
    </div>
</body>
</html>