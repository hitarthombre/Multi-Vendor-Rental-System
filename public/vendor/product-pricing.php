<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Models\Pricing;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\PricingRepository;
use RentalPlatform\Repositories\VariantRepository;
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
$pricingRepo = new PricingRepository();
$variantRepo = new VariantRepository();

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

// Get pricing for this product
$pricingRules = $pricingRepo->findByProduct($productId);

// Get variants
$variants = $variantRepo->findByProduct($productId);

$errors = [];
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_pricing') {
        $variantId = $_POST['variant_id'] ?? null;
        $durationUnit = $_POST['duration_unit'] ?? '';
        $pricePerUnit = floatval($_POST['price_per_unit'] ?? 0);
        $minimumDuration = intval($_POST['minimum_duration'] ?? 1);
        
        // Validation
        if (empty($durationUnit)) {
            $errors[] = 'Duration unit is required';
        }
        
        if ($pricePerUnit <= 0) {
            $errors[] = 'Price must be greater than zero';
        }
        
        if ($minimumDuration < 1) {
            $errors[] = 'Minimum duration must be at least 1';
        }
        
        // If no errors, create pricing
        if (empty($errors)) {
            try {
                $pricing = Pricing::create(
                    $productId,
                    $variantId,
                    $durationUnit,
                    $pricePerUnit,
                    $minimumDuration
                );
                
                $pricingRepo->create($pricing);
                $success = 'Pricing rule added successfully!';
                
                // Refresh pricing rules
                $pricingRules = $pricingRepo->findByProduct($productId);
            } catch (Exception $e) {
                $errors[] = 'Failed to add pricing: ' . $e->getMessage();
            }
        }
    } elseif ($action === 'delete_pricing') {
        $pricingId = $_POST['pricing_id'] ?? '';
        if ($pricingId) {
            try {
                $pricingRepo->delete($pricingId);
                $success = 'Pricing rule deleted successfully!';
                
                // Refresh pricing rules
                $pricingRules = $pricingRepo->findByProduct($productId);
            } catch (Exception $e) {
                $errors[] = 'Failed to delete pricing: ' . $e->getMessage();
            }
        }
    }
}

$pageTitle = 'Pricing Configuration - ' . $product->getName();
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
        
        .btn-danger {
            background: #e74c3c;
            color: white;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }
        
        .btn-danger:hover {
            background: #c0392b;
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
        
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
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
        }
        
        .breadcrumb a {
            color: #3498db;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .card h3 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.3rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            font-size: 0.9rem;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        
        .form-group .help-text {
            font-size: 0.85rem;
            color: #7f8c8d;
            margin-top: 0.25rem;
        }
        
        .pricing-list {
            list-style: none;
        }
        
        .pricing-item {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1rem;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .pricing-info {
            flex: 1;
        }
        
        .pricing-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.25rem;
        }
        
        .pricing-details {
            font-size: 0.85rem;
            color: #7f8c8d;
        }
        
        .pricing-price {
            font-size: 1.5rem;
            font-weight: 700;
            color: #27ae60;
            margin-right: 1rem;
        }
        
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: #95a5a6;
        }
        
        .empty-state-icon {
            font-size: 3rem;
            margin-bottom: 0.5rem;
        }
        
        .info-box {
            background: #e8f4f8;
            border-left: 4px solid #3498db;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 2rem;
        }
        
        .info-box p {
            color: #5d6d7e;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        
        @media (max-width: 768px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
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
            <h2>Pricing Configuration</h2>
            <div class="breadcrumb">
                <a href="/Multi-Vendor-Rental-System/public/vendor/products.php">My Products</a> / 
                <a href="/Multi-Vendor-Rental-System/public/vendor/product-edit.php?id=<?= htmlspecialchars($productId) ?>"><?= htmlspecialchars($product->getName()) ?></a> / 
                Pricing
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Error:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>
        
        <div class="info-box">
            <p>
                💰 <strong>Pricing Rules:</strong> Set different prices based on rental duration (hourly, daily, weekly, monthly). 
                You can also set variant-specific pricing if your product has variants.
            </p>
        </div>
        
        <div class="content-grid">
            <!-- Add Pricing Form -->
            <div class="card">
                <h3>Add Pricing Rule</h3>
                <form method="POST">
                    <input type="hidden" name="action" value="add_pricing">
                    
                    <div class="form-group">
                        <label for="variant_id">Apply To</label>
                        <select id="variant_id" name="variant_id">
                            <option value="">Base Product (All Variants)</option>
                            <?php foreach ($variants as $variant): ?>
                                <option value="<?= htmlspecialchars($variant->getId()) ?>">
                                    Variant: <?= htmlspecialchars($variant->getSku()) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="help-text">Choose if this pricing applies to a specific variant or the base product</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="duration_unit">Duration Unit <span class="required">*</span></label>
                        <select id="duration_unit" name="duration_unit" required>
                            <option value="">-- Select Duration --</option>
                            <option value="Hourly">Hourly</option>
                            <option value="Daily">Daily</option>
                            <option value="Weekly">Weekly</option>
                            <option value="Monthly">Monthly</option>
                        </select>
                        <div class="help-text">How customers will rent this product</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="price_per_unit">Price Per Unit <span class="required">*</span></label>
                        <input 
                            type="number" 
                            id="price_per_unit" 
                            name="price_per_unit" 
                            step="0.01" 
                            min="0.01"
                            placeholder="0.00"
                            required
                        >
                        <div class="help-text">Price for one unit of the selected duration</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="minimum_duration">Minimum Duration</label>
                        <input 
                            type="number" 
                            id="minimum_duration" 
                            name="minimum_duration" 
                            min="1"
                            value="1"
                        >
                        <div class="help-text">Minimum number of units customer must rent</div>
                    </div>
                    
                    <button type="submit" class="btn btn-primary" style="width: 100%;">Add Pricing Rule</button>
                </form>
            </div>
            
            <!-- Current Pricing Rules -->
            <div class="card">
                <h3>Current Pricing Rules (<?= count($pricingRules) ?>)</h3>
                
                <?php if (empty($pricingRules)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">💵</div>
                        <p>No pricing rules yet</p>
                        <p style="font-size: 0.85rem;">Add your first pricing rule to start accepting rentals</p>
                    </div>
                <?php else: ?>
                    <ul class="pricing-list">
                        <?php foreach ($pricingRules as $pricing): ?>
                            <li class="pricing-item">
                                <div class="pricing-info">
                                    <div class="pricing-title">
                                        <?= htmlspecialchars($pricing->getDurationUnit()) ?> Rental
                                        <?php if ($pricing->getVariantId()): ?>
                                            <span style="font-size: 0.85rem; color: #7f8c8d;">
                                                (Variant Specific)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="pricing-details">
                                        Min: <?= htmlspecialchars($pricing->getMinimumDuration()) ?> <?= strtolower($pricing->getDurationUnit()) ?>
                                    </div>
                                </div>
                                <div class="pricing-price">
                                    ₹<?= number_format($pricing->getPricePerUnit(), 2) ?>
                                </div>
                                <form method="POST" onsubmit="return confirm('Delete this pricing rule?');">
                                    <input type="hidden" name="action" value="delete_pricing">
                                    <input type="hidden" name="pricing_id" value="<?= htmlspecialchars($pricing->getId()) ?>">
                                    <button type="submit" class="btn btn-danger">Delete</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
