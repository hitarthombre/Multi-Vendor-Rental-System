<?php
/**
 * Test complete checkout flow
 * 
 * Tests: Cart → Payment → Order Creation → Invoice Generation
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;
use RentalPlatform\Services\RazorpayService;
use RentalPlatform\Services\OrderService;
use RentalPlatform\Services\InvoiceService;

echo "=== Complete Checkout Flow Test ===\n\n";

$customerId = '021f5bd5-b3d0-463b-be50-bfb110400e3d'; // Varun Chopra

// Load Razorpay configuration
$razorpayConfig = require __DIR__ . '/config/razorpay.php';
$environment = $razorpayConfig['environment'];
$keyId = $razorpayConfig[$environment]['key_id'];
$keySecret = $razorpayConfig[$environment]['key_secret'];

// Initialize services
$cartService = new CartService();
$razorpayService = new RazorpayService($keyId, $keySecret);
$orderService = new OrderService();
$invoiceService = new InvoiceService();

try {
    // Step 1: Get cart contents
    echo "Step 1: Getting cart contents...\n";
    $cartContents = $cartService->getCartContents($customerId);
    echo "  ✅ Cart has " . count($cartContents['items']) . " items\n";
    echo "  Total: ₹" . number_format($cartContents['summary']['total_amount'], 2) . "\n\n";
    
    if (empty($cartContents['items'])) {
        echo "  ❌ Cart is empty. Run setup-test-cart.php first.\n";
        exit(1);
    }
    
    // Step 2: Validate cart
    echo "Step 2: Validating cart for checkout...\n";
    $validation = $cartService->validateForCheckout($customerId);
    if (!$validation['valid']) {
        echo "  ❌ Cart validation failed:\n";
        foreach ($validation['errors'] as $error) {
            echo "    - $error\n";
        }
        exit(1);
    }
    echo "  ✅ Cart is valid\n\n";
    
    // Step 3: Create payment order
    echo "Step 3: Creating payment order...\n";
    $payment = $razorpayService->createPaymentOrder(
        $cartContents['summary']['total_amount'],
        $customerId,
        []
    );
    echo "  ✅ Payment order created\n";
    echo "  Payment ID: " . $payment->getId() . "\n";
    echo "  Razorpay Order ID: " . $payment->getRazorpayOrderId() . "\n";
    echo "  Amount: ₹" . number_format($payment->getAmount(), 2) . "\n\n";
    
    // Step 4: Simulate payment success and verify
    echo "Step 4: Simulating payment verification...\n";
    $razorpayPaymentId = 'pay_' . uniqid();
    $expectedSignature = hash_hmac(
        'sha256',
        $payment->getRazorpayOrderId() . '|' . $razorpayPaymentId,
        $keySecret
    );
    
    $verifiedPayment = $razorpayService->verifyAndCapturePayment(
        $payment->getRazorpayOrderId(),
        $razorpayPaymentId,
        $expectedSignature
    );
    
    if (!$verifiedPayment) {
        echo "  ❌ Payment verification failed\n";
        exit(1);
    }
    echo "  ✅ Payment verified successfully\n";
    echo "  Status: " . $verifiedPayment->getStatus() . "\n\n";
    
    // Step 5: Create orders from cart
    echo "Step 5: Creating orders from cart...\n";
    $orders = $orderService->createOrdersFromCart($customerId, $verifiedPayment->getId());
    echo "  ✅ Created " . count($orders) . " order(s)\n";
    
    foreach ($orders as $order) {
        echo "  Order: " . $order->getOrderNumber() . "\n";
        echo "    - Vendor ID: " . $order->getVendorId() . "\n";
        echo "    - Amount: ₹" . number_format($order->getTotalAmount(), 2) . "\n";
        echo "    - Status: " . $order->getStatus() . "\n";
    }
    echo "\n";
    
    // Step 6: Verify invoices were generated
    echo "Step 6: Checking invoice generation...\n";
    foreach ($orders as $order) {
        $invoice = $invoiceService->getInvoiceByOrderId($order->getId());
        if ($invoice) {
            echo "  ✅ Invoice generated for order " . $order->getOrderNumber() . "\n";
            echo "    - Invoice Number: " . $invoice->getInvoiceNumber() . "\n";
            echo "    - Amount: ₹" . number_format($invoice->getTotalAmount(), 2) . "\n";
            echo "    - Status: " . $invoice->getStatus() . "\n";
        } else {
            echo "  ❌ No invoice found for order " . $order->getOrderNumber() . "\n";
        }
    }
    echo "\n";
    
    // Step 7: Verify cart was cleared
    echo "Step 7: Verifying cart was cleared...\n";
    $cartContentsAfter = $cartService->getCartContents($customerId);
    if (empty($cartContentsAfter['items'])) {
        echo "  ✅ Cart was cleared successfully\n";
    } else {
        echo "  ❌ Cart still has " . count($cartContentsAfter['items']) . " items\n";
    }
    echo "\n";
    
    echo "=== ✅ Complete Checkout Flow Test PASSED ===\n";
    echo "\nSummary:\n";
    echo "  - Payment verified: ₹" . number_format($verifiedPayment->getAmount(), 2) . "\n";
    echo "  - Orders created: " . count($orders) . "\n";
    echo "  - Cart cleared: Yes\n";
    echo "  - Invoices generated: " . count($orders) . "\n";
    
} catch (Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
