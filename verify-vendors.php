<?php
require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;

$db = Connection::getInstance();

echo "=== Vendor Data Verification ===\n\n";

// Check vendor users
$stmt = $db->query("SELECT COUNT(*) as count FROM users WHERE role = 'Vendor'");
$vendorUserCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Vendor Users: {$vendorUserCount}\n";

// Check vendor profiles
$stmt = $db->query("SELECT COUNT(*) as count FROM vendors");
$vendorProfileCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
echo "Vendor Profiles: {$vendorProfileCount}\n\n";

// List vendors with their user IDs
echo "Vendor Details:\n";
$stmt = $db->query("
    SELECT u.id as user_id, u.username, u.email, v.id as vendor_id, v.business_name 
    FROM users u 
    LEFT JOIN vendors v ON u.id = v.user_id 
    WHERE u.role = 'Vendor'
    ORDER BY u.username
");

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "- {$row['username']} ({$row['email']})\n";
    echo "  User ID: {$row['user_id']}\n";
    if ($row['vendor_id']) {
        echo "  Vendor ID: {$row['vendor_id']}\n";
        echo "  Business: {$row['business_name']}\n";
    } else {
        echo "  ⚠️  NO VENDOR PROFILE!\n";
    }
    echo "\n";
}
