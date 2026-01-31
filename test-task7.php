<?php

// Test Task 7 implementation
require_once 'src/Database/Connection.php';
require_once 'src/Helpers/UUID.php';
require_once 'src/Models/Product.php';
require_once 'src/Models/Category.php';
require_once 'src/Repositories/ProductRepository.php';
require_once 'src/Repositories/CategoryRepository.php';
require_once 'src/Services/ProductDiscoveryService.php';

use RentalPlatform\Services\ProductDiscoveryService;

echo "=== Task 7: Product Discovery and Search Test ===\n\n";

try {
    $discoveryService = new ProductDiscoveryService();
    
    // Test 1: Basic product listing
    echo "1. Testing basic product listing...\n";
    $result = $discoveryService->getProducts();
    echo "✓ Found {$result['pagination']['total']} products\n";
    echo "✓ Pagination: Page {$result['pagination']['current_page']} of {$result['pagination']['total_pages']}\n\n";
    
    // Test 2: Search functionality
    echo "2. Testing search functionality...\n";
    $searchResult = $discoveryService->searchProducts('laptop');
    echo "✓ Search for 'laptop' found {$searchResult['pagination']['total']} products\n\n";
    
    // Test 3: Category filtering
    echo "3. Testing category filtering...\n";
    $filterOptions = $discoveryService->getFilterOptions();
    echo "✓ Available categories: " . count($filterOptions['categories']) . "\n";
    
    if (!empty($filterOptions['categories'])) {
        $firstCategory = $filterOptions['categories'][0];
        $categoryResult = $discoveryService->getProductsByCategory($firstCategory['id']);
        echo "✓ Category '{$firstCategory['name']}' has {$categoryResult['pagination']['total']} products\n";
    }
    echo "\n";
    
    // Test 4: Product details
    echo "4. Testing product details...\n";
    if (!empty($result['products'])) {
        $firstProduct = $result['products'][0];
        $details = $discoveryService->getProductDetails($firstProduct->getId());
        if ($details) {
            echo "✓ Product details loaded: {$details['name']}\n";
            echo "✓ Availability: {$details['availability']['message']}\n";
        }
    }
    echo "\n";
    
    // Test 5: Featured products
    echo "5. Testing featured products...\n";
    $featured = $discoveryService->getFeaturedProducts(3);
    echo "✓ Found " . count($featured) . " featured products\n\n";
    
    // Test 6: Category hierarchy
    echo "6. Testing category hierarchy...\n";
    $hierarchy = $discoveryService->getCategoryHierarchy();
    echo "✓ Root categories: " . count($hierarchy) . "\n\n";
    
    echo "=== Task 7.1: Product Listing and Filtering - COMPLETED ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}