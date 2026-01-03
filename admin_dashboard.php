<?php
require_once 'config.php';
requireAdmin();
$conn = getDBConnection();

$view = $_GET['view'] ?? 'overview';
$selected_category = $_GET['category'] ?? 0;
$selected_brand = $_GET['brand'] ?? 0;

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_categories':
            $sql = "SELECT c.*, (SELECT COUNT(*) FROM brands WHERE category_id = c.id) as brand_count
                    FROM categories c ORDER BY parent_id, name";
            $result = $conn->query($sql);
            $categories = [];
            while ($row = $result->fetch_assoc()) $categories[] = $row;
            echo json_encode($categories);
            exit;
            
        case 'get_brands':
            $category_id = intval($_GET['category_id']);
            $stmt = $conn->prepare("SELECT b.*, (SELECT COUNT(*) FROM models WHERE brand_id = b.id) as model_count,
                                    c.name as category_name, c.icon as category_icon
                                    FROM brands b 
                                    JOIN categories c ON b.category_id = c.id
                                    WHERE b.category_id = ? ORDER BY b.name");
            $stmt->bind_param("i", $category_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $brands = [];
            while ($row = $result->fetch_assoc()) $brands[] = $row;
            echo json_encode($brands);
            exit;
            
        case 'get_models':
            $brand_id = intval($_GET['brand_id']);
            $stmt = $conn->prepare("SELECT m.*, b.name as brand_name 
                                    FROM models m 
                                    JOIN brands b ON m.brand_id = b.id
                                    WHERE m.brand_id = ? ORDER BY m.name");
            $stmt->bind_param("i", $brand_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $models = [];
            while ($row = $result->fetch_assoc()) $models[] = $row;
            echo json_encode($models);
            exit;
            
        case 'add_brand':
            $category_id = intval($_POST['category_id']);
            $name = trim($_POST['name']);
            $stmt = $conn->prepare("INSERT INTO brands (category_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $category_id, $name);
            $stmt->execute();
            echo json_encode(['success' => true, 'id' => $conn->insert_id]);
            exit;
            
        case 'add_model':
            $brand_id = intval($_POST['brand_id']);
            $name = trim($_POST['name']);
            $stmt = $conn->prepare("INSERT INTO models (brand_id, name) VALUES (?, ?)");
            $stmt->bind_param("is", $brand_id, $name);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
            
        case 'delete_brand':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM brands WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
            
        case 'delete_model':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("DELETE FROM models WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            echo json_encode(['success' => true]);
            exit;
    }
}

// Get category stats
$stats = [];
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM brands) as total_brands,
    (SELECT COUNT(*) FROM models) as total_models");
$stats = $result->fetch_assoc();

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Brands & Models Management</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header-content { max-width: 1600px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        h1 { font-size: 28px; }
        .user-info { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; }
        .logout-btn { background: rgba(255,255,255,0.3); padding: 10px 20px; border: 2px solid white; border-radius: 50px; text-decoration: none; color: white; font-weight: 600; transition: all 0.3s; }
        .logout-btn:hover { background: white; color: #667eea; }
        
        .container { max-width: 1600px; margin: 30px auto; padding: 0 30px; }
        
        .tabs { display: flex; gap: 15px; margin-bottom: 30px; background: white; padding: 10px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); flex-wrap: wrap; }
        .tab { padding: 15px 30px; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 600; background: transparent; color: #666; text-decoration: none; display: inline-block; transition: all 0.3s; }
        .tab:hover { background: rgba(102, 126, 234, 0.1); }
        .tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        
        /* Stats Cards */
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 25px; margin-bottom: 30px; }
        .stat-card { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.08); text-align: center; transition: all 0.3s; }
        .stat-card:hover { transform: translateY(-5px); box-shadow: 0 6px 25px rgba(0,0,0,0.15); }
        .stat-icon { font-size: 48px; margin-bottom: 15px; }
        .stat-value { font-size: 36px; font-weight: 700; color: #667eea; margin-bottom: 8px; }
        .stat-label { font-size: 16px; color: #666; font-weight: 600; }
        
        .content { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        /* Three Column Layout */
        .three-column { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 30px; }
        .column { background: #f8f9fa; padding: 25px; border-radius: 15px; border: 2px solid #e0e0e0; }
        .column-title { font-size: 20px; font-weight: 700; margin-bottom: 20px; color: #333; padding-bottom: 15px; border-bottom: 3px solid #667eea; }
        
        .item-card { background: white; padding: 20px; border-radius: 12px; margin-bottom: 15px; border: 2px solid #e0e0e0; transition: all 0.3s; cursor: pointer; }
        .item-card:hover { border-color: #667eea; transform: translateX(5px); box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .item-card.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-color: #667eea; }
        .item-name { font-size: 16px; font-weight: 700; margin-bottom: 8px; }
        .item-count { font-size: 13px; opacity: 0.8; }
        
        .add-section { background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%); padding: 20px; border-radius: 12px; margin-top: 20px; border: 2px dashed #667eea; }
        .add-input { width: 100%; padding: 12px; border: 2px solid #667eea; border-radius: 8px; margin-bottom: 10px; font-size: 14px; }
        .btn-add { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 24px; border: none; border-radius: 8px; cursor: pointer; font-weight: 700; width: 100%; transition: all 0.3s; }
        .btn-add:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4); }
        
        .btn-delete { background: linear-gradient(135deg, #ef5350 0%, #e53935 100%); color: white; padding: 8px 16px; border: none; border-radius: 6px; cursor: pointer; font-size: 12px; margin-top: 10px; width: 100%; transition: all 0.3s; }
        .btn-delete:hover { transform: translateY(-2px); box-shadow: 0 4px 15px rgba(239, 83, 80, 0.4); }
        
        .empty-state { text-align: center; padding: 40px 20px; color: #999; }
        .empty-icon { font-size: 48px; margin-bottom: 15px; opacity: 0.5; }
        
        /* Custom SweetAlert2 styling */
        .swal2-popup { border-radius: 20px !important; }
        .swal2-confirm { border-radius: 10px !important; padding: 12px 30px !important; }
        .swal2-cancel { border-radius: 10px !important; padding: 12px 30px !important; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon">🛒</div>
                <div>
                    <h1>Brands & Models Management</h1>
                    <p style="opacity: 0.9; font-size: 14px;">Admin Dashboard</p>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="user-info">👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <div class="tabs">
            <a href="admin_dashboard.php" class="tab active"> Brands & Models</a>
            <a href="categories.php" class="tab"> Categories & Fields</a>
            <a href="crud_app/products_crud.php" class="tab"> All Products</a>
            <a href="crud_app/employee_crud.php" class="tab"> Employees</a>
            <a href="seller_login.php" class="tab"> Seller Portal</a>
        </div>
        
        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-value"><?php echo $stats['total_categories']; ?></div>
                <div class="stat-label">Total Categories</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-value"><?php echo $stats['total_brands']; ?></div>
                <div class="stat-label">Total Brands</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"></div>
                <div class="stat-value"><?php echo $stats['total_models']; ?></div>
                <div class="stat-label">Total Models</div>
            </div>
        </div>
        
        <div class="content">
            <h2 style="font-size: 28px; font-weight: 700; margin-bottom: 30px; color: #333;">
                 Manage Brands & Models
            </h2>
            <p style="color: #666; margin-bottom: 30px; font-size: 16px;">
                Select a category → Add brands → Add models for each brand
            </p>
            
            <div class="three-column">
                <!-- Column 1: Categories -->
                <div class="column">
                    <div class="column-title"> Select Category</div>
                    <div id="categoryList"></div>
                </div>
                
                <!-- Column 2: Brands -->
                <div class="column">
                    <div class="column-title" id="brandColumnTitle"> Select Brand</div>
                    <div id="brandList">
                        <div class="empty-state">
                            <div class="empty-icon"></div>
                            <p>Select a category first</p>
                        </div>
                    </div>
                    <div id="addBrandSection" style="display:none;" class="add-section">
                        <input type="text" id="newBrandName" class="add-input" placeholder="Enter brand name">
                        <button class="btn-add" onclick="addBrand()"> Add Brand</button>
                    </div>
                </div>
                
                <!-- Column 3: Models -->
                <div class="column">
                    <div class="column-title" id="modelColumnTitle"> Manage Models</div>
                    <div id="modelList">
                        <div class="empty-state">
                            <div class="empty-icon"></div>
                            <p>Select a brand first</p>
                        </div>
                    </div>
                    <div id="addModelSection" style="display:none;" class="add-section">
                        <input type="text" id="newModelName" class="add-input" placeholder="Enter model name">
                        <button class="btn-add" onclick="addModel()"> Add Model</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        let selectedCategoryId = 0;
        let selectedBrandId = 0;
        let categories = [];
        let brands = [];
        
        function loadCategories() {
            fetch('?action=get_categories')
                .then(r => r.json())
                .then(data => {
                    categories = data;
                    renderCategories();
                });
        }
        
        function renderCategories() {
            const list = document.getElementById('categoryList');
            let html = '';
            
            function renderNode(parentId, level) {
                const cats = categories.filter(c => c.parent_id == parentId);
                cats.forEach(cat => {
                    html += `
                        <div class="item-card ${selectedCategoryId === cat.id ? 'active' : ''}" onclick="selectCategory(${cat.id})">
                            <div class="item-name">${'—'.repeat(level)} ${cat.icon || ''} ${cat.name}</div>
                            <div class="item-count">${cat.brand_count} brands</div>
                        </div>
                    `;
                    renderNode(cat.id, level + 1);
                });
            }
            
            renderNode(null, 0);
            list.innerHTML = html || '<div class="empty-state"><div class="empty-icon"></div><p>No categories yet</p></div>';
        }
        
        function selectCategory(categoryId) {
            selectedCategoryId = categoryId;
            selectedBrandId = 0;
            renderCategories();
            loadBrands(categoryId);
            document.getElementById('modelList').innerHTML = '<div class="empty-state"><div class="empty-icon">👈</div><p>Select a brand first</p></div>';
            document.getElementById('addModelSection').style.display = 'none';
        }
        
        function loadBrands(categoryId) {
            fetch(`?action=get_brands&category_id=${categoryId}`)
                .then(r => r.json())
                .then(data => {
                    brands = data;
                    renderBrands();
                    document.getElementById('addBrandSection').style.display = 'block';
                    if (data.length > 0) {
                        document.getElementById('brandColumnTitle').textContent = ` Brands in ${data[0].category_name}`;
                    }
                });
        }
        
        function renderBrands() {
            const list = document.getElementById('brandList');
            if (brands.length === 0) {
                list.innerHTML = '<div class="empty-state"><div class="empty-icon"></div><p>No brands yet. Add one!</p></div>';
                return;
            }
            
            list.innerHTML = brands.map(brand => `
                <div class="item-card ${selectedBrandId === brand.id ? 'active' : ''}" onclick="selectBrand(${brand.id}, '${brand.name}')">
                    <div class="item-name">${brand.name}</div>
                    <div class="item-count">${brand.model_count} models</div>
                    <button class="btn-delete" onclick="event.stopPropagation(); deleteBrand(${brand.id}, '${brand.name}')">🗑️ Delete</button>
                </div>
            `).join('');
        }
        
        function selectBrand(brandId, brandName) {
            selectedBrandId = brandId;
            renderBrands();
            loadModels(brandId);
            document.getElementById('modelColumnTitle').textContent = ` Models for ${brandName}`;
            document.getElementById('addModelSection').style.display = 'block';
        }
        
        function loadModels(brandId) {
            fetch(`?action=get_models&brand_id=${brandId}`)
                .then(r => r.json())
                .then(models => {
                    const list = document.getElementById('modelList');
                    if (models.length === 0) {
                        list.innerHTML = '<div class="empty-state"><div class="empty-icon"></div><p>No models yet. Add one!</p></div>';
                        return;
                    }
                    
                    list.innerHTML = models.map(model => `
                        <div class="item-card">
                            <div class="item-name">${model.name}</div>
                            <button class="btn-delete" onclick="deleteModel(${model.id}, '${model.name}')">🗑️ Delete</button>
                        </div>
                    `).join('');
                });
        }
        
        function addBrand() {
            const name = document.getElementById('newBrandName').value.trim();
            if (!name) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Please enter a brand name',
                    confirmButtonColor: '#f2994a'
                });
                return;
            }
            
            const formData = new FormData();
            formData.append('category_id', selectedCategoryId);
            formData.append('name', name);
            
            fetch('?action=add_brand', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('newBrandName').value = '';
                        loadBrands(selectedCategoryId);
                        loadCategories();
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Brand Added!',
                            text: `${name} has been added successfully`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
        }
        
        function addModel() {
            const name = document.getElementById('newModelName').value.trim();
            if (!name) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Oops!',
                    text: 'Please enter a model name',
                    confirmButtonColor: '#f2994a'
                });
                return;
            }
            
            const formData = new FormData();
            formData.append('brand_id', selectedBrandId);
            formData.append('name', name);
            
            fetch('?action=add_model', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('newModelName').value = '';
                        loadModels(selectedBrandId);
                        
                        Swal.fire({
                            icon: 'success',
                            title: 'Model Added!',
                            text: `${name} has been added successfully`,
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                });
        }
        
        function deleteBrand(id, name) {
            Swal.fire({
                title: 'Delete Brand?',
                html: `Are you sure you want to delete <strong>${name}</strong>?<br><small style="color: #dc3545;">This will also delete all models under this brand!</small>`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#eb3349',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('?action=delete_brand', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                loadBrands(selectedCategoryId);
                                loadCategories();
                                document.getElementById('modelList').innerHTML = '<div class="empty-state"><div class="empty-icon">👈</div><p>Select a brand</p></div>';
                                document.getElementById('addModelSection').style.display = 'none';
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: `${name} has been deleted`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        });
                }
            });
        }
        
        function deleteModel(id, name) {
            Swal.fire({
                title: 'Delete Model?',
                text: `Delete ${name}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#eb3349',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('id', id);
                    
                    fetch('?action=delete_model', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                loadModels(selectedBrandId);
                                
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Deleted!',
                                    text: `${name} has been deleted`,
                                    timer: 2000,
                                    showConfirmButton: false
                                });
                            }
                        });
                }
            });
        }
        
        loadCategories();
    </script>
</body>
</html>