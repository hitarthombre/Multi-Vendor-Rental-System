<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\CategoryRepository;
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

// Get product ID
$productId = $_GET['id'] ?? '';
if (empty($productId)) {
    header('Location: /vendor/products.php');
    exit;
}

// Initialize repositories
$db = Connection::getInstance()->getConnection();
$productRepo = new ProductRepository($db);
$categoryRepo = new CategoryRepository($db);

// Get product
$product = $productRepo->findById($productId);
if (!$product || !$product->belongsToVendor($user['id'])) {
    header('Location: /vendor/products.php');
    exit;
}

$categories = $categoryRepo->findAll();
$errors = [];
$formData = [];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $formData = [
        'name' => trim($_POST['name'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'category_id' => $_POST['category_id'] ?? null,
        'verification_required' => isset($_POST['verification_required']),
        'status' => $_POST['status'] ?? 'Active'
    ];
    
    // Validation
    if (empty($formData['name'])) {
        $errors[] = 'Product name is required';
    }
    
    if (empty($formData['description'])) {
        $errors[] = 'Product description is required';
    }
    
    if (strlen($formData['description']) < 20) {
        $errors[] = 'Product description must be at least 20 characters';
    }
    
    // If no errors, update product
    if (empty($errors)) {
        try {
            $product->setName($formData['name']);
            $product->setDescription($formData['description']);
            $product->setCategoryId($formData['category_id']);
            $product->setVerificationRequired($formData['verification_required']);
            $product->setStatus($formData['status']);
            
            $productRepo->update($product);
            
            header('Location: /vendor/products.php?success=updated');
            exit;
        } catch (Exception $e) {
            $errors[] = 'Failed to update product: ' . $e->getMessage();
        }
    }
} else {
    // Pre-fill form with existing data
    $formData = [
        'name' => $product->getName(),
        'description' => $product->getDescription(),
        'category_id' => $product->getCategoryId(),
        'verification_required' => $product->isVerificationRequired(),
        'status' => $product->getStatus()
    ];
}

$pageTitle = 'Edit Product';
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
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 1rem;
            font-family: inherit;
        }
        
        .form-group textarea {
            min-height: 120px;
            resize: vertical;
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
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
        
        .checkbox-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .checkbox-group input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }
        
        .checkbox-group label {
            margin: 0;
            cursor: pointer;
            font-weight: normal;
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
            <a href="/dashboard.php" class="btn btn-secondary">Dashboard</a>
            <a href="/logout.php" class="btn btn-secondary">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <div class="page-header">
            <h2>Edit Product</h2>
            <div class="breadcrumb">
                <a href="/vendor/products.php">My Products</a> / Edit Product
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
                    <label for="name">Product Name <span class="required">*</span></label>
                    <input 
                        type="text" 
                        id="name" 
                        name="name" 
                        value="<?= htmlspecialchars($formData['name']) ?>"
                        placeholder="e.g., Professional Camera Kit"
                        required
                    >
                    <div class="help-text">Give your product a clear, descriptive name</div>
                </div>
                
                <div class="form-group">
                    <label for="description">Description <span class="required">*</span></label>
                    <textarea 
                        id="description" 
                        name="description" 
                        placeholder="Describe your product in detail..."
                        required
                    ><?= htmlspecialchars($formData['description']) ?></textarea>
                    <div class="help-text">Minimum 20 characters. Include key features and specifications.</div>
                </div>
                
                <div class="form-group">
                    <label for="category_id">Category</label>
                    <select id="category_id" name="category_id">
                        <option value="">-- Select Category --</option>
                        <?php foreach ($categories as $category): ?>
                            <option 
                                value="<?= htmlspecialchars($category->getId()) ?>"
                                <?= $formData['category_id'] === $category->getId() ? 'selected' : '' ?>
                            >
                                <?= htmlspecialchars($category->getName()) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="help-text">Help customers find your product by selecting the right category</div>
                </div>
                
                <div class="form-group">
                    <label for="status">Status</label>
                    <select id="status" name="status">
                        <option value="Active" <?= $formData['status'] === 'Active' ? 'selected' : '' ?>>Active</option>
                        <option value="Inactive" <?= $formData['status'] === 'Inactive' ? 'selected' : '' ?>>Inactive</option>
                    </select>
                    <div class="help-text">Active products are visible to customers</div>
                </div>
                
                <div class="form-group">
                    <div class="checkbox-group">
                        <input 
                            type="checkbox" 
                            id="verification_required" 
                            name="verification_required"
                            <?= $formData['verification_required'] ? 'checked' : '' ?>
                        >
                        <label for="verification_required">Require verification before rental</label>
                    </div>
                    <div class="help-text" style="margin-left: 1.75rem;">
                        Enable this if you want to manually approve each rental request (e.g., for high-value items)
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="/vendor/products.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn btn-primary">Update Product</button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
