<?php
/**
 * Quick Login Helper for Vendors
 * 
 * This script helps you log in as any vendor for testing
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Auth\Session;

$db = Connection::getInstance();

echo "=== Vendor Login Helper ===\n\n";

// Get all vendors
$stmt = $db->query("
    SELECT u.id, u.username, u.email, v.business_name 
    FROM users u 
    INNER JOIN vendors v ON u.id = v.user_id 
    WHERE u.role = 'Vendor'
    ORDER BY v.business_name
");

$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($vendors)) {
    die("No vendors found in database!\n");
}

echo "Available Vendors:\n";
foreach ($vendors as $index => $vendor) {
    echo ($index + 1) . ". {$vendor['business_name']} ({$vendor['email']})\n";
}

echo "\nSelect vendor number (1-" . count($vendors) . "): ";
$handle = fopen("php://stdin", "r");
$line = trim(fgets($handle));
fclose($handle);

$selectedIndex = (int)$line - 1;

if ($selectedIndex < 0 || $selectedIndex >= count($vendors)) {
    die("Invalid selection!\n");
}

$selectedVendor = $vendors[$selectedIndex];

// Start session and log in
Session::start();
Session::set('user_id', $selectedVendor['id']);
Session::set('username', $selectedVendor['username']);
Session::set('role', 'Vendor');

echo "\n✓ Logged in as: {$selectedVendor['business_name']}\n";
echo "✓ Email: {$selectedVendor['email']}\n";
echo "✓ User ID: {$selectedVendor['id']}\n\n";
echo "You can now access the vendor dashboard at:\n";
echo "http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/dashboard.php\n\n";
echo "Note: This session will expire when you close your browser or after 30 minutes of inactivity.\n";
