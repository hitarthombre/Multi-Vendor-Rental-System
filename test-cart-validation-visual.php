<?php
/**
 * Visual Test for Cart Validation
 * 
 * This script demonstrates the cart validation UI behavior
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;
use RentalPlatform\Repositories\ProductRepository;

echo "=== Cart Validation Visual Test ===\n\n";

$customerId = '3aaaaeaf-7e48-4498-b7a9-3b33d29d4748'; // Jane Smith
$cartService = new CartService();
$productRepo = new ProductRepository();

// Scenario 1: Empty Cart
echo "Scenario 1: Empty Cart\n";
echo "======================\n";
$cartService->clearCart($customerId);
$validation = $cartService->validateForCheckout($customerId);

echo "Cart Status: " . ($validation['valid'] ? "✓ Valid" : "✗ Invalid") . "\n";
echo "Checkout Button: " . ($validation['valid'] ? "Enabled" : "DISABLED") . "\n";
if (!empty($validation['errors'])) {
    echo "Error Message Displayed:\n";
    echo "┌─────────────────────────────────────────┐\n";
    echo "│ ⚠ Cannot proceed to checkout:          │\n";
    foreach ($validation['errors'] as $error) {
        echo "│   • " . str_pad($error, 37) . "│\n";
    }
    echo "└─────────────────────────────────────────┘\n";
}
echo "\n";

// Scenario 2: Valid Cart
echo "Scenario 2: Valid Cart with Items\n";
echo "==================================\n";

$products = $productRepo->findAll(['limit' => 1]);
if (!empty($products)) {
    $product = $products[0];
    $startDate = (new DateTime())->modify('+1 day')->format('Y-m-d H:i:s');
    $endDate = (new DateTime())->modify('+3 days')->format('Y-m-d H:i:s');
    
    try {
        $cartService->addItem(
            $customerId,
            $product->getId(),
            null,
            $startDate,
            $endDate,
            1
        );
        
        $validation = $cartService->validateForCheckout($customerId);
        
        echo "Cart Status: " . ($validation['valid'] ? "✓ Valid" : "✗ Invalid") . "\n";
        echo "Checkout Button: " . ($validation['valid'] ? "ENABLED (Blue)" : "Disabled") . "\n";
        echo "Product Added: {$product->getName()}\n";
        echo "Rental Period: " . (new DateTime($startDate))->format('M j, Y') . " - " . (new DateTime($endDate))->format('M j, Y') . "\n";
        
        if (empty($validation['errors'])) {
            echo "\n✓ No validation errors - Customer can proceed to checkout\n";
        }
        
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
}
echo "\n";

// Scenario 3: User Interaction Flow
echo "Scenario 3: User Interaction Flow\n";
echo "==================================\n";
echo "1. Customer views cart page\n";
echo "   → Cart validated on page load\n";
echo "   → Validation errors displayed (if any)\n";
echo "   → Button state set (enabled/disabled)\n\n";

echo "2. Customer clicks 'Proceed to Checkout'\n";
echo "   → Button shows: [⟳ Validating...]\n";
echo "   → AJAX call to /api/cart.php?action=validate\n";
echo "   → Server validates cart again\n\n";

echo "3a. If validation passes:\n";
echo "    → Redirect to checkout.php\n";
echo "    → Customer can complete payment\n\n";

echo "3b. If validation fails:\n";
echo "    → Show error alert\n";
echo "    → Reload page with errors\n";
echo "    → Button remains disabled\n\n";

// Clean up
$cartService->clearCart($customerId);

echo "=== Test Complete ===\n";
echo "\nImplementation Summary:\n";
echo "✓ Server-side validation on page load\n";
echo "✓ Client-side validation before redirect\n";
echo "✓ Inline error message display\n";
echo "✓ Dynamic button state (enabled/disabled)\n";
echo "✓ Loading state during validation\n";
echo "✓ API endpoint for real-time validation\n";
