<?php
require_once '../config.php';
requireAdmin();
$conn = getDBConnection();

// Get all products with related data
$sql = "SELECT p.*, 
        s.name as seller_name, 
        c.name as category_name,
        b.name as brand_name,
        m.name as model_name
        FROM products p 
        LEFT JOIN sellers s ON p.seller_id = s.id 
        LEFT JOIN categories c ON p.category_id = c.id 
        LEFT JOIN brands b ON p.brand_id = b.id
        LEFT JOIN models m ON p.model_id = m.id
        ORDER BY p.created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Products - Admin</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', system-ui, sans-serif; background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%); min-height: 100vh; }
        
        .back-nav {
            padding: 20px 30px;
            background: white;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .back-nav a {
            color: #667eea;
            text-decoration: none;
            font-weight: 600;
            font-size: 16px;
        }
        .back-nav a:hover { color: #764ba2; }
        
        .container { max-width: 1400px; margin: 30px auto; padding: 0 30px; }
        
        .header-section {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .header-section h1 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }
        .stats { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-top: 20px; }
        .stat-box {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-value { font-size: 36px; font-weight: bold; }
        .stat-label { font-size: 14px; opacity: 0.9; margin-top: 5px; }
        
        .products-grid {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }
        th {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 12px;
        }
        tr:hover { background: #f8f9fa; }
        
        .status-badge {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .status-active { background: #d4edda; color: #155724; }
        .status-sold { background: #cce5ff; color: #004085; }
        .status-inactive { background: #f8d7da; color: #721c24; }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn-edit {
            background: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            color: white;
        }
        .btn-delete {
            background: linear-gradient(135deg, #ef5350 0%, #e53935 100%);
            color: white;
            margin-left: 5px;
        }
        .btn:hover { transform: translateY(-2px); }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .empty-icon { font-size: 64px; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="back-nav">
        <a href="../admin_dashboard.php">← Back to Admin Dashboard</a>
    </div>
    
    <div class="container">
        <div class="header-section">
            <h1> All Products Management</h1>
            <p style="color: #666; margin-top: 10px;">View and manage all products from all sellers</p>
            
            <div class="stats">
                <div class="stat-box">
                    <div class="stat-value"><?php echo $result->num_rows; ?></div>
                    <div class="stat-label">Total Products</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        $result->data_seek(0);
                        $active = 0;
                        while($r = $result->fetch_assoc()) if($r['status']=='active') $active++;
                        echo $active;
                        $result->data_seek(0);
                        ?>
                    </div>
                    <div class="stat-label">Active</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        $result->data_seek(0);
                        $sold = 0;
                        while($r = $result->fetch_assoc()) if($r['status']=='sold') $sold++;
                        echo $sold;
                        $result->data_seek(0);
                        ?>
                    </div>
                    <div class="stat-label">Sold</div>
                </div>
                <div class="stat-box">
                    <div class="stat-value">
                        <?php 
                        $result->data_seek(0);
                        $views = 0;
                        while($r = $result->fetch_assoc()) $views += $r['views'];
                        echo number_format($views);
                        $result->data_seek(0);
                        ?>
                    </div>
                    <div class="stat-label">Total Views</div>
                </div>
            </div>
        </div>
        
        <div class="products-grid">
            <?php if ($result->num_rows > 0): ?>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Brand/Model</th>
                            <th>Price</th>
                            <th>Seller</th>
                            <th>Location</th>
                            <th>Status</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = $result->fetch_assoc()): ?>
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
                            <td><?php echo htmlspecialchars($product['seller_name'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($product['location'] ?? 'N/A'); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $product['status']; ?>">
                                    <?php echo strtoupper($product['status']); ?>
                                </span>
                            </td>
                            <td><?php echo number_format($product['views']); ?></td>
                            <td>
                                <a href="product_edit.php?id=<?php echo $product['id']; ?>" class="btn btn-edit">Edit</a>
                                <a href="product_delete.php?id=<?php echo $product['id']; ?>" 
                                   class="btn btn-delete"
                                   onclick="return confirm('Delete this product?')">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="empty-state">
                    <div class="empty-icon"></div>
                    <h3>No products found</h3>
                    <p>No products have been posted yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>