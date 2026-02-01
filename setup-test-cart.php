<?php
/**
 * Setup test cart for payment testing
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;

$customerId = '021f5bd5-b3d0-463b-be50-bfb110400e3d'; // Varun Chopra
$cartService = new CartService();

echo "Setting up test cart for customer: $customerId\n\n";

// Clear existing cart
$cartService->clearCart($customerId);
echo "✅ Cleared existing cart\n";

// Get some products
$db = \RentalPlatform\Database\Connection::getInstance();
$stmt = $db->query("SELECT p.id, p.name, v.id as variant_id FROM products p JOIN variants v ON p.id = v.product_id WHERE p.status = 'Active' LIMIT 2");
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($products)) {
    echo "❌ No products found\n";
    exit(1);
}

// Add products to cart
$startDate = date('Y-m-d H:i:s', strtotime('+1 day'));
$endDate = date('Y-m-d H:i:s', strtotime('+4 days'));

foreach ($products as $product) {
    try {
        $result = $cartService->addItem(
            $customerId,
            $product['id'],
            $product['variant_id'],
            $startDate,
            $endDate,
            1
        );
        echo "✅ Added: {$product['name']}\n";
    } catch (Exception $e) {
        echo "❌ Failed to add {$product['name']}: " . $e->getMessage() . "\n";
    }
}

echo "\n";
$cartContents = $cartService->getCartContents($customerId);
echo "Cart Summary:\n";
echo "  Items: " . count($cartContents['items']) . "\n";
echo "  Total: ₹" . number_format($cartContents['summary']['total_amount'], 2) . "\n";
echo "\n✅ Test cart ready!\n";
