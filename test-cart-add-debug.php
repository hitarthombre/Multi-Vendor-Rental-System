<?php
require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;
use RentalPlatform\Models\RentalPeriod;
use RentalPlatform\Repositories\PricingRepository;

// Test customer ID
$customerId = '3aaaaeaf-7e48-4498-b7a9-3b33d29d4748'; // jane_smith

// Test product ID
$productId = '2c74f960-527e-4c59-a61e-84d5904ef43d';

// Get variant
$db = \RentalPlatform\Database\Connection::getInstance();
$stmt = $db->prepare("SELECT id FROM variants WHERE product_id = ? LIMIT 1");
$stmt->execute([$productId]);
$variant = $stmt->fetch(PDO::FETCH_ASSOC);
$variantId = $variant ? $variant['id'] : null;

echo "Product ID: $productId\n";
echo "Variant ID: " . ($variantId ?? 'NULL') . "\n\n";

// Create rental period
$rentalPeriod = RentalPeriod::createFromStrings(
    '2026-02-05T10:00:00',
    '2026-02-10T10:00:00'
);

echo "Rental Period:\n";
echo "  Duration Value: " . $rentalPeriod->getDurationValue() . "\n";
echo "  Duration Unit: " . $rentalPeriod->getDurationUnit() . "\n\n";

// Check pricing
$pricingRepo = new PricingRepository();
$pricing = $pricingRepo->findByProductAndVariant($productId, $variantId);

echo "Pricing found: " . count($pricing) . "\n";
foreach ($pricing as $p) {
    echo "  - Unit: " . $p->getDurationUnit() . ", Price: " . $p->getPricePerUnit() . ", Min: " . $p->getMinimumDuration() . "\n";
}

// Now try to add to cart
echo "\n\nTrying to add to cart...\n";
$cartService = new CartService();

try {
    $result = $cartService->addItem(
        $customerId,
        $productId,
        $variantId,
        '2026-02-05T10:00:00',
        '2026-02-10T10:00:00',
        1
    );
    
    echo "SUCCESS!\n";
    print_r($result);
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
