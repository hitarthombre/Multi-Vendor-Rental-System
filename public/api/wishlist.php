<?php
header('Content-Type: application/json');

require_once '../../src/Models/Wishlist.php';
require_once '../../src/Repositories/WishlistRepository.php';

use RentalPlatform\Models\Wishlist;
use RentalPlatform\Repositories\WishlistRepository;

// For demo purposes, use a hardcoded customer ID
// In a real application, this would come from the session
$customerId = 'demo-customer-123';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = $_POST['product_id'] ?? $_GET['product_id'] ?? '';

if (empty($productId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Product ID is required']);
    exit;
}

$wishlistRepo = new WishlistRepository();

try {
    switch ($action) {
        case 'add':
            $wishlist = Wishlist::create($customerId, $productId);
            $success = $wishlistRepo->create($wishlist);
            
            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Product added to wishlist',
                    'in_wishlist' => true
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Product is already in your wishlist',
                    'in_wishlist' => true
                ]);
            }
            break;
            
        case 'remove':
            $success = $wishlistRepo->remove($customerId, $productId);
            
            echo json_encode([
                'success' => $success,
                'message' => $success ? 'Product removed from wishlist' : 'Failed to remove product',
                'in_wishlist' => false
            ]);
            break;
            
        case 'check':
            $inWishlist = $wishlistRepo->exists($customerId, $productId);
            
            echo json_encode([
                'success' => true,
                'in_wishlist' => $inWishlist
            ]);
            break;
            
        case 'count':
            $count = $wishlistRepo->countByCustomer($customerId);
            
            echo json_encode([
                'success' => true,
                'count' => $count
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Server error: ' . $e->getMessage()
    ]);
}