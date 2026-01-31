<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors in output
ini_set('log_errors', 1);

header('Content-Type: application/json');

require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Models\Wishlist;
use RentalPlatform\Repositories\WishlistRepository;
use RentalPlatform\Repositories\UserRepository;

// Start session
Session::start();

// For demo purposes, if no user is logged in, use the first customer in the database
$customerId = null;
if (Session::isAuthenticated()) {
    $customerId = Session::getUserId();
} else {
    // Get first customer from database for demo
    try {
        $userRepo = new UserRepository();
        $db = \RentalPlatform\Database\Connection::getInstance();
        $stmt = $db->query("SELECT id FROM users WHERE role = 'Customer' LIMIT 1");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row) {
            $customerId = $row['id'];
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'error' => 'Unable to determine customer ID'
        ]);
        exit;
    }
}

if (!$customerId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Authentication required'
    ]);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';
$productId = $_POST['product_id'] ?? $_GET['product_id'] ?? '';

if (empty($productId)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Product ID is required']);
    exit;
}

try {
    $wishlistRepo = new WishlistRepository();

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
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Server error: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}