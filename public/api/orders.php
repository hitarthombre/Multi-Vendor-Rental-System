<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/Services/OrderService.php';

use RentalPlatform\Services\OrderService;

// For demo purposes, use hardcoded user IDs
// In a real application, these would come from the session
$customerId = 'demo-customer-123';
$vendorId = 'demo-vendor-456';
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
                    
                    $orders = $orderService->createOrdersFromCart($customerId, $paymentId);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Orders created successfully',
                        'data' => array_map(fn($order) => $order->toArray(), $orders)
                    ]);
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
                    
                    $orderService->rejectOrder($orderId, $vendorId, $reason);
                    echo json_encode([
                        'success' => true,
                        'message' => 'Order rejected successfully'
                    ]);
                    break;
                    
                case 'complete':
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
                    
                    $orderService->completeRental($orderId, $vendorId, $reason);
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