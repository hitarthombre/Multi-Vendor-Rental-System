<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\VariantRepository;
use RentalPlatform\Repositories\AttributeRepository;
use RentalPlatform\Repositories\AttributeValueRepository;
use RentalPlatform\Database\Connection;

// Start session and check authentication
Session::start();
if (!Session::isAuthenticated()) {
    header('Location: /Multi-Vendor-Rental-System/public/login.php');
    exit;
}

// Check if user is a vendor
$user = Session::getUser();
if ($user['role'] !== 'Vendor') {
    header('Location: /Multi-Vendor-Rental-System/public/dashboard.php');
    exit;
}

// Get product ID
$productId = $_GET['product_id'] ?? '';
if (empty($productId)) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/products.php');
    exit;
}

// Initialize repositories
$productRepo = new ProductRepository();
$variantRepo = new VariantRepository();
$attributeRepo = new AttributeRepository($db);
$attributeValueRepo = new AttributeValueRepository($db);

// Get product and verify ownership
$product = $productRepo->findById($productId);

// Get vendor ID
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($user['user_id']);
if (!$vendor) {
    die('Vendor profile not found. Please contact support.');
}

if (!$product || !$product->belongsToVendor($vendor->getId())) {
    header('Location: /Multi-Vendor-Rental-System/public/vendor/products.php');
    exit;
}

// Get variants for this product
$variants = $variantRepo->findByProduct($productId);

// Get all available attributes
$attributes = $attributeRepo->findAll();

$pageTitle = 'Product Variants - ' . $product->getName();
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
        
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        
        .btn-success {
            background: #27ae60;
            color: white;
        }
        
        .btn-success:hover {
            background: #229954;
        }
        
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c0392b;
        }
        
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 2rem;
        }
        
        .page-header {
            margin-bottom: 2rem;
        }
        
        .page-header h2 {
            font-size: 2rem;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .breadcrumb {
            color: #7f8c8d;
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .product-info {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .product-info h3 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .product-info p {
            color: #7f8c8d;
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }
        
        .section-header h3 {
            font-size: 1.5rem;
            color: #2c3e50;
        }
        
        .variants-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .variant-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .variant-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }
        
        .variant-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 1rem;
        }
        
        .variant-sku {
            font-weight: 600;
            color: #2c3e50;
            font-size: 1.1rem;
        }
        
        .variant-attributes {
            margin-bottom: 1rem;
        }
        
        .attribute-tag {
            display: inline-block;
            background: #ecf0f1;
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.85rem;
            margin: 0.25rem;
            color: #2c3e50;
        }
        
        .variant-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .variant-actions .btn {
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
        
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }
        
        .info-box h4 {
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }
        
        .info-box p {
            color: #5d6d7e;
            font-size: 0.9rem;
            line-height: 1.5;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>🏪 Rental Platform - Vendor Portal</h1>
        <div class="user-info">
            <span>Welcome, <?= htmlspecialchars($user['username']) ?></span>
            <a href="/Multi-Vendor-Rental-System/public/dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="/Multi-Vendor-Rental-System/public/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2>Product Variants</h2>
            <div class="breadcrumb">
                <a href="/Multi-Vendor-Rental-System/public/vendor/products.php">My Products</a> / 
                <a href="/Multi-Vendor-Rental-System/public/vendor/product-edit.php?id=<?= htmlspecialchars($productId) ?>"><?= htmlspecialchars($product->getName()) ?></a> / 
                Variants
            </div>
        </div>
        
        <div class="product-info">
            <h3><?= htmlspecialchars($product->getName()) ?></h3>
            <p><?= htmlspecialchars($product->getDescription()) ?></p>
        </div>
        
        <div class="info-box">
            <h4>ℹ️ About Product Variants</h4>
            <p>
                Variants allow you to offer different configurations of the same product. For example, if you rent cameras, 
                you might have variants for different lens types, colors, or models. Each variant can have its own pricing and availability.
            </p>
        </div>
        
        <div class="section-header">
            <h3>Variants (<?= count($variants) ?>)</h3>
            <a href="/Multi-Vendor-Rental-System/public/vendor/variant-create.php?product_id=<?= htmlspecialchars($productId) ?>" class="btn btn-success">+ Add Variant</a>
        </div>
        
        <?php if (empty($variants)): ?>
            <div class="empty-state">
                <div class="empty-state-icon">🎨</div>
                <h3>No variants yet</h3>
                <p>Create variants to offer different configurations of this product</p>
                <a href="/Multi-Vendor-Rental-System/public/vendor/variant-create.php?product_id=<?= htmlspecialchars($productId) ?>" class="btn btn-success">Add First Variant</a>
            </div>
        <?php else: ?>
            <div class="variants-grid">
                <?php foreach ($variants as $variant): ?>
                    <div class="variant-card">
                        <div class="variant-header">
                            <div class="variant-sku">SKU: <?= htmlspecialchars($variant->getSku()) ?></div>
                        </div>
                        
                        <div class="variant-attributes">
                            <?php 
                            $attributeValues = $variant->getAttributeValues();
                            foreach ($attributeValues as $attrId => $valueId):
                                $attribute = $attributeRepo->findById($attrId);
                                $value = $attributeValueRepo->findById($valueId);
                                if ($attribute && $value):
                            ?>
                                <span class="attribute-tag">
                                    <?= htmlspecialchars($attribute->getName()) ?>: <?= htmlspecialchars($value->getValue()) ?>
                                </span>
                            <?php 
                                endif;
                            endforeach; 
                            ?>
                        </div>
                        
                        <?php if ($variant->getQuantity()): ?>
                            <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1rem;">
                                Quantity: <?= htmlspecialchars($variant->getQuantity()) ?>
                            </p>
                        <?php endif; ?>
                        
                        <div class="variant-actions">
                            <a href="/Multi-Vendor-Rental-System/public/vendor/variant-edit.php?id=<?= htmlspecialchars($variant->getId()) ?>" class="btn btn-primary">Edit</a>
                            <form method="POST" action="/Multi-Vendor-Rental-System/public/vendor/variant-delete.php" style="flex: 1;" onsubmit="return confirm('Are you sure you want to delete this variant?');">
                                <input type="hidden" name="variant_id" value="<?= htmlspecialchars($variant->getId()) ?>">
                                <input type="hidden" name="product_id" value="<?= htmlspecialchars($productId) ?>">
                                <button type="submit" class="btn btn-danger" style="width: 100%;">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
