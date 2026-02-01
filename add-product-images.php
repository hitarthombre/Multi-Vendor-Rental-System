<?php
/**
 * Add category-specific placeholder images to all products
 */

require_once __DIR__ . '/vendor/autoload.php';

$db = \RentalPlatform\Database\Connection::getInstance();

echo "Adding category-specific images to products...\n";
echo str_repeat("=", 60) . "\n\n";

// Category-specific placeholder images from Unsplash (free to use)
$categoryImages = [
    'Camping' => [
        'https://images.unsplash.com/photo-1504280390367-361c6d9f38f4?w=800', // Tent
        'https://images.unsplash.com/photo-1478131143081-80f7f84ca84d?w=800', // Camping
        'https://images.unsplash.com/photo-1537225228614-56cc3556d7ed?w=800'  // Outdoor
    ],
    'Construction' => [
        'https://images.unsplash.com/photo-1504307651254-35680f356dfd?w=800', // Construction tools
        'https://images.unsplash.com/photo-1581094794329-c8112a89af12?w=800', // Hard hat
        'https://images.unsplash.com/photo-1621905251918-48416bd8575a?w=800'  // Construction site
    ],
    'Electronics' => [
        'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=800', // Electronics
        'https://images.unsplash.com/photo-1550009158-9ebf69173e03?w=800', // Tech devices
        'https://images.unsplash.com/photo-1519389950473-47ba0277781c?w=800'  // Technology
    ],
    'Events' => [
        'https://images.unsplash.com/photo-1511578314322-379afb476865?w=800', // Event setup
        'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?w=800', // Party
        'https://images.unsplash.com/photo-1464366400600-7168b8af9bc3?w=800'  // Event venue
    ],
    'Furniture' => [
        'https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800', // Modern furniture
        'https://images.unsplash.com/photo-1493663284031-b7e3aefcae8e?w=800', // Living room
        'https://images.unsplash.com/photo-1538688525198-9b88f6f53126?w=800'  // Furniture design
    ],
    'Music' => [
        'https://images.unsplash.com/photo-1511379938547-c1f69419868d?w=800', // Music studio
        'https://images.unsplash.com/photo-1514320291840-2e0a9bf2a9ae?w=800', // Musical instruments
        'https://images.unsplash.com/photo-1507838153414-b4b713384a76?w=800'  // Music equipment
    ],
    'Photography' => [
        'https://images.unsplash.com/photo-1502920917128-1aa500764cbd?w=800', // Camera
        'https://images.unsplash.com/photo-1606800052052-a08af7148866?w=800', // Photography gear
        'https://images.unsplash.com/photo-1516035069371-29a1b244cc32?w=800'  // Camera equipment
    ],
    'Sports' => [
        'https://images.unsplash.com/photo-1461896836934-ffe607ba8211?w=800', // Sports equipment
        'https://images.unsplash.com/photo-1517649763962-0c623066013b?w=800', // Fitness
        'https://images.unsplash.com/photo-1571902943202-507ec2618e8f?w=800'  // Sports gear
    ],
    'Tools' => [
        'https://images.unsplash.com/photo-1530124566582-a618bc2615dc?w=800', // Tools
        'https://images.unsplash.com/photo-1572981779307-38b8cabb2407?w=800', // Workshop tools
        'https://images.unsplash.com/photo-1504148455328-c376907d081c?w=800'  // Power tools
    ],
    'Vehicles' => [
        'https://images.unsplash.com/photo-1492144534655-ae79c964c9d7?w=800', // Car
        'https://images.unsplash.com/photo-1449965408869-eaa3f722e40d?w=800', // Vehicle
        'https://images.unsplash.com/photo-1503376780353-7e6692767b70?w=800'  // Automobile
    ]
];

try {
    // Get all products with their category
    $stmt = $db->query("
        SELECT p.id, p.name, p.images, c.name as category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY c.name, p.name
    ");
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $updated = 0;
    $skipped = 0;
    $byCategory = [];
    
    foreach ($products as $product) {
        $categoryName = $product['category_name'] ?? 'Unknown';
        
        // Initialize category counter
        if (!isset($byCategory[$categoryName])) {
            $byCategory[$categoryName] = 0;
        }
        
        // Always update images (force update to fix watch images issue)
        $images = $categoryImages[$categoryName] ?? $categoryImages['Electronics']; // Default to Electronics if category not found
        
        // Update product with category-specific images
        $imagesJson = json_encode($images);
        $updateStmt = $db->prepare("UPDATE products SET images = ? WHERE id = ?");
        $updateStmt->execute([$imagesJson, $product['id']]);
        
        echo "✓ Updated '{$product['name']}' ({$categoryName})\n";
        $updated++;
        $byCategory[$categoryName]++;
    }
    
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Summary by Category:\n";
    foreach ($byCategory as $category => $count) {
        echo sprintf("  %-20s : %d products\n", $category, $count);
    }
    echo "\n" . str_repeat("=", 60) . "\n";
    echo "Total Updated: $updated products\n";
    echo "\n✓ Done! All products now have category-specific images.\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
