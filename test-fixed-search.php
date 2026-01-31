<?php

require_once 'src/Database/Connection.php';
require_once 'src/Helpers/UUID.php';
require_once 'src/Models/Product.php';
require_once 'src/Repositories/ProductRepository.php';
require_once 'src/Services/ProductDiscoveryService.php';

use RentalPlatform\Services\ProductDiscoveryService;

echo "=== Testing Fixed Product Discovery ===\n\n";

try {
    $discoveryService = new ProductDiscoveryService();
    
    // Test 1: Basic product listing
    echo "1. Testing basic product listing...\n";
    $result = $discoveryService->getProducts();
    echo "✓ Found {$result['pagination']['total']} products\n\n";
    
    // Test 2: Search functionality
    echo "2. Testing search functionality...\n";
    $searchResult = $discoveryService->searchProducts('test');
    echo "✓ Search for 'test' found {$searchResult['pagination']['total']} products\n\n";
    
    // Test 3: Category filtering
    echo "3. Testing filter options...\n";
    $filterOptions = $discoveryService->getFilterOptions();
    echo "✓ Available categories: " . count($filterOptions['categories']) . "\n";
    
    if (!empty($filterOptions['categories'])) {
        $firstCategory = $filterOptions['categories'][0];
        echo "✓ Testing category filter for: " . $firstCategory['name'] . "\n";
        $categoryResult = $discoveryService->getProductsByCategory($firstCategory['id']);
        echo "✓ Found {$categoryResult['pagination']['total']} products in category\n";
    }
    
    echo "\n=== Product Discovery Service Working! ===\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . " Line: " . $e->getLine() . "\n";
}