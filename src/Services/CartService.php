<?php

namespace RentalPlatform\Services;

use RentalPlatform\Models\Cart;
use RentalPlatform\Models\CartItem;
use RentalPlatform\Models\RentalPeriod;
use RentalPlatform\Repositories\CartRepository;
use RentalPlatform\Repositories\CartItemRepository;
use RentalPlatform\Repositories\RentalPeriodRepository;
use RentalPlatform\Repositories\ProductRepository;
use RentalPlatform\Repositories\PricingRepository;
use Exception;

/**
 * Cart Service
 * 
 * Handles cart operations and business logic
 */
class CartService
{
    private CartRepository $cartRepo;
    private CartItemRepository $cartItemRepo;
    private RentalPeriodRepository $rentalPeriodRepo;
    private ProductRepository $productRepo;
    private PricingRepository $pricingRepo;

    public function __construct()
    {
        $this->cartRepo = new CartRepository();
        $this->cartItemRepo = new CartItemRepository();
        $this->rentalPeriodRepo = new RentalPeriodRepository();
        $this->productRepo = new ProductRepository();
        $this->pricingRepo = new PricingRepository();
    }

    /**
     * Get or create cart for customer
     * 
     * @param string $customerId
     * @return Cart
     */
    public function getOrCreateCart(string $customerId): Cart
    {
        return $this->cartRepo->getOrCreateForCustomer($customerId);
    }

    /**
     * Add item to cart
     * 
     * @param string $customerId
     * @param string $productId
     * @param string|null $variantId
     * @param string $startDateTime
     * @param string $endDateTime
     * @param int $quantity
     * @return array
     * @throws Exception
     */
    public function addItem(
        string $customerId,
        string $productId,
        ?string $variantId,
        string $startDateTime,
        string $endDateTime,
        int $quantity = 1
    ): array {
        // Validate product exists
        $product = $this->productRepo->findById($productId);
        if (!$product || $product->getStatus() !== 'Active') {
            throw new Exception('Product not found or not available');
        }

        // Create rental period
        $rentalPeriod = RentalPeriod::createFromStrings($startDateTime, $endDateTime);
        
        // Validate rental period
        $errors = $this->rentalPeriodRepo->validate($rentalPeriod);
        if (!empty($errors)) {
            throw new Exception('Invalid rental period: ' . implode(', ', $errors));
        }

        // Save rental period
        $this->rentalPeriodRepo->create($rentalPeriod);

        // If no variant specified, get the first available variant for the product
        if ($variantId === null) {
            $db = \RentalPlatform\Database\Connection::getInstance();
            $stmt = $db->prepare("SELECT id FROM variants WHERE product_id = ? LIMIT 1");
            $stmt->execute([$productId]);
            $variant = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($variant) {
                $variantId = $variant['id'];
            }
        }

        // Calculate price
        $price = $this->calculatePrice($productId, $variantId, $rentalPeriod);

        // Get or create cart
        $cart = $this->getOrCreateCart($customerId);

        // Check if item already exists in cart
        $existingItem = $this->cartItemRepo->findExisting(
            $cart->getId(),
            $productId,
            $variantId,
            $rentalPeriod->getId()
        );

        if ($existingItem) {
            // Update quantity
            $existingItem->updateQuantity($existingItem->getQuantity() + $quantity);
            $existingItem->updatePrice($price);
            $this->cartItemRepo->update($existingItem);
            $cartItem = $existingItem;
        } else {
            // Create new cart item
            $cartItem = CartItem::create(
                $cart->getId(),
                $productId,
                $variantId,
                $rentalPeriod->getId(),
                $quantity,
                $price
            );
            $this->cartItemRepo->create($cartItem);
        }

        // Update cart timestamp
        $cart->touch();
        $this->cartRepo->update($cart);

        return [
            'success' => true,
            'message' => 'Item added to cart',
            'cart_item_id' => $cartItem->getId(),
            'cart_summary' => $this->getCartSummary($cart->getId())
        ];
    }

