<?php
require_once 'config.php';
requireAdmin();
$conn = getDBConnection();

$view = $_GET['view'] ?? 'overview';

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

// Get statistics
$stats = [];
$result = $conn->query("SELECT 
    (SELECT COUNT(*) FROM categories) as total_categories,
    (SELECT COUNT(*) FROM brands) as total_brands,
    (SELECT COUNT(*) FROM models) as total_models,
    (SELECT COUNT(*) FROM products) as total_products,
    (SELECT COUNT(*) FROM products WHERE status='active') as active_products,
    (SELECT COUNT(*) FROM products WHERE status='sold') as sold_products,
    (SELECT SUM(views) FROM products) as total_views,
    (SELECT COUNT(*) FROM sellers) as total_sellers,
    (SELECT COUNT(*) FROM employees) as total_employees");
$stats = $result->fetch_assoc();

// Get recent products
$recent_products = $conn->query("SELECT p.*, s.name as seller_name, c.name as category_name 
    FROM products p 
    LEFT JOIN sellers s ON p.seller_id = s.id 
    LEFT JOIN categories c ON p.category_id = c.id 
    ORDER BY p.created_at DESC LIMIT 10");

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            background: #f7f8fc;
            color: #1e293b;
        }
        
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 260px;
            height: 100vh;
            background: white;
            border-right: 1px solid #e2e8f0;
            padding: 24px 0;
            overflow-y: auto;
            z-index: 100;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0 24px;
            margin-bottom: 32px;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .logo-text {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .nav-menu {
            list-style: none;
            padding: 0 16px;
        }
        
        .nav-item {
            margin-bottom: 4px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            color: #64748b;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            font-size: 14px;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .nav-link:hover {
            background: #f1f5f9;
            color: #667eea;
        }
        
        .nav-link.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .nav-link i {
            width: 20px;
            font-size: 18px;
        }
        
        .main-content {
            margin-left: 260px;
            min-height: 100vh;
        }
        
        .top-bar {
            background: white;
            border-bottom: 1px solid #e2e8f0;
            padding: 16px 32px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        
        .search-box {
            position: relative;
            width: 400px;
        }
        
        .search-box input {
            width: 100%;
            padding: 10px 16px 10px 42px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        
        .search-box i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        
        .top-bar-right {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        
        .notification-btn {
            position: relative;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background: #f1f5f9;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #64748b;
            font-size: 18px;
            transition: all 0.2s;
        }
        
        .notification-btn:hover {
            background: #e2e8f0;
        }
        
        .notification-badge {
            position: absolute;
            top: -4px;
            right: -4px;
            width: 18px;
            height: 18px;
            background: #ef4444;
            border-radius: 50%;
            font-size: 10px;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
        }
        
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 8px 12px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.2s;
        }
        
        .user-profile:hover {
            background: #f1f5f9;
        }
        
        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .user-info {
            text-align: right;
        }
        
        .user-name {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
        }
        
        .user-role {
            font-size: 12px;
            color: #64748b;
        }
        
        .content-area {
            padding: 32px;
        }
        
        .welcome-banner {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            padding: 32px;
            color: white;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        
        .welcome-banner::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -10%;
            width: 400px;
            height: 400px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
        }
        
        .welcome-content {
            position: relative;
            z-index: 1;
        }
        
        .welcome-banner h1 {
            font-size: 28px;
            margin-bottom: 8px;
        }
        
        .welcome-banner p {
            font-size: 16px;
            opacity: 0.9;
            margin-bottom: 20px;
        }
        
        .btn-primary {
            background: white;
            color: #667eea;
            padding: 12px 24px;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
            margin-bottom: 32px;
        }
        
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .stat-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            transform: translateY(-2px);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 16px;
        }
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 500;
        }
        
        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
        }
        
        .stat-icon.blue { background: #dbeafe; color: #3b82f6; }
        .stat-icon.green { background: #d1fae5; color: #10b981; }
        .stat-icon.purple { background: #e9d5ff; color: #a855f7; }
        .stat-icon.orange { background: #fed7aa; color: #f97316; }
        .stat-icon.red { background: #fee2e2; color: #ef4444; }
        .stat-icon.indigo { background: #e0e7ff; color: #6366f1; }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }
        
        .stat-change {
            font-size: 13px;
            color: #10b981;
            font-weight: 500;
        }
        
        .section {
            background: white;
            border-radius: 12px;
            padding: 24px;
            border: 1px solid #e2e8f0;
            margin-bottom: 24px;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 18px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .btn-secondary {
            padding: 8px 16px;
            background: #f1f5f9;
            color: #667eea;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.2s;
        }
        
        .btn-secondary:hover {
            background: #e2e8f0;
        }
        
        .table-container {
            overflow-x: auto;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        thead {
            background: #f8fafc;
        }
        
        th {
            text-align: left;
            padding: 12px 16px;
            font-size: 12px;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        td {
            padding: 16px;
            border-top: 1px solid #e2e8f0;
            font-size: 14px;
            color: #475569;
        }
        
        tbody tr:hover {
            background: #f8fafc;
        }
        
        .status-badge {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }
        
        .status-active { background: #d1fae5; color: #065f46; }
        .status-sold { background: #dbeafe; color: #1e40af; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .product-title {
            font-weight: 600;
            color: #1e293b;
        }
        
        .action-buttons {
            display: flex;
            gap: 8px;
        }
        
        .btn-icon {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .btn-edit {
            background: #fef3c7;
            color: #d97706;
        }
        
        .btn-edit:hover {
            background: #fde68a;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #fecaca;
        }
        
        .three-column {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
        }
        
        .column {
            background: #f8fafc;
            padding: 20px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
        }
        
        .column-title {
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 16px;
            color: #1e293b;
            padding-bottom: 12px;
            border-bottom: 2px solid #667eea;
        }
        
        .item-card {
            background: white;
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 12px;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            cursor: pointer;
        }
        
        .item-card:hover {
            border-color: #667eea;
            transform: translateX(3px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }
        
        .item-card.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        
        .item-name {
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 6px;
        }
        
        .item-count {
            font-size: 12px;
            opacity: 0.7;
        }
        
        .add-section {
            background: #e0e7ff;
            padding: 16px;
            border-radius: 8px;
            margin-top: 16px;
            border: 2px dashed #667eea;
        }
        
        .add-input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #667eea;
            border-radius: 6px;
            margin-bottom: 10px;
            font-size: 14px;
        }
        
        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            width: 100%;
            transition: all 0.2s;
            font-size: 14px;
        }
        
        .btn-add:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
        }
        
        .btn-delete-item {
            background: #fee2e2;
            color: #dc2626;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 11px;
            margin-top: 8px;
            width: 100%;
            transition: all 0.2s;
            font-weight: 600;
        }
        
        .btn-delete-item:hover {
            background: #fecaca;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #94a3b8;
        }
        
        .empty-icon {
            font-size: 36px;
            margin-bottom: 12px;
            opacity: 0.5;
        }
        
        .view-container {
            display: none;
        }
        
        .view-container.active {
            display: block;
        }
        
        @media (max-width: 1200px) {
            .three-column {
                grid-template-columns: 1fr;
                gap: 16px;
            }
        }
        
        @media (max-width: 1024px) {
            .sidebar {
                width: 70px;
            }
            
            .logo-text,
            .nav-link span {
                display: none;
            }
            
            .logo {
                justify-content: center;
            }
            
            .nav-link {
                justify-content: center;
            }
            
            .main-content {
                margin-left: 70px;
            }
            
            .search-box {
                width: 300px;
            }
            
            .user-info {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s;
                width: 260px;
            }
            
            .sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
            
            .top-bar {
                padding: 12px 16px;
            }
            
            .search-box {
                display: none;
            }
            
            .content-area {
                padding: 16px;
            }
            
            .welcome-banner {
                padding: 24px;
            }
            
            .welcome-banner h1 {
                font-size: 22px;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 12px;
            }
            
            .stat-card {
                padding: 16px;
            }
            
            .stat-value {
                font-size: 24px;
            }
            
            .section {
                padding: 16px;
            }
            
            table {
                font-size: 12px;
            }
            
            th, td {
                padding: 8px;
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .top-bar-right {
                gap: 8px;
            }
            
            .user-avatar {
                width: 32px;
                height: 32px;
            }
        }
        
        .mobile-menu-btn {
            display: none;
            width: 40px;
            height: 40px;
            border: none;
            background: #f1f5f9;
            border-radius: 8px;
            cursor: pointer;
            color: #64748b;
            font-size: 18px;
        }
        
        @media (max-width: 768px) {
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }
        
        .swal2-popup { border-radius: 20px !important; }
        .swal2-confirm { border-radius: 10px !important; padding: 12px 30px !important; }
        .swal2-cancel { border-radius: 10px !important; padding: 12px 30px !important; }
    </style>
</head>
<body>
    <aside class="sidebar" id="sidebar">
        <div class="logo">
            <div class="logo-icon">🛒</div>
            <span class="logo-text">Admin Panel</span>
        </div>
        
        <ul class="nav-menu">
            <li class="nav-item">
                <a class="nav-link" onclick="showView('overview')">
                    <i class="fas fa-home"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" onclick="showView('brands')">
                    <i class="fas fa-tags"></i>
                    <span>Brands & Models</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="crud_app/products_crud.php" class="nav-link">
                    <i class="fas fa-box"></i>
                    <span>Products</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="categories.php" class="nav-link">
                    <i class="fas fa-th-large"></i>
                    <span>Categories</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="crud_app/employee_crud.php" class="nav-link">
                    <i class="fas fa-users"></i>
                    <span>Employees</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="seller_login.php" class="nav-link">
                    <i class="fas fa-store"></i>
                    <span>Sellers</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-chart-line"></i>
                    <span>Analytics</span>
                </a>
            </li>
            <li class="nav-item">
                <a href="#" class="nav-link">
                    <i class="fas fa-cog"></i>
                    <span>Settings</span>
                </a>
            </li>
            <li class="nav-item" style="margin-top: 20px;">
                <a href="logout.php" class="nav-link">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Logout</span>
                </a>
            </li>
        </ul>
    </aside>
    
    <main class="main-content">
        <div class="top-bar">
            <button class="mobile-menu-btn" onclick="toggleSidebar()">
                <i class="fas fa-bars"></i>
            </button>
            
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search products, categories, sellers...">
            </div>
            
            <div class="top-bar-right">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-badge">3</span>
                </button>
                
                <div class="user-profile">
                    <div class="user-avatar">
                        <?php echo strtoupper(substr($_SESSION['admin_username'], 0, 1)); ?>
                    </div>
                    <div class="user-info">
                        <div class="user-name"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></div>
                        <div class="user-role">Administrator</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="content-area">
            <div id="overview-view" class="view-container active">
                <div class="welcome-banner">
                    <div class="welcome-content">
                        <h1>Welcome back, <?php echo htmlspecialchars($_SESSION['admin_username']); ?>! 🎉</h1>
                        <p>Here's what's happening with your marketplace today.</p>
                        <a href="crud_app/products_crud.php" class="btn-primary">
                            <i class="fas fa-plus"></i> Add New Product
                        </a>
                    </div>
                </div>
                
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Total Products</span>
                            <div class="stat-icon blue">
                                <i class="fas fa-box"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_products']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +12% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Active Listings</span>
                            <div class="stat-icon green">
                                <i class="fas fa-check-circle"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['active_products']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +8% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Sold Items</span>
                            <div class="stat-icon purple">
                                <i class="fas fa-shopping-bag"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['sold_products']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +24% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Total Views</span>
                            <div class="stat-icon orange">
                                <i class="fas fa-eye"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_views']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +35% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Total Sellers</span>
                            <div class="stat-icon indigo">
                                <i class="fas fa-store"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_sellers']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +5% from last month
                        </div>
                    </div>
                    
                    <div class="stat-card">
                        <div class="stat-header">
                            <span class="stat-label">Total Brands</span>
                            <div class="stat-icon red">
                                <i class="fas fa-tags"></i>
                            </div>
                        </div>
                        <div class="stat-value"><?php echo number_format($stats['total_brands']); ?></div>
                        <div class="stat-change">
                            <i class="fas fa-arrow-up"></i> +3% from last month
                        </div>
                    </div>
                </div>
                
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">Recent Products</h2>
                        <a href="crud_app/products_crud.php" class="btn-secondary">View All</a>
                    </div>
                    
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Product</th>
                                    <th>Category</th>
                                    <th>Seller</th>
                                    <th>Price</th>
                                    <th>Status</th>
                                    <th>Views</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($recent_products->num_rows > 0): ?>
                                    <?php while($product = $recent_products->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $product['id']; ?></td>
                                        <td class="product-title"><?php echo htmlspecialchars($product['title']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($product['seller_name'] ?? 'N/A'); ?></td>
                                        <td><strong>₹<?php echo number_format($product['price'], 2); ?></strong></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $product['status']; ?>">
                                                <?php echo ucfirst($product['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo number_format($product['views']); ?></td>
                                        <td>
                                            <div class="action-buttons">
                                                <button class="btn-icon btn-edit" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn-icon btn-delete" title="Delete">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" style="text-align: center; padding: 40px; color: #94a3b8;">
                                            No products found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <div id="brands-view" class="view-container">
                <div class="section">
                    <div class="section-header">
                        <h2 class="section-title">🏷️ Manage Brands & Models</h2>
                        <p style="color: #64748b; font-size: 14px;">Select a category → Add brands → Add models for each brand</p>
                    </div>
                    
                    <div class="three-column">
                        <div class="column">
                            <div class="column-title">📁 Select Category</div>
                            <div id="categoryList"></div>
                        </div>
                        
                        <div class="column">
                            <div class="column-title" id="brandColumnTitle">🏷️ Select Brand</div>
                            <div id="brandList">
                                <div class="empty-state">
                                    <div class="empty-icon">👈</div>
                                    <p>Select a category first</p>
                                </div>
                            </div>
                            <div id="addBrandSection" style="display:none;" class="add-section">
                                <input type="text" id="newBrandName" class="add-input" placeholder="Enter brand name">
                                <button class="btn-add" onclick="addBrand()">➕ Add Brand</button>
                            </div>
                        </div>
                        
                        <div class="column">
                            <div class="column-title" id="modelColumnTitle">🔧 Manage Models</div>
                            <div id="modelList">
                                <div class="empty-state">
                                    <div class="empty-icon">👈</div>
                                    <p>Select a brand first</p>
                                </div>
                            </div>
                            <div id="addModelSection" style="display:none;" class="add-section">
                                <input type="text" id="newModelName" class="add-input" placeholder="Enter model name">
                                <button class="btn-add" onclick="addModel()">➕ Add Model</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    
    <script>
        let selectedCategoryId = 0;
        let selectedBrandId = 0;
        let categories = [];
        let brands = [];
        let currentView = '<?php echo $view; ?>';
        
        function showView(viewName) {
            document.querySelectorAll('.view-container').forEach(v => v.classList.remove('active'));
            document.querySelectorAll('.nav-link').forEach(l => l.classList.remove('active'));
            
            document.getElementById(viewName + '-view').classList.add('active');
            event.target.closest('.nav-link').classList.add('active');
            
            if (viewName === 'brands') {
                loadCategories();
            }
            
            if (window.innerWidth <= 768) {
                document.getElementById('sidebar').classList.remove('mobile-open');
            }
        }
        
        document.addEventListener('DOMContentLoaded', function() {
            const navLinks = document.querySelectorAll('.nav-link');
            navLinks[currentView === 'brands' ? 1 : 0].classList.add('active');
            
            if (currentView === 'brands') {
                document.getElementById('brands-view').classList.add('active');
                document.getElementById('overview-view').classList.remove('active');
                loadCategories();
            }
        });
        
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
            list.innerHTML = html || '<div class="empty-state"><div class="empty-icon">📦</div><p>No categories yet</p></div>';
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
                        document.getElementById('brandColumnTitle').textContent = `🏷️ Brands in ${data[0].category_name}`;
                    }
                });
        }
        
        function renderBrands() {
            const list = document.getElementById('brandList');
            if (brands.length === 0) {
                list.innerHTML = '<div class="empty-state"><div class="empty-icon">📦</div><p>No brands yet. Add one!</p></div>';
                return;
            }
            
            list.innerHTML = brands.map(brand => `
                <div class="item-card ${selectedBrandId === brand.id ? 'active' : ''}" onclick="selectBrand(${brand.id}, '${brand.name.replace(/'/g, "\\'")}')">
                    <div class="item-name">${brand.name}</div>
                    <div class="item-count">${brand.model_count} models</div>
                    <button class="btn-delete-item" onclick="event.stopPropagation(); deleteBrand(${brand.id}, '${brand.name.replace(/'/g, "\\'")}')">🗑️ Delete</button>
                </div>
            `).join('');
        }
        
        function selectBrand(brandId, brandName) {
            selectedBrandId = brandId;
            renderBrands();
            loadModels(brandId);
            document.getElementById('modelColumnTitle').textContent = `🔧 Models for ${brandName}`;
            document.getElementById('addModelSection').style.display = 'block';
        }
        
        function loadModels(brandId) {
            fetch(`?action=get_models&brand_id=${brandId}`)
                .then(r => r.json())
                .then(models => {
                    const list = document.getElementById('modelList');
                    if (models.length === 0) {
                        list.innerHTML = '<div class="empty-state"><div class="empty-icon">📦</div><p>No models yet. Add one!</p></div>';
                        return;
                    }
                    
                    list.innerHTML = models.map(model => `
                        <div class="item-card">
                            <div class="item-name">${model.name}</div>
                            <button class="btn-delete-item" onclick="deleteModel(${model.id}, '${model.name.replace(/'/g, "\\'")}')">🗑️ Delete</button>
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
                    confirmButtonColor: '#667eea'
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
                    confirmButtonColor: '#667eea'
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
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
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
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#94a3b8',
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
        
        function toggleSidebar() {
            document.getElementById('sidebar').classList.toggle('mobile-open');
        }
        
        document.addEventListener('click', function(event) {
            const sidebar = document.getElementById('sidebar');
            const menuBtn = document.querySelector('.mobile-menu-btn');
            
            if (window.innerWidth <= 768) {
                if (!sidebar.contains(event.target) && !menuBtn.contains(event.target)) {
                    sidebar.classList.remove('mobile-open');
                }
            }
        });
    </script>
</body>
</html>