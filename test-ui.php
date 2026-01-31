<?php

// Simple test to check if the UI pages work
echo "=== Testing Product Discovery UI ===\n\n";

// Test if the products.php page can be loaded
echo "1. Testing products.php page...\n";

// Simulate the products.php page logic
require_once 'src/Database/Connection.php';
require_once 'src/Helpers/UUID.php';
require_once 'src/Models/Product.php';
require_once 'src/Models/Category.php';
require_once 'src/Repositories/ProductRepository.php';
require_once 'src/Repositories/CategoryRepository.php';
require_once 'src/Services/ProductDiscoveryService.php';

use RentalPlatform\Services\ProductDiscoveryService;

try {
    $discoveryService = new ProductDiscoveryService();
    
    // Simulate query parameters
    $filters = [];
    $page = 1;
    $perPage = 12;
    
    $result = $discoveryService->getProducts($filters, $page, $perPage);
    $products = $result['products'];
    $pagination = $result['pagination'];
    
    $filterOptions = $discoveryService->getFilterOptions();
    $categoryHierarchy = $discoveryService->getCategoryHierarchy();
    
    echo "✓ Products page data loaded successfully\n";
    echo "✓ Found " . count($products) . " products to display\n";
    echo "✓ Pagination: Page {$pagination['current_page']} of {$pagination['total_pages']}\n";
    echo "✓ Filter options: " . count($filterOptions['categories']) . " categories\n";
    echo "✓ Category hierarchy: " . count($categoryHierarchy) . " root categories\n\n";
    
    // Test product details page
    if (!empty($products)) {
        echo "2. Testing product details...\n";
        $firstProduct = $products[0];
        $productDetails = $discoveryService->getProductDetails($firstProduct->getId());
        
        if ($productDetails) {
            echo "✓ Product details loaded for: " . $productDetails['name'] . "\n";
            echo "✓ Availability: " . $productDetails['availability']['message'] . "\n";
            
            $relatedProducts = $discoveryService->getRelatedProducts($firstProduct->getId(), 4);
            echo "✓ Found " . count($relatedProducts) . " related products\n\n";
        }
    }
    
    echo "=== UI Components Ready! ===\n";
    echo "You can now access:\n";
    echo "- http://localhost/public/products.php (Product listing page)\n";
    echo "- http://localhost/public/product-details.php?id=<product-id> (Product details)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}