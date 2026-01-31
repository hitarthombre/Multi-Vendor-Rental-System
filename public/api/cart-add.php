<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Models\CartItem;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\VariantRepository;
use RentalPlatform\Repositories\InventoryLockRepository;

header('Content-Type: application/json');

Session::start();

// Check authentication
if (!Session::isAuthenticated()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Authentication required']);
    exit;
}

// Check customer role
if (Session::getRole() !== 'Customer') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Only customers can add items to cart']);
    exit;
}

// Get request data
$data = json_decode(file_get_contents('php://input'), true);

// Validate required fields
$requiredFields = ['variant_id', 'quantity', 'start_date', 'end_date'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "Missing required field: $field"]);
        exit;
    }
}

try {
    $customerId = Session::getUserId();
    $variantId = $data['variant_id'];
    $quantity = (int)$data['quantity'];
    $startDate = new DateTime($data['start_date']);
    $endDate = new DateTime($data['end_date']);
    
    // Validate quantity
    if ($quantity < 1) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Quantity must be at least 1']);
        exit;
    }
    
    // Validate dates
    if ($startDate >= $endDate) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'End date must be after start date']);
        exit;
    }
    
    if ($startDate < new DateTime()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Start date cannot be in the past']);
        exit;
    }
    
    // Get variant and product details
    $variantRepo = new VariantRepository();
    $variant = $variantRepo->findById($variantId);
    
    if (!$variant) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Variant not found']);
        exit;
    }
    
    $productRepo = new ProductRepository();
    $product = $productRepo->findById($variant->getProductId());
    
    if (!$product) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
    
    // Check if product is active
    if ($product->getStatus() !== 'Active') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product is not available']);
        exit;
    }
    
    // Check availability
    $inventoryRepo = new InventoryLockRepository();
    if (!$inventoryRepo->isAvailable($variantId, $startDate, $endDate, $quantity)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Product is not available for the selected dates']);
        exit;
    }
    
    // Calculate price (simplified - in production, use pricing service)
    $pricePerUnit = 100.00; // Placeholder - should come from pricing calculation
    
    // Get or create cart
    $cartRepo = new CartRepository();
    $cart = $cartRepo->getOrCreateForCustomer($customerId);
    
    // Create cart item
    $cartItem = CartItem::create(
        $cart->getId(),
        $variantId,
        $product->getId(),
        $product->getVendorId(),
        $quantity,
        $pricePerUnit,
        $startDate,
        $endDate
    );
    
    // Add to cart
    $cartRepo->addItem($cartItem);
    $cart->addItem($cartItem);
    $cartRepo->update($cart);
    
    echo json_encode([
        'success' => true,
        'message' => 'Item added to cart successfully',
        'cart_count' => $cart->getItemCount()
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred: ' . $e->getMessage()
    ]);
}
