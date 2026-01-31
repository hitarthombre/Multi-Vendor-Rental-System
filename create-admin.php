<?php
/**
 * Create Admin Account
 * Run this script once to create an administrator account
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\UserRepository;

try {
    echo "Creating Administrator Account...\n\n";
    
    // Get database connection
    $db = Connection::getInstance();
    $userRepo = new UserRepository();
    
    // Check if admin already exists
    $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
    $stmt->execute(['admin', 'admin@rental.com']);
    $existingAdmin = $stmt->fetch();
    
    if ($existingAdmin) {
        echo "❌ Admin account already exists!\n";
        echo "   Username: " . $existingAdmin['username'] . "\n";
        echo "   Email: " . $existingAdmin['email'] . "\n\n";
        echo "Use these credentials to login:\n";
        echo "   Username: " . $existingAdmin['username'] . "\n";
        echo "   Password: password123\n\n";
        exit(0);
    }
    
    // Create admin user
    $admin = User::create(
        'admin',
        'admin@rental.com',
        'password123',
        User::ROLE_ADMINISTRATOR
    );
    
    // Save to database
    $userRepo->create($admin);
    
    echo "✅ Administrator account created successfully!\n\n";
    echo "╔══════════════════════════════════════════════════════════╗\n";
    echo "║              ADMINISTRATOR LOGIN CREDENTIALS             ║\n";
    echo "╚══════════════════════════════════════════════════════════╝\n\n";
    echo "🌐 Login URL:\n";
    echo "   http://localhost:8081/Multi-Vendor-Rental-System/public/login.php\n\n";
    echo "👤 Username: admin\n";
    echo "📧 Email: admin@rental.com\n";
    echo "🔑 Password: password123\n\n";
    echo "🎯 Admin Dashboard:\n";
    echo "   http://localhost:8081/Multi-Vendor-Rental-System/public/admin/dashboard.php\n\n";
    echo "═══════════════════════════════════════════════════════════\n\n";
    
} catch (Exception $e) {
    echo "❌ Error creating admin account: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
