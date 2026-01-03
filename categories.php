<?php
require_once 'config.php';
requireAdmin();
$conn = getDBConnection();

// Current view mode
$view = $_GET['view'] ?? 'list';

// Handle AJAX requests
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['action']) {
        case 'get_categories':
            $sql = "SELECT c.*, 
                    (SELECT COUNT(*) FROM categories WHERE parent_id = c.id) as child_count,
                    (SELECT COUNT(*) FROM category_fields WHERE category_id = c.id) as field_count,
                    (SELECT COUNT(*) FROM brands WHERE category_id = c.id) as brand_count,
                    p.name as parent_name
                    FROM categories c
                    LEFT JOIN categories p ON c.parent_id = p.id
                    ORDER BY c.parent_id, c.name";
            $result = $conn->query($sql);
            $categories = [];
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row;
            }
            echo json_encode($categories);
            exit;
            
        case 'get_category':
            $id = intval($_GET['id']);
            $stmt = $conn->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $category = $stmt->get_result()->fetch_assoc();
            
            $stmt = $conn->prepare("SELECT * FROM category_fields WHERE category_id = ? ORDER BY display_order");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $fields = [];
            while ($row = $result->fetch_assoc()) {
                $fields[] = $row;
            }
            
            echo json_encode(['category' => $category, 'fields' => $fields]);
            exit;
            
        case 'delete_category':
            $id = intval($_POST['id']);
            $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM categories WHERE parent_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            if ($result['cnt'] > 0) {
                echo json_encode(['success' => false, 'message' => 'Cannot delete category with subcategories']);
            } else {
                $stmt = $conn->prepare("DELETE FROM categories WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                echo json_encode(['success' => true]);
            }
            exit;
    }
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_GET['action'])) {
    if (isset($_POST['save_category'])) {
        $id = intval($_POST['category_id']);
        $name = trim($_POST['name']);
        $parent_id = !empty($_POST['parent_id']) ? intval($_POST['parent_id']) : null;
        $icon = trim($_POST['icon']) ?: '';
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', trim($name)));
        
        if ($id > 0) {
            $stmt = $conn->prepare("UPDATE categories SET name=?, parent_id=?, slug=?, icon=? WHERE id=?");
            $stmt->bind_param("sissi", $name, $parent_id, $slug, $icon, $id);
            $stmt->execute();
            $category_id = $id;
            $message = "updated";
        } else {
            $stmt = $conn->prepare("INSERT INTO categories (name, parent_id, slug, icon) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("siss", $name, $parent_id, $slug, $icon);
            $stmt->execute();
            $category_id = $conn->insert_id;
            $message = "created";
        }
        
        $stmt = $conn->prepare("DELETE FROM category_fields WHERE category_id = ?");
        $stmt->bind_param("i", $category_id);
        $stmt->execute();
        
        if (isset($_POST['fields']) && is_array($_POST['fields'])) {
            foreach ($_POST['fields'] as $index => $field) {
                $field_name = trim($field['name']);
                if (empty($field_name)) continue;
                
                $field_type = $field['type'];
                $is_mandatory = isset($field['mandatory']) ? 1 : 0;
                $dropdown_options = ($field_type === 'dropdown' && !empty($field['options'])) 
                    ? json_encode(array_map('trim', explode(',', $field['options']))) 
                    : null;
                
                $stmt = $conn->prepare("INSERT INTO category_fields (category_id, field_name, field_type, is_mandatory, dropdown_options, display_order) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issisi", $category_id, $field_name, $field_type, $is_mandatory, $dropdown_options, $index);
                $stmt->execute();
            }
        }
        
        header("Location: categories.php?view=list&{$message}=1");
        exit;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Categories Management</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px 30px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); }
        .header-content { max-width: 1600px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-icon { width: 50px; height: 50px; background: rgba(255,255,255,0.2); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; }
        h1 { font-size: 28px; }
        .header-right { display: flex; align-items: center; gap: 20px; }
        .user-info { background: rgba(255,255,255,0.2); padding: 10px 20px; border-radius: 50px; }
        .logout-btn { background: rgba(255,255,255,0.3); padding: 10px 20px; border: 2px solid white; border-radius: 50px; text-decoration: none; color: white; font-weight: 600; transition: all 0.3s; }
        
        .container { max-width: 1600px; margin: 30px auto; padding: 0 30px; }
        
        .tabs { display: flex; gap: 15px; margin-bottom: 30px; background: white; padding: 10px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .tab { padding: 15px 30px; border: none; border-radius: 10px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; background: transparent; color: #666; text-decoration: none; display: inline-block; }
        .tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4); }
        
        /* View Tabs */
        .view-tabs { display: flex; gap: 15px; margin-bottom: 30px; }
        .view-tab { padding: 15px 40px; background: white; border: none; border-radius: 12px; cursor: pointer; font-size: 16px; font-weight: 600; transition: all 0.3s; text-decoration: none; color: #666; display: flex; align-items: center; gap: 10px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
        .view-tab:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
        .view-tab.active { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .view-tab-icon { font-size: 24px; }
        
        .content { background: white; padding: 40px; border-radius: 20px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        
        .success, .info { padding: 20px; border-radius: 15px; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; font-weight: 600; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
        .success { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; }
        .info { background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; }
        
        /* Category Tree */
        .category-tree { display: grid; gap: 20px; }
        .category-card { background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%); border: 2px solid #e0e0e0; border-radius: 15px; padding: 25px; transition: all 0.3s; }
        .category-card:hover { transform: translateY(-3px); box-shadow: 0 8px 25px rgba(0,0,0,0.12); border-color: #667eea; }
        .category-card.level-0 { border-left: 5px solid #667eea; }
        .category-card.level-1 { margin-left: 40px; border-left: 5px solid #764ba2; }
        .category-card.level-2 { margin-left: 80px; border-left: 5px solid #56ab2f; }
        
        .category-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
        .category-main { flex: 1; }
        .category-title { display: flex; align-items: center; gap: 15px; margin-bottom: 10px; }
        .category-icon { font-size: 36px; }
        .category-name { font-size: 24px; font-weight: 700; color: #333; }
        
        .category-stats { display: flex; gap: 15px; flex-wrap: wrap; margin-top: 15px; }
        .stat-item { background: white; padding: 8px 16px; border-radius: 20px; font-size: 13px; font-weight: 600; color: #666; border: 2px solid #e0e0e0; }
        .stat-number { color: #667eea; font-size: 16px; margin-right: 5px; }
        
        .category-actions { display: flex; gap: 10px; }
        .btn { padding: 12px 24px; border: none; border-radius: 10px; cursor: pointer; font-size: 14px; font-weight: 600; transition: all 0.3s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .btn-edit { background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%); color: white; }
        .btn-delete { background: linear-gradient(135deg, #ef5350 0%, #e53935 100%); color: white; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
        .btn:hover { transform: scale(1.05); box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
        
        /* Add Form */
        .add-category-section { background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); padding: 40px; border-radius: 20px; border: 3px dashed #667eea; }
        .form-title { font-size: 28px; font-weight: 700; color: #333; margin-bottom: 30px; display: flex; align-items: center; gap: 15px; }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; margin-bottom: 30px; }
        .form-group { margin-bottom: 25px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 8px; font-weight: 700; color: #333; font-size: 15px; }
        input, select, textarea { width: 100%; padding: 14px 18px; border: 2px solid #e0e0e0; border-radius: 12px; font-size: 15px; transition: all 0.3s; font-family: inherit; }
        input:focus, select:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1); }
        small { color: #666; font-size: 13px; display: block; margin-top: 5px; }
        
        /* Fields Section */
        .fields-section { background: white; padding: 30px; border-radius: 15px; margin-top: 30px; border: 2px solid #e0e0e0; }
        .fields-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; }
        .section-title { font-size: 22px; font-weight: 700; color: #333; }
        
        .field-card { background: linear-gradient(135deg, #fff 0%, #f8f9fa 100%); border: 2px solid #e0e0e0; padding: 25px; border-radius: 12px; margin-bottom: 20px; position: relative; }
        .field-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
        .field-number { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 8px 20px; border-radius: 25px; font-weight: 700; }
        .btn-remove { background: linear-gradient(135deg, #ef5350 0%, #e53935 100%); color: white; padding: 8px 16px; border: none; border-radius: 8px; cursor: pointer; font-weight: 600; }
        
        .btn-add-field { background: linear-gradient(135deg, #56ab2f 0%, #a8e063 100%); color: white; padding: 15px 30px; border: none; border-radius: 12px; cursor: pointer; font-weight: 700; width: 100%; margin-top: 15px; font-size: 16px; }
        .btn-add-field:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(86, 171, 47, 0.4); }
        
        .checkbox-label { display: flex; align-items: center; gap: 10px; font-weight: 600; color: #555; margin-top: 15px; }
        .checkbox-label input { width: auto; }
        
        .form-actions { display: flex; gap: 15px; margin-top: 40px; padding-top: 30px; border-top: 3px solid #e0e0e0; }
        .btn-save { flex: 1; padding: 18px; font-size: 18px; }
        .btn-cancel { background: #6c757d; color: white; padding: 18px 40px; }
        
        .empty-state { text-align: center; padding: 80px 20px; }
        .empty-icon { font-size: 80px; margin-bottom: 20px; opacity: 0.5; }
        .empty-text { font-size: 18px; color: #999; }
        
        @media (max-width: 1200px) {
            .form-grid { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-icon"></div>
                <div>
                    <h1>Category Management</h1>
                    <p style="opacity: 0.9; font-size: 14px;">Admin Dashboard</p>
                </div>
            </div>
            <div class="header-right">
                <div class="user-info">👤 <?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                <a href="logout.php" class="logout-btn">Logout</a>
            </div>
        </div>
    </div>
    
    <div class="container">
        <!-- Main Navigation -->
        <div class="tabs">
            <a href="admin_dashboard.php" class="tab"> Brands & Models</a>
            <a href="categories.php" class="tab active"> Categories & Fields</a>
            <a href="seller_login.php" class="tab"> Seller Portal</a>
        </div>
        
        <!-- View Tabs -->
        <div class="view-tabs">
            <a href="?view=list" class="view-tab <?php echo $view === 'list' ? 'active' : ''; ?>">
                <span class="view-tab-icon"></span>
                <span>All Categories</span>
            </a>
            <a href="?view=add" class="view-tab <?php echo $view === 'add' ? 'active' : ''; ?>">
                <span class="view-tab-icon"></span>
                <span>Add New Category</span>
            </a>
        </div>
        
        <div class="content">
            <?php if (isset($_GET['created'])): ?>
                <div class="success">
                    <span style="font-size: 32px;"></span>
                    <span>Category created successfully!</span>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['updated'])): ?>
                <div class="info">
                    <span style="font-size: 32px;"></span>
                    <span>Category updated successfully!</span>
                </div>
            <?php endif; ?>
            
            <?php if ($view === 'list'): ?>
                <!-- ALL CATEGORIES VIEW -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                    <h2 style="font-size: 32px; font-weight: 700; color: #333;"> All Categories</h2>
                    <a href="?view=add" class="btn btn-primary" style="font-size: 16px; padding: 15px 30px;">
                         Add New Category
                    </a>
                </div>
                
                <div id="categoryTree" class="category-tree">
                    <div class="empty-state">
                        <div class="empty-icon"></div>
                        <div class="empty-text">Loading categories...</div>
                    </div>
                </div>
                
            <?php elseif ($view === 'add' || $view === 'edit'): ?>
                <!-- ADD/EDIT CATEGORY FORM -->
                <?php 
                $editing = $view === 'edit' && isset($_GET['id']);
                $category_id = $editing ? intval($_GET['id']) : 0;
                ?>
                <div class="add-category-section">
                    <div class="form-title" id="formTitle">
                        <?php echo $editing ? '✏️ Edit Category' : ' Add New Category'; ?>
                    </div>
                    
                    <?php if ($editing): ?>
                        <div style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px 25px; border-radius: 12px; margin-bottom: 25px;">
                            <div style="display: flex; align-items: flex-start; gap: 15px;">
                                <span style="font-size: 32px;"></span>
                                <div style="flex: 1;">
                                    <strong style="font-size: 18px;">Editing Mode</strong>
                                    <p style="margin-top: 8px; opacity: 0.95;">You can change the category name, icon, parent, and all fields</p>
                                    <div id="currentParentInfo" style="margin-top: 12px; padding: 12px; background: rgba(255,255,255,0.2); border-radius: 8px; font-size: 14px;">
                                        <strong>Current Parent:</strong> <span id="currentParentName">Loading...</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" id="categoryForm">
                        <input type="hidden" name="category_id" id="category_id" value="<?php echo $category_id; ?>">
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label> Category Name *</label>
                                <input type="text" name="name" id="name" placeholder="e.g., Electronics, Cars, Furniture" required oninput="updateHierarchyPreview()">
                            </div>
                            
                            <div class="form-group">
                                <label> Icon (Emoji)</label>
                                <input type="text" name="icon" id="icon" placeholder="📱" maxlength="10">
                                <small>Add an emoji icon for visual appeal</small>
                            </div>
                            
                            <div class="form-group full-width">
                                <label> Parent Category</label>
                                <select name="parent_id" id="parent_id" onchange="updateHierarchyPreview()">
                                    <option value="">-- Root Category (Top Level) --</option>
                                </select>
                                <small>
                                    <strong>Root Category:</strong> Makes this a main category (Electronics, Cars, etc.)<br>
                                    <strong>With Parent:</strong> Makes this a subcategory (Mobiles under Electronics)
                                </small>
                                
                                <!-- Hierarchy Preview -->
                                <div id="hierarchyPreview" style="margin-top: 15px; padding: 15px; background: #e3f2fd; border-radius: 10px; border-left: 4px solid #667eea; display: none;">
                                    <strong style="color: #667eea;"> Category Hierarchy Preview:</strong>
                                    <div id="hierarchyPath" style="margin-top: 10px; font-size: 16px; color: #333;"></div>
                                </div>
                            </div>
                            
                            <?php if ($editing): ?>
                                <div class="form-group full-width">
                                    <div style="background: #fff3cd; border: 2px solid #ffc107; padding: 15px; border-radius: 10px;">
                                        <strong style="color: #856404;"> Warning about changing parent:</strong>
                                        <p style="color: #856404; margin-top: 8px; font-size: 14px;">
                                            If you change the parent category, this category and all its subcategories will move under the new parent. 
                                            Make sure this won't break your category structure!
                                        </p>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="fields-section">
                            <div class="fields-header">
                                <div class="section-title"> Custom Fields</div>
                            </div>
                            <p style="color: #666; margin-bottom: 20px;">Add fields that will appear when users post products in this category</p>
                            
                            <div id="fieldsContainer"></div>
                            
                            <button type="button" class="btn-add-field" onclick="addField()">
                                 Add New Field
                            </button>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="save_category" class="btn btn-primary btn-save">
                                 Save Category
                            </button>
                            <a href="?view=list" class="btn btn-cancel">
                                Cancel
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        let categories = [];
        let fieldCounter = 0;
        
        <?php if ($view === 'list'): ?>
        function loadCategories() {
            fetch('?action=get_categories')
                .then(r => r.json())
                .then(data => {
                    categories = data;
                    renderCategoryTree();
                });
        }
        
        function renderCategoryTree() {
            const tree = document.getElementById('categoryTree');
            const items = [];
            
            function renderNode(parentId, level) {
                const cats = categories.filter(c => c.parent_id == parentId);
                if (cats.length === 0 && level === 0) {
                    items.push(`
                        <div class="empty-state">
                            <div class="empty-icon"></div>
                            <div class="empty-text">No categories yet. Create your first one!</div>
                            <a href="?view=add" class="btn btn-primary" style="margin-top: 30px; font-size: 16px; padding: 15px 40px;">
                                 Add First Category
                            </a>
                        </div>
                    `);
                    return;
                }
                
                cats.forEach(cat => {
                    items.push(`
                        <div class="category-card level-${level}">
                            <div class="category-header">
                                <div class="category-main">
                                    <div class="category-title">
                                        <span class="category-icon">${cat.icon || ''}</span>
                                        <span class="category-name">${cat.name}</span>
                                        ${level > 0 ? '<span style="background: #e3f2fd; color: #667eea; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">Level ' + level + '</span>' : '<span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 4px 12px; border-radius: 15px; font-size: 12px; margin-left: 10px;">Root</span>'}
                                    </div>
                                    ${cat.parent_name ? `<div style="color: #666; font-size: 14px; margin-top: 5px;">↳ Under: <strong>${cat.parent_name}</strong></div>` : '<div style="color: #666; font-size: 14px; margin-top: 5px;">📂 Main Category (No Parent)</div>'}
                                    
                                    <div class="category-stats">
                                        <div class="stat-item">
                                            <span class="stat-number">${cat.field_count}</span> Custom Fields
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">${cat.child_count}</span> Subcategories
                                        </div>
                                        <div class="stat-item">
                                            <span class="stat-number">${cat.brand_count}</span> Brands
                                        </div>
                                    </div>
                                </div>
                                <div class="btn-group">
                                    <a href="?view=edit&id=${cat.id}" class="btn btn-edit">
                                        ✏️ Edit
                                    </a>
                                    <button class="btn btn-delete" onclick="deleteCategory(${cat.id})">
                                         Delete
                                    </button>
                                </div>
                            </div>
                        </div>
                    `);
                    if (cat.child_count > 0) {
                        renderNode(cat.id, level + 1);
                    }
                });
            }
            
            renderNode(null, 0);
            tree.innerHTML = items.join('');
        }
        
        function deleteCategory(id) {
            if (!confirm('Are you sure you want to delete this category?')) return;
            
            const formData = new FormData();
            formData.append('id', id);
            
            fetch('?action=delete_category', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        loadCategories();
                    } else {
                        alert(data.message);
                    }
                });
        }
        
        loadCategories();
        <?php endif; ?>
        
        <?php if ($view === 'add' || $view === 'edit'): ?>
        function loadCategoriesForSelect() {
            fetch('?action=get_categories')
                .then(r => r.json())
                .then(data => {
                    categories = data;
                    updateParentSelect();
                    
                    <?php if ($view === 'edit' && isset($_GET['id'])): ?>
                    loadCategoryData(<?php echo intval($_GET['id']); ?>);
                    <?php endif; ?>
                });
        }
        
        function updateParentSelect() {
            const select = document.getElementById('parent_id');
            const currentId = parseInt(document.getElementById('category_id').value);
            
            let html = '<option value="" style="font-weight: bold; color: #667eea;">-- Root Category (Top Level) --</option>';
            
            // Get all children of current category to prevent circular references
            const getChildrenIds = (catId) => {
                let childIds = [catId];
                const children = categories.filter(c => c.parent_id === catId);
                children.forEach(child => {
                    childIds = [...childIds, ...getChildrenIds(child.id)];
                });
                return childIds;
            };
            
            const excludeIds = currentId ? getChildrenIds(currentId) : [currentId];
            
            function renderOptions(parentId, level) {
                const cats = categories.filter(c => c.parent_id == parentId && !excludeIds.includes(c.id));
                cats.forEach(cat => {
                    const indent = '—'.repeat(level);
                    const levelColor = level === 0 ? '#667eea' : level === 1 ? '#764ba2' : level === 2 ? '#56ab2f' : '#ffa726';
                    html += `<option value="${cat.id}" style="padding-left: ${level * 15}px; color: ${levelColor};">
                        ${indent} ${cat.icon || ''} ${cat.name}
                    </option>`;
                    renderOptions(cat.id, level + 1);
                });
            }
            
            renderOptions(null, 0);
            select.innerHTML = html;
        }
        
        function updateHierarchyPreview() {
            const parentSelect = document.getElementById('parent_id');
            const preview = document.getElementById('hierarchyPreview');
            const pathDiv = document.getElementById('hierarchyPath');
            const categoryName = document.getElementById('name').value || '[Category Name]';
            
            if (!parentSelect.value) {
                preview.style.display = 'none';
                return;
            }
            
            // Build hierarchy path
            const parentId = parseInt(parentSelect.value);
            const path = [];
            
            function buildPath(catId) {
                const cat = categories.find(c => c.id === catId);
                if (cat) {
                    path.unshift(`${cat.icon || ''} ${cat.name}`);
                    if (cat.parent_id) {
                        buildPath(cat.parent_id);
                    }
                }
            }
            
            buildPath(parentId);
            path.push(`<strong style="color: #667eea;">${categoryName}</strong>`);
            
            pathDiv.innerHTML = path.join(' <span style="color: #667eea;">→</span> ');
            preview.style.display = 'block';
        }
        
        function loadCategoryData(id) {
            fetch(`?action=get_category&id=${id}`)
                .then(r => r.json())
                .then(data => {
                    document.getElementById('name').value = data.category.name;
                    document.getElementById('icon').value = data.category.icon || '';
                    
                    // Update current parent info
                    if (document.getElementById('currentParentInfo')) {
                        const parentNameSpan = document.getElementById('currentParentName');
                        if (data.category.parent_id) {
                            const parent = categories.find(c => c.id == data.category.parent_id);
                            if (parent) {
                                parentNameSpan.innerHTML = `${parent.icon || ''} <strong>${parent.name}</strong> <span style="opacity: 0.8;">(Level ${getLevel(parent.id)})</span>`;
                            }
                        } else {
                            parentNameSpan.innerHTML = '<strong>None</strong> <span style="opacity: 0.8;">(This is a Root Category)</span>';
                        }
                    }
                    
                    // Set parent after options are loaded
                    setTimeout(() => {
                        document.getElementById('parent_id').value = data.category.parent_id || '';
                        updateHierarchyPreview();
                        
                        // Highlight current parent selection
                        const parentSelect = document.getElementById('parent_id');
                        if (data.category.parent_id) {
                            const selectedOption = parentSelect.options[parentSelect.selectedIndex];
                            if (selectedOption) {
                                selectedOption.style.background = '#e3f2fd';
                                selectedOption.style.fontWeight = 'bold';
                            }
                        }
                    }, 100);
                    
                    document.getElementById('fieldsContainer').innerHTML = '';
                    fieldCounter = 0;
                    data.fields.forEach(field => addField(field));
                });
        }
        
        function getLevel(categoryId) {
            let level = 0;
            let current = categories.find(c => c.id === categoryId);
            while (current && current.parent_id) {
                level++;
                current = categories.find(c => c.id === current.parent_id);
            }
            return level;
        }
        
        function addField(field = null) {
            const container = document.getElementById('fieldsContainer');
            const index = fieldCounter++;
            
            const fieldHtml = `
                <div class="field-card" id="field_${index}">
                    <div class="field-header">
                        <span class="field-number">Field #${index + 1}</span>
                        <button type="button" class="btn-remove" onclick="removeField(${index})">🗑️ Remove</button>
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Field Name *</label>
                            <input type="text" name="fields[${index}][name]" placeholder="e.g., Brand, RAM, Color" value="${field ? field.field_name : ''}" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Field Type *</label>
                            <select name="fields[${index}][type]" onchange="toggleOptions(${index}, this.value)">
                                <option value="text" ${field && field.field_type === 'text' ? 'selected' : ''}>📝 Text Input</option>
                                <option value="number" ${field && field.field_type === 'number' ? 'selected' : ''}>🔢 Number Input</option>
                                <option value="dropdown" ${field && field.field_type === 'dropdown' ? 'selected' : ''}>📋 Dropdown (Select)</option>
                                <option value="textarea" ${field && field.field_type === 'textarea' ? 'selected' : ''}>📄 Text Area</option>
                                <option value="brand_dropdown" ${field && field.field_type === 'brand_dropdown' ? 'selected' : ''}>🏷️ Brand Dropdown</option>
                                <option value="model_dropdown" ${field && field.field_type === 'model_dropdown' ? 'selected' : ''}>📱 Model Dropdown</option>
                            </select>
                        </div>
                        
                        <div class="form-group full-width" id="options_container_${index}" style="display: ${field && field.field_type === 'dropdown' ? 'block' : 'none'};">
                            <label>Dropdown Options (comma separated) *</label>
                            <input type="text" name="fields[${index}][options]" placeholder="Option 1, Option 2, Option 3" value="${field && field.dropdown_options ? JSON.parse(field.dropdown_options).join(', ') : ''}">
                            <small>Example: Black, White, Blue, Red</small>
                        </div>
                    </div>
                    
                    <label class="checkbox-label">
                        <input type="checkbox" name="fields[${index}][mandatory]" ${field && field.is_mandatory ? 'checked' : ''}>
                        <span> This is a required field</span>
                    </label>
                </div>
            `;
            
            container.insertAdjacentHTML('beforeend', fieldHtml);
        }
        
        function removeField(index) {
            if (confirm('Remove this field?')) {
                document.getElementById(`field_${index}`).remove();
            }
        }
        
        function toggleOptions(index, type) {
            document.getElementById(`options_container_${index}`).style.display = type === 'dropdown' ? 'block' : 'none';
        }
        
        loadCategoriesForSelect();
        <?php endif; ?>
    </script>
</body>
</html>

