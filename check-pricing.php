<?php
require_once __DIR__ . '/vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();

// Check total pricing records
$stmt = $db->query("SELECT COUNT(*) as count FROM pricing");
$count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Total pricing records: $count\n\n";

// Check pricing for specific product
$productId = '2c74f960-527e-4c59-a61e-84d5904ef43d';
$stmt = $db->prepare("SELECT * FROM pricing WHERE product_id = ?");
$stmt->execute([$productId]);
$pricing = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Pricing for product $productId:\n";
if (empty($pricing)) {
    echo "NO PRICING FOUND!\n";
} else {
    foreach ($pricing as $p) {
        echo "  - Duration: {$p['duration_value']} {$p['duration_unit']}, Price: {$p['price_per_unit']}\n";
    }
}

// Check variants for this product
$stmt = $db->prepare("SELECT id, sku FROM variants WHERE product_id = ?");
$stmt->execute([$productId]);
$variants = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "\nVariants for product $productId:\n";
if (empty($variants)) {
    echo "NO VARIANTS FOUND!\n";
} else {
    foreach ($variants as $v) {
        echo "  - ID: {$v['id']}, SKU: {$v['sku']}\n";
    }
}
