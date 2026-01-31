<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Services\ProductDiscoveryService;

$productId = $_GET['id'] ?? '';

if (empty($productId)) {
    header('Location: products.php');
    exit;
}

$discoveryService = new ProductDiscoveryService();

try {
    $product = $discoveryService->getProductDetails($productId);
    
    if (!$product) {
        $error = "Product not found or not available.";
    } else {
        $relatedProducts = $discoveryService->getRelatedProducts($productId, 4);
    }
} catch (Exception $e) {
    $error = "Error loading product: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($product) ? htmlspecialchars($product['name']) : 'Product Not Found' ?> - Multi-Vendor Rental Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f8f9fa;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        header {
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            padding: 1rem 0;
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 1.5rem;
            font-weight: bold;
            color: #007bff;
        }
        
        .nav-links {
            display: flex;
            gap: 1rem;
        }
        
        .nav-links a {
            text-decoration: none;
            color: #333;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .nav-links a:hover {
            background: #f8f9fa;
        }
        
        .breadcrumb {
            margin-bottom: 2rem;
        }
        
        .breadcrumb a {
            color: #007bff;
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .product-detail {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            margin-bottom: 2rem;
        }
        
        .product-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            padding: 2rem;
        }
        
        .product-images {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .main-image {
            width: 100%;
            height: 400px;
            background: #f0f0f0;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
        }
        
        .main-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .product-info h1 {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: #333;
        }
        
        .product-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }
        
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 4px;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .badge-verification {
            background: #fff3cd;
            color: #856404;
        }
        
        .product-description {
            margin-bottom: 2rem;
            line-height: 1.7;
        }
        
        .product-actions {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 1rem;
            text-decoration: none;
            text-align: center;
            transition: all 0.2s;
            font-weight: 500;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
            transform: translateY(-1px);
        }
        
        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 2px solid #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
        
        .product-meta {
            border-top: 1px solid #eee;
            padding: 2rem;
            background: #f8f9fa;
        }
        
        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }
        
        .meta-item {
            display: flex;
            flex-direction: column;
        }
        
        .meta-label {
            font-weight: 600;
            color: #666;
            font-size: 0.875rem;
            margin-bottom: 0.25rem;
        }
        
        .meta-value {
            color: #333;
        }
        
        .related-products {
            margin-top: 3rem;
        }
        
        .related-products h2 {
            margin-bottom: 1.5rem;
            color: #333;
        }
        
        .related-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
        }
        
        .related-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s;
        }
        
        .related-card:hover {
            transform: translateY(-2px);
        }
        
        .related-image {
            width: 100%;
            height: 150px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.875rem;
        }
        
        .related-info {
            padding: 1rem;
        }
        
        .related-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .related-description {
            color: #666;
            font-size: 0.875rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 2rem;
            border-radius: 8px;
            text-align: center;
            margin: 2rem 0;
        }
        
        @media (max-width: 768px) {
            .product-header {
                grid-template-columns: 1fr;
            }
            
            .product-actions {
                flex-direction: column;
            }
            
            .meta-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">RentalHub</div>
                <nav class="nav-links">
                    <a href="products.php">Browse Products</a>
                    <a href="../wishlist.php">Wishlist</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <div class="breadcrumb">
            <a href="products.php">Products</a>
            <?php if (isset($product) && $product['category']): ?>
                > <a href="products.php?category=<?= htmlspecialchars($product['category']['id']) ?>">
                    <?= htmlspecialchars($product['category']['name']) ?>
                </a>
            <?php endif; ?>
            <?php if (isset($product)): ?>
                > <?= htmlspecialchars($product['name']) ?>
            <?php endif; ?>
        </div>

        <?php if (isset($error)): ?>
            <div class="error">
                <h2>Product Not Available</h2>
                <p><?= htmlspecialchars($error) ?></p>
                <a href="products.php" class="btn btn-primary" style="margin-top: 1rem;">Browse Other Products</a>
            </div>
        <?php else: ?>
            <div class="product-detail">
                <div class="product-header">
                    <div class="product-images">
                        <div class="main-image">
                            <?php if (!empty($product['images'])): ?>
                                <img src="<?= htmlspecialchars($product['images'][0]) ?>" 
                                     alt="<?= htmlspecialchars($product['name']) ?>" id="mainImage">
                            <?php else: ?>
                                No image available
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <div class="product-info">
                        <h1><?= htmlspecialchars($product['name']) ?></h1>
                        
                        <div class="product-badges">
                            <span class="badge badge-available"><?= htmlspecialchars($product['availability']['message']) ?></span>
                            <?php if ($product['verification_required']): ?>
                                <span class="badge badge-verification">Verification Required</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-description">
                            <?= nl2br(htmlspecialchars($product['description'])) ?>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn btn-primary" onclick="rentProduct('<?= htmlspecialchars($product['id']) ?>')">
                                Rent This Item
                            </button>
                            <button class="btn btn-outline" onclick="addToWishlist('<?= htmlspecialchars($product['id']) ?>')" id="wishlistBtn">
                                ♡ Add to Wishlist
                            </button>
                        </div>
                    </div>
                </div>
                
                <div class="product-meta">
                    <div class="meta-grid">
                        <div class="meta-item">
                            <span class="meta-label">Product ID</span>
                            <span class="meta-value"><?= htmlspecialchars($product['id']) ?></span>
                        </div>
                        <?php if ($product['category']): ?>
                            <div class="meta-item">
                                <span class="meta-label">Category</span>
                                <span class="meta-value"><?= htmlspecialchars($product['category']['name']) ?></span>
                            </div>
                        <?php endif; ?>
                        <div class="meta-item">
                            <span class="meta-label">Status</span>
                            <span class="meta-value"><?= htmlspecialchars($product['status']) ?></span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Verification</span>
                            <span class="meta-value">
                                <?= $product['verification_required'] ? 'Required' : 'Not Required' ?>
                            </span>
                        </div>
                        <div class="meta-item">
                            <span class="meta-label">Added</span>
                            <span class="meta-value"><?= date('M j, Y', strtotime($product['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($relatedProducts)): ?>
                <div class="related-products">
                    <h2>Related Products</h2>
                    <div class="related-grid">
                        <?php foreach ($relatedProducts as $related): ?>
                            <a href="product-details.php?id=<?= htmlspecialchars($related->getId()) ?>" class="related-card">
                                <div class="related-image">
                                    <?php if (!empty($related->getImages())): ?>
                                        <img src="<?= htmlspecialchars($related->getImages()[0]) ?>" 
                                             alt="<?= htmlspecialchars($related->getName()) ?>"
                                             style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        No image available
                                    <?php endif; ?>
                                </div>
                                <div class="related-info">
                                    <div class="related-name"><?= htmlspecialchars($related->getName()) ?></div>
                                    <div class="related-description"><?= htmlspecialchars($related->getDescription()) ?></div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>

    <script>
        function rentProduct(productId) {
            alert('Rental functionality will be implemented in later tasks. Product ID: ' + productId);
        }
        
        function addToWishlist(productId) {
            fetch('../api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=add&product_id=' + encodeURIComponent(productId)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    updateWishlistButton(true);
                } else {
                    alert(data.message || 'Failed to add to wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to wishlist');
            });
        }
        
        function updateWishlistButton(inWishlist) {
            const button = document.getElementById('wishlistBtn');
            if (inWishlist) {
                button.innerHTML = '♥ In Wishlist';
                button.style.background = '#28a745';
                button.style.borderColor = '#28a745';
                button.style.color = 'white';
            } else {
                button.innerHTML = '♡ Add to Wishlist';
                button.style.background = 'transparent';
                button.style.borderColor = '#007bff';
                button.style.color = '#007bff';
            }
        }
        
        // Check wishlist status on page load
        document.addEventListener('DOMContentLoaded', function() {
            const productId = '<?= htmlspecialchars($product['id'] ?? '') ?>';
            if (productId) {
                fetch(`../api/wishlist.php?action=check&product_id=${encodeURIComponent(productId)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.in_wishlist) {
                            updateWishlistButton(true);
                        }
                    })
                    .catch(error => console.error('Error checking wishlist:', error));
            }
        });
    </script>
</body>
</html>