    /**
     * Update cart item quantity
     * 
     * @param string $customerId
     * @param string $cartItemId
     * @param int $quantity
     * @return array
     * @throws Exception
     */
    public function updateItemQuantity(string $customerId, string $cartItemId, int $quantity): array
    {
        $cartItem = $this->cartItemRepo->findById($cartItemId);
        if (!$cartItem) {
            throw new Exception('Cart item not found');
        }

        // Verify ownership
        $cart = $this->cartRepo->findById($cartItem->getCartId());
        if (!$cart || $cart->getCustomerId() !== $customerId) {
            throw new Exception('Unauthorized access to cart item');
        }

        if ($quantity <= 0) {
            // Remove item
            $this->cartItemRepo->delete($cartItemId);
            $message = 'Item removed from cart';
        } else {
            // Update quantity
            $cartItem->updateQuantity($quantity);
            $this->cartItemRepo->update($cartItem);
            $message = 'Item quantity updated';
        }

        // Update cart timestamp
        $cart->touch();
        $this->cartRepo->update($cart);

        return [
            'success' => true,
            'message' => $message,
            'cart_summary' => $this->getCartSummary($cart->getId())
        ];
    }

    /**
     * Remove item from cart
     * 
     * @param string $customerId
     * @param string $cartItemId
     * @return array
     * @throws Exception
     */
    public function removeItem(string $customerId, string $cartItemId): array
    {
        return $this->updateItemQuantity($customerId, $cartItemId, 0);
    }

    /**
     * Update rental period for cart item
     * 
     * @param string $customerId
     * @param string $cartItemId
     * @param string $startDateTime
     * @param string $endDateTime
     * @return array
     * @throws Exception
     */
    public function updateRentalPeriod(
        string $customerId,
        string $cartItemId,
        string $startDateTime,
        string $endDateTime
    ): array {
        $cartItem = $this->cartItemRepo->findById($cartItemId);
        if (!$cartItem) {
            throw new Exception('Cart item not found');
        }

        // Verify ownership
        $cart = $this->cartRepo->findById($cartItem->getCartId());
        if (!$cart || $cart->getCustomerId() !== $customerId) {
            throw new Exception('Unauthorized access to cart item');
        }

        // Create new rental period
        $newRentalPeriod = RentalPeriod::createFromStrings($startDateTime, $endDateTime);
        
        // Validate rental period
        $errors = $this->rentalPeriodRepo->validate($newRentalPeriod);
        if (!empty($errors)) {
            throw new Exception('Invalid rental period: ' . implode(', ', $errors));
        }

        // Save new rental period
        $this->rentalPeriodRepo->create($newRentalPeriod);

        // Recalculate price
        $newPrice = $this->calculatePrice(
            $cartItem->getProductId(),
            $cartItem->getVariantId(),
            $newRentalPeriod
        );

        // Update cart item
        $cartItem->updateRentalPeriod($newRentalPeriod->getId());
        $cartItem->updatePrice($newPrice);
        $this->cartItemRepo->update($cartItem);

        // Update cart timestamp
        $cart->touch();
        $this->cartRepo->update($cart);

        return [
            'success' => true,
            'message' => 'Rental period updated',
            'new_price' => $newPrice,
            'cart_summary' => $this->getCartSummary($cart->getId())
        ];
    }

    /**
     * Clear cart
     * 
     * @param string $customerId
     * @return array
     */
    public function clearCart(string $customerId): array
    {
        $cart = $this->cartRepo->findByCustomerId($customerId);
        if (!$cart) {
            return ['success' => true, 'message' => 'Cart is already empty'];
        }

        $this->cartItemRepo->deleteByCartId($cart->getId());

        // Update cart timestamp
        $cart->touch();
        $this->cartRepo->update($cart);

        return [
            'success' => true,
            'message' => 'Cart cleared',
            'cart_summary' => $this->getCartSummary($cart->getId())
        ];
    }

