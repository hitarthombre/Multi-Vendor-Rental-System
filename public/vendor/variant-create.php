<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Models\Variant;
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

// Get all attributes with their values
$attributes = $attributeRepo->findAll();
$attributesWithValues = [];
foreach ($attributes as $attribute) {
    $attributesWithValues[$attribute->getId()] = [
        'attribute' => $attribute,
        'values' => $attributeValueRepo->findByAttribute($attribute->getId())
    ];
}

$errors = [];
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'sku' => trim($_POST['sku'] ?? ''),
        'quantity' => intval($_POST['quantity'] ?? 0),
        'attributes' => $_POST['attributes'] ?? []
    ];
    
    // Validation
    if (empty($formData['sku'])) {
        $errors[] = 'SKU is required';
    }
    
    if ($formData['quantity'] < 0) {
        $errors[] = 'Quantity cannot be negative';
    }
    
    if (empty($formData['attributes'])) {
        $errors[] = 'Please select at least one attribute value';
    }
    
    // If no errors, create variant
    if (empty($errors)) {
        try {
            $variant = Variant::create(
                $productId,
                $formData['attributes'],
                $formData['sku'],
                $formData['quantity']
            );
            
            $variantRepo->create($variant);
            
            header('Location: /Multi-Vendor-Rental-System/public/vendor/product-variants.php?product_id=' . $productId . '&success=created');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to create variant: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Add Variant - ' . $product->getName();
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
        
        .container {
            max-width: 800px;
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
        
        .form-card {
            background: white;
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-group input[type="text"],
        .form-group input[type="number"],
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
        
        .attribute-section {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        
        .attribute-section h4 {
            color: #2c3e50;
            margin-bottom: 1rem;
            font-size: 1.1rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid #e0e0e0;
        }
        
        .form-actions button,
        .form-actions a {
            flex: 1;
            text-align: center;
            padding: 0.75rem;
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
            <h2>Add Variant</h2>
            <div class="breadcrumb">
                <a href="/Multi-Vendor-Rental-System/public/vendor/products.php">My Products</a> / 
                <a href="/Multi-Vendor-Rental-System/public/vendor/product-variants.php?product_id=<?= htmlspecialchars($productId) ?>"><?= htmlspecialchars($product->getName()) ?></a> / 
                Add Variant
            </div>
        </div>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <strong>Please fix the following errors:</strong>
                <ul style="margin-top: 0.5rem; margin-left: 1.5rem;">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="form-card">
            <form method="POST">
                <div class="form-group">
                    <label for="sku">SKU (Stock Keeping Unit) <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="sku" 
                        name="sku" 
                        value="<?= htmlspecialchars($formData['sku'] ?? '') ?>"
                        placeholder="e.g., CAM-CANON-5D-BLK"
                        required
                    >
                    <div class="help-text">Unique identifier for this variant</div>
                </div>
                
                <div class="form-group">
                    <label for="quantity">Quantity Available</label>
                    <input 
                        type="number" 
                        id="quantity" 
                        name="quantity" 
                        value="<?= htmlspecialchars($formData['quantity'] ?? '1') ?>"
                        min="0"
                    >
                    <div class="help-text">Number of units available for rent (optional)</div>
                </div>
                
                <div class="attribute-section">
                    <h4>Variant Attributes <span class="required">*</span></h4>
                    <p style="color: #7f8c8d; font-size: 0.9rem; margin-bottom: 1rem;">
                        Select attribute values that define this variant
                    </p>
                    
                    <?php foreach ($attributesWithValues as $attrId => $data): ?>
                        <div class="form-group">
                            <label for="attr_<?= htmlspecialchars($attrId) ?>">
                                <?= htmlspecialchars($data['attribute']->getName()) ?>
                            </label>
                            <select id="attr_<?= htmlspecialchars($attrId) ?>" name="attributes[<?= htmlspecialchars($attrId) ?>]">
                                <option value="">-- Select <?= htmlspecialchars($data['attribute']->getName()) ?> --</option>
                                <?php foreach ($data['values'] as $value): ?>
                                    <option 
                                        value="<?= htmlspecialchars($value->getId()) ?>"
                                        <?= (isset($formData['attributes'][$attrId]) && $formData['attributes'][$attrId] === $value->getId()) ? 'selected' : '' ?>
                                    >
                                        <?= htmlspecialchars($value->getValue()) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($attributesWithValues)): ?>
                        <p style="color: #e67e22;">
                            ⚠️ No attributes available. Please contact administrator to add product attributes.
                        </p>
                    <?php endif; ?>
                </div>
                
                <div class="form-actions">
                    <a href="/Multi-Vendor-Rental-System/public/vendor/product-variants.php?product_id=<?= htmlspecialchars($productId) ?>" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Create Variant</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
