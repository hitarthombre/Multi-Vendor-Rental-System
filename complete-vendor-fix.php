<?php
/**
 * Complete Vendor Session Fix
 * 
 * This script will:
 * 1. Show your current session state
 * 2. Clear the session
 * 3. Create a new vendor session
 * 4. Verify everything works
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Database\Connection;
use RentalPlatform\Auth\Session;
use RentalPlatform\Repositories\VendorRepository;

$db = Connection::getInstance();

echo "=== Complete Vendor Session Fix ===\n\n";

// Step 1: Check current session
echo "Step 1: Checking current session...\n";
Session::start();

if (Session::has('user_id')) {
    $currentUserId = Session::get('user_id');
    $currentRole = Session::get('role');
    echo "  Current user_id: {$currentUserId}\n";
    echo "  Current role: {$currentRole}\n";
    
    // Check if this user_id exists
    $stmt = $db->prepare("SELECT id, username, email, role FROM users WHERE id = ?");
    $stmt->execute([$currentUserId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "  ✓ User exists in database\n";
        
        if ($user['role'] === 'Vendor') {
            // Check if vendor profile exists
            $stmt = $db->prepare("SELECT id FROM vendors WHERE user_id = ?");
            $stmt->execute([$currentUserId]);
            $vendorProfile = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($vendorProfile) {
                echo "  ✓ Vendor profile exists\n";
                echo "  ⚠️  Session looks valid but you're seeing an error?\n";
                echo "  This might be a browser cache issue.\n\n";
            } else {
                echo "  ❌ Vendor profile NOT found for this user!\n";
                echo "  This is the problem - the user exists but has no vendor profile.\n\n";
            }
        } else {
            echo "  ❌ User is not a Vendor (role: {$user['role']})\n";
            echo "  You're logged in as a {$user['role']}, not a Vendor.\n\n";
        }
    } else {
        echo "  ❌ User does NOT exist in database\n";
        echo "  This user_id is from old data that was cleared.\n\n";
    }
} else {
    echo "  No active session found\n\n";
}

// Step 2: Clear session
echo "Step 2: Clearing session...\n";
Session::destroy();
echo "  ✓ Session cleared\n\n";

// Step 3: Get a valid vendor
echo "Step 3: Finding a valid vendor...\n";
$stmt = $db->query("
    SELECT u.id, u.username, u.email, u.role, v.id as vendor_id, v.business_name 
    FROM users u 
    INNER JOIN vendors v ON u.id = v.user_id 
    WHERE u.role = 'Vendor'
    ORDER BY v.business_name
    LIMIT 1
");

$vendor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$vendor) {
    die("  ❌ No vendors found in database!\n  Please run: php seed-comprehensive-data.php\n");
}

echo "  ✓ Found vendor: {$vendor['business_name']}\n";
echo "    Email: {$vendor['email']}\n";
echo "    User ID: {$vendor['id']}\n";
echo "    Vendor ID: {$vendor['vendor_id']}\n\n";

// Step 4: Create new session
echo "Step 4: Creating new vendor session...\n";
Session::start();
Session::set('user_id', $vendor['id']);
Session::set('username', $vendor['username']);
Session::set('role', $vendor['role']);

echo "  ✓ Session created\n";
echo "  ✓ user_id: " . Session::get('user_id') . "\n";
echo "  ✓ role: " . Session::get('role') . "\n\n";

// Step 5: Verify with VendorRepository
echo "Step 5: Verifying with VendorRepository...\n";
$vendorRepo = new VendorRepository();
$vendorObj = $vendorRepo->findByUserId($vendor['id']);

if ($vendorObj) {
    echo "  ✓ VendorRepository->findByUserId() works!\n";
    echo "  ✓ Business Name: {$vendorObj->getBusinessName()}\n\n";
} else {
    die("  ❌ VendorRepository->findByUserId() failed!\n");
}

// Step 6: Instructions
echo "=== SUCCESS! ===\n\n";
echo "Your session has been fixed. Now:\n\n";
echo "OPTION 1 (Recommended):\n";
echo "1. Close ALL browser windows and tabs\n";
echo "2. Open a NEW browser window\n";
echo "3. Go to: http://localhost:8081/Multi-Vendor-Rental-System/public/vendor/dashboard.php\n";
echo "4. You should see the vendor dashboard\n\n";

echo "OPTION 2 (If Option 1 doesn't work):\n";
echo "1. Open browser in Incognito/Private mode\n";
echo "2. Go to: http://localhost:8081/Multi-Vendor-Rental-System/public/login.php\n";
echo "3. Login with:\n";
echo "   Email: {$vendor['email']}\n";
echo "   Password: vendor123\n\n";

echo "OPTION 3 (Clear browser cache):\n";
echo "1. Press Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)\n";
echo "2. Clear 'Cached images and files'\n";
echo "3. Clear 'Cookies and other site data'\n";
echo "4. Go to the vendor dashboard\n\n";

echo "If you STILL see the error after trying all options:\n";
echo "1. Check the browser console (F12) for JavaScript errors\n";
echo "2. Check Apache error logs in: C:\\xampp\\apache\\logs\\error.log\n";
echo "3. Make sure you're accessing the correct URL\n";