    /**
     * Get cart contents
     * 
     * @param string $customerId
     * @return array
     */
    public function getCartContents(string $customerId): array
    {
        $cart = $this->cartRepo->findByCustomerId($customerId);
        if (!$cart) {
            return [
                'items' => [],
                'summary' => [
                    'total_items' => 0,
                    'vendor_count' => 0,
                    'total_amount' => 0.0
                ],
                'vendors' => []
            ];
        }

        $items = $this->cartItemRepo->findWithProductDetails($cart->getId());
        $summary = $this->cartItemRepo->getCartSummary($cart->getId());
        $vendors = $this->cartItemRepo->groupByVendor($cart->getId());

        return [
            'items' => $items,
            'summary' => $summary,
            'vendors' => $vendors
        ];
    }

    /**
     * Get cart summary
     * 
     * @param string $cartId
     * @return array
     */
    public function getCartSummary(string $cartId): array
    {
        return $this->cartItemRepo->getCartSummary($cartId);
    }

    /**
     * Calculate price for product/variant and rental period
     * 
     * @param string $productId
     * @param string|null $variantId
     * @param RentalPeriod $rentalPeriod
     * @return float
     * @throws Exception
     */
    private function calculatePrice(string $productId, ?string $variantId, RentalPeriod $rentalPeriod): float
    {
        // Get pricing for product/variant
        $pricing = $this->pricingRepo->findByProductAndVariant($productId, $variantId);
        
        if (empty($pricing)) {
            throw new Exception('No pricing found for this product');
        }

        // Find best matching pricing based on duration unit
        $bestPricing = null;
        $durationUnit = $rentalPeriod->getDurationUnit();
        
        foreach ($pricing as $price) {
            if ($price->getDurationUnit() === $durationUnit) {
                $bestPricing = $price;
                break;
            }
        }

        // If no exact match, use daily pricing as default
        if (!$bestPricing) {
            foreach ($pricing as $price) {
                if ($price->getDurationUnit() === RentalPeriod::UNIT_DAILY) {
                    $bestPricing = $price;
                    break;
                }
            }
        }

        // If still no pricing found, use the first available
        if (!$bestPricing) {
            $bestPricing = $pricing[0];
        }

        // Check minimum duration
        if ($rentalPeriod->getDurationValue() < $bestPricing->getMinimumDuration()) {
            throw new Exception(
                "Minimum rental duration is {$bestPricing->getMinimumDuration()} {$bestPricing->getDurationUnit()}"
            );
        }

        // Calculate total price
        return $bestPricing->getPricePerUnit() * $rentalPeriod->getDurationValue();
    }

    /**
     * Validate cart for checkout
     * 
     * @param string $customerId
     * @return array
     */
    public function validateForCheckout(string $customerId): array
    {
        $cart = $this->cartRepo->findByCustomerId($customerId);
        if (!$cart) {
            return ['valid' => false, 'errors' => ['Cart is empty']];
        }

        $items = $this->cartItemRepo->findByCartId($cart->getId());
        if (empty($items)) {
            return ['valid' => false, 'errors' => ['Cart is empty']];
        }

        $errors = [];

        foreach ($items as $item) {
            // Check product availability
            $product = $this->productRepo->findById($item->getProductId());
            if (!$product || $product->getStatus() !== 'Active') {
                $errors[] = "Product {$item->getProductId()} is no longer available";
                continue;
            }

            // Check rental period validity
            $rentalPeriod = $this->rentalPeriodRepo->findById($item->getRentalPeriodId());
            if (!$rentalPeriod) {
                $errors[] = "Invalid rental period for product {$item->getProductId()}";
                continue;
            }

            $periodErrors = $this->rentalPeriodRepo->validate($rentalPeriod);
            if (!empty($periodErrors)) {
                $errors[] = "Invalid rental period for product {$item->getProductId()}: " . implode(', ', $periodErrors);
            }

            // TODO: Check inventory availability (will be implemented in inventory management tasks)
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}