<?php
require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Models\Pricing;
use RentalPlatform\Repositories\PricingRepository;

$db = Connection::getInstance();
$pricingRepo = new PricingRepository();

// Get all products
$stmt = $db->query('SELECT id, name FROM products LIMIT 20');
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Creating pricing for " . count($products) . " products...\n\n";

foreach ($products as $product) {
    // Create daily pricing
    $dailyPricing = Pricing::create(
        $product['id'],
        null, // No variant
        'daily',
        50.00, // $50 per day
        1 // Minimum 1 day
    );
    $pricingRepo->create($dailyPricing);
    
    // Create weekly pricing (discounted)
    $weeklyPricing = Pricing::create(
        $product['id'],
        null,
        'weekly',
        300.00, // $300 per week (saves $50)
        1 // Minimum 1 week
    );
    $pricingRepo->create($weeklyPricing);
    
    // Create monthly pricing (more discounted)
    $monthlyPricing = Pricing::create(
        $product['id'],
        null,
        'monthly',
        1000.00, // $1000 per month (saves $500)
        1 // Minimum 1 month
    );
    $pricingRepo->create($monthlyPricing);
    
    echo "✓ Created pricing for: {$product['name']}\n";
}

echo "\n✅ Pricing seeding complete!\n";
