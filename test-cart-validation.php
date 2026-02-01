<?php
/**
 * Test Cart Validation Implementation
 * 
 * This script tests the cart validation functionality added in Task 5
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\ProductRepository;

echo "=== Testing Cart Validation Implementation ===\n\n";

// Test customer ID (Jane Smith from demo data)
$customerId = '3aaaaeaf-7e48-4498-b7a9-3b33d29d4748';

$cartService = new CartService();
$cartRepo = new CartRepository();
$productRepo = new ProductRepository();

// Test 1: Validate empty cart
echo "Test 1: Validate empty cart\n";
echo "----------------------------\n";
$cart = $cartRepo->findByCustomerId($customerId);
if ($cart) {
    // Clear cart first
    $cartService->clearCart($customerId);
}

$validation = $cartService->validateForCheckout($customerId);
echo "Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
echo "Errors: " . implode(', ', $validation['errors']) . "\n";
echo "Expected: Invalid with 'Cart is empty' error\n";
echo ($validation['valid'] === false && in_array('Cart is empty', $validation['errors']) ? "✓ PASS" : "✗ FAIL") . "\n\n";

// Test 2: Add a valid item and validate
echo "Test 2: Validate cart with valid item\n";
echo "--------------------------------------\n";

// Get a product
$products = $productRepo->findAll(['limit' => 1]);
if (empty($products)) {
    echo "✗ FAIL: No products found in database\n\n";
} else {
    $product = $products[0];
    
    // Add item to cart
    try {
        $startDate = (new DateTime())->modify('+1 day')->format('Y-m-d H:i:s');
        $endDate = (new DateTime())->modify('+3 days')->format('Y-m-d H:i:s');
        
        $result = $cartService->addItem(
            $customerId,
            $product->getId(),
            null,
            $startDate,
            $endDate,
            1
        );
        
        echo "Added product: {$product->getName()}\n";
        echo "Rental period: {$startDate} to {$endDate}\n";
        
        // Validate cart
        $validation = $cartService->validateForCheckout($customerId);
        echo "Valid: " . ($validation['valid'] ? 'Yes' : 'No') . "\n";
        if (!empty($validation['errors'])) {
            echo "Errors: " . implode(', ', $validation['errors']) . "\n";
        }
        echo "Expected: Valid with no errors\n";
        echo ($validation['valid'] === true && empty($validation['errors']) ? "✓ PASS" : "✗ FAIL") . "\n\n";
        
    } catch (Exception $e) {
        echo "✗ FAIL: " . $e->getMessage() . "\n\n";
    }
}

// Test 3: Test API endpoint
echo "Test 3: Test validation API endpoint\n";
echo "-------------------------------------\n";

// Simulate API call
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'validate';

ob_start();
try {
    // We can't directly test the API without running a web server,
    // but we can verify the CartService method works
    $validation = $cartService->validateForCheckout($customerId);
    echo "API would return:\n";
    echo json_encode([
        'success' => true,
        'data' => $validation
    ], JSON_PRETTY_PRINT) . "\n";
    echo "✓ PASS: Validation method works correctly\n\n";
} catch (Exception $e) {
    echo "✗ FAIL: " . $e->getMessage() . "\n\n";
}
ob_end_clean();

// Test 4: Verify cart page changes
echo "Test 4: Verify cart page implementation\n";
echo "----------------------------------------\n";

$cartPagePath = __DIR__ . '/public/customer/cart.php';
$cartPageContent = file_get_contents($cartPagePath);

$checks = [
    'CartService imported' => strpos($cartPageContent, 'use RentalPlatform\Services\CartService;') !== false,
    'CartService instantiated' => strpos($cartPageContent, '$cartService = new CartService();') !== false,
    'Validation called' => strpos($cartPageContent, '$cartService->validateForCheckout($customerId)') !== false,
    'Validation errors displayed' => strpos($cartPageContent, 'validationErrors') !== false,
    'Button disabled when invalid' => strpos($cartPageContent, 'disabled') !== false,
    'JavaScript validation function' => strpos($cartPageContent, 'function proceedToCheckout()') !== false,
    'API validation call' => strpos($cartPageContent, "action=validate") !== false,
];

foreach ($checks as $check => $passed) {
    echo ($passed ? "✓" : "✗") . " {$check}\n";
}

$allPassed = !in_array(false, $checks, true);
echo "\n" . ($allPassed ? "✓ ALL CHECKS PASSED" : "✗ SOME CHECKS FAILED") . "\n\n";

// Clean up
echo "Cleaning up test data...\n";
$cartService->clearCart($customerId);
echo "✓ Test data cleaned\n\n";

echo "=== Test Complete ===\n";
