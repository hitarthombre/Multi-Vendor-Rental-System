<?php
require_once __DIR__ . '/vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();

// Check for cart items with NULL product_id or variant_id
$stmt = $db->query("SELECT id, cart_id, product_id, variant_id FROM cart_items WHERE product_id IS NULL OR variant_id IS NULL");
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Cart items with NULL product_id or variant_id:\n";
echo "Count: " . count($items) . "\n\n";

foreach ($items as $item) {
    echo "ID: {$item['id']}\n";
    echo "Cart ID: {$item['cart_id']}\n";
    echo "Product ID: " . ($item['product_id'] ?? 'NULL') . "\n";
    echo "Variant ID: " . ($item['variant_id'] ?? 'NULL') . "\n";
    echo "---\n";
}

// Delete them
if (count($items) > 0) {
    $deleted = $db->exec("DELETE FROM cart_items WHERE product_id IS NULL OR variant_id IS NULL");
    echo "\nDeleted $deleted cart items with NULL values\n";
}

echo "\nDone!\n";
