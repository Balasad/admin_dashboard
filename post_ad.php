<?php
require_once 'config.php';

// Check if seller is logged in
if (!isset($_SESSION['seller_id'])) {
    header('Location: seller_login.php');
    exit;
}

$conn = getDBConnection();
$seller_id = $_SESSION['seller_id'];

// Get seller info
$stmt = $conn->prepare("SELECT * FROM sellers WHERE id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$seller = $stmt->get_result()->fetch_assoc();

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    if ($_GET['action'] === 'get_subcategories') {
        $parent_id = isset($_GET['parent_id']) ? intval($_GET['parent_id']) : 0;
        $sql = $parent_id > 0 
            ? "SELECT * FROM categories WHERE parent_id = ? ORDER BY name"
            : "SELECT * FROM categories WHERE parent_id IS NULL ORDER BY name";
        $stmt = $conn->prepare($sql);
        if ($parent_id > 0) $stmt->bind_param("i", $parent_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $cats = [];
        while ($row = $result->fetch_assoc()) $cats[] = $row;
        echo json_encode($cats);
        exit;
    }
    
    if ($_GET['action'] === 'get_brands') {
        $category_id = intval($_GET['category_id']);
        $stmt = $conn->prepare("SELECT * FROM brands WHERE category_id = ? ORDER BY name");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $brands = [];
        while ($row = $result->fetch_assoc()) $brands[] = $row;
        echo json_encode($brands);
        exit;
    }
    
    if ($_GET['action'] === 'get_models') {
        $brand_id = intval($_GET['brand_id']);
        $stmt = $conn->prepare("SELECT * FROM models WHERE brand_id = ? ORDER BY name");
        $stmt->bind_param("i", $brand_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $models = [];
        while ($row = $result->fetch_assoc()) $models[] = $row;
        echo json_encode($models);
        exit;
    }
    
    if ($_GET['action'] === 'get_category_fields') {
        $category_id = intval($_GET['category_id']);
        $categoryIds = [$category_id];
        $current = $category_id;
        while ($current) {
            $stmt = $conn->prepare("SELECT parent_id FROM categories WHERE id = ?");
            $stmt->bind_param("i", $current);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            if ($result && $result['parent_id']) {
                $categoryIds[] = $result['parent_id'];
                $current = $result['parent_id'];
            } else {
                $current = null;
            }
        }
        
        $placeholders = implode(',', array_fill(0, count($categoryIds), '?'));
        $stmt = $conn->prepare("SELECT cf.*, c.name as category_name 
                                FROM category_fields cf 
                                JOIN categories c ON cf.category_id = c.id 
                                WHERE cf.category_id IN ($placeholders) 
                                ORDER BY cf.display_order");
        $stmt->bind_param(str_repeat('i', count($categoryIds)), ...$categoryIds);
        $stmt->execute();
        $result = $stmt->get_result();
        $fields = [];
        while ($row = $result->fetch_assoc()) $fields[] = $row;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM categories WHERE parent_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        $hasChildren = $stmt->get_result()->fetch_assoc()['cnt'] > 0;
        
        echo json_encode(['fields' => $fields, 'hasChildren' => $hasChildren]);
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_product'])) {
    $category_id = intval($_POST['category_id']);
    $brand_id = !empty($_POST['brand_id']) ? intval($_POST['brand_id']) : null;
    $model_id = !empty($_POST['model_id']) ? intval($_POST['model_id']) : null;
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $location = trim($_POST['location']);
    $user_name = trim($_POST['user_name']);
    $user_phone = trim($_POST['user_phone']);
    $user_email = trim($_POST['user_email']);
    
    // FIXED: Now includes seller_id and status
    $status = 'active';
    $stmt = $conn->prepare("INSERT INTO products (seller_id, category_id, brand_id, model_id, title, description, price, location, user_name, user_phone, user_email, status) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiiissdsssss", $seller_id, $category_id, $brand_id, $model_id, $title, $description, $price, $location, $user_name, $user_phone, $user_email, $status);
    $stmt->execute();
    $product_id = $conn->insert_id;
    
    // Save custom field values
    foreach ($_POST as $key => $value) {
        if (strpos($key, 'field_') === 0) {
            $field_id = intval(str_replace('field_', '', $key));
            $stmt = $conn->prepare("INSERT INTO product_field_values (product_id, field_id, field_value) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $product_id, $field_id, $value);
            $stmt->execute();
        }
    }
    
    // FIXED: Redirect to seller dashboard instead of post_ad.php
    header('Location: seller_dashboard.php?success=added');
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Your Ad - Seller Portal</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        
        /* Header */
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header-content { max-width: 1200px; margin: 0 auto; padding: 25px 30px; display: flex; justify-content: space-between; align-items: center; }
        .logo { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 60px; height: 60px; background: rgba(255,255,255,0.2); border-radius: 15px; display: flex; align-items: center; justify-content: center; font-size: 32px; }
        .logo h1 { font-size: 32px; font-weight: 700; }
        .logo p { font-size: 14px; opacity: 0.9; }
        .header-right { display: flex; align-items: center; gap: 15px; }
        .user-info { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; }
        .dashboard-btn { background: rgba(255,255,255,0.2); padding: 12px 25px; border-radius: 50px; text-decoration: none; color: white; font-weight: 600; border: 2px solid white; transition: all 0.3s; }
        .dashboard-btn:hover { background: white; color: #667eea; }
        
        /* Hero */
        .hero { background: white; border-bottom: 1px solid #e0e0e0; box-shadow: 0 2px 10px rgba(0,0,0,0.05); }
        .hero-content { max-width: 1200px; margin: 0 auto; padding: 40px 30px; }
        .hero h2 { font-size: 36px; color: #333; margin-bottom: 10px; }
        .hero p { font-size: 18px; color: #666; }
        
        /* Main Content */
        .container { max-width: 900px; margin: 40px auto; padding: 0 30px; }
        .card { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 10px 40px rgba(0,0,0,0.1); }
        
        /* Breadcrumb */
        .breadcrumb { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #f0f0f0; }
        .breadcrumb-item { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 10px 20px; border-radius: 50px; font-size: 14px; font-weight: 600; display: flex; align-items: center; gap: 8px; }
        .breadcrumb-arrow { opacity: 0.5; }
        
        /* Form Elements */
        .form-group { margin-bottom: 25px; }
        label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; font-size: 15px; }
        .required { color: #e74c3c; margin-left: 3px; }
        .field-info { display: inline-block; background: #f0f0f0; color: #666; font-size: 11px; padding: 3px 10px; border-radius: 10px; margin-left: 8px; font-weight: normal; }
        input, select, textarea { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; transition: all 0.3s; font-family: inherit; }
        input:focus, select:focus, textarea:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        select { cursor: pointer; background: white; }
        textarea { min-height: 120px; resize: vertical; }
        input[type="number"] { -moz-appearance: textfield; }
        input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
        
        /* Section Headers */
        .section-header { font-size: 24px; font-weight: 700; color: #333; margin: 40px 0 25px; padding-bottom: 15px; border-bottom: 3px solid #667eea; display: flex; align-items: center; gap: 10px; }
        .section-header:first-child { margin-top: 0; }
        
        /* Grid Layout */
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        
        /* Submit Button */
        .btn-submit { width: 100%; padding: 18px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 12px; font-size: 18px; font-weight: 700; cursor: pointer; transition: all 0.3s; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6); }
        
        /* Instructions Box */
        .instructions { background: linear-gradient(135deg, #fff5e6 0%, #ffe6cc 100%); border: 2px solid #ffa726; padding: 25px; border-radius: 15px; margin-bottom: 30px; }
        .instructions h3 { color: #e65100; margin-bottom: 15px; font-size: 18px; }
        .instructions ul { margin-left: 20px; }
        .instructions li { margin: 8px 0; color: #e65100; }
        
        @media (max-width: 768px) {
            .grid-2 { grid-template-columns: 1fr; }
            .header-content { flex-direction: column; gap: 20px; text-align: center; }
            .hero h2 { font-size: 28px; }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <div class="header-content">
            <div class="logo">
                <div class="logo-icon">🛒</div>
                <div>
                    <h1>Seller Portal</h1>
                    <p>Post Your Advertisement</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">👤 <?php echo htmlspecialchars($seller['name']); ?></div>
                <a href="seller_dashboard.php" class="dashboard-btn"> Dashboard</a>
            </div>
        </div>
    </div>
    
    <!-- Hero Section -->
    <div class="hero">
        <div class="hero-content">
            <h2> Post Your Ad</h2>
            <p>Create a new listing to reach potential buyers</p>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="container">
        <div class="card">
            <div class="section-header">
                 Select Category
            </div>
            
            <div class="instructions">
                <h3> Quick Tips:</h3>
                <ul>
                    <li>Select the most specific category for your product</li>
                    <li>Most fields are dropdown menus - just select from options</li>
                    <li>All fields marked with * are required</li>
                </ul>
            </div>
            
            <div id="breadcrumb" class="breadcrumb"></div>
            <div id="categorySelectors"></div>
            
            <form method="POST" id="productForm" style="display:none;">
                <input type="hidden" name="category_id" id="final_category_id">
                <input type="hidden" name="brand_id" id="hidden_brand_id">
                <input type="hidden" name="model_id" id="hidden_model_id">
                
                <div class="section-header">
                     Product Specifications
                </div>
                
                <div id="customFields"></div>
                
                <div class="section-header">
                     Basic Information
                </div>
                
                <div class="form-group">
                    <label>Ad Title <span class="required">*</span></label>
                    <input type="text" name="title" placeholder="e.g., iPhone 15 Pro Max 256GB - Like New Condition" required>
                </div>
                
                <div class="form-group">
                    <label>Description <span class="required">*</span></label>
                    <textarea name="description" placeholder="Describe your product in detail... Include condition, reason for selling, any accessories included, etc." required></textarea>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Price (₹) <span class="required">*</span></label>
                        <input type="number" name="price" placeholder="Enter price" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Location <span class="required">*</span></label>
                        <input type="text" name="location" placeholder="City, State" required>
                    </div>
                </div>
                
                <div class="section-header">
                     Contact Information
                </div>
                
                <div class="form-group">
                    <label>Your Name <span class="required">*</span></label>
                    <input type="text" name="user_name" placeholder="Enter your full name" value="<?php echo htmlspecialchars($seller['name']); ?>" required>
                </div>
                
                <div class="grid-2">
                    <div class="form-group">
                        <label>Phone Number <span class="required">*</span></label>
                        <input type="tel" name="user_phone" placeholder="Enter your phone number" value="<?php echo htmlspecialchars($seller['phone'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email (Optional)</label>
                        <input type="email" name="user_email" placeholder="Enter your email" value="<?php echo htmlspecialchars($seller['email'] ?? ''); ?>">
                    </div>
                </div>
                
                <button type="submit" name="submit_product" class="btn-submit">
                     Post My Ad Now
                </button>
            </form>
        </div>
    </div>
    
    <script>
        let selectedPath = [];
        let currentBrandField = null;
        let currentModelField = null;
        
        function loadSubcategories(parentId, level) {
            const container = document.getElementById('categorySelectors');
            const selectors = container.querySelectorAll('.form-group');
            for (let i = level; i < selectors.length; i++) {
                selectors[i].remove();
            }
            
            selectedPath = selectedPath.slice(0, level);
            
            fetch(`?action=get_subcategories&parent_id=${parentId}`)
                .then(r => r.json())
                .then(categories => {
                    if (categories.length > 0) {
                        const div = document.createElement('div');
                        div.className = 'form-group';
                        div.innerHTML = `
                            <label>${level === 0 ? 'Main Category' : 'Select Subcategory'} <span class="required">*</span></label>
                            <select onchange="onCategorySelect(this.value, ${level})" required>
                                <option value="">-- Choose ${level === 0 ? 'category' : 'subcategory'} --</option>
                                ${categories.map(cat => `<option value="${cat.id}">${cat.icon || ''} ${cat.name}</option>`).join('')}
                            </select>
                        `;
                        container.appendChild(div);
                    }
                });
        }
        
        function onCategorySelect(categoryId, level) {
            if (!categoryId) return;
            
            const select = event.target;
            const categoryName = select.options[select.selectedIndex].text;
            
            selectedPath[level] = { id: categoryId, name: categoryName };
            updateBreadcrumb();
            
            fetch(`?action=get_category_fields&category_id=${categoryId}`)
                .then(r => r.json())
                .then(data => {
                    if (!data.hasChildren) {
                        document.getElementById('final_category_id').value = categoryId;
                        renderCustomFields(data.fields, categoryId);
                        document.getElementById('productForm').style.display = 'block';
                        
                        const container = document.getElementById('categorySelectors');
                        const selectors = container.querySelectorAll('.form-group');
                        for (let i = level + 1; i < selectors.length; i++) {
                            selectors[i].remove();
                        }
                    } else {
                        document.getElementById('productForm').style.display = 'none';
                        loadSubcategories(categoryId, level + 1);
                    }
                });
        }
        
        function updateBreadcrumb() {
            const breadcrumb = document.getElementById('breadcrumb');
            if (selectedPath.length === 0) {
                breadcrumb.style.display = 'none';
                return;
            }
            breadcrumb.style.display = 'flex';
            breadcrumb.innerHTML = selectedPath.map((item, idx) => 
                `${idx > 0 ? '<span class="breadcrumb-arrow">→</span>' : ''}
                <div class="breadcrumb-item">${item.name}</div>`
            ).join('');
        }
        
        function renderCustomFields(fields, categoryId) {
            const container = document.getElementById('customFields');
            let html = '';
            
            fields.forEach((field, idx) => {
                html += '<div class="form-group">';
                html += `<label>${field.field_name} ${field.is_mandatory ? '<span class="required">*</span>' : ''}<span class="field-info">from ${field.category_name}</span></label>`;
                
                if (field.field_type === 'brand_dropdown') {
                    html += `<select id="brand_select" onchange="onBrandChange(this.value, ${categoryId})" name="field_${field.id}" ${field.is_mandatory ? 'required' : ''}>
                        <option value="">-- Select Brand --</option>
                    </select>`;
                    currentBrandField = field;
                } else if (field.field_type === 'model_dropdown') {
                    html += `<select id="model_select" name="field_${field.id}" ${field.is_mandatory ? 'required' : ''} disabled>
                        <option value="">-- Select Brand First --</option>
                    </select>`;
                    currentModelField = field;
                } else if (field.field_type === 'dropdown') {
                    const options = JSON.parse(field.dropdown_options);
                    html += `<select name="field_${field.id}" ${field.is_mandatory ? 'required' : ''}>
                        <option value="">-- Select --</option>
                        ${options.map(opt => `<option value="${opt}">${opt}</option>`).join('')}
                    </select>`;
                } else if (field.field_type === 'textarea') {
                    html += `<textarea name="field_${field.id}" ${field.is_mandatory ? 'required' : ''}></textarea>`;
                } else {
                    html += `<input type="${field.field_type}" name="field_${field.id}" ${field.is_mandatory ? 'required' : ''}>`;
                }
                
                html += '</div>';
            });
            
            container.innerHTML = html;
            
            if (currentBrandField) {
                loadBrandsForCategory(categoryId);
            }
        }
        
        function loadBrandsForCategory(categoryId) {
            fetch(`?action=get_brands&category_id=${categoryId}`)
                .then(r => r.json())
                .then(brands => {
                    const select = document.getElementById('brand_select');
                    if (select) {
                        select.innerHTML = '<option value="">-- Select Brand --</option>' + 
                            brands.map(b => `<option value="${b.id}">${b.name}</option>`).join('');
                    }
                });
        }
        
        function onBrandChange(brandId, categoryId) {
            document.getElementById('hidden_brand_id').value = brandId;
            const modelSelect = document.getElementById('model_select');
            
            if (!brandId) {
                modelSelect.innerHTML = '<option value="">-- Select Brand First --</option>';
                modelSelect.disabled = true;
                document.getElementById('hidden_model_id').value = '';
                return;
            }
            
            modelSelect.disabled = false;
            fetch(`?action=get_models&brand_id=${brandId}`)
                .then(r => r.json())
                .then(models => {
                    modelSelect.innerHTML = '<option value="">-- Select Model --</option>' + 
                        models.map(m => `<option value="${m.id}">${m.name}</option>`).join('');
                    
                    modelSelect.onchange = function() {
                        document.getElementById('hidden_model_id').value = this.value;
                    };
                });
        }
        
        loadSubcategories(0, 0);
    </script>
</body>
</html>