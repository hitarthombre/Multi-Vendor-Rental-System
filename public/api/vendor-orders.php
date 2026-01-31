<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\UserRepository;

// Start session and require vendor role
Session::start();
Middleware::requireRole(User::ROLE_VENDOR);

$userId = Session::getUserId();

// Get vendor profile
$vendorRepo = new VendorRepository();
$vendor = $vendorRepo->findByUserId($userId);

if (!$vendor) {
    http_response_code(404);
    echo json_encode([
        'success' => false,
        'error' => 'Vendor profile not found'
    ]);
    exit;
}

$vendorId = $vendor->getId();
$orderRepo = new OrderRepository();
$userRepo = new UserRepository();

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'list':
                    // Get all orders for this vendor
                    $orders = $orderRepo->findByVendorId($vendorId);
                    
                    // Get customer details for orders
                    $customerDetails = [];
                    foreach ($orders as $order) {
                        if (!isset($customerDetails[$order->getCustomerId()])) {
                            $customer = $userRepo->findById($order->getCustomerId());
                            $customerDetails[$order->getCustomerId()] = $customer ? $customer->toArray() : null;
                        }
                    }
                    
                    // Format response
                    $ordersData = [];
                    foreach ($orders as $order) {
                        $orderData = $order->toArray();
                        $orderData['customer'] = $customerDetails[$order->getCustomerId()];
                        $ordersData[] = $orderData;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $ordersData
                    ]);
                    break;
                    
                case 'pending_approvals':
                    // Get orders requiring vendor approval
                    $orders = $orderRepo->getPendingApprovals($vendorId);
                    
                    // Get customer details for orders
                    $customerDetails = [];
                    foreach ($orders as $order) {
                        if (!isset($customerDetails[$order->getCustomerId()])) {
                            $customer = $userRepo->findById($order->getCustomerId());
                            $customerDetails[$order->getCustomerId()] = $customer ? $customer->toArray() : null;
                        }
                    }
                    
                    // Format response
                    $ordersData = [];
                    foreach ($orders as $order) {
                        $orderData = $order->toArray();
                        $orderData['customer'] = $customerDetails[$order->getCustomerId()];
                        $ordersData[] = $orderData;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $ordersData
                    ]);
                    break;
                    
                case 'active_rentals':
                    // Get active rentals for this vendor
                    $orders = $orderRepo->getActiveRentals($vendorId);
                    
                    // Get customer details for orders
                    $customerDetails = [];
                    foreach ($orders as $order) {
                        if (!isset($customerDetails[$order->getCustomerId()])) {
                            $customer = $userRepo->findById($order->getCustomerId());
                            $customerDetails[$order->getCustomerId()] = $customer ? $customer->toArray() : null;
                        }
                    }
                    
                    // Format response
                    $ordersData = [];
                    foreach ($orders as $order) {
                        $orderData = $order->toArray();
                        $orderData['customer'] = $customerDetails[$order->getCustomerId()];
                        $ordersData[] = $orderData;
                    }
                    
                    echo json_encode([
                        'success' => true,
                        'data' => $ordersData
                    ]);
                    break;
                    
                case 'statistics':
                    // Get order statistics for this vendor
                    $stats = $orderRepo->getVendorStatistics($vendorId);
                    
                    // Calculate totals
                    $totalOrders = array_sum(array_column($stats, 'count'));
                    $totalRevenue = array_sum(array_column($stats, 'total_amount'));
                    $pendingApprovals = $stats[\RentalPlatform\Models\Order::STATUS_PENDING_VENDOR_APPROVAL]['count'] ?? 0;
                    $activeRentals = $stats[\RentalPlatform\Models\Order::STATUS_ACTIVE_RENTAL]['count'] ?? 0;
                    $completedOrders = $stats[\RentalPlatform\Models\Order::STATUS_COMPLETED]['count'] ?? 0;
                    
                    echo json_encode([
                        'success' => true,
                        'data' => [
                            'total_orders' => $totalOrders,
                            'total_revenue' => $totalRevenue,
                            'pending_approvals' => $pendingApprovals,
                            'active_rentals' => $activeRentals,
                            'completed_orders' => $completedOrders,
                            'stats_by_status' => $stats
                        ]
                    ]);
                    break;
                    
                default:
                    http_response_code(400);
                    echo json_encode([
                        'success' => false,
                        'error' => 'Invalid action. Supported actions: list, pending_approvals, active_rentals, statistics'
                    ]);
                    break;
            }
            break;
            
        default:
            http_response_code(405);
            echo json_encode([
                'success' => false,
                'error' => 'Method not allowed. Only GET requests are supported.'
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