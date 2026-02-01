<?php
require_once __DIR__ . '/vendor/autoload.php';

use RentalPlatform\Auth\Session;
use RentalPlatform\Services\CartService;
use RentalPlatform\Repositories\CartRepository;

Session::start();

// Get customer ID from session
$customerId = Session::getUserId();

if (!$customerId) {
    die("Not logged in. Please log in first.\n");
}

echo "Testing Cart Validation for Customer: $customerId\n";
echo str_repeat("=", 60) . "\n\n";

// Get cart
$cartRepo = new CartRepository();
$cart = $cartRepo->findByCustomerId($customerId);

if (!$cart) {
    die("No cart found for customer.\n");
}

echo "Cart ID: " . $cart->getId() . "\n";
echo "Cart Items: " . count($cart->getItems()) . "\n\n";

// Test validation
$cartService = new CartService();
$validationResult = $cartService->validateForCheckout($customerId);

echo "Validation Result:\n";
echo "Valid: " . ($validationResult['valid'] ? 'YES' : 'NO') . "\n";

if (!empty($validationResult['errors'])) {
    echo "\nValidation Errors:\n";
    foreach ($validationResult['errors'] as $error) {
        echo "  - $error\n";
    }
} else {
    echo "\nNo validation errors found.\n";
}

echo "\n" . str_repeat("=", 60) . "\n";
echo "Cart should be " . ($validationResult['valid'] ? "VALID" : "INVALID") . " for checkout\n";
