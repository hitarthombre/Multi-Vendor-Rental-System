<?php
require_once __DIR__ . '/vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();

echo "Categories and Product Count:\n";
echo str_repeat("=", 60) . "\n\n";

$stmt = $db->query("
    SELECT c.name as category, COUNT(p.id) as product_count 
    FROM categories c 
    LEFT JOIN products p ON c.id = p.category_id 
    GROUP BY c.id, c.name 
    ORDER BY c.name
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo sprintf("%-30s : %d products\n", $row['category'], $row['product_count']);
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Sample products from each category:\n\n";

$stmt = $db->query("
    SELECT c.name as category, p.name as product_name
    FROM categories c 
    INNER JOIN products p ON c.id = p.category_id 
    GROUP BY c.id, c.name
    ORDER BY c.name
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['category']}: {$row['product_name']}\n";
}
