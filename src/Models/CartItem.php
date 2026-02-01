<?php

namespace RentalPlatform\Models;

use RentalPlatform\Helpers\UUID;

/**
 * CartItem Model
 * 
 * Represents an item in a shopping cart
 */
class CartItem
{
    private string $id;
    private string $cartId;
    private string $productId;
    private ?string $variantId;
    private string $rentalPeriodId;
    private int $quantity;
    private float $tentativePrice;
    private string $createdAt;
    private string $updatedAt;

    public function __construct(
        string $id,
        string $cartId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $tentativePrice,
        string $createdAt,
        string $updatedAt
    ) {
        $this->id = $id;
        $this->cartId = $cartId;
        $this->productId = $productId;
        $this->variantId = $variantId;
        $this->rentalPeriodId = $rentalPeriodId;
        $this->quantity = $quantity;
        $this->tentativePrice = $tentativePrice;
        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    /**
     * Create a new cart item
     */
    public static function create(
        string $cartId,
        string $productId,
        ?string $variantId,
        string $rentalPeriodId,
        int $quantity,
        float $tentativePrice
    ): self {
        $now = date('Y-m-d H:i:s');
        return new self(
            UUID::generate(),
            $cartId,
            $productId,
            $variantId,
            $rentalPeriodId,
            $quantity,
            $tentativePrice,
            $now,
            $now
        );
    }

    // Getters
    public function getId(): string { return $this->id; }
    public function getCartId(): string { return $this->cartId; }
    public function getProductId(): string { return $this->productId; }
    public function getVariantId(): ?string { return $this->variantId; }
    public function getRentalPeriodId(): string { return $this->rentalPeriodId; }
    public function getQuantity(): int { return $this->quantity; }
    public function getTentativePrice(): float { return $this->tentativePrice; }
    public function getCreatedAt(): string { return $this->createdAt; }
    public function getUpdatedAt(): string { return $this->updatedAt; }
    
    /**
     * Get subtotal (price * quantity)
     */
    public function getSubtotal(): float {
        return $this->tentativePrice * $this->quantity;
    }
    
    /**
     * Get price per unit (alias for getTentativePrice)
     */
    public function getPricePerUnit(): float {
        return $this->tentativePrice;
    }
    
    /**
     * Get rental period start date
     * Fetches from RentalPeriod model
     */
    public function getStartDate(): ?\DateTime {
        $db = \RentalPlatform\Database\Connection::getInstance();
        $stmt = $db->prepare("SELECT start_datetime FROM rental_periods WHERE id = ?");
        $stmt->execute([$this->rentalPeriodId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result && $result['start_datetime']) {
            return new \DateTime($result['start_datetime']);
        }
        return null;
    }
    
    /**
     * Get rental period end date
     * Fetches from RentalPeriod model
     */
    public function getEndDate(): ?\DateTime {
        $db = \RentalPlatform\Database\Connection::getInstance();
        $stmt = $db->prepare("SELECT end_datetime FROM rental_periods WHERE id = ?");
        $stmt->execute([$this->rentalPeriodId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result && $result['end_datetime']) {
            return new \DateTime($result['end_datetime']);
        }
        return null;
    }
    
    /**
     * Get rental duration in days
     * Fetches from RentalPeriod model
     */
    public function getRentalDuration(): int {
        $db = \RentalPlatform\Database\Connection::getInstance();
        $stmt = $db->prepare("SELECT duration_value, duration_unit FROM rental_periods WHERE id = ?");
        $stmt->execute([$this->rentalPeriodId]);
        $result = $stmt->fetch(\PDO::FETCH_ASSOC);
        
        if ($result) {
            // For now, assume duration_value represents days
            // In a more complex system, you'd convert based on duration_unit
            return (int)$result['duration_value'];
        }
        return 0;
    }

    // Setters
    public function setQuantity(int $quantity): void {
        $this->quantity = $quantity;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function updateQuantity(int $quantity): void {
        $this->setQuantity($quantity);
    }

    public function updatePrice(float $price): void {
        $this->tentativePrice = $price;
        $this->updatedAt = date('Y-m-d H:i:s');
    }

    public function toArray(): array {
        return [
            'id' => $this->id,
            'cart_id' => $this->cartId,
            'product_id' => $this->productId,
            'variant_id' => $this->variantId,
            'rental_period_id' => $this->rentalPeriodId,
            'quantity' => $this->quantity,
            'tentative_price' => $this->tentativePrice,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }
}
