<?php
/**
 * Comprehensive Data Seeding Script
 * 
 * Creates realistic dummy data for 3 months:
 * - 10 vendors with 30+ products each
 * - 20+ customers
 * - Orders, payments, invoices, and rentals
 * - Reviews and interactions
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Helpers\UUID;

$db = Connection::getInstance();

echo "=== Comprehensive Data Seeding Started ===\n\n";

// Ask if user wants to clear existing data
echo "WARNING: This will add new data to your database.\n";
echo "Do you want to clear existing demo data first? (yes/no): ";
$handle = fopen ("php://stdin","r");
$line = trim(fgets($handle));
if(strtolower($line) === 'yes' || strtolower($line) === 'y'){
    echo "\nClearing existing data...\n";
    
    // Disable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    
    // Clear tables in reverse order of dependencies
    $tables = ['invoice_line_items', 'invoices', 'inventory_locks', 'order_items', 'orders', 
               'payments', 'rental_periods', 'pricing', 'variants', 'products', 
               'vendors', 'users', 'categories'];
    
    foreach ($tables as $table) {
        try {
            $db->exec("DELETE FROM {$table}");
            echo "  ✓ Cleared {$table}\n";
        } catch (Exception $e) {
            echo "  - Skipped {$table} (may not exist)\n";
        }
    }
    
    // Re-enable foreign key checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "✓ Database cleared\n\n";
}
fclose($handle);

// Helper function to generate random date within last 3 months
function randomDate($startDaysAgo = 90, $endDaysAgo = 0) {
    $start = strtotime("-{$startDaysAgo} days");
    $end = strtotime("-{$endDaysAgo} days");
    $timestamp = mt_rand($start, $end);
    return date('Y-m-d H:i:s', $timestamp);
}

// ============================================
// 1. CREATE CATEGORIES
// ============================================
echo "Creating categories...\n";

$categories = [
    ['Electronics', 'Electronic devices and gadgets'],
    ['Furniture', 'Home and office furniture'],
    ['Tools', 'Power tools and equipment'],
    ['Vehicles', 'Cars, bikes, and transportation'],
    ['Photography', 'Cameras and photography equipment'],
    ['Events', 'Party and event supplies'],
    ['Sports', 'Sports equipment and gear'],
    ['Music', 'Musical instruments and audio equipment'],
    ['Camping', 'Outdoor and camping gear'],
    ['Construction', 'Heavy machinery and construction equipment']
];

$categoryIds = [];
foreach ($categories as $cat) {
    $id = UUID::generate();
    $stmt = $db->prepare("INSERT INTO categories (id, name, description, created_at) VALUES (?, ?, ?, NOW())");
    $stmt->execute([$id, $cat[0], $cat[1]]);
    $categoryIds[$cat[0]] = $id;
}
echo "✓ Created " . count($categories) . " categories\n\n";

// ============================================
// 2. CREATE 20+ CUSTOMERS
// ============================================
echo "Creating customers...\n";

$customerNames = [
    'Rahul Sharma', 'Priya Patel', 'Amit Kumar', 'Sneha Reddy', 'Vikram Singh',
    'Anjali Gupta', 'Rohan Mehta', 'Kavya Iyer', 'Arjun Nair', 'Pooja Desai',
    'Karan Malhotra', 'Divya Krishnan', 'Siddharth Joshi', 'Neha Agarwal', 'Aditya Rao',
    'Riya Kapoor', 'Varun Chopra', 'Ishita Bansal', 'Nikhil Verma', 'Shreya Saxena',
    'Manish Pandey', 'Tanvi Shah', 'Harsh Sinha', 'Meera Kulkarni', 'Gaurav Bhatia'
];

$customerIds = [];
foreach ($customerNames as $index => $name) {
    $userId = UUID::generate();
    $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
    $passwordHash = password_hash('password123', PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (id, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, 'Customer', ?)");
    $createdAt = randomDate(90, 30);
    $stmt->execute([$userId, $name, $email, $passwordHash, $createdAt]);
    
    $customerIds[] = $userId;
}
echo "✓ Created " . count($customerIds) . " customers\n\n";

// ============================================
// 3. CREATE 10 VENDORS WITH PROFILES
// ============================================
echo "Creating vendors...\n";

$vendorData = [
    ['TechRent Pro', 'Premium electronics and gadgets rental', 'Electronics', '#3B82F6'],
    ['Furniture Hub', 'Quality furniture for every occasion', 'Furniture', '#10B981'],
    ['ToolMaster Rentals', 'Professional tools and equipment', 'Tools', '#F59E0B'],
    ['DriveEasy', 'Affordable vehicle rentals', 'Vehicles', '#EF4444'],
    ['PhotoPro Gear', 'Professional photography equipment', 'Photography', '#8B5CF6'],
    ['EventMagic', 'Complete event and party solutions', 'Events', '#EC4899'],
    ['SportZone', 'Sports equipment for all activities', 'Sports', '#06B6D4'],
    ['MusicBox Rentals', 'Musical instruments and audio gear', 'Music', '#F97316'],
    ['CampMasters', 'Outdoor and camping essentials', 'Camping', '#84CC16'],
    ['BuildPro Equipment', 'Heavy machinery and construction tools', 'Construction', '#6366F1']
];

$vendorIds = [];
$vendorCategories = [];

foreach ($vendorData as $index => $vendor) {
    // Create vendor user
    $userId = UUID::generate();
    $vendorId = UUID::generate();
    $email = strtolower(str_replace(' ', '', $vendor[0])) . '@vendor.com';
    $passwordHash = password_hash('vendor123', PASSWORD_BCRYPT);
    
    $stmt = $db->prepare("INSERT INTO users (id, username, email, password_hash, role, created_at) VALUES (?, ?, ?, ?, 'Vendor', ?)");
    $createdAt = randomDate(90, 60);
    $stmt->execute([$userId, $vendor[0], $email, $passwordHash, $createdAt]);
    
    // Create vendor profile
    $stmt = $db->prepare("INSERT INTO vendors (id, user_id, business_name, legal_name, brand_color, status, created_at) VALUES (?, ?, ?, ?, ?, 'Active', ?)");
    $stmt->execute([$vendorId, $userId, $vendor[0], $vendor[0], $vendor[3], $createdAt]);
    
    $vendorIds[] = $vendorId;
    $vendorCategories[$vendorId] = $vendor[2];
}
echo "✓ Created " . count($vendorIds) . " vendors\n\n";

// ============================================
// 4. CREATE 30+ PRODUCTS PER VENDOR
// ============================================
echo "Creating products for each vendor...\n";

$productTemplates = [
    'Electronics' => [
        'Laptop', 'Desktop Computer', 'iPad Pro', 'MacBook Air', 'Gaming Console',
        'Smart TV 55"', 'Projector', 'Wireless Speaker', 'Headphones', 'Camera',
        'Drone', 'VR Headset', 'Smartwatch', 'Tablet', 'Monitor 27"',
        'Keyboard', 'Mouse', 'Webcam', 'Microphone', 'Router',
        'External SSD', 'Power Bank', 'Charging Station', 'Smart Home Hub', 'Security Camera',
        'Printer', 'Scanner', 'Graphics Tablet', 'E-Reader', 'Portable AC',
        'Air Purifier', 'Coffee Maker', 'Blender', 'Vacuum Cleaner', 'Iron'
    ],
    'Furniture' => [
        'Office Chair', 'Standing Desk', 'Conference Table', 'Sofa Set', 'Dining Table',
        'Bookshelf', 'Filing Cabinet', 'Wardrobe', 'Bed Frame', 'Mattress',
        'Coffee Table', 'TV Stand', 'Shoe Rack', 'Study Table', 'Bean Bag',
        'Recliner', 'Bar Stool', 'Lounge Chair', 'Side Table', 'Console Table',
        'Dresser', 'Nightstand', 'Ottoman', 'Bench', 'Cabinet',
        'Desk Lamp', 'Floor Lamp', 'Wall Shelf', 'Coat Rack', 'Mirror',
        'Plant Stand', 'Magazine Rack', 'Folding Chair', 'Folding Table', 'Cushions'
    ],
    'Tools' => [
        'Drill Machine', 'Angle Grinder', 'Circular Saw', 'Jigsaw', 'Sander',
        'Welding Machine', 'Air Compressor', 'Pressure Washer', 'Generator', 'Ladder',
        'Toolbox Set', 'Wrench Set', 'Screwdriver Set', 'Hammer Drill', 'Impact Driver',
        'Chainsaw', 'Leaf Blower', 'Hedge Trimmer', 'Lawn Mower', 'Tile Cutter',
        'Paint Sprayer', 'Heat Gun', 'Multimeter', 'Soldering Iron', 'Pipe Wrench',
        'Bolt Cutter', 'Wire Stripper', 'Level Tool', 'Measuring Tape', 'Stud Finder',
        'Nail Gun', 'Staple Gun', 'Glue Gun', 'Rotary Tool', 'Bench Grinder'
    ],
    'Vehicles' => [
        'Sedan Car', 'SUV', 'Hatchback', 'Luxury Car', 'Sports Car',
        'Electric Scooter', 'Motorcycle', 'Bicycle', 'Mountain Bike', 'Road Bike',
        'Van', 'Pickup Truck', 'Mini Bus', 'Tempo', 'Auto Rickshaw',
        'Electric Car', 'Hybrid Car', 'Convertible', 'Minivan', 'Compact Car',
        'Cargo Van', 'Box Truck', 'Flatbed Truck', 'Tow Truck', 'Ambulance',
        'Food Truck', 'Mobile Office', 'RV', 'Camper Van', 'Trailer',
        'Golf Cart', 'ATV', 'Dirt Bike', 'Cruiser Bike', 'Tandem Bicycle'
    ],
    'Photography' => [
        'DSLR Camera', 'Mirrorless Camera', 'Cinema Camera', 'Action Camera', 'Drone Camera',
        '50mm Lens', '24-70mm Lens', '70-200mm Lens', 'Wide Angle Lens', 'Macro Lens',
        'Tripod', 'Monopod', 'Gimbal', 'Slider', 'Jib Arm',
        'Studio Lights', 'LED Panel', 'Softbox', 'Reflector', 'Backdrop',
        'Light Stand', 'C-Stand', 'Boom Arm', 'Flash', 'Trigger',
        'Memory Card', 'Camera Bag', 'Lens Filter', 'Battery Grip', 'Remote Shutter',
        'Green Screen', 'Teleprompter', 'Monitor', 'Video Recorder', 'Audio Recorder'
    ],
    'Events' => [
        'Tent 20x20', 'Canopy', 'Folding Chairs', 'Banquet Tables', 'Round Tables',
        'Stage Platform', 'Dance Floor', 'Red Carpet', 'Stanchions', 'Rope Barriers',
        'PA System', 'DJ Equipment', 'Karaoke Machine', 'Microphone Set', 'Speakers',
        'Disco Lights', 'LED Uplighting', 'String Lights', 'Fog Machine', 'Bubble Machine',
        'Photo Booth', 'Backdrop Stand', 'Balloon Arch', 'Centerpieces', 'Linens',
        'Chafing Dishes', 'Beverage Dispenser', 'Ice Chest', 'Serving Trays', 'Glassware',
        'Plates Set', 'Cutlery Set', 'Napkins', 'Table Covers', 'Chair Covers'
    ],
    'Sports' => [
        'Treadmill', 'Exercise Bike', 'Elliptical', 'Rowing Machine', 'Weight Bench',
        'Dumbbells Set', 'Barbell Set', 'Kettlebell', 'Resistance Bands', 'Yoga Mat',
        'Tennis Racket', 'Badminton Set', 'Cricket Kit', 'Football', 'Basketball',
        'Volleyball', 'Table Tennis', 'Carrom Board', 'Chess Set', 'Snooker Table',
        'Golf Clubs', 'Golf Bag', 'Skateboard', 'Roller Skates', 'Ice Skates',
        'Hockey Stick', 'Baseball Bat', 'Boxing Gloves', 'Punching Bag', 'Jump Rope',
        'Cycling Helmet', 'Knee Pads', 'Elbow Pads', 'Sports Shoes', 'Gym Bag'
    ],
    'Music' => [
        'Acoustic Guitar', 'Electric Guitar', 'Bass Guitar', 'Keyboard', 'Piano',
        'Drum Kit', 'Tabla Set', 'Harmonium', 'Violin', 'Saxophone',
        'Trumpet', 'Flute', 'Clarinet', 'Ukulele', 'Banjo',
        'Amplifier', 'Mixer', 'Audio Interface', 'Studio Monitor', 'Headphones',
        'Microphone', 'Pop Filter', 'Mic Stand', 'Cable Set', 'Pedal Board',
        'Guitar Effects', 'Drum Machine', 'Synthesizer', 'MIDI Controller', 'Looper',
        'Metronome', 'Tuner', 'Capo', 'Guitar Strap', 'Music Stand'
    ],
    'Camping' => [
        'Camping Tent', 'Sleeping Bag', 'Camping Mat', 'Backpack', 'Hiking Boots',
        'Portable Stove', 'Cooler Box', 'Water Filter', 'Flashlight', 'Lantern',
        'Camping Chair', 'Folding Table', 'Hammock', 'Tarp', 'Rope',
        'First Aid Kit', 'Multi-tool', 'Knife Set', 'Axe', 'Saw',
        'Compass', 'GPS Device', 'Binoculars', 'Telescope', 'Fishing Rod',
        'Kayak', 'Canoe', 'Life Jacket', 'Paddle', 'Dry Bag',
        'Camping Cookware', 'Utensil Set', 'Water Bottle', 'Thermos', 'Portable Grill'
    ],
    'Construction' => [
        'Excavator', 'Bulldozer', 'Backhoe', 'Loader', 'Crane',
        'Forklift', 'Scissor Lift', 'Boom Lift', 'Telehandler', 'Skid Steer',
        'Concrete Mixer', 'Compactor', 'Roller', 'Paver', 'Grader',
        'Dump Truck', 'Concrete Pump', 'Scaffolding', 'Shoring', 'Formwork',
        'Jackhammer', 'Breaker', 'Tamper', 'Vibrator', 'Cutter',
        'Welding Generator', 'Air Compressor', 'Water Pump', 'Dewatering Pump', 'Hoist',
        'Safety Harness', 'Hard Hat', 'Safety Boots', 'Gloves', 'Goggles'
    ]
];

$productIds = [];
$productsByVendor = [];

foreach ($vendorIds as $vendorId) {
    $category = $vendorCategories[$vendorId];
    $products = $productTemplates[$category];
    
    $productsByVendor[$vendorId] = [];
    
    foreach ($products as $index => $productName) {
        $productId = UUID::generate();
        $categoryId = $categoryIds[$category];
        
        // Random pricing between 500 and 50000
        $basePrice = mt_rand(500, 50000);
        $deposit = $basePrice * 0.2; // 20% deposit
        
        $description = "High-quality {$productName} available for rent. Perfect for your needs.";
        $status = mt_rand(1, 10) > 1 ? 'Active' : 'Inactive'; // 90% active
        
        $stmt = $db->prepare("INSERT INTO products (id, vendor_id, category_id, name, description, status, security_deposit, product_type, verification_required, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'Rental', ?, ?)");
        $createdAt = randomDate(85, 30);
        $verificationRequired = mt_rand(1, 10) > 7 ? 1 : 0; // 30% require verification
        $stmt->execute([$productId, $vendorId, $categoryId, $productName, $description, $status, $deposit, $verificationRequired, $createdAt]);
        
        // Create variant
        $variantId = UUID::generate();
        $stmt = $db->prepare("INSERT INTO variants (id, product_id, sku, quantity, created_at) VALUES (?, ?, ?, ?, ?)");
        $sku = 'SKU-' . strtoupper(substr(md5($productId), 0, 8));
        $stock = mt_rand(1, 10);
        $stmt->execute([$variantId, $productId, $sku, $stock, $createdAt]);
        
        // Create pricing for different durations
        $durations = [
            ['Hourly', $basePrice / 24, 1],
            ['Daily', $basePrice, 1],
            ['Daily', $basePrice * 6, 7], // Weekly discount (minimum 7 days)
            ['Daily', $basePrice * 20, 30] // Monthly discount (minimum 30 days)
        ];
        
        foreach ($durations as $duration) {
            $pricingId = UUID::generate();
            $stmt = $db->prepare("INSERT INTO pricing (id, product_id, variant_id, duration_unit, price_per_unit, minimum_duration, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pricingId, $productId, $variantId, $duration[0], $duration[1], $duration[2], $createdAt]);
        }
        
        $productIds[] = $productId;
        $productsByVendor[$vendorId][] = [
            'product_id' => $productId,
            'variant_id' => $variantId,
            'price' => $basePrice,
            'deposit' => $deposit
        ];
    }
}

$totalProducts = count($productIds);
echo "✓ Created {$totalProducts} products across all vendors\n\n";

// ============================================
// 5. CREATE RENTAL PERIODS
// ============================================
echo "Creating rental periods...\n";

$rentalPeriods = [];
for ($i = 0; $i < 200; $i++) {
    $periodId = UUID::generate();
    $startDate = randomDate(90, 10);
    $durationDays = mt_rand(1, 30);
    $endDate = date('Y-m-d H:i:s', strtotime($startDate . ' +' . $durationDays . ' days'));
    
    $stmt = $db->prepare("INSERT INTO rental_periods (id, start_datetime, end_datetime, duration_value, duration_unit) VALUES (?, ?, ?, ?, 'Daily')");
    $stmt->execute([$periodId, $startDate, $endDate, $durationDays]);
    
    $rentalPeriods[] = [
        'id' => $periodId,
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
}
echo "✓ Created " . count($rentalPeriods) . " rental periods\n\n";

// ============================================
// 6. CREATE ORDERS, PAYMENTS, AND INVOICES
// ============================================
echo "Creating orders with payments and invoices...\n";

$orderStatuses = ['Pending_Vendor_Approval', 'Active_Rental', 'Completed', 'Rejected'];
$orderCount = 0;

foreach ($customerIds as $customerId) {
    // Each customer makes 3-8 orders over 3 months
    $numOrders = mt_rand(3, 8);
    
    for ($i = 0; $i < $numOrders; $i++) {
        // Pick random vendor
        $vendorId = $vendorIds[array_rand($vendorIds)];
        $vendorProducts = $productsByVendor[$vendorId];
        
        // Pick 1-3 products from this vendor
        $numItems = mt_rand(1, 3);
        $orderItems = [];
        $totalAmount = 0;
        
        for ($j = 0; $j < $numItems; $j++) {
            $product = $vendorProducts[array_rand($vendorProducts)];
            $quantity = mt_rand(1, 2);
            $unitPrice = $product['price'];
            $itemTotal = $unitPrice * $quantity;
            $totalAmount += $itemTotal + $product['deposit'];
            
            $orderItems[] = [
                'product_id' => $product['product_id'],
                'variant_id' => $product['variant_id'],
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total_price' => $itemTotal,
                'deposit' => $product['deposit']
            ];
        }
        
        // Create payment first
        $paymentId = UUID::generate();
        $razorpayPaymentId = 'pay_' . strtoupper(substr(md5($paymentId), 0, 14));
        $paymentDate = randomDate(90, 5);
        
        $stmt = $db->prepare("INSERT INTO payments (id, customer_id, razorpay_payment_id, amount, currency, status, verified_at, created_at) VALUES (?, ?, ?, ?, 'INR', 'Verified', ?, ?)");
        $stmt->execute([$paymentId, $customerId, $razorpayPaymentId, $totalAmount, $paymentDate, $paymentDate]);
        
        // Create order
        $orderId = UUID::generate();
        $orderNumber = 'ORD-' . date('Ymd', strtotime($paymentDate)) . '-' . str_pad($orderCount + 1, 4, '0', STR_PAD_LEFT);
        $status = $orderStatuses[array_rand($orderStatuses)];
        
        // Adjust status based on date (older orders more likely to be completed)
        $daysAgo = (time() - strtotime($paymentDate)) / 86400;
        if ($daysAgo > 60) {
            $status = mt_rand(1, 10) > 3 ? 'Completed' : 'Active_Rental';
        } elseif ($daysAgo > 30) {
            $status = mt_rand(1, 10) > 5 ? 'Active_Rental' : 'Pending_Vendor_Approval';
        }
        
        $depositAmount = array_sum(array_column($orderItems, 'deposit'));
        $depositStatus = $status === 'Completed' ? 'Released' : 'Held';
        
        $stmt = $db->prepare("INSERT INTO orders (id, order_number, customer_id, vendor_id, payment_id, status, total_amount, deposit_amount, deposit_status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$orderId, $orderNumber, $customerId, $vendorId, $paymentId, $status, $totalAmount, $depositAmount, $depositStatus, $paymentDate, $paymentDate]);
        
        // Create order items
        $rentalPeriod = $rentalPeriods[array_rand($rentalPeriods)];
        foreach ($orderItems as $item) {
            $orderItemId = UUID::generate();
            $stmt = $db->prepare("INSERT INTO order_items (id, order_id, product_id, variant_id, rental_period_id, quantity, unit_price, total_price, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$orderItemId, $orderId, $item['product_id'], $item['variant_id'], $rentalPeriod['id'], $item['quantity'], $item['unit_price'], $item['total_price'], $paymentDate]);
            
            // Create inventory lock for active/completed orders
            if (in_array($status, ['Active_Rental', 'Completed'])) {
                $lockId = UUID::generate();
                $stmt = $db->prepare("INSERT INTO inventory_locks (id, order_id, product_id, variant_id, rental_period_id, locked_at) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$lockId, $orderId, $item['product_id'], $item['variant_id'], $rentalPeriod['id'], $paymentDate]);
            }
        }
        
        // Create invoice
        $invoiceId = UUID::generate();
        $invoiceNumber = 'INV-' . date('Ymd', strtotime($paymentDate)) . '-' . str_pad($orderCount + 1, 4, '0', STR_PAD_LEFT);
        $subtotal = $totalAmount - $depositAmount;
        $taxAmount = $subtotal * 0.18; // 18% GST
        $invoiceTotal = $subtotal + $taxAmount;
        
        $stmt = $db->prepare("INSERT INTO invoices (id, invoice_number, order_id, customer_id, vendor_id, subtotal, tax_amount, total_amount, status, finalized_at, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Finalized', ?, ?)");
        $stmt->execute([$invoiceId, $invoiceNumber, $orderId, $customerId, $vendorId, $subtotal, $taxAmount, $invoiceTotal, $paymentDate, $paymentDate]);
        
        // Create invoice line items
        foreach ($orderItems as $item) {
            $lineItemId = UUID::generate();
            $stmt = $db->prepare("INSERT INTO invoice_line_items (id, invoice_id, item_type, description, quantity, unit_price, tax_rate, tax_amount, total_price, created_at) VALUES (?, ?, 'Rental', ?, ?, ?, 0.18, ?, ?, ?)");
            $itemTax = $item['total_price'] * 0.18;
            $itemTotal = $item['total_price'] + $itemTax;
            $stmt->execute([$lineItemId, $invoiceId, 'Product Rental', $item['quantity'], $item['unit_price'], $itemTax, $itemTotal, $paymentDate]);
        }
        
        $orderCount++;
    }
}

echo "✓ Created {$orderCount} orders with payments and invoices\n\n";

// ============================================
// 7. SUMMARY
// ============================================
echo "\n=== Data Seeding Complete ===\n\n";
echo "Summary:\n";
echo "- Categories: " . count($categories) . "\n";
echo "- Customers: " . count($customerIds) . "\n";
echo "- Vendors: " . count($vendorIds) . "\n";
echo "- Products: {$totalProducts}\n";
echo "- Rental Periods: " . count($rentalPeriods) . "\n";
echo "- Orders: {$orderCount}\n";
echo "- Date Range: Last 3 months\n\n";

echo "Login Credentials:\n";
echo "- Customers: [name]@example.com / password123\n";
echo "- Vendors: [businessname]@vendor.com / vendor123\n";
echo "- Example: rahul.sharma@example.com / password123\n";
echo "- Example: techrentpro@vendor.com / vendor123\n\n";

echo "✓ All done! Your database is now populated with realistic data.\n";
