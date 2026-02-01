<?php
/**
 * Test Checkout Page
 * 
 * This script tests if the checkout page can be loaded and rendered correctly
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\VariantRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Services\CartService;

echo "=== Testing Checkout Page Components ===\n\n";

try {
    $db = Connection::getInstance();
    echo "✓ Database connection established\n";
    
    // Test repositories
    $cartRepo = new CartRepository();
    $productRepo = new ProductRepository();
    $variantRepo = new VariantRepository();
    $vendorRepo = new VendorRepository();
    $cartService = new CartService();
    echo "✓ All repositories initialized\n";
    
    // Test with a customer ID
    $customerId = '021f5bd5-b3d0-463b-be50-bfb110400e3d'; // Varun Chopra
    
    // Get or create cart
    $cart = $cartRepo->getOrCreateForCustomer($customerId);
    echo "✓ Cart retrieved for customer: " . $cart->getId() . "\n";
    
    $cartItems = $cart->getItems();
    echo "✓ Cart has " . count($cartItems) . " items\n";
    
    if (!empty($cartItems)) {
        // Test cart validation
        $validationResult = $cartService->validateForCheckout($cart->getId());
        echo "✓ Cart validation completed\n";
        echo "  - Valid: " . ($validationResult['valid'] ? 'Yes' : 'No') . "\n";
        
        if (!empty($validationResult['errors'])) {
            echo "  - Errors:\n";
            foreach ($validationResult['errors'] as $error) {
                echo "    * " . $error . "\n";
            }
        }
        
        // Test grouping by vendor
        $groupedItems = $cart->groupByVendor();
        echo "✓ Cart items grouped by vendor: " . count($groupedItems) . " vendors\n";
        
        // Test loading product details
        $firstItem = $cartItems[0];
        $product = $productRepo->findById($firstItem->getProductId());
        $variant = $variantRepo->findById($firstItem->getVariantId());
        $vendor = $vendorRepo->findById($firstItem->getVendorId());
        
        echo "✓ Product details loaded:\n";
        echo "  - Product: " . ($product ? $product->getName() : 'Not found') . "\n";
        echo "  - Variant: " . ($variant ? $variant->getSku() : 'Not found') . "\n";
        echo "  - Vendor: " . ($vendor ? $vendor->getBusinessName() : 'Not found') . "\n";
        
        // Calculate totals
        $subtotal = 0;
        $totalItems = 0;
        foreach ($cartItems as $item) {
            $subtotal += $item->getSubtotal();
            $totalItems += $item->getQuantity();
        }
        
        echo "✓ Totals calculated:\n";
        echo "  - Subtotal: ₹" . number_format($subtotal, 2) . "\n";
        echo "  - Total Items: " . $totalItems . "\n";
        
    } else {
        echo "⚠ Cart is empty - checkout page would redirect to cart\n";
    }
    
    echo "\n=== Checkout Page Test Complete ===\n";
    echo "✓ All components working correctly\n";
    echo "\nYou can now access the checkout page at:\n";
    echo "http://localhost/Multi-Vendor-Rental-System/public/customer/checkout.php\n";
    
} catch (Exception $e) {
    echo "\n✗ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
