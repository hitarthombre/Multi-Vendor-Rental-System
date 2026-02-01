<?php
/**
 * Payment API Endpoint
 * 
 * Handles payment order creation and verification for checkout flow
 * Requirements: 2.1, 2.6, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Services\RazorpayService;
use RentalPlatform\Services\CartService;
use RentalPlatform\Services\OrderService;
use RentalPlatform\Services\InvoiceService;
use RentalPlatform\Services\NotificationService;

// Authentication check - customer must be logged in
// For demo purposes, use a hardcoded customer ID
// In a real application, this would come from the session
$customerId = '021f5bd5-b3d0-463b-be50-bfb110400e3d'; // Varun Chopra

if (empty($customerId)) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

// Get action from request
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($action)) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Action parameter is required'
    ]);
    exit;
}

// Load Razorpay configuration
$razorpayConfig = require __DIR__ . '/../../config/razorpay.php';
$environment = $razorpayConfig['environment'];
$keyId = $razorpayConfig[$environment]['key_id'];
$keySecret = $razorpayConfig[$environment]['key_secret'];

// DEMO MODE: Use mock payment for demonstration
$useMockPayment = true; // Set to false when you have valid Razorpay keys

// Initialize services
$razorpayService = new RazorpayService($keyId, $keySecret);
$cartService = new CartService();
$orderService = new OrderService();
$invoiceService = new InvoiceService();
$notificationService = new NotificationService();

try {
    switch ($action) {
        case 'create_order':
            if ($useMockPayment) {
                handleMockCreateOrder($customerId, $cartService, $keyId);
            } else {
                handleCreateOrder($customerId, $cartService, $razorpayService, $keyId);
            }
            break;
            
        case 'verify_payment':
            if ($useMockPayment) {
                handleMockVerifyPayment($customerId, $orderService, $invoiceService, $notificationService);
            } else {
                handleVerifyPayment($customerId, $razorpayService, $orderService, $invoiceService, $notificationService);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Handle create_order action
 * Requirements: 2.1, 2.2, 2.3
 */
function handleCreateOrder(string $customerId, CartService $cartService, RazorpayService $razorpayService, string $keyId): void
{
    // Get customer's cart
    $cart = $cartService->getOrCreateCart($customerId);
    $cartContents = $cartService->getCartContents($customerId);
    
    // Validate cart is not empty
    if (empty($cartContents['items'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cart is empty'
        ]);
        return;
    }
    
    // Validate cart for checkout
    $validation = $cartService->validateForCheckout($customerId);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cart validation failed',
            'validation_errors' => $validation['errors']
        ]);
        return;
    }
    
    // Calculate total amount from cart summary
    $summary = $cartContents['summary'];
    $totalAmount = $summary['total_amount'];
    
    // Create payment order via Razorpay
    $payment = $razorpayService->createPaymentOrder(
        $totalAmount,
        $customerId,
        [
            'cart_id' => $cart->getId(),
            'item_count' => $summary['total_items'],
            'vendor_count' => $summary['vendor_count']
        ]
    );
    
    // Return payment details for frontend
    echo json_encode([
        'success' => true,
        'data' => [
            'razorpay_order_id' => $payment->getRazorpayOrderId(),
            'amount' => (int)($payment->getAmount() * 100), // Convert to paise for Razorpay
            'currency' => $payment->getCurrency(),
            'key_id' => $keyId,
            'payment_id' => $payment->getId()
        ]
    ]);
}

/**
 * Handle verify_payment action
 * Requirements: 2.6, 2.7, 2.8, 3.1, 3.2, 3.3, 3.4, 3.5, 3.6, 4.1, 4.2, 4.3, 4.4, 4.5
 */
function handleVerifyPayment(
    string $customerId,
    RazorpayService $razorpayService,
    OrderService $orderService,
    InvoiceService $invoiceService,
    NotificationService $notificationService
): void {
    // Get payment details from request
    $razorpayOrderId = $_POST['razorpay_order_id'] ?? '';
    $razorpayPaymentId = $_POST['razorpay_payment_id'] ?? '';
    $razorpaySignature = $_POST['razorpay_signature'] ?? '';
    
    if (empty($razorpayOrderId) || empty($razorpayPaymentId) || empty($razorpaySignature)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Missing required payment parameters'
        ]);
        return;
    }
    
    // Verify payment signature
    $payment = $razorpayService->verifyAndCapturePayment(
        $razorpayOrderId,
        $razorpayPaymentId,
        $razorpaySignature
    );
    
    if (!$payment) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Payment verification failed'
        ]);
        return;
    }
    
    // Payment verified successfully - create orders
    try {
        // Create orders from cart (multi-vendor splitting happens here)
        $orders = $orderService->createOrdersFromCart($customerId, $payment->getId());
        
        // Generate invoices for each order
        $invoices = [];
        foreach ($orders as $order) {
            try {
                $invoice = $invoiceService->generateInvoiceForOrder($order->getId());
                $invoiceService->finalizeInvoice($invoice->getId(), $customerId);
                $invoices[] = $invoice;
            } catch (Exception $e) {
                // Log error but don't fail the entire process
                error_log("Failed to generate invoice for order {$order->getId()}: " . $e->getMessage());
            }
        }
        
        // Send notifications
        try {
            // Send payment success notification to customer
            $notificationService->sendPaymentSuccessNotification($customerId, $payment);
            
            // Send order confirmation notifications
            foreach ($orders as $order) {
                $notificationService->sendOrderCreatedNotification($customerId, $order);
                
                // Notify vendor if approval is required
                if ($order->requiresVendorApproval()) {
                    $notificationService->sendApprovalRequestNotification($order->getVendorId(), $order);
                }
            }
            
            // Send admin notification
            $notificationService->sendAdminNewOrdersNotification($orders);
            
        } catch (Exception $e) {
            // Log error but don't fail the entire process
            error_log("Failed to send notifications: " . $e->getMessage());
        }
        
        // Prepare response with order details
        $orderData = [];
        foreach ($orders as $order) {
            $orderData[] = [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'vendor_id' => $order->getVendorId(),
                'amount' => $order->getTotalAmount(),
                'status' => $order->getStatus()
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'orders' => $orderData,
                'total_amount' => $payment->getAmount(),
                'payment_id' => $payment->getId()
            ]
        ]);
        
    } catch (Exception $e) {
        // Order creation failed - initiate refund
        try {
            $razorpayService->initiateRefund(
                $payment->getId(),
                'order_creation_failed',
                $payment->getAmount(),
                'Order creation failed: ' . $e->getMessage()
            );
        } catch (Exception $refundError) {
            error_log("Failed to initiate refund: " . $refundError->getMessage());
        }
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Order creation failed: ' . $e->getMessage(),
            'refund_initiated' => true
        ]);
    }
}

