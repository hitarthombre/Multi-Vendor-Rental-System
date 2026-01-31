<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../../src/Services/CartService.php';

use RentalPlatform\Services\CartService;

// For demo purposes, use a hardcoded customer ID
// In a real application, this would come from the session
$customerId = 'demo-customer-123';

$method = $_SERVER['REQUEST_METHOD'];
$action = $_POST['action'] ?? $_GET['action'] ?? '';

$cartService = new CartService();

try {
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'contents':
                    $contents = $cartService->getCartContents($customerId);
                    echo json_encode([
                        'success' => true,
                        'data' => $contents
                    ]);
                    break;
                    
                case 'summary':
                    $cart = $cartService->getOrCreateCart($customerId);
                    $summary = $cartService->getCartSummary($cart->getId());
                    echo json_encode([
                        'success' => true,
                        'data' => $summary
                    ]);
                    break;
                    
                case 'validate':
                    $validation = $cartService->validateForCheckout($customerId);
                    echo json_encode([
                        'success' => true,
                        'data' => $validation
                    ]);
                    break;
                    
                default:
                    // Default to contents
                    $contents = $cartService->getCartContents($customerId);
                    echo json_encode([
                        'success' => true,
                        'data' => $contents
                    ]);
                    break;
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'add':
                    $productId = $_POST['product_id'] ?? '';
                    $variantId = $_POST['variant_id'] ?? null;
                    $startDateTime = $_POST['start_datetime'] ?? '';
                    $endDateTime = $_POST['end_datetime'] ?? '';
                    $quantity = (int)($_POST['quantity'] ?? 1);
                    
                    if (empty($productId) || empty($startDateTime) || empty($endDateTime)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing required fields: product_id, start_datetime, end_datetime'
                        ]);
                        break;
                    }
                    
                    $result = $cartService->addItem(
                        $customerId,
                        $productId,
                        $variantId,
                        $startDateTime,
                        $endDateTime,
                        $quantity
                    );
                    
                    echo json_encode($result);
                    break;
                    
                case 'update_quantity':
                    $cartItemId = $_POST['cart_item_id'] ?? '';
                    $quantity = (int)($_POST['quantity'] ?? 0);
                    
                    if (empty($cartItemId)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing cart_item_id'
                        ]);
                        break;
                    }
                    
                    $result = $cartService->updateItemQuantity($customerId, $cartItemId, $quantity);
                    echo json_encode($result);
                    break;
                    
                case 'update_period':
                    $cartItemId = $_POST['cart_item_id'] ?? '';
                    $startDateTime = $_POST['start_datetime'] ?? '';
                    $endDateTime = $_POST['end_datetime'] ?? '';
                    
                    if (empty($cartItemId) || empty($startDateTime) || empty($endDateTime)) {
                        http_response_code(400);
                        echo json_encode([
                            'success' => false,
                            'error' => 'Missing required fields: cart_item_id, start_datetime, end_datetime'
                        ]);
                        break;
                    }
                    
                    $result = $cartService->updateRentalPeriod(
                        $customerId,
                        $cartItemId,
                        $startDateTime,
                        $endDateTime
                    );
                    
                    echo json_encode($result);
                    break;
                    
                case 'clear':
                    $result = $cartService->clearCart($customerId);
                    echo json_encode($result);
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
            
        case 'DELETE':
            $cartItemId = $_GET['cart_item_id'] ?? '';
            
            if (empty($cartItemId)) {
                http_response_code(400);
                echo json_encode([
                    'success' => false,
                    'error' => 'Missing cart_item_id'
                ]);
                break;
            }
            
            $result = $cartService->removeItem($customerId, $cartItemId);
            echo json_encode($result);
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