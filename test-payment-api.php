<?php
/**
 * Test script for Payment API
 * 
 * Tests the create_order and verify_payment endpoints
 */

require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Services\CartService;
use RentalPlatform\Services\RazorpayService;

echo "=== Payment API Test ===\n\n";

// Test customer ID
$customerId = '021f5bd5-b3d0-463b-be50-bfb110400e3d'; // Varun Chopra

// Initialize services
$cartService = new CartService();

echo "1. Testing Cart Contents...\n";
$cartContents = $cartService->getCartContents($customerId);
echo "   Cart Items: " . count($cartContents['items']) . "\n";
echo "   Total Amount: ₹" . number_format($cartContents['summary']['total_amount'], 2) . "\n";
echo "   Vendor Count: " . $cartContents['summary']['vendor_count'] . "\n\n";

if (empty($cartContents['items'])) {
    echo "   ⚠️  Cart is empty. Please add items to cart first.\n";
    echo "   Run: php test-cart-add.php\n\n";
} else {
    echo "   ✅ Cart has items\n\n";
}

echo "2. Testing Cart Validation...\n";
$validation = $cartService->validateForCheckout($customerId);
if ($validation['valid']) {
    echo "   ✅ Cart is valid for checkout\n\n";
} else {
    echo "   ❌ Cart validation failed:\n";
    foreach ($validation['errors'] as $error) {
        echo "      - $error\n";
    }
    echo "\n";
}

echo "3. Testing Payment Order Creation...\n";
if (!empty($cartContents['items']) && $validation['valid']) {
    // Load Razorpay configuration
    $razorpayConfig = require __DIR__ . '/config/razorpay.php';
    $environment = $razorpayConfig['environment'];
    $keyId = $razorpayConfig[$environment]['key_id'];
    $keySecret = $razorpayConfig[$environment]['key_secret'];
    
    $razorpayService = new RazorpayService($keyId, $keySecret);
    
    try {
        $payment = $razorpayService->createPaymentOrder(
            $cartContents['summary']['total_amount'],
            $customerId,
            [
                'cart_id' => $cartService->getOrCreateCart($customerId)->getId(),
                'item_count' => $cartContents['summary']['total_items'],
                'vendor_count' => $cartContents['summary']['vendor_count']
            ]
        );
        
        echo "   ✅ Payment order created successfully\n";
        echo "   Payment ID: " . $payment->getId() . "\n";
        echo "   Razorpay Order ID: " . $payment->getRazorpayOrderId() . "\n";
        echo "   Amount: ₹" . number_format($payment->getAmount(), 2) . "\n";
        echo "   Amount in paise: " . (int)($payment->getAmount() * 100) . "\n";
        echo "   Currency: " . $payment->getCurrency() . "\n";
        echo "   Status: " . $payment->getStatus() . "\n\n";
        
        echo "4. Testing Payment Signature Verification...\n";
        // Simulate Razorpay response
        $razorpayPaymentId = 'pay_' . uniqid();
        
        // Generate correct signature
        $expectedSignature = hash_hmac(
            'sha256',
            $payment->getRazorpayOrderId() . '|' . $razorpayPaymentId,
            $keySecret
        );
        
        echo "   Testing with correct signature...\n";
        $verifiedPayment = $razorpayService->verifyAndCapturePayment(
            $payment->getRazorpayOrderId(),
            $razorpayPaymentId,
            $expectedSignature
        );
        
        if ($verifiedPayment) {
            echo "   ✅ Payment verification successful\n";
            echo "   Payment Status: " . $verifiedPayment->getStatus() . "\n";
            echo "   Verified At: " . ($verifiedPayment->getVerifiedAt() ? $verifiedPayment->getVerifiedAt()->format('Y-m-d H:i:s') : 'N/A') . "\n\n";
        } else {
            echo "   ❌ Payment verification failed\n\n";
        }
        
        echo "   Testing with incorrect signature...\n";
        $wrongSignature = 'wrong_signature_' . uniqid();
        $failedPayment = $razorpayService->verifyAndCapturePayment(
            $payment->getRazorpayOrderId(),
            'pay_' . uniqid(),
            $wrongSignature
        );
        
        if (!$failedPayment) {
            echo "   ✅ Correctly rejected invalid signature\n\n";
        } else {
            echo "   ❌ Should have rejected invalid signature\n\n";
        }
        
    } catch (Exception $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
} else {
    echo "   ⚠️  Skipping payment order creation (cart not ready)\n\n";
}

echo "=== API Endpoint URLs ===\n";
echo "Create Order: POST http://localhost:8081/api/payment.php?action=create_order\n";
echo "Verify Payment: POST http://localhost:8081/api/payment.php?action=verify_payment\n";
echo "\n";

echo "=== Test Complete ===\n";
