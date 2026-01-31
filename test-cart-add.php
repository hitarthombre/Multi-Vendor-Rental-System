<?php
require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;

// Test customer ID
$customerId = '3aaaaeaf-7e48-4498-b7a9-3b33d29d4748'; // jane_smith

// Test product ID
$productId = '2c74f960-527e-4c59-a61e-84d5904ef43d';

$cartService = new CartService();

try {
    echo "Testing cart add...\n";
    echo "Customer ID: $customerId\n";
    echo "Product ID: $productId\n";
    echo "Variant ID: NULL (auto-select)\n\n";
    
    $result = $cartService->addItem(
        $customerId,
        $productId,
        null, // variant_id - should auto-select
        '2026-02-05T10:00:00',
        '2026-02-10T10:00:00',
        1
    );
    
    echo "SUCCESS!\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
