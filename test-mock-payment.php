<?php
/**
 * Test Mock Payment Flow
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Services\CartService;
use RentalPlatform\Services\OrderService;

Session::start();

$customerId = Session::getUserId();

if (!$customerId) {
    die("Not logged in. Please log in first.\n");
}

echo "Testing Mock Payment Flow\n";
echo str_repeat("=", 60) . "\n\n";

// Step 1: Check cart
echo "Step 1: Checking cart...\n";
$cartService = new CartService();
$cartContents = $cartService->getCartContents($customerId);

if (empty($cartContents['items'])) {
    die("ERROR: Cart is empty. Add items first.\n");
}

echo "✓ Cart has " . count($cartContents['items']) . " items\n";
echo "✓ Total amount: ₹" . number_format($cartContents['summary']['total_amount'], 2) . "\n\n";

// Step 2: Create mock payment
echo "Step 2: Creating mock payment record...\n";
try {
    $db = \RentalPlatform\Database\Connection::getInstance();
    $paymentId = \RentalPlatform\Helpers\UUID::generate();
    $mockOrderId = 'order_MOCK' . strtoupper(bin2hex(random_bytes(10)));
    $mockPaymentId = 'pay_MOCK' . strtoupper(bin2hex(random_bytes(10)));
    $totalAmount = $cartContents['summary']['total_amount'];
    
    $stmt = $db->prepare("
        INSERT INTO payments (
            id, customer_id, razorpay_order_id, razorpay_payment_id,
            amount, currency, status, payment_method, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->execute([
        $paymentId,
        $customerId,
        $mockOrderId,
        $mockPaymentId,
        $totalAmount,
        'INR',
        'Completed',
        'mock_demo'
    ]);
    
    echo "✓ Payment record created\n";
    echo "  Payment ID: $paymentId\n";
    echo "  Mock Order ID: $mockOrderId\n";
    echo "  Mock Payment ID: $mockPaymentId\n\n";
    
} catch (Exception $e) {
    die("ERROR creating payment: " . $e->getMessage() . "\n");
}

// Step 3: Create orders
echo "Step 3: Creating orders from cart...\n";
try {
    $orderService = new OrderService();
    $orders = $orderService->createOrdersFromCart($customerId, $paymentId);
    
    echo "✓ Created " . count($orders) . " order(s)\n";
    foreach ($orders as $order) {
        echo "  - Order " . $order->getOrderNumber() . ": ₹" . number_format($order->getTotalAmount(), 2) . "\n";
    }
    echo "\n";
    
} catch (Exception $e) {
    echo "ERROR creating orders: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    die();
}

echo str_repeat("=", 60) . "\n";
echo "✓ Mock payment flow test completed successfully!\n";
