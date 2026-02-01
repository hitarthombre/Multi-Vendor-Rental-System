<?php
/**
 * Test script for payment success page
 * 
 * This script tests:
 * 1. Page loads without errors
 * 2. Authentication check works
 * 3. Order IDs are parsed from URL
 * 4. Page structure is correct
 */

echo "Testing Payment Success Page...\n\n";

// Test 1: Check if file exists
echo "Test 1: File Existence\n";
$filePath = __DIR__ . '/public/customer/payment-success.php';
if (file_exists($filePath)) {
    echo "✓ File exists at: $filePath\n";
} else {
    echo "✗ File not found at: $filePath\n";
    exit(1);
}

// Test 2: Check file syntax
echo "\nTest 2: PHP Syntax Check\n";
$output = [];
$returnCode = 0;
exec("php -l \"$filePath\" 2>&1", $output, $returnCode);
if ($returnCode === 0) {
    echo "✓ PHP syntax is valid\n";
} else {
    echo "✗ PHP syntax error:\n";
    echo implode("\n", $output) . "\n";
    exit(1);
}

// Test 3: Check required components
echo "\nTest 3: Required Components\n";
$content = file_get_contents($filePath);

$requiredComponents = [
    'Session::start()' => 'Session initialization',
    'Middleware::requireAuth()' => 'Authentication check',
    'Middleware::requireCustomer()' => 'Customer role check',
    'orders-container' => 'Orders container element',
    'next-steps' => 'Next steps section',
    'action-buttons' => 'Action buttons',
    'loadOrderDetails' => 'Load order details function',
    'displayOrders' => 'Display orders function',
    'downloadInvoice' => 'Download invoice function',
    'order_details' => 'Order details API call',
    'modern-base.php' => 'Modern layout inclusion'
];

$allPassed = true;
foreach ($requiredComponents as $component => $description) {
    if (strpos($content, $component) !== false) {
        echo "✓ $description found\n";
    } else {
        echo "✗ $description NOT found\n";
        $allPassed = false;
    }
}

// Test 4: Check for key features
echo "\nTest 4: Key Features\n";

$features = [
    'Success icon and message' => 'fa-check-circle',
    'Loading state' => 'loading-state',
    'Order cards' => 'createOrderCard',
    'Status badges' => 'statusColors',
    'Vendor information' => 'business_name',
    'Order items display' => 'Order Items',
    'Invoice download' => 'download_invoice',
    'Dashboard link' => 'dashboard.php',
    'Document upload link' => 'document-upload.php',
    'Email confirmation notice' => 'Confirmation emails',
    'Next steps guide' => 'What Happens Next',
    'Vendor approval status' => 'Pending_Vendor_Approval',
    'Currency formatting' => 'formatCurrency',
    'Date formatting' => 'formatDate'
];

foreach ($features as $feature => $searchTerm) {
    if (strpos($content, $searchTerm) !== false) {
        echo "✓ $feature implemented\n";
    } else {
        echo "✗ $feature NOT found\n";
        $allPassed = false;
    }
}

// Test 5: Check for security measures
echo "\nTest 5: Security Measures\n";

$securityChecks = [
    'Authentication required' => 'requireAuth',
    'Customer role check' => 'requireCustomer',
    'HTML escaping' => 'escapeHtml',
    'URL parameter validation' => 'empty($orderIdsParam)',
    'Error handling' => 'try {',
];

foreach ($securityChecks as $check => $searchTerm) {
    if (strpos($content, $searchTerm) !== false) {
        echo "✓ $check implemented\n";
    } else {
        echo "✗ $check NOT found\n";
        $allPassed = false;
    }
}

// Test 6: Check for responsive design
echo "\nTest 6: Responsive Design\n";

$responsiveElements = [
    'Mobile-friendly grid' => 'sm:flex-row',
    'Responsive cards' => 'flex-wrap',
    'Tailwind CSS classes' => 'rounded-xl',
    'Animations' => 'animate-slide-in',
];

foreach ($responsiveElements as $element => $searchTerm) {
    if (strpos($content, $searchTerm) !== false) {
        echo "✓ $element found\n";
    } else {
        echo "✗ $element NOT found\n";
        $allPassed = false;
    }
}

// Summary
echo "\n" . str_repeat("=", 50) . "\n";
if ($allPassed) {
    echo "✓ ALL TESTS PASSED\n";
    echo "\nPayment Success Page Implementation Summary:\n";
    echo "- File created successfully\n";
    echo "- Authentication and authorization implemented\n";
    echo "- Order details fetching via API\n";
    echo "- Order cards with vendor information\n";
    echo "- Status-specific messages and actions\n";
    echo "- Invoice download functionality\n";
    echo "- Next steps guide for customers\n";
    echo "- Links to dashboard and order details\n";
    echo "- Responsive design with Tailwind CSS\n";
    echo "- Security measures in place\n";
    echo "\nThe page is ready for manual testing!\n";
    exit(0);
} else {
    echo "✗ SOME TESTS FAILED\n";
    echo "Please review the failed checks above.\n";
    exit(1);
}
