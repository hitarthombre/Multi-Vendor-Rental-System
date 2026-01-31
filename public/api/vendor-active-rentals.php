<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Auth\Middleware;
use RentalPlatform\Models\User;
use RentalPlatform\Repositories\VendorRepository;
use RentalPlatform\Repositories\OrderRepository;
use RentalPlatform\Repositories\OrderItemRepository;
use RentalPlatform\Repositories\UserRepository;

header('Content-Type: application/json');

try {
    Session::start();
    
    // Check if user is authenticated and is a vendor
    if (!Session::isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['error' => 'Authentication required']);
        exit;
    }
    
    $user = Session::getUser();
    if ($user->getRole() !== User::ROLE_VENDOR) {
        http_response_code(403);
        echo json_encode(['error' => 'Vendor access required']);
        exit;
    }
    
    $userId = Session::getUserId();
    
    // Get vendor profile
    $vendorRepo = new VendorRepository();
    $vendor = $vendorRepo->findByUserId($userId);
    
    if (!$vendor) {
        http_response_code(404);
        echo json_encode(['error' => 'Vendor profile not found']);
        exit;
    }
    
    // Get active rentals for this vendor
    $orderRepo = new OrderRepository();
    $orderItemRepo = new OrderItemRepository();
    $userRepo = new UserRepository();
    
    $activeRentals = $orderRepo->getActiveRentals($vendor->getId());
    
    // Build response with detailed information
    $response = [
        'success' => true,
        'data' => [
            'vendor_id' => $vendor->getId(),
            'vendor_name' => $vendor->getBusinessName(),
            'total_active_rentals' => count($activeRentals),
            'rentals' => []
        ]
    ];
    
    foreach ($activeRentals as $rental) {
        $customer = $userRepo->findById($rental->getCustomerId());
        $items = $orderItemRepo->findWithProductDetails($rental->getId());
        
        $rentalData = [
            'order' => $rental->toArray(),
            'customer' => $customer ? [
                'id' => $customer->getId(),
                'username' => $customer->getUsername(),
                'email' => $customer->getEmail()
            ] : null,
            'items' => $items,
            'summary' => [
                'total_items' => count($items),
                'total_amount' => $rental->getTotalAmount(),
                'deposit_amount' => $rental->getDepositAmount(),
                'deposit_status' => $rental->getDepositStatus()
            ]
        ];
        
        $response['data']['rentals'][] = $rentalData;
    }
    
    // Add aggregate statistics
    $response['data']['statistics'] = [
        'total_revenue' => array_sum(array_map(fn($rental) => $rental->getTotalAmount(), $activeRentals)),
        'total_deposits_held' => array_sum(array_map(fn($rental) => $rental->getDepositAmount(), $activeRentals)),
        'average_order_value' => count($activeRentals) > 0 ? 
            array_sum(array_map(fn($rental) => $rental->getTotalAmount(), $activeRentals)) / count($activeRentals) : 0
    ];
    
    echo json_encode($response, JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}
?>