<?php
/**
 * Debug Vendor Login Issue
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Auth\Session;

$db = Connection::getInstance();

echo "=== Vendor Login Debug ===\n\n";

// Test with a known vendor email
$testEmail = 'techrentpro@vendor.com';
echo "Testing with: {$testEmail}\n\n";

// Step 1: Find user by email
echo "Step 1: Finding user by email...\n";
$stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$testEmail]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ User not found with email: {$testEmail}\n");
}

echo "✓ User found:\n";
echo "  - ID: {$user['id']}\n";
echo "  - Username: {$user['username']}\n";
echo "  - Role: {$user['role']}\n\n";

// Step 2: Find vendor profile by user_id
echo "Step 2: Finding vendor profile by user_id...\n";
$stmt = $db->prepare("SELECT * FROM vendors WHERE user_id = ?");
$stmt->execute([$user['id']]);
$vendorData = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendorData) {
    echo "❌ No vendor profile found for user_id: {$user['id']}\n";
    echo "\nChecking all vendors in database:\n";
    $stmt = $db->query("SELECT id, user_id, business_name FROM vendors LIMIT 5");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  - Vendor ID: {$row['id']}, User ID: {$row['user_id']}, Business: {$row['business_name']}\n";
    }
    die("\n");
}

echo "✓ Vendor profile found:\n";
echo "  - Vendor ID: {$vendorData['id']}\n";
echo "  - Business Name: {$vendorData['business_name']}\n";
echo "  - Status: {$vendorData['status']}\n\n";

// Step 3: Test VendorRepository
echo "Step 3: Testing VendorRepository->findByUserId()...\n";
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($user['id']);

if (!$vendor) {
    die("❌ VendorRepository->findByUserId() returned null!\n");
}

echo "✓ VendorRepository->findByUserId() works!\n";
echo "  - Vendor ID: {$vendor->getId()}\n";
echo "  - Business Name: {$vendor->getBusinessName()}\n\n";

// Step 4: Test session
echo "Step 4: Testing session...\n";
Session::start();
Session::set('user_id', $user['id']);
Session::set('username', $user['username']);
Session::set('role', $user['role']);

$sessionUserId = Session::getUserId();
echo "✓ Session user_id: {$sessionUserId}\n";

if ($sessionUserId !== $user['id']) {
    die("❌ Session user_id doesn't match!\n");
}

// Step 5: Test with session user_id
echo "\nStep 5: Testing VendorRepository with session user_id...\n";
$vendor2 = $vendorRepo->findByUserId($sessionUserId);

if (!$vendor2) {
    die("❌ VendorRepository->findByUserId() with session user_id returned null!\n");
}

echo "✓ Everything works!\n\n";

echo "=== All Tests Passed ===\n";
echo "The vendor login should work correctly.\n";
echo "If you still see the error, try:\n";
echo "1. Clear your browser cookies\n";
echo "2. Log out completely: http://localhost:8081/Multi-Vendor-Rental-System/public/logout.php\n";
echo "3. Log in again with: {$testEmail} / vendor123\n";
