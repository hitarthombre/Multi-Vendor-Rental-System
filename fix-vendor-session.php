<?php
/**
 * Fix Vendor Session Script
 * 
 * This script will clear your current session and log you in as a vendor
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Auth\Session;

// Start session
Session::start();

// Clear all session data
Session::destroy();

// Start fresh session
Session::start();

$db = Connection::getInstance();

echo "=== Fixing Vendor Session ===\n\n";

// Get first vendor
$stmt = $db->query("
    SELECT u.id, u.username, u.email, u.role, v.business_name 
    FROM users u 
    INNER JOIN vendors v ON u.id = v.user_id 
    WHERE u.role = 'Vendor'
    LIMIT 1
");

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("❌ No vendors found in database!\n");
}

// Set session variables
Session::set('user_id', $vendor['id']);
Session::set('username', $vendor['username']);
Session::set('role', $vendor['role']);

echo "✓ Session cleared and recreated\n";
echo "✓ Logged in as: {$vendor['business_name']}\n";
echo "✓ Email: {$vendor['email']}\n";
echo "✓ User ID: {$vendor['id']}\n\n";

echo "Now open your browser and go to:\n";
echo "http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/dashboard.php\n\n";

echo "If you still see an error, please:\n";
echo "1. Close ALL browser windows\n";
echo "2. Open a NEW browser window\n";
echo "3. Go to the login page and use these credentials:\n";
echo "   Email: {$vendor['email']}\n";
echo "   Password: vendor123\n";
