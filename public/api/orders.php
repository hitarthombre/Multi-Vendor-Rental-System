<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/Services/OrderService.php';
require_once '../../src/Auth/Session.php';
require_once '../../src/Repositories/VendorRepository.php';

use RentalPlatform\Services\OrderService;
use RentalPlatform\Auth\Session;
use RentalPlatform\Repositories\VendorRepository;

// Start session and get user info
Session::start();
$userId = Session::getUserId();
$userRole = Session::getUserRole();

// Get vendor ID if user is a vendor
$vendorId = null;
if ($userRole === 'vendor' && $userId) {
    $vendorRepo = new VendorRepository();
    $vendor = $vendorRepo->findByUserId($userId);
    if ($vendor) {
        $vendorId = $vendor->getId();
    }
}

// Handle JSON input
$input = json_decode(file_get_contents('php://input'), true);
if ($input) {
    $_POST = array_merge($_POST, $input);
}

// For demo purposes, use hardcoded user IDs when session is not available
$customerId = $userId ?? 'demo-customer-123';
$adminId = 'demo-admin-789';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$orderService = new OrderService();

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'customer_orders':
                    $orders = $orderService->getCustomerOrders($customerId);
                    echo json_encode([
                        'success' => true,
                        'data' => array_map(fn($order) => $order->toArray(), $orders)
                    ]);
                    break;
                    
                case 'vendor_orders':
                    $orders = $orderService->getVendorOrders($vendorId);
                    echo json_encode([
                        'success' => true,
                        'data' => array_map(fn($order) => $order->toArray(), $orders)
                    ]);
                    break;
                    
                case 'pending_approvals':
                    $orders = $orderService->getVendorPendingApprovals($vendorId);
                    echo json_encode([
                        'success' => true,
                        'data' => array_map(fn($order) => $order->toArray(), $orders)
                    ]);
                    break;
                    
                case 'active_rentals':
                    $orders = $orderService->getVendorActiveRentals($vendorId);
                    echo json_encode([
                        'success' => true,
                        'data' => array_map(fn($order) => $order->toArray(), $orders)
                    ]);
                    break;
                    
                case 'order_details':
                    $orderId = $_GET['order_id'] ?? '';
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id parameter'
                        ]);
                        break;
                    }
                    
                    $details = $orderService->getOrderDetails($orderId);
                    echo json_encode([
                        'success' => true,
                        'data' => $details
                    ]);
                    break;
                    
                case 'vendor_order_review':
                    $orderId = $_GET['order_id'] ?? '';
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id parameter'
                        ]);
                        break;
                    }
                    
                    if (!$vendorId) {
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Vendor access required'
                        ]);
                        break;
                    }
                    
                    $reviewData = $orderService->getVendorOrderReviewData($orderId, $vendorId);
                    echo json_encode([
                        'success' => true,
                        'data' => $reviewData
                    ]);
                    break;
                    
                case 'download_invoice':
                    $orderId = $_GET['order_id'] ?? '';
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id parameter'
                        ]);
                        break;
                    }
                    
                    try {
                        // Get order details to verify customer ownership
                        $orderDetails = $orderService->getOrderDetails($orderId);
                        $order = $orderDetails['order'];
                        
                        // Verify customer ownership (in a real app, get from session)
                        if ($order['customer_id'] !== $customerId) {
                            http_response_code(403);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Unauthorized access to order'
                            ]);
                            break;
                        }
                        
                        // Check if order status allows invoice download
                        if (!in_array($order['status'], ['Active_Rental', 'Completed'])) {
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Invoice not available for this order status'
                            ]);
                            break;
                        }
                        
                        // Generate and serve PDF invoice
                        require_once '../../src/Services/InvoiceService.php';
                        $invoiceService = new \RentalPlatform\Services\InvoiceService();
                        
                        $pdfContent = $invoiceService->generateInvoicePDF($orderId);
                        
                        // Set headers for PDF download
                        header('Content-Type: application/pdf');
                        header('Content-Disposition: attachment; filename="invoice-' . $order['order_number'] . '.pdf"');
                        header('Content-Length: ' . strlen($pdfContent));
                        header('Cache-Control: private, max-age=0, must-revalidate');
                        header('Pragma: public');
                        
                        echo $pdfContent;
                        exit;
                        
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Failed to generate invoice: ' . $e->getMessage()
                        ]);
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
            break;
            
        case 'POST':
            switch ($action) {
                case 'create_from_cart':
                    $paymentId = $_POST['payment_id'] ?? '';
                    
                    if (empty($paymentId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing payment_id'
                        ]);
                        break;
                    }
                    
                    try {
                        $orders = $orderService->createOrdersFromCart($customerId, $paymentId);
                        echo json_encode([
                            'success' => true,
                            'message' => 'Orders created successfully',
                            'data' => array_map(fn($order) => $order->toArray(), $orders)
                        ]);
                    } catch (Exception $e) {
                        // Enhanced error handling for Tasks 28.1 and 28.3
                        $errorMessage = $e->getMessage();
                        
                        if (strpos($errorMessage, 'Payment verification failed') !== false) {
                            // Task 28.1: Payment verification failure
                            http_response_code(400);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Payment verification failed',
                                'error_type' => 'payment_verification_failure',
                                'message' => 'Your payment could not be verified. Your cart has been preserved. Please try again.'
                            ]);
                        } elseif (strpos($errorMessage, 'Inventory conflicts detected') !== false) {
                            // Task 28.3: Inventory conflict
                            http_response_code(409);
                            echo json_encode([
                                'success' => false,
                                'error' => 'Inventory conflict',
                                'error_type' => 'inventory_conflict',
                                'message' => 'Some items in your cart are no longer available for the selected dates. Please review your cart and try again.'
                            ]);
                        } else {
                            // Generic error
                            http_response_code(500);
                            echo json_encode([
                                'success' => false,
                                'error' => $errorMessage
                            ]);
                        }
                    }
                    break;
                    
                case 'approve':
                    $orderId = $_POST['order_id'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id'
                        ]);
                        break;
                    }
                    
                    if (!$vendorId) {
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Vendor access required'
                        ]);
                        break;
                    }
                    
                    $orderService->approveOrder($orderId, $vendorId, $reason);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order approved successfully'
                    ]);
                    break;
                    
                case 'reject':
                    $orderId = $_POST['order_id'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    
                    if (empty($orderId) || empty($reason)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id or reason'
                        ]);
                        break;
                    }
                    
                    if (!$vendorId) {
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Vendor access required'
                        ]);
                        break;
                    }
                    
                    $orderService->rejectOrder($orderId, $vendorId, $reason);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order rejected successfully'
                    ]);
                    break;
                    
                case 'complete':
                    $orderId = $_POST['order_id'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $releaseDeposit = filter_var($_POST['release_deposit'] ?? true, FILTER_VALIDATE_BOOLEAN);
                    $penaltyAmount = floatval($_POST['penalty_amount'] ?? 0);
                    $penaltyReason = $_POST['penalty_reason'] ?? '';
                    
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id'
                        ]);
                        break;
                    }
                    
                    // Validate penalty amount if provided
                    if ($penaltyAmount > 0 && empty($penaltyReason)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Penalty reason is required when applying penalty'
                        ]);
                        break;
                    }
                    
                    $orderService->completeRental($orderId, $vendorId, $reason, $releaseDeposit, $penaltyAmount, $penaltyReason);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Rental completed successfully'
                    ]);
                    break;
                    
                case 'transition_status':
                    $orderId = $_POST['order_id'] ?? '';
                    $newStatus = $_POST['new_status'] ?? '';
                    $reason = $_POST['reason'] ?? '';
                    $actorId = $_POST['actor_id'] ?? $adminId; // Default to admin for manual transitions
                    
                    if (empty($orderId) || empty($newStatus)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id or new_status'
                        ]);
                        break;
                    }
                    
                    $orderService->transitionOrderStatus($orderId, $newStatus, $actorId, $reason);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order status updated successfully'
                    ]);
                    break;
                    
                case 'process_auto_approvals':
                    $orderService->processAutoApprovals();
                    echo json_encode([
                        'success' => true,
                        'message' => 'Auto-approvals processed successfully'
                    ]);
                    break;
                    
                case 'apply_late_fee':
                    $orderId = $_POST['order_id'] ?? '';
                    $lateFeeAmount = floatval($_POST['late_fee_amount'] ?? 0);
                    $reason = $_POST['reason'] ?? '';
                    
                    if (empty($orderId) || $lateFeeAmount <= 0 || empty($reason)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing required parameters: order_id, late_fee_amount, reason'
                        ]);
                        break;
                    }
                    
                    if (!$vendorId) {
                        http_response_code(403);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Vendor access required'
                        ]);
                        break;
                    }
                    
                    try {
                        $orderService->applyLateFee($orderId, $vendorId, $lateFeeAmount, $reason);
                        echo json_encode([
                            'success' => true,
                            'message' => 'Late fee applied successfully'
                        ]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ]);
                    }
                    break;
                    
                case 'cancel_for_document_timeout':
                    $orderId = $_POST['order_id'] ?? '';
                    $reason = $_POST['reason'] ?? 'Order cancelled due to document upload timeout';
                    $processRefund = filter_var($_POST['process_refund'] ?? true, FILTER_VALIDATE_BOOLEAN);
                    
                    if (empty($orderId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing order_id parameter'
                        ]);
                        break;
                    }
                    
                    try {
                        $orderService->cancelOrderForDocumentTimeout($orderId, $reason, $processRefund);
                        echo json_encode([
                            'success' => true,
                            'message' => 'Order cancelled successfully'
                        ]);
                    } catch (Exception $e) {
                        http_response_code(500);
                        echo json_encode([
                            'success' => false,
                            'error' => $e->getMessage()
                        ]);
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
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed'
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