/**
 * MOCK: Handle create_order action (Demo Mode)
 * Simulates Razorpay order creation without actual API call
 */
function handleMockCreateOrder(string $customerId, CartService $cartService, string $keyId): void
{
    // Get customer's cart
    $cart = $cartService->getOrCreateCart($customerId);
    $cartContents = $cartService->getCartContents($customerId);
    
    if (empty($cartContents['items'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cart is empty'
        ]);
        return;
    }
    
    // Validate cart
    $validation = $cartService->validateForCheckout($customerId);
    if (!$validation['valid']) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Cart validation failed',
            'validation_errors' => $validation['errors']
        ]);
        return;
    }
    
    // Calculate total amount
    $totalAmount = $cartContents['summary']['total_amount'];
    $amountInPaise = (int)($totalAmount * 100);
    
    // Generate mock Razorpay order ID
    $mockOrderId = 'order_MOCK' . strtoupper(bin2hex(random_bytes(10)));
    
    // Return mock order data
    echo json_encode([
        'success' => true,
        'data' => [
            'razorpay_order_id' => $mockOrderId,
            'amount' => $amountInPaise,
            'currency' => 'INR',
            'key_id' => $keyId,
            'customer_id' => $customerId,
            'cart_summary' => $cartContents['summary'],
            'mock_mode' => true
        ]
    ]);
}

/**
 * MOCK: Handle verify_payment action (Demo Mode)
 * Simulates successful payment verification and creates orders
 */
function handleMockVerifyPayment(
    string $customerId,
    OrderService $orderService,
    InvoiceService $invoiceService,
    NotificationService $notificationService
): void {
    try {
        // Get mock payment data from request
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            throw new Exception('Invalid request data');
        }
        
        $mockOrderId = $input['razorpay_order_id'] ?? '';
        $mockPaymentId = 'pay_MOCK' . strtoupper(bin2hex(random_bytes(10)));
        
        // Get cart service
        $cartService = new CartService();
        $cartContents = $cartService->getCartContents($customerId);
        
        if (empty($cartContents['items'])) {
            throw new Exception('Cart is empty');
        }
        
        // Create mock payment record
        $db = \RentalPlatform\Database\Connection::getInstance();
        $paymentId = \RentalPlatform\Helpers\UUID::generate();
        $totalAmount = $cartContents['summary']['total_amount'];
        
        $stmt = $db->prepare("
            INSERT INTO payments (
                id, customer_id, razorpay_order_id, razorpay_payment_id,
                razorpay_signature, amount, currency, status, verified_at, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        
        $stmt->execute([
            $paymentId,
            $customerId,
            $mockOrderId,
            $mockPaymentId,
            'mock_signature_demo',
            $totalAmount,
            'INR',
            'Verified'
        ]);
        
        // Create orders from cart (grouped by vendor)
        $orders = $orderService->createOrdersFromCart($customerId, $paymentId);
        
        // Note: Skipping invoice generation and notifications in demo mode
        // These can be added later when the methods are properly implemented
        
        // Clear cart after successful order creation
        $cartService->clearCart($customerId);
        
        // Prepare response
        $orderData = [];
        foreach ($orders as $order) {
            $orderData[] = [
                'order_id' => $order->getId(),
                'order_number' => $order->getOrderNumber(),
                'vendor_id' => $order->getVendorId(),
                'amount' => $order->getTotalAmount(),
                'status' => $order->getStatus()
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'orders' => $orderData,
                'total_amount' => $totalAmount,
                'payment_id' => $paymentId,
                'mock_mode' => true,
                'message' => 'DEMO MODE: Payment simulated successfully'
            ]
        ]);
        
    } catch (Exception $e) {
        error_log("Mock payment verification error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => explode("\n", $e->getTraceAsString())
        ]);
    }
}
