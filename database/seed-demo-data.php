<?php
/**
 * Demo Data Seeder
 * 
 * Creates demo vendors, products, and users for testing
 * Run this file from command line: php database/seed-demo-data.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Models\User;
use RentalPlatform\Models\Vendor;
use RentalPlatform\Models\Product;
use RentalPlatform\Models\Category;
use RentalPlatform\Repositories\UserRepository;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\CategoryRepository;

echo "🌱 Starting demo data seeding...\n\n";

try {
    $db = Connection::getInstance();
    $userRepo = new UserRepository();
    $vendorRepo = new VendorRepository();
    $productRepo = new ProductRepository();
    $categoryRepo = new CategoryRepository();
    
    // Store credentials for output
    $credentials = [
        'vendors' => [],
        'customers' => []
    ];
    
    // Create categories first
    echo "📁 Creating categories...\n";
    $categories = [
        'Real Estate' => null,
        'Electronics' => null,
        'Vehicles' => null,
        'Furniture' => null,
        'Computers' => null
    ];
    
    foreach ($categories as $categoryName => $parentId) {
        $category = Category::create($categoryName, '', $parentId);
        $categoryRepo->create($category);
        $categories[$categoryName] = $category->getId();
        echo "  ✓ Created category: {$categoryName}\n";
    }
    
    echo "\n";
    
    // ==================== VENDOR 1: House Rentals ====================
    echo "🏠 Creating House Rentals vendor...\n";
    
    $user1 = User::create('houserentals', 'house@rentals.com', 'password123', User::ROLE_VENDOR);
    $userRepo->create($user1);
    
    $vendor1 = Vendor::create(
        $user1->getId(),
        'Premium House Rentals',
        'Premium House Rentals Pvt Ltd',
        'GST29AABCP1234H1Z5',
        'contact@houserentals.com',
        '+91-9876543210'
    );
    $vendorRepo->create($vendor1);
    
    $credentials['vendors'][] = [
        'business' => 'Premium House Rentals',
        'username' => 'houserentals',
        'email' => 'house@rentals.com',
        'password' => 'password123'
    ];
    
    // House products
    $houseProducts = [
        [
            'name' => '3BHK Luxury Apartment in Downtown',
            'description' => 'Spacious 3BHK apartment with modern amenities, fully furnished with AC, modular kitchen, and parking. Located in prime downtown area with easy access to shopping malls, schools, and hospitals.',
            'images' => ['https://images.unsplash.com/photo-1522708323590-d24dbb6b0267?w=800', 'https://images.unsplash.com/photo-1502672260266-1c1ef2d93688?w=800']
        ],
        [
            'name' => '2BHK Cozy Villa with Garden',
            'description' => 'Beautiful 2BHK independent villa with private garden, parking space, and 24/7 security. Perfect for small families looking for peaceful living.',
            'images' => ['https://images.unsplash.com/photo-1564013799919-ab600027ffc6?w=800', 'https://images.unsplash.com/photo-1600596542815-ffad4c1539a9?w=800']
        ],
        [
            'name' => 'Studio Apartment Near IT Park',
            'description' => 'Modern studio apartment ideal for working professionals. Fully furnished with high-speed internet, gym access, and 24/7 power backup.',
            'images' => ['https://images.unsplash.com/photo-1522771739844-6a9f6d5f14af?w=800']
        ]
    ];
    
    foreach ($houseProducts as $productData) {
        $product = Product::create(
            $vendor1->getId(),
            $productData['name'],
            $productData['description'],
            $categories['Real Estate'],
            $productData['images']
        );
        $productRepo->create($product);
        echo "  ✓ Created product: {$productData['name']}\n";
    }
    
    echo "\n";
    
    // ==================== VENDOR 2: Music Systems ====================
    echo "🎵 Creating Music Systems vendor...\n";
    
    $user2 = User::create('soundwave', 'sound@wave.com', 'password123', User::ROLE_VENDOR);
    $userRepo->create($user2);
    
    $vendor2 = Vendor::create(
        $user2->getId(),
        'SoundWave Audio Rentals',
        'SoundWave Audio Solutions LLP',
        'GST27AABCS5678K1Z9',
        'info@soundwave.com',
        '+91-9876543211'
    );
    $vendorRepo->create($vendor2);
    
    $credentials['vendors'][] = [
        'business' => 'SoundWave Audio Rentals',
        'username' => 'soundwave',
        'email' => 'sound@wave.com',
        'password' => 'password123'
    ];
    
    // Music system products
    $musicProducts = [
        [
            'name' => 'Professional DJ Sound System',
            'description' => 'Complete DJ setup with 2000W speakers, mixer, turntables, and lighting. Perfect for parties, weddings, and events. Includes technician support.',
            'images' => ['https://images.unsplash.com/photo-1598488035139-bdbb2231ce04?w=800', 'https://images.unsplash.com/photo-1519508234439-4f23643125c1?w=800']
        ],
        [
            'name' => 'Home Theater 5.1 Surround System',
            'description' => 'Premium 5.1 channel home theater system with Dolby Atmos support. Includes soundbar, subwoofer, and satellite speakers. Easy setup.',
            'images' => ['https://images.unsplash.com/photo-1558618666-fcd25c85cd64?w=800']
        ],
        [
            'name' => 'Portable Bluetooth Party Speaker',
            'description' => 'High-power portable speaker with RGB lights, wireless mic, and 12-hour battery life. Perfect for outdoor parties and gatherings.',
            'images' => ['https://images.unsplash.com/photo-1608043152269-423dbba4e7e1?w=800']
        ],
        [
            'name' => 'Studio Monitor Speakers Pair',
            'description' => 'Professional studio-grade monitor speakers for music production and recording. Flat frequency response and accurate sound reproduction.',
            'images' => ['https://images.unsplash.com/photo-1545454675-3531b543be5d?w=800']
        ]
    ];
    
    foreach ($musicProducts as $productData) {
        $product = Product::create(
            $vendor2->getId(),
            $productData['name'],
            $productData['description'],
            $categories['Electronics'],
            $productData['images']
        );
        $productRepo->create($product);
        echo "  ✓ Created product: {$productData['name']}\n";
    }
    
    echo "\n";
    
    // ==================== VENDOR 3: Vehicles ====================
    echo "🚗 Creating Vehicle Rentals vendor...\n";
    
    $user3 = User::create('driveaway', 'drive@away.com', 'password123', User::ROLE_VENDOR);
    $userRepo->create($user3);
    
    $vendor3 = Vendor::create(
        $user3->getId(),
        'DriveAway Car Rentals',
        'DriveAway Mobility Services Pvt Ltd',
        'GST29AABCD9012M1Z3',
        'bookings@driveaway.com',
        '+91-9876543212'
    );
    $vendorRepo->create($vendor3);
    
    $credentials['vendors'][] = [
        'business' => 'DriveAway Car Rentals',
        'username' => 'driveaway',
        'email' => 'drive@away.com',
        'password' => 'password123'
    ];
    
    // Vehicle products
    $vehicleProducts = [
        [
            'name' => 'Honda City Sedan - Automatic',
            'description' => 'Comfortable sedan with automatic transmission, perfect for city drives and long trips. Includes GPS, music system, and full insurance coverage.',
            'images' => ['https://images.unsplash.com/photo-1549317661-bd32c8ce0db2?w=800', 'https://images.unsplash.com/photo-1552519507-da3b142c6e3d?w=800']
        ],
        [
            'name' => 'Mahindra Thar 4x4 SUV',
            'description' => 'Rugged 4x4 SUV perfect for adventure trips and off-road driving. Powerful diesel engine with all-terrain capabilities.',
            'images' => ['https://images.unsplash.com/photo-1519641471654-76ce0107ad1b?w=800']
        ],
        [
            'name' => 'Toyota Innova Crysta - 7 Seater',
            'description' => 'Spacious 7-seater MPV ideal for family trips and group travel. Comfortable seating, ample luggage space, and excellent fuel efficiency.',
            'images' => ['https://images.unsplash.com/photo-1464219789935-c2d9d9aba644?w=800']
        ],
        [
            'name' => 'Royal Enfield Classic 350',
            'description' => 'Iconic motorcycle perfect for weekend rides and touring. Well-maintained with all safety gear included.',
            'images' => ['https://images.unsplash.com/photo-1558981806-ec527fa84c39?w=800']
        ],
        [
            'name' => 'Luxury Mercedes E-Class',
            'description' => 'Premium luxury sedan with chauffeur service available. Perfect for business meetings, weddings, and special occasions.',
            'images' => ['https://images.unsplash.com/photo-1618843479313-40f8afb4b4d8?w=800']
        ]
    ];
    
    foreach ($vehicleProducts as $productData) {
        $product = Product::create(
            $vendor3->getId(),
            $productData['name'],
            $productData['description'],
            $categories['Vehicles'],
            $productData['images']
        );
        $productRepo->create($product);
        echo "  ✓ Created product: {$productData['name']}\n";
    }
    
    echo "\n";
    
    // ==================== VENDOR 4: Furniture ====================
    echo "🛋️ Creating Furniture Rentals vendor...\n";
    
    $user4 = User::create('furnishpro', 'furnish@pro.com', 'password123', User::ROLE_VENDOR);
    $userRepo->create($user4);
    
    $vendor4 = Vendor::create(
        $user4->getId(),
        'FurnishPro Rentals',
        'FurnishPro Home Solutions Pvt Ltd',
        'GST27AABCF3456N1Z7',
        'hello@furnishpro.com',
        '+91-9876543213'
    );
    $vendorRepo->create($vendor4);
    
    $credentials['vendors'][] = [
        'business' => 'FurnishPro Rentals',
        'username' => 'furnishpro',
        'email' => 'furnish@pro.com',
        'password' => 'password123'
    ];
    
    // Furniture products
    $furnitureProducts = [
        [
            'name' => 'Modern L-Shape Sofa Set',
            'description' => 'Elegant L-shaped sofa with premium fabric upholstery. Seats 6-7 people comfortably. Includes matching cushions and coffee table.',
            'images' => ['https://images.unsplash.com/photo-1555041469-a586c61ea9bc?w=800', 'https://images.unsplash.com/photo-1586023492125-27b2c045efd7?w=800']
        ],
        [
            'name' => 'King Size Bed with Mattress',
            'description' => 'Luxurious king-size bed with orthopedic mattress, side tables, and wardrobe. Premium wood finish with modern design.',
            'images' => ['https://images.unsplash.com/photo-1505693416388-ac5ce068fe85?w=800']
        ],
        [
            'name' => '6-Seater Dining Table Set',
            'description' => 'Beautiful wooden dining table with 6 cushioned chairs. Perfect for family dinners and gatherings. Easy to maintain.',
            'images' => ['https://images.unsplash.com/photo-1617806118233-18e1de247200?w=800']
        ],
        [
            'name' => 'Executive Office Desk Setup',
            'description' => 'Complete office setup with ergonomic desk, executive chair, filing cabinet, and bookshelf. Ideal for home office or startup.',
            'images' => ['https://images.unsplash.com/photo-1518455027359-f3f8164ba6bd?w=800']
        ],
        [
            'name' => 'Modular Wardrobe 3-Door',
            'description' => 'Spacious 3-door wardrobe with mirror, shelves, and hanging space. Modern design with soft-close mechanism.',
            'images' => ['https://images.unsplash.com/photo-1595428774223-ef52624120d2?w=800']
        ]
    ];
    
    foreach ($furnitureProducts as $productData) {
        $product = Product::create(
            $vendor4->getId(),
            $productData['name'],
            $productData['description'],
            $categories['Furniture'],
            $productData['images']
        );
        $productRepo->create($product);
        echo "  ✓ Created product: {$productData['name']}\n";
    }
    
    echo "\n";
    
    // ==================== VENDOR 5: Computers ====================
    echo "💻 Creating Computer Rentals vendor...\n";
    
    $user5 = User::create('techrent', 'tech@rent.com', 'password123', User::ROLE_VENDOR);
    $userRepo->create($user5);
    
    $vendor5 = Vendor::create(
        $user5->getId(),
        'TechRent Computer Solutions',
        'TechRent IT Services Pvt Ltd',
        'GST29AABCT7890P1Z1',
        'support@techrent.com',
        '+91-9876543214'
    );
    $vendorRepo->create($vendor5);
    
    $credentials['vendors'][] = [
        'business' => 'TechRent Computer Solutions',
        'username' => 'techrent',
        'email' => 'tech@rent.com',
        'password' => 'password123'
    ];
    
    // Computer products
    $computerProducts = [
        [
            'name' => 'MacBook Pro 16" M2 Max',
            'description' => 'High-performance MacBook Pro with M2 Max chip, 32GB RAM, 1TB SSD. Perfect for video editing, design work, and development.',
            'images' => ['https://images.unsplash.com/photo-1517336714731-489689fd1ca8?w=800', 'https://images.unsplash.com/photo-1611186871348-b1ce696e52c9?w=800']
        ],
        [
            'name' => 'Gaming PC - RTX 4070 Setup',
            'description' => 'Powerful gaming PC with RTX 4070, Intel i7 13th gen, 32GB RAM, 1TB NVMe SSD. Includes gaming keyboard, mouse, and 27" monitor.',
            'images' => ['https://images.unsplash.com/photo-1587202372634-32705e3bf49c?w=800']
        ],
        [
            'name' => 'Dell XPS 15 Laptop',
            'description' => 'Premium business laptop with 4K display, Intel i7, 16GB RAM, 512GB SSD. Lightweight and perfect for professionals.',
            'images' => ['https://images.unsplash.com/photo-1593642632823-8f785ba67e45?w=800']
        ],
        [
            'name' => 'iPad Pro 12.9" with Apple Pencil',
            'description' => 'Latest iPad Pro with M2 chip, 256GB storage, Apple Pencil 2, and Magic Keyboard. Ideal for designers and students.',
            'images' => ['https://images.unsplash.com/photo-1544244015-0df4b3ffc6b0?w=800']
        ],
        [
            'name' => 'Workstation PC for 3D Rendering',
            'description' => 'Professional workstation with AMD Threadripper, 64GB RAM, RTX A5000, 2TB SSD. Perfect for 3D modeling and rendering.',
            'images' => ['https://images.unsplash.com/photo-1591799264318-7e6ef8ddb7ea?w=800']
        ],
        [
            'name' => 'HP All-in-One Desktop',
            'description' => 'Space-saving all-in-one desktop with 24" touchscreen, Intel i5, 8GB RAM, 512GB SSD. Great for home and office use.',
            'images' => ['https://images.unsplash.com/photo-1587831990711-23ca6441447b?w=800']
        ]
    ];
    
    foreach ($computerProducts as $productData) {
        $product = Product::create(
            $vendor5->getId(),
            $productData['name'],
            $productData['description'],
            $categories['Computers'],
            $productData['images']
        );
        $productRepo->create($product);
        echo "  ✓ Created product: {$productData['name']}\n";
    }
    
    echo "\n";
    
    // ==================== CREATE CUSTOMER USERS ====================
    echo "👤 Creating customer users...\n";
    
    $customer1 = User::create('john_doe', 'john@example.com', 'password123', User::ROLE_CUSTOMER);
    $userRepo->create($customer1);
    
    $credentials['customers'][] = [
        'name' => 'John Doe',
        'username' => 'john_doe',
        'email' => 'john@example.com',
        'password' => 'password123'
    ];
    echo "  ✓ Created customer: john_doe\n";
    
    $customer2 = User::create('jane_smith', 'jane@example.com', 'password123', User::ROLE_CUSTOMER);
    $userRepo->create($customer2);
    
    $credentials['customers'][] = [
        'name' => 'Jane Smith',
        'username' => 'jane_smith',
        'email' => 'jane@example.com',
        'password' => 'password123'
    ];
    echo "  ✓ Created customer: jane_smith\n";
    
    echo "\n";
    
    // ==================== SAVE CREDENTIALS ====================
    echo "💾 Saving credentials to file...\n";
    
    $credentialsContent = "# DEMO USER CREDENTIALS\n";
    $credentialsContent .= "# Generated on: " . date('Y-m-d H:i:s') . "\n\n";
    $credentialsContent .= "## VENDOR ACCOUNTS\n\n";
    
    foreach ($credentials['vendors'] as $vendor) {
        $credentialsContent .= "### {$vendor['business']}\n";
        $credentialsContent .= "- **Username:** {$vendor['username']}\n";
        $credentialsContent .= "- **Email:** {$vendor['email']}\n";
        $credentialsContent .= "- **Password:** {$vendor['password']}\n";
        $credentialsContent .= "- **Login URL:** http://localhost:8081/Multi-Vendor-Rental-System/public/login.php\n\n";
    }
    
    $credentialsContent .= "\n## CUSTOMER ACCOUNTS\n\n";
    
    foreach ($credentials['customers'] as $customer) {
        $credentialsContent .= "### {$customer['name']}\n";
        $credentialsContent .= "- **Username:** {$customer['username']}\n";
        $credentialsContent .= "- **Email:** {$customer['email']}\n";
        $credentialsContent .= "- **Password:** {$customer['password']}\n";
        $credentialsContent .= "- **Login URL:** http://localhost:8081/Multi-Vendor-Rental-System/public/login.php\n\n";
    }
    
    $credentialsContent .= "\n## QUICK REFERENCE\n\n";
    $credentialsContent .= "All accounts use the same password: **password123**\n\n";
    $credentialsContent .= "### Vendor Usernames:\n";
    foreach ($credentials['vendors'] as $vendor) {
        $credentialsContent .= "- {$vendor['username']} ({$vendor['business']})\n";
    }
    
    $credentialsContent .= "\n### Customer Usernames:\n";
    foreach ($credentials['customers'] as $customer) {
        $credentialsContent .= "- {$customer['username']} ({$customer['name']})\n";
    }
    
    file_put_contents(__DIR__ . '/DEMO_CREDENTIALS.md', $credentialsContent);
    
    echo "  ✓ Credentials saved to database/DEMO_CREDENTIALS.md\n";
    
    echo "\n";
    echo "✅ Demo data seeding completed successfully!\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "📊 Summary:\n";
    echo "  • 5 Vendors created\n";
    echo "  • 23 Products created\n";
    echo "  • 2 Customer accounts created\n";
    echo "  • 5 Categories created\n";
    echo "\n";
    echo "🔑 Check database/DEMO_CREDENTIALS.md for login details\n";
    echo "🌐 Login at: http://localhost:8081/Multi-Vendor-Rental-System/public/login.php\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
