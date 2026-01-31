<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Authorization;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\CategoryRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Database\Connection;

// Start session and check authentication
Session::start();
if (!Session::isAuthenticated()) {
    header('Location: /login.php');
    exit;
}

// Check if user is a vendor
$user = Session::getUser();
if ($user['role'] !== 'Vendor') {
    header('Location: /dashboard.php');
    exit;
}

// Initialize repositories
$productRepo = new ProductRepository();
$categoryRepo = new CategoryRepository();
$vendorRepo = new VendorRepository();

// Get vendor profile
$userId = $user['user_id'];
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

// Get vendor's products
$vendorId = $vendor->getId();
$products = $productRepo->findByVendorId($vendorId);
$categories = $categoryRepo->findAll();

// Handle delete action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $productId = $_POST['product_id'] ?? '';
    if ($productId) {
        $product = $productRepo->findById($productId);
        if ($product && $product->belongsToVendor($vendorId)) {
            $productRepo->delete($productId);
            header('Location: /vendor/products.php?success=deleted');
            exit;
        }
    }
}

$pageTitle = 'My Products';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Rental Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            color: #333;
        }
        
        .header {
            background: #fff;
            border-bottom: 1px solid #e0e0e0;
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .header h1 {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        .header .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 0.9rem;
            transition: all 0.2s;
        }
        
        .btn-primary {
            background: #3498db;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2980b9;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 2rem;
            color: #2c3e50;
        }
        
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: white;
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background: #ecf0f1;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #95a5a6;
            font-size: 3rem;
        }
        
        .product-content {
            padding: 1.5rem;
        }
        
        .product-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #2c3e50;
        }
        
        .product-description {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            line-height: 1.5;
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-meta {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.85rem;
        }
        
        .product-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        
        .status-inactive {
            background: #fff3cd;
            color: #856404;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .product-actions .btn {
            flex: 1;
            text-align: center;
            font-size: 0.85rem;
            padding: 0.5rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            background: white;
            border-radius: 8px;
        }
        
        .empty-state-icon {
            font-size: 4rem;
            color: #bdc3c7;
            margin-bottom: 1rem;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: #7f8c8d;
            margin-bottom: 0.5rem;
        }
        
        .empty-state p {
            color: #95a5a6;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏪 Rental Platform - Vendor Portal</h1>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($user['username']) ?></span>
            <a href="/dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <?php if ($_GET['success'] === 'deleted'): ?>
                    Product deleted successfully!
                <?php elseif ($_GET['success'] === 'created'): ?>
                    Product created successfully!
                <?php elseif ($_GET['success'] === 'updated'): ?>
                    Product updated successfully!
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="page-header">
            <h2>My Products</h2>
            <a href="/Multi-Vendor-Rental-System/public/vendor/product-create.php" class="btn btn-primary">+ Add New Product</a>
        </div>
        
        <?php if (empty($products)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">📦</div>
                <h3>No products yet</h3>
                <p>Start by adding your first rental product to the platform</p>
                <a href="/Multi-Vendor-Rental-System/public/vendor/product-create.php" class="btn btn-primary">Add Your First Product</a>
            </div>
        <?php else: ?>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                    <div class="product-card">
                        <div class="product-image">
                            <?php if (!empty($product->getImages())): ?>
                                <img src="<?= htmlspecialchars($product->getImages()[0]) ?>" alt="<?= htmlspecialchars($product->getName()) ?>" style="width: 100%; height: 100%; object-fit: cover;">
                            <?php else: ?>
                                📦
                            <?php endif; ?>
                        </div>
                        <div class="product-content">
                            <h3 class="product-title"><?= htmlspecialchars($product->getName()) ?></h3>
                            <p class="product-description"><?= htmlspecialchars($product->getDescription()) ?></p>
                            <div class="product-meta">
                                <span class="product-status status-<?= strtolower($product->getStatus()) ?>">
                                    <?= htmlspecialchars($product->getStatus()) ?>
                                </span>
                                <?php if ($product->isVerificationRequired()): ?>
                                    <span style="color: #e67e22;">🔒 Verification Required</span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <a href="/Multi-Vendor-Rental-System/public/vendor/product-edit.php?id=<?= htmlspecialchars($product->getId()) ?>" class="btn btn-primary">Edit</a>
                                <form method="POST" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this product?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="product_id" value="<?= htmlspecialchars($product->getId()) ?>">
                                    <button type="submit" class="btn btn-danger" style="width: 100%;">Delete</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
