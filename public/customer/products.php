<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Services\ProductDiscoveryService;

$discoveryService = new ProductDiscoveryService();

// Get filters from query parameters
$filters = [];
if (!empty($_GET['category'])) {
    $filters['category_id'] = $_GET['category'];
}
if (!empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}
if (isset($_GET['verification'])) {
    $filters['verification_required'] = $_GET['verification'] === '1';
}

// Handle attribute filters
if (!empty($_GET['attr'])) {
    $filters['attributes'] = $_GET['attr'];
}

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 12;

try {
    $result = $discoveryService->getProducts($filters, $page, $perPage);
    $products = $result['products'];
    $pagination = $result['pagination'];
    
    $filterOptions = $discoveryService->getFilterOptions();
    $categoryHierarchy = $discoveryService->getCategoryHierarchy();
} catch (Exception $e) {
    $error = "Error loading products: " . $e->getMessage();
    $products = [];
    $pagination = [];
    $filterOptions = ['categories' => [], 'attributes' => []];
    $categoryHierarchy = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Products - Multi-Vendor Rental Platform</title>
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
        
        .search-bar {
            flex: 1;
            max-width: 400px;
            margin: 0 2rem;
        }
        
        .search-bar input {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
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
        
        .main-content {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 2rem;
        }
        
        .sidebar {
            background: #fff;
            padding: 1.5rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            height: fit-content;
        }
        
        .filter-section {
            margin-bottom: 2rem;
        }
        
        .filter-section h3 {
            margin-bottom: 1rem;
            color: #333;
            font-size: 1.1rem;
        }
        
        .filter-option {
            margin-bottom: 0.5rem;
        }
        
        .filter-option label {
            display: flex;
            align-items: center;
            cursor: pointer;
            padding: 0.25rem 0;
        }
        
        .filter-option input {
            margin-right: 0.5rem;
        }
        
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .product-card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .product-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 16px rgba(0,0,0,0.15);
        }
        
        .product-image {
            width: 100%;
            height: 200px;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #666;
            font-size: 0.9rem;
        }
        
        .product-info {
            padding: 1rem;
        }
        
        .product-name {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #333;
        }
        
        .product-description {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 0.75rem;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
        
        .product-badges {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 0.75rem;
        }
        
        .badge {
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }
        
        .badge-verification {
            background: #fff3cd;
            color: #856404;
        }
        
        .badge-available {
            background: #d4edda;
            color: #155724;
        }
        
        .product-actions {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9rem;
            text-decoration: none;
            text-align: center;
            transition: background-color 0.2s;
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-primary:hover {
            background: #0056b3;
        }
        
        .btn-outline {
            background: transparent;
            color: #007bff;
            border: 1px solid #007bff;
        }
        
        .btn-outline:hover {
            background: #007bff;
            color: white;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin: 2rem 0;
        }
        
        .pagination a, .pagination span {
            padding: 0.5rem 0.75rem;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background: #f8f9fa;
        }
        
        .pagination .current {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }
        
        .results-info {
            margin-bottom: 1rem;
            color: #666;
        }
        
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 1rem;
            border-radius: 4px;
            margin-bottom: 1rem;
        }
        
        @media (max-width: 768px) {
            .main-content {
                grid-template-columns: 1fr;
            }
            
            .header-content {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-bar {
                margin: 0;
                max-width: none;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">RentalHub</div>
                <form class="search-bar" method="GET">
                    <input type="text" name="search" placeholder="Search products..." 
                           value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    <?php foreach ($_GET as $key => $value): ?>
                        <?php if ($key !== 'search'): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" 
                                   value="<?= htmlspecialchars($value) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>
                </form>
                <nav class="nav-links">
                    <a href="../search.php">Search</a>
                    <a href="../wishlist.php">Wishlist</a>
                    <a href="../index.php">Home</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="container">
        <?php if (isset($error)): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="main-content">
            <aside class="sidebar">
                <form method="GET" id="filterForm">
                    <input type="hidden" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>">
                    
                    <!-- Categories Filter -->
                    <div class="filter-section">
                        <h3>Categories</h3>
                        <?php foreach ($filterOptions['categories'] as $category): ?>
                            <div class="filter-option">
                                <label>
                                    <input type="radio" name="category" 
                                           value="<?= htmlspecialchars($category['id']) ?>"
                                           <?= ($_GET['category'] ?? '') === $category['id'] ? 'checked' : '' ?>
                                           onchange="this.form.submit()">
                                    <?= htmlspecialchars($category['name']) ?> 
                                    (<?= $category['product_count'] ?>)
                                </label>
                            </div>
                        <?php endforeach; ?>
                        <?php if (!empty($_GET['category'])): ?>
                            <div class="filter-option">
                                <a href="?<?= http_build_query(array_diff_key($_GET, ['category' => ''])) ?>" 
                                   style="color: #dc3545; font-size: 0.9rem;">Clear category filter</a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Verification Filter -->
                    <div class="filter-section">
                        <h3>Verification</h3>
                        <div class="filter-option">
                            <label>
                                <input type="checkbox" name="verification" value="1"
                                       <?= ($_GET['verification'] ?? '') === '1' ? 'checked' : '' ?>
                                       onchange="this.form.submit()">
                                Requires verification
                            </label>
                        </div>
                    </div>
                </form>
            </aside>

            <main>
                <div class="results-info">
                    <?php if (!empty($products)): ?>
                        Showing <?= count($products) ?> of <?= $pagination['total'] ?> products
                        <?php if (!empty($_GET['search'])): ?>
                            for "<?= htmlspecialchars($_GET['search']) ?>"
                        <?php endif; ?>
                    <?php else: ?>
                        No products found
                        <?php if (!empty($_GET['search'])): ?>
                            for "<?= htmlspecialchars($_GET['search']) ?>"
                        <?php endif; ?>
                    <?php endif; ?>
                </div>

                <div class="product-grid">
                    <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <div class="product-image">
                                <?php if (!empty($product->getImages())): ?>
                                    <img src="<?= htmlspecialchars($product->getImages()[0]) ?>" 
                                         alt="<?= htmlspecialchars($product->getName()) ?>"
                                         style="width: 100%; height: 100%; object-fit: cover;">
                                <?php else: ?>
                                    No image available
                                <?php endif; ?>
                            </div>
                            
                            <div class="product-info">
                                <h3 class="product-name"><?= htmlspecialchars($product->getName()) ?></h3>
                                <p class="product-description"><?= htmlspecialchars($product->getDescription()) ?></p>
                                
                                <div class="product-badges">
                                    <span class="badge badge-available">Available</span>
                                    <?php if ($product->isVerificationRequired()): ?>
                                        <span class="badge badge-verification">Verification Required</span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <a href="product-details.php?id=<?= htmlspecialchars($product->getId()) ?>" 
                                       class="btn btn-primary">View Details</a>
                                    <button class="btn btn-outline" onclick="addToWishlist('<?= htmlspecialchars($product->getId()) ?>')">
                                        ♡ Wishlist
                                    </button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] - 1])) ?>">
                                ← Previous
                            </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $pagination['current_page'] - 2); $i <= min($pagination['total_pages'], $pagination['current_page'] + 2); $i++): ?>
                            <?php if ($i === $pagination['current_page']): ?>
                                <span class="current"><?= $i ?></span>
                            <?php else: ?>
                                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>"><?= $i ?></a>
                            <?php endif; ?>
                        <?php endfor; ?>

                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?= http_build_query(array_merge($_GET, ['page' => $pagination['current_page'] + 1])) ?>">
                                Next →
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script>
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
                    updateWishlistButton(productId, true);
                } else {
                    alert(data.message || 'Failed to add to wishlist');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to add to wishlist');
            });
        }
        
        function updateWishlistButton(productId, inWishlist) {
            const buttons = document.querySelectorAll(`button[onclick="addToWishlist('${productId}')"]`);
            buttons.forEach(button => {
                if (inWishlist) {
                    button.innerHTML = '♥ In Wishlist';
                    button.style.background = '#28a745';
                    button.style.borderColor = '#28a745';
                    button.style.color = 'white';
                } else {
                    button.innerHTML = '♡ Wishlist';
                    button.style.background = 'transparent';
                    button.style.borderColor = '#007bff';
                    button.style.color = '#007bff';
                }
            });
        }
        
        // Check wishlist status for all products on page load
        document.addEventListener('DOMContentLoaded', function() {
            const productCards = document.querySelectorAll('.product-card');
            productCards.forEach(card => {
                const button = card.querySelector('button[onclick*="addToWishlist"]');
                if (button) {
                    const productId = button.getAttribute('onclick').match(/'([^']+)'/)[1];
                    
                    fetch(`../api/wishlist.php?action=check&product_id=${encodeURIComponent(productId)}`)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success && data.in_wishlist) {
                                updateWishlistButton(productId, true);
                            }
                        })
                        .catch(error => console.error('Error checking wishlist:', error));
                }
            });
        });
    </script>
</body>
</html>