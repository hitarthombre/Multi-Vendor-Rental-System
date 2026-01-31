<?php
/**
 * Fix Products Without Variants
 * 
 * Ensures all products have at least one variant
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Helpers\UUID;

$db = Connection::getInstance();

echo "=== Fixing Products Without Variants ===\n\n";

// Find products without variants
$stmt = $db->query("
    SELECT p.id, p.name 
    FROM products p 
    LEFT JOIN variants v ON p.id = v.product_id 
    WHERE v.id IS NULL
");

$productsWithoutVariants = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($productsWithoutVariants)) {
    echo "✓ All products have variants!\n";
    exit(0);
}

echo "Found " . count($productsWithoutVariants) . " products without variants\n\n";

foreach ($productsWithoutVariants as $product) {
    echo "Creating variant for: {$product['name']}\n";
    
    $variantId = UUID::generate();
    $sku = 'SKU-' . strtoupper(substr(md5($product['id']), 0, 8));
    
    $stmt = $db->prepare("
        INSERT INTO variants (id, product_id, sku, quantity, created_at) 
        VALUES (?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([$variantId, $product['id'], $sku]);
    echo "  ✓ Created variant: {$variantId}\n";
}

echo "\n✓ All products now have variants!\n";
