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

// Handle status update
if (isset($_POST['update_status'])) {
    $product_id = intval($_POST['product_id']);
    $status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE products SET status = ? WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("sii", $status, $product_id, $seller_id);
    $stmt->execute();
    
    header('Location: seller_dashboard.php?updated=1');
    exit;
}

// Handle delete
if (isset($_POST['delete_product'])) {
    $product_id = intval($_POST['product_id']);
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ? AND seller_id = ?");
    $stmt->bind_param("ii", $product_id, $seller_id);
    $stmt->execute();
    
    header('Location: seller_dashboard.php?deleted=1');
    exit;
}

// Get seller's products with category info
$stmt = $conn->prepare("SELECT p.*, c.name as category_name, c.icon as category_icon,
                        b.name as brand_name, m.name as model_name
                        FROM products p
                        LEFT JOIN categories c ON p.category_id = c.id
                        LEFT JOIN brands b ON p.brand_id = b.id
                        LEFT JOIN models m ON p.model_id = m.id
                        WHERE p.seller_id = ?
                        ORDER BY p.created_at DESC");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$products = $stmt->get_result();

// Get statistics - REAL TIME from database
$stmt = $conn->prepare("SELECT 
                        COUNT(*) as total_ads,
                        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active_ads,
                        SUM(CASE WHEN status = 'sold' THEN 1 ELSE 0 END) as sold_ads,
                        SUM(views) as total_views
                        FROM products WHERE seller_id = ?");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Calculate some dynamic stats for the cards
$total_revenue = 0;
$stmt = $conn->prepare("SELECT SUM(price) as revenue FROM products WHERE seller_id = ? AND status = 'sold'");
$stmt->bind_param("i", $seller_id);
$stmt->execute();
$revenue_result = $stmt->get_result()->fetch_assoc();
$total_revenue = $revenue_result['revenue'] ?? 0;

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Seller Portal</title>
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', -apple-system, BlinkMacSystemFont, sans-serif; 
            background: #f5f7fa;
            display: flex;
            min-height: 100vh;
        }
        
        /* Sidebar */
        .sidebar {
            width: 250px;
            background: white;
            padding: 30px 0;
            box-shadow: 2px 0 10px rgba(0,0,0,0.05);
            position: fixed;
            height: 100vh;
            overflow-y: auto;
        }
        
        .logo {
            padding: 0 30px 30px;
            display: flex;
            margin-right: auto;
            align-items: center;
            gap: 12px;
            font-size: 24px;
            font-weight: 700;
            color: #5f72e4;
        }
        
        .logo-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #5f72e4 0%, #8b5cf6 100%);
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        
        .nav-section {
            margin: 20px 0;
        }
        
        .nav-title {
            padding: 0 30px;
            font-size: 11px;
            font-weight: 600;
            color: #a0aec0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .nav-item {
            padding: 12px 30px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #64748b;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 14px;
            font-weight: 500;
        }
        
        .nav-item:hover {
            background: #f8f9fa;
            color: #5f72e4;
        }
        
        .nav-item.active {
            background: linear-gradient(90deg, #e0e7ff 0%, transparent 100%);
            color: #5f72e4;
            border-left: 3px solid #5f72e4;
        }
        
        .nav-item i {
            width: 20px;
            font-size: 18px;
        }
        
        /* Main Content */
        .main-content {
            margin-left: 250px;
            flex: 1;
            padding: 0;
        }
        
        /* Top Bar */
        .top-bar {
            background: white;
            padding: 20px 40px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            position: sticky;
            top: 0;
            z-index: 100;
        }
        
        .search-box {
            flex: 1;
            max-width: 500px;
            position: relative;
        }
        
        .search-box input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #5f72e4;
            box-shadow: 0 0 0 3px rgba(95, 114, 228, 0.1);
        }
        
        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
            font-size: 18px;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
            position: relative;
        }
        
        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #5f72e4 0%, #8b5cf6 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .user-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(95, 114, 228, 0.4);
        }
        
        .dropdown-menu {
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.15);
            min-width: 220px;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s;
            z-index: 1000;
        }
        
        .dropdown-menu.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }
        
        .dropdown-header {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
        }
        
        .dropdown-user-name {
            font-weight: 700;
            color: #1e293b;
            font-size: 15px;
            margin-bottom: 4px;
        }
        
        .dropdown-user-email {
            font-size: 13px;
            color: #64748b;
        }
        
        .dropdown-items {
            padding: 8px 0;
        }
        
        .dropdown-item {
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #475569;
            text-decoration: none;
            transition: all 0.2s;
            cursor: pointer;
            font-size: 14px;
        }
        
        .dropdown-item:hover {
            background: #f8fafc;
            color: #5f72e4;
        }
        
        .dropdown-item i {
            font-size: 18px;
            width: 20px;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #f1f5f9;
            margin: 8px 0;
        }
        
        .dropdown-item.logout {
            color: #ef4444;
        }
        
        .dropdown-item.logout:hover {
            background: #fef2f2;
            color: #dc2626;
        }
        
        /* Content Area */
        .content-area {
            padding: 30px 40px;
        }
        
        /* Welcome Card */
        .welcome-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 20px;
            padding: 40px;
            color: white;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
        }
        
        .welcome-content h1 {
            font-size: 28px;
            margin-bottom: 10px;
        }
        
        .welcome-content p {
            font-size: 16px;
            opacity: 0.95;
            margin-bottom: 20px;
        }
        
        .btn-view-badge {
            background: rgba(255,255,255,0.25);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            display: inline-block;
            font-weight: 600;
            transition: all 0.3s;
            border: 2px solid rgba(255,255,255,0.3);
        }
        
        .btn-view-badge:hover {
            background: white;
            color: #667eea;
        }
        
        .welcome-illustration {
            font-size: 120px;
        }
        
        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 28px;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #4e0a45ff, #3d0c5eff);
        }
        
        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 20px;
        }
        
        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        
        /* .stat-icon.profit { background: linear-gradient(135deg, #10b981 0%, #059669 100%); }
        .stat-icon.sales { background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%); }
        .stat-icon.active { background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); }
        .stat-icon.views { background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%); } */
        
        .stat-label {
            font-size: 14px;
            color: #64748b;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 10px;
        }
        
        .stat-change {
            font-size: 13px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        
        .stat-change.positive { color: #10b981; }
        .stat-change.negative { color: #ef4444; }
        
        /* Products Section */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 24px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
        }
        
        .btn-post {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s;
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
        }
        
        .btn-post:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(16, 185, 129, 0.4);
        }
        
        .products-table {
            background: white;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            background: #f8fafc;
            padding: 16px 20px;
            text-align: left;
            font-size: 12px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #e2e8f0;
        }
        
        td {
            padding: 16px 20px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 14px;
            color: #334155;
        }
        
        tr:hover {
            background: #f8fafc;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            display: inline-block;
        }
        
        .status-active { background: #d1fae5; color: #065f46; }
        .status-sold { background: #dbeafe; color: #1e40af; }
        .status-inactive { background: #fee2e2; color: #991b1b; }
        
        .status-select {
            padding: 8px 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            background: white;
            transition: all 0.2s;
        }
        
        .status-select:focus {
            outline: none;
            border-color: #5f72e4;
        }
        
        .btn {
            padding: 8px 14px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s;
            margin: 0 4px;
        }
        
        .btn-edit {
            background: #fef3c7;
            color: #92400e;
        }
        
        .btn-delete {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }
        
        .empty-state {
            text-align: center;
            padding: 80px 20px;
            color: #94a3b8;
        }
        
        .empty-icon {
            font-size: 80px;
            margin-bottom: 20px;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            color: #64748b;
            margin-bottom: 10px;
        }
        
        /* Responsive */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .main-content {
                margin-left: 0;
            }
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 768px) {
            .welcome-card {
                flex-direction: column;
                text-align: center;
            }
            .welcome-illustration {
                font-size: 80px;
            }
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .content-area {
                padding: 20px;
            }
            table {
                font-size: 12px;
            }
            th, td {
                padding: 12px 10px;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="logo">
            <img src="images/logo.png" alt="Midrate Logo" style="width: 50%; height: 30%; object-fit: contain; align-items: right;">
            <!-- <span>MidRate</span> -->
        </div>
        
        <div class="nav-section">
            <div class="nav-item active">
                <i></i>
                <span>Dashboard</span>
            </div>
            <div class="nav-item" onclick="window.location.href='post_ad.php'">
                <i></i>
                <span>Products</span>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">PAGES</div>
            <div class="nav-item">
                <i></i>
                <span>Account Settings</span>
            </div>
            <div class="nav-item">
                <i></i>
                <span>Authentications</span>
            </div>
            <div class="nav-item">
                <i></i>
                <span>Users</span>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-title">MISC</div>
            <div class="nav-item">
                <i></i>
                <span>Support</span>
            </div>
            <div class="nav-item">
                <i></i>
                <span>Documentation</span>
            </div>
        </div>
    </div>
    
    <!-- Main Content -->
    <div class="main-content">
        <!-- Top Bar -->
        <div class="top-bar">
            <div class="search-box">
                <span class="search-icon"></span>
                <input type="text" placeholder="Search...">
            </div>
            <div class="user-menu">
                <div class="user-avatar" onclick="toggleDropdown()" title="<?php echo htmlspecialchars($seller['name']); ?>">
                    <?php echo strtoupper(substr($seller['name'], 0, 1)); ?>
                </div>
                
                <!-- Dropdown Menu -->
                <div class="dropdown-menu" id="userDropdown">
                    <div class="dropdown-header">
                        <div class="dropdown-user-name"><?php echo htmlspecialchars($seller['name']); ?></div>
                        <div class="dropdown-user-email"><?php echo htmlspecialchars($seller['email'] ?? 'seller@example.com'); ?></div>
                    </div>
                    <div class="dropdown-items">
                        <a href="#" class="dropdown-item">
                            <i>👤</i>
                            <span>My Profile</span>
                        </a>
                        <a href="seller_dashboard.php" class="dropdown-item">
                            <i></i>
                            <span>Dashboard</span>
                        </a>
                        <a href="post_ad.php" class="dropdown-item">
                            <i></i>
                            <span>Post New Ad</span>
                        </a>
                        <a href="#" class="dropdown-item">
                            <i></i>
                            <span>Settings</span>
                        </a>
                        <div class="dropdown-divider"></div>
                        <a href="seller_logout.php" class="dropdown-item logout">
                            <i></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Content Area -->
        <div class="content-area">
            <!-- Welcome Card -->
            <div class="welcome-card">
                <div class="welcome-content">
                    <h1>Congratulations <?php echo htmlspecialchars(explode(' ', $seller['name'])[0]); ?>! 🎉</h1>
                    <p>You have <?php echo $stats['active_ads'] ?? 0; ?> active listings. Keep up the great work!</p>
                    <a href="post_ad.php" class="btn-view-badge">Post New Ad</a>
                </div>
                <div class="welcome-illustration">
                    
                </div>
            </div>
            
            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Revenue</div>
                            <div class="stat-value">₹<?php echo number_format($total_revenue, 0); ?></div>
                            <div class="stat-change positive">
                                <span>↑</span>
                                <span>+42.8%</span>
                            </div>
                        </div>
                        <div class="stat-icon profit"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Sales</div>
                            <div class="stat-value"><?php echo $stats['sold_ads'] ?? 0; ?></div>
                            <div class="stat-change positive">
                                <span>↑</span>
                                <span>+28.4%</span>
                            </div>
                        </div>
                        <div class="stat-icon sales"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Active Ads</div>
                            <div class="stat-value"><?php echo $stats['active_ads'] ?? 0; ?></div>
                            <div class="stat-change positive">
                                <span>↑</span>
                                <span>+15.2%</span>
                            </div>
                        </div>
                        <div class="stat-icon active"></div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-header">
                        <div>
                            <div class="stat-label">Total Views</div>
                            <div class="stat-value"><?php echo number_format($stats['total_views'] ?? 0); ?></div>
                            <div class="stat-change positive">
                                <span>↑</span>
                                <span>+32.1%</span>
                            </div>
                        </div>
                        <div class="stat-icon views"></div>
                    </div>
                </div>
            </div>
            
            <!-- Products Section -->
            <div class="section-header">
                <h2 class="section-title"> My Listings</h2>
                <a href="post_ad.php" class="btn-post">
                    <span>+</span>
                    Post New Ad
                </a>
            </div>
            
            <div class="products-table">
                <?php if ($products->num_rows == 0): ?>
                    <div class="empty-state">
                        <div class="empty-icon"></div>
                        <h3>No ads posted yet</h3>
                        <p>Start selling by posting your first ad!</p>
                        <a href="post_ad.php" class="btn-post" style="margin-top: 20px;">Post Your First Ad</a>
                    </div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Brand/Model</th>
                                <th>Price</th>
                                <th>Location</th>
                                <th>Status</th>
                                <th>Views</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $products->data_seek(0);
                            while ($product = $products->fetch_assoc()): 
                            ?>
                            <tr>
                                <td><strong>#<?php echo $product['id']; ?></strong></td>
                                <td><?php echo htmlspecialchars($product['title']); ?></td>
                                <td><?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?></td>
                                <td>
                                    <?php 
                                    $bm = [];
                                    if($product['brand_name']) $bm[] = $product['brand_name'];
                                    if($product['model_name']) $bm[] = $product['model_name'];
                                    echo htmlspecialchars(implode(' - ', $bm) ?: 'N/A');
                                    ?>
                                </td>
                                <td><strong>₹<?php echo number_format($product['price'], 2); ?></strong></td>
                                <td><?php echo htmlspecialchars($product['location'] ?? 'N/A'); ?></td>
                                <td>
                                    <form method="POST" style="margin: 0; display: inline;">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <select name="status" class="status-select" onchange="confirmStatusChange(this)">
                                            <option value="active" <?php echo $product['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                            <option value="sold" <?php echo $product['status'] == 'sold' ? 'selected' : ''; ?>>Sold</option>
                                            <option value="inactive" <?php echo $product['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                        </select>
                                        <input type="hidden" name="update_status" value="1">
                                    </form>
                                </td>
                                <td><?php echo number_format($product['views']); ?></td>
                                <td>
                                    <a href="crud_app/product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">✏️ Edit</a>
                                    <form method="POST" style="display: inline; margin: 0;" onsubmit="return confirmDelete('<?php echo htmlspecialchars($product['title'], ENT_QUOTES); ?>')">
                                        <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                        <button type="submit" name="delete_product" class="btn btn-delete">🗑️ Delete</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Dropdown Toggle
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('active');
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            
            if (!avatar.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
        
        function confirmStatusChange(selectElement) {
            const form = selectElement.closest('form');
            const selectedStatus = selectElement.value;
            const originalStatus = selectElement.getAttribute('data-original') || selectElement.value;
            
            if (originalStatus === selectedStatus) return;
            
            Swal.fire({
                title: 'Update Status?',
                text: `Change status to "${selectedStatus.toUpperCase()}"?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#5f72e4',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, update it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                } else {
                    selectElement.value = originalStatus;
                }
            });
        }
        
        // Store original status values
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.status-select').forEach(select => {
                select.setAttribute('data-original', select.value);
            });
        });
        
        function confirmDelete(title) {
            event.preventDefault();
            const form = event.target;
            
            Swal.fire({
                title: 'Delete Ad?',
                html: `Are you sure you want to delete <strong>"${title}"</strong>?<br>This action cannot be undone.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef5350',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
            
            return false;
        }
        
        // Show alerts based on URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.get('updated') === '1') {
            Swal.fire({
                icon: 'success',
                title: 'Status Updated!',
                text: 'Product status has been updated successfully',
                timer: 2000,
                showConfirmButton: false
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.get('deleted') === '1') {
            Swal.fire({
                icon: 'success',
                title: 'Deleted!',
                text: 'Product has been deleted successfully',
                timer: 2000,
                showConfirmButton: false
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
        
        if (urlParams.get('success') === 'added') {
            Swal.fire({
                icon: 'success',
                title: 'Ad Posted!',
                text: 'Your ad has been posted successfully',
                timer: 2000,
                showConfirmButton: false
            });
            window.history.replaceState({}, document.title, window.location.pathname);
        }
    </script>
</body>
</